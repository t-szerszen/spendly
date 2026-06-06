<?php
// controllers/TransactionsController.php

/**
 * Klasa TransactionsController
 * 
 * Obsługuje operacje związane z transakcjami zalogowanego użytkownika.
 * Umożliwia wyświetlanie pełnej historii transakcji, dodawanie nowych
 * rekordów (przychodów i wydatków) oraz usuwanie istniejących transakcji.
 * Chroni akcje przed dostępem niezalogowanych osób.
 */
class TransactionsController
{
    private $transactionModel;
    private $recurringTransactionModel;
    private $sharedBudgetModel;
    private $authService;

    public function __construct()
    {
        require_once __DIR__ . '/../models/RecurringTransaction.php';
        $this->transactionModel = new Transaction();
        $this->recurringTransactionModel = new RecurringTransaction();
        $this->sharedBudgetModel = new SharedBudget();
        $this->authService = new AuthService();
    }

    public function show()
    {
        if (!$this->authService->isLoggedIn()) {
            header('Location: ' . url('login'));
            exit;
        }

        $userId = $_SESSION['user_id'];
        $this->recurringTransactionModel->generateDueForUser($userId, $this->transactionModel);
        $transactions = $this->transactionModel->getAllByUser($userId);
        $recurringTransactions = $this->recurringTransactionModel->getAllByUser($userId);

        $data = [
            'title' => 'Wszystkie transakcje',
            'transactions' => $transactions,
            'recurringTransactions' => $recurringTransactions
        ];
        require_once __DIR__ . '/../views/transactions.php';
    }

    public function store()
    {
        if (!$this->authService->isLoggedIn()) {
            header('Location: ' . url('login'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $date = $_POST['date'] ?? '';
            $type = $_POST['type'] ?? '';
            $transactionMode = $_POST['transaction_mode'] ?? 'single';
            $sharedBudgetId = $this->resolveSharedBudgetId($_POST['shared_budget_id'] ?? null);
            $month = $this->getFormattedMonth($date);
            $_SESSION['last_added_date'] = $date;

            if (!$this->isValidTransactionPayload($_POST, $sharedBudgetId, $transactionMode)) {
                $this->redirectAfterStore($_POST['redirect_to'] ?? 'dashboard', $month, 'invalid');
            }

            if ($sharedBudgetId !== null && !$this->sharedBudgetModel->userHasAccess($sharedBudgetId, (int) $_SESSION['user_id'])) {
                $this->redirectAfterStore($_POST['redirect_to'] ?? 'dashboard', $month, 'forbidden-budget');
            }

            if ($transactionMode === 'recurring') {
                $this->recurringTransactionModel->add([
                    'user_id' => $_SESSION['user_id'],
                    'start_date' => $date,
                    'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
                    'frequency' => $_POST['frequency'],
                    'category_id' => $_POST['category_id'],
                    'amount' => $_POST['amount'],
                    'type' => $type,
                    'shared_budget_id' => $sharedBudgetId,
                    'description' => trim($_POST['description'] ?? ''),
                ]);
                $this->recurringTransactionModel->generateDueForUser((int) $_SESSION['user_id'], $this->transactionModel);
                $this->redirectAfterStore($_POST['redirect_to'] ?? 'dashboard', $month, 'recurring-added');
            } else {
                $this->transactionModel->add([
                    'user_id' => $_SESSION['user_id'],
                    'date' => $date,
                    'category_id' => $_POST['category_id'],
                    'amount' => $_POST['amount'],
                    'type' => $type,
                    'entry_kind' => 'standard',
                    'shared_budget_id' => $sharedBudgetId,
                    'description' => trim($_POST['description'] ?? ''),
                ]);
            }

            $this->redirectAfterStore($_POST['redirect_to'] ?? 'dashboard', $month, 'added');
        }
    }

    public function destroyRecurring()
    {
        if (!$this->authService->isLoggedIn()) {
            header('Location: ' . url('login'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $recurringTransactionId = (int) ($_POST['id'] ?? 0);
            if ($recurringTransactionId > 0) {
                $this->recurringTransactionModel->delete($recurringTransactionId, (int) $_SESSION['user_id']);
            }
        }

        header('Location: ' . url('transactions'));
        exit;
    }

    public function destroy()
    {
        if (!$this->authService->isLoggedIn()) {
            header('Location: ' . url('login'));
            exit;
        }

        $month = $this->getFormattedMonth(null);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $transactionId = $_POST['id'] ?? null;
            $userId = $_SESSION['user_id'];

            if ($transactionId) {
                $transaction = $this->transactionModel->getRecordByUser($transactionId, $userId);
                if ($transaction) {
                    $month = $this->getFormattedMonth($transaction['date']);
                    $this->transactionModel->delete($transactionId, $userId);
                }
            }
        }

        $redirectTo = $_POST['redirect_to'] ?? 'dashboard';
        if ($redirectTo === 'wallet') {
            header('Location: ' . url('wallet?month=' . $month));
            exit;
        }

        header('Location: ' . url('dashboard'));
        exit;
    }

    private function getFormattedMonth($date)
    {
        $month = (new DateTimeImmutable('first day of this month'))->format('Y-m');

        if (!$date) {
            return $month;
        }

        $savedDate = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        if ($savedDate !== false) {
            return $savedDate->format('Y-m');
        }

        return $month;
    }

    private function resolveSharedBudgetId($rawSharedBudgetId): ?int
    {
        if ($rawSharedBudgetId === null || $rawSharedBudgetId === '' || $rawSharedBudgetId === 'private') {
            return null;
        }

        $sharedBudgetId = (int) $rawSharedBudgetId;

        return $sharedBudgetId > 0 ? $sharedBudgetId : null;
    }

    private function isValidTransactionPayload(array $payload, ?int $sharedBudgetId, string $transactionMode = 'single'): bool
    {
        $date = $payload['date'] ?? '';
        $type = $payload['type'] ?? '';
        $categoryId = (int) ($payload['category_id'] ?? 0);
        $amount = (float) ($payload['amount'] ?? 0);
        $allowedTypes = ['income', 'expense', 'savings', 'pocket_money'];
        $allowedModes = ['single', 'recurring'];
        $allowedFrequencies = ['weekly', 'monthly', 'quarterly', 'yearly'];

        if (!in_array($type, $allowedTypes, true)) {
            return false;
        }

        if (!in_array($transactionMode, $allowedModes, true)) {
            return false;
        }

        if ($sharedBudgetId !== null && $type !== 'expense') {
            return false;
        }

        if ($categoryId <= 0 || $amount <= 0) {
            return false;
        }

        if (!$this->isValidDate($date)) {
            return false;
        }

        if ($transactionMode !== 'recurring') {
            return true;
        }

        if (!in_array($payload['frequency'] ?? '', $allowedFrequencies, true)) {
            return false;
        }

        $endDate = $payload['end_date'] ?? '';
        if ($endDate === '') {
            return true;
        }

        return $this->isValidDate($endDate) && $endDate >= $date;
    }

    private function redirectAfterStore(string $redirectTo, string $month, string $status): void
    {
        if ($redirectTo === 'wallet') {
            header('Location: ' . url('wallet?month=' . urlencode($month) . '&transaction=' . urlencode($status)));
            exit;
        }

        header('Location: ' . url('dashboard?transaction=' . urlencode($status)));
        exit;
    }

    private function isValidDate(string $date): bool
    {
        $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $errors = DateTimeImmutable::getLastErrors();

        if ($parsedDate === false) {
            return false;
        }

        if ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            return false;
        }

        return $parsedDate->format('Y-m-d') === $date;
    }
}
