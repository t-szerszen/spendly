<?php
// controllers/DashboardController.php

/**
 * Klasa DashboardController
 * 
 * Odpowiada za wyświetlanie panelu głównego (dashboard) dla zalogowanego użytkownika.
 * Pobiera z bazy danych statystyki (sumę wydatków i przychodów), listę dostępnych
 * kategorii oraz listę ostatnich transakcji użytkownika, a następnie przekazuje
 * je do widoku 'dashboard.php'.
 */
class DashboardController
{
    private $transactionModel;
    private $householdExpenseModel;
    private $authService;
    private $categoryModel;

    public function __construct()
    {
        $this->transactionModel = new Transaction();
        $this->householdExpenseModel = new HouseholdExpense();
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

        $selectedDate = !empty($_GET['month'])
            ? DateTimeImmutable::createFromFormat('Y-m', $_GET['month'])
            : false;

        if ($selectedDate === false) {
            $selectedDate = new DateTimeImmutable('first day of this month');
        }

        $month = $selectedDate->format('Y-m');

        // Pobieramy kategorie
        $categories = $this->categoryModel->getCategories();

        // Pobieramy dane z określonego miesiąca
        $transactions = $this->transactionModel->getByMonth($userId, $month);
        $totalExpense = $this->transactionModel->getTotalByTypeAndMonth($userId, $month, 'expense');
        $totalIncome = $this->transactionModel->getTotalByTypeAndMonth($userId, $month, 'income');
        $balance = $totalIncome - $totalExpense;
        $householdMonthlyCost = $this->householdExpenseModel->getUserMonthlyHouseholdCost(
            $userId,
            (int) $selectedDate->format('Y'),
            (int) $selectedDate->format('n')
        );

        // Przekazujemy wszystko do $data
        $data = [
            'title' => 'Dashboard',
            'categories' => $categories,
            'recentTransactions' => $transactions,
            'selectedMonth' => $month,
            'quickAddPath' => 'transaction/add',
            'stats' => [
                'totalExpense' => $totalExpense,
                'totalIncome' => $totalIncome,
                'balance' => $balance
            ],
            'householdMonthlyCost' => $householdMonthlyCost
        ];

        require_once __DIR__ . '/../views/dashboard.php';
    }
}
