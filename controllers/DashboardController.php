<?php
// controllers/DashboardController.php

/**
 * Klasa DashboardController
 * 
 * Odpowiada za wyświetlanie panelu głównego dla zalogowanego użytkownika.
 * Dashboard jest szybkim startem: pokazuje aktualny miesiąc, quick add,
 * skróty do głównych modułów oraz kilka ostatnich transakcji.
 */
class DashboardController
{
    private $transactionModel;
    private $sharedBudgetModel;
    private $authService;
    private $categoryModel;

    public function __construct()
    {
        $this->transactionModel = new Transaction();
        $this->sharedBudgetModel = new SharedBudget();
        $this->authService = new AuthService();
        $this->categoryModel = new Category();
    }

    public function show()
    {
        if (!$this->authService->isLoggedIn()) {
            header('Location: ' . url('login'));
            exit;
        }

        $userId = $_SESSION['user_id'];

        $selectedDate = new DateTimeImmutable('first day of this month');
        $month = $selectedDate->format('Y-m');

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
