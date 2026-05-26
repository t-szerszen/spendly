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
    private $authService;

    public function __construct()
    {
        $this->transactionModel = new Transaction();
        $this->authService = new AuthService();
    }

    public function show()
    {
        if (!$this->authService->isLoggedIn()) {
            header('Location: ' . url('login'));
            exit;
        }

        $userId = $_SESSION['user_id'];
        $transactions = $this->transactionModel->getAllByUser($userId);

        $data = [
            'title' => 'Wszystkie transakcje',
            'transactions' => $transactions
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
            $month = $this->getFormattedMonth($_POST['date']);
            $_SESSION['last_added_date'] = $_POST['date'];

            $this->transactionModel->add([
                'user_id' => $_SESSION['user_id'],
                'date' => $_POST['date'],
                'category_id' => $_POST['category_id'],
                'amount' => $_POST['amount'],
                'type' => $_POST['type'],
                'description' => $_POST['description'],
            ]);

            header('Location: ' . url('dashboard?month=' . $month));
            exit;
        }
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

        header('Location: ' . url('dashboard?month=' . $month));
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
}
