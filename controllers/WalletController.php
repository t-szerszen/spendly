<?php

/**
 * Klasa WalletController
 *
 * Odpowiada za miesięczny widok portfela zalogowanego użytkownika.
 * Przygotowuje statystyki przychodów, wydatków, bilansu, udziału we wspólnych budżetach
 * oraz dane wymagane przez formularz szybkiego dodawania transakcji.
 */
class WalletController
{
    private $transactionModel;
    private $recurringTransactionModel;
    private $sharedBudgetModel;
    private $authService;
    private $categoryModel;

    public function __construct()
    {
        $this->transactionModel = new Transaction();
        $this->recurringTransactionModel = new RecurringTransaction();
        $this->sharedBudgetModel = new SharedBudget();
        $this->authService = new AuthService();
        $this->categoryModel = new Category();
    }

    public function show()
    {
        // Portfel jest dostępny wyłącznie po zalogowaniu.
        if (!$this->authService->isLoggedIn()) {
            header('Location: ' . url('login'));
            exit;
        }

        $userId = $_SESSION['user_id'];
        // Przed wyświetleniem portfela generowane są zaległe transakcje cykliczne.
        $this->recurringTransactionModel->generateDueForUser($userId, $this->transactionModel);

        // Parametr month pozwala przeglądać historię portfela miesiąc po miesiącu.
        $selectedDate = !empty($_GET['month'])
            ? DateTimeImmutable::createFromFormat('!Y-m', $_GET['month'])
            : false;

        if ($selectedDate === false) {
            $selectedDate = new DateTimeImmutable('first day of this month');
        }

        $month = $selectedDate->format('Y-m');
        // Dane finansowe zasilają kafelki statystyk, tabelę transakcji i komponent quickAdd.
        $categories = $this->categoryModel->getCategories();
        $transactions = $this->transactionModel->getByMonth($userId, $month);
        $totalExpense = $this->transactionModel->getTotalByTypeAndMonth($userId, $month, 'expense');
        $totalIncome = $this->transactionModel->getTotalByTypeAndMonth($userId, $month, 'income');
        $balance = $totalIncome - $totalExpense;
        $sharedBudgetMonthlyCost = $this->transactionModel->getUserSharedBudgetCostByMonth(
            $userId,
            (int) $selectedDate->format('Y'),
            (int) $selectedDate->format('n')
        );
        $sharedBudgets = $this->sharedBudgetModel->findByUser($userId);

        // Struktura danych przekazywana do widoku wallet.php i komponentu quickAdd.php.
        $data = [
            'title' => 'Portfel',
            'categories' => $categories,
            'sharedBudgets' => $sharedBudgets,
            'transactions' => $transactions,
            'selectedMonth' => $month,
            'quickAddPath' => 'transaction/add',
            'quickAddRedirect' => 'wallet',
            'stats' => [
                'totalExpense' => $totalExpense,
                'totalIncome' => $totalIncome,
                'balance' => $balance,
            ],
            'sharedBudgetMonthlyCost' => $sharedBudgetMonthlyCost,
        ];

        require_once __DIR__ . '/../views/wallet.php';
    }
}
