<?php
// controllers/DashboardController.php

require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../models/Category.php';

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
    private $authService;
    private $categoryModel;

    public function __construct()
    {
        $this->transactionModel = new Transaction();
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

        if (!empty($_GET['month'])) {
            $month = $_GET['month'];
        } else {
            $month = (new DateTimeImmutable('first day of this month'))->format('Y-m');
        }

        // Pobieramy kategorie
        $categories = $this->categoryModel->getCategories();

        // Pobieramy dane z określonego miesiąca
        $transactions = $this->transactionModel->getByMonth($userId, $month);
        $totalExpense = $this->transactionModel->getTotalByTypeAndMonth($userId, $month, 'expense');
        $totalIncome = $this->transactionModel->getTotalByTypeAndMonth($userId, $month, 'income');
        $balance = $totalIncome - $totalExpense;

        // Przekazujemy wszystko do $data
        $data = [
            'title' => 'Dashboard',
            'categories' => $categories,
            'recentTransactions' => $transactions,
            'months' => $this->getMonths(),
            'selectedMonth' => $month,
            'stats' => [
                'totalExpense' => $totalExpense,
                'totalIncome' => $totalIncome,
                'balance' => $balance
            ]
        ];

        require_once __DIR__ . '/../views/dashboard.php';
    }

    private function getMonths () 
    {
        $months = [];
        $current = new DateTimeImmutable('first day of January 2026');
        $end = new DateTimeImmutable('first day of January 2100');

        while ($current <= $end) {
            $value = $current->format('Y-m');
            $label = $this->formatMonthLabel($current);

            $months[$value] = $label;
            $current = $current->modify('+1 month');
        }

        return $months;
    }

    private function formatMonthLabel(DateTimeImmutable $date): string
    {
        $months = [
            1 => 'styczeń',
            2 => 'luty',
            3 => 'marzec',
            4 => 'kwiecień',
            5 => 'maj',
            6 => 'czerwiec',
            7 => 'lipiec',
            8 => 'sierpień',
            9 => 'wrzesień',
            10 => 'październik',
            11 => 'listopad',
            12 => 'grudzień',
        ];

        return $months[(int) $date->format('n')] . ' ' . $date->format('Y');
    }
}
