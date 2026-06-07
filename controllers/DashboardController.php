<?php
// controllers/DashboardController.php

/**
 * Klasa DashboardController
 * 
 * Odpowiada za przygotowanie panelu głównego dla zalogowanego użytkownika.
 * Łączy podstawowe statystyki bieżącego miesiąca, ostatnie transakcje,
 * dane formularza szybkiego dodawania oraz skróty do najważniejszych modułów aplikacji.
 */
class DashboardController
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
        // Zabezpiecza panel główny przed dostępem niezalogowanych użytkowników.
        if (!$this->authService->isLoggedIn()) {
            header('Location: ' . url('login'));
            exit;
        }

        $userId = $_SESSION['user_id'];

        // Przed wyświetleniem panelu generowane są zaległe transakcje cykliczne użytkownika.
        $this->recurringTransactionModel->generateDueForUser($userId, $this->transactionModel);

        $selectedDate = new DateTimeImmutable('first day of this month');
        $month = $selectedDate->format('Y-m');

        // Dane zbierane dla kafelków statystycznych, formularza szybkiego dodawania i listy ostatnich operacji.
        $categories = $this->categoryModel->getCategories();
        $recentTransactions = $this->transactionModel->getRecent($userId, 5);
        $monthTransactions = $this->transactionModel->getByMonth($userId, $month);
        $totalExpense = $this->transactionModel->getTotalByTypeAndMonth($userId, $month, 'expense');
        $totalIncome = $this->transactionModel->getTotalByTypeAndMonth($userId, $month, 'income');
        $balance = $totalIncome - $totalExpense;
        $sharedBudgetMonthlyCost = $this->transactionModel->getUserSharedBudgetCostByMonth(
            $userId,
            (int) $selectedDate->format('Y'),
            (int) $selectedDate->format('n')
        );
        $sharedBudgets = $this->sharedBudgetModel->findByUser($userId);

        // Struktura przekazywana do widoku dashboard.php i komponentu quickAdd.php.
        $data = [
            'title' => 'Dashboard',
            'categories' => $categories,
            'sharedBudgets' => $sharedBudgets,
            'recentTransactions' => $recentTransactions,
            'monthTransactionsCount' => count($monthTransactions),
            'selectedMonth' => $month,
            'quickAddPath' => 'transaction/add',
            'quickAddRedirect' => 'dashboard',
            'stats' => [
                'totalExpense' => $totalExpense,
                'totalIncome' => $totalIncome,
                'balance' => $balance
            ],
            'sharedBudgetMonthlyCost' => $sharedBudgetMonthlyCost
        ];

        require_once __DIR__ . '/../views/dashboard.php';
    }
}
