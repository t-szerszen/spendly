<?php
// controllers/DashboardController.php

require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../services/AuthService.php';

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
        $db = Database::getInstance()->getConnection();

        // 1. Pobieramy kategorie
        $stmtCats = $db->query("SELECT id, name FROM categories ORDER BY name ASC");
        $categories = $stmtCats->fetchAll();

        // 2. Pobieramy dane do statystyk
        $totalExpense = $this->transactionModel->getTotalByType($userId, 'expense');
        $totalIncome = $this->transactionModel->getTotalByType($userId, 'income');
        $balance = $totalIncome - $totalExpense;

        // 3. Pobieramy ostatnie 10 transakcji
        $recentTransactions = $this->transactionModel->getRecent($userId, 10);

        // 4. Przekazujemy wszystko do $data
        $data = [
            'title' => 'Dashboard',
            'categories' => $categories,
            'recentTransactions' => $recentTransactions,
            'stats' => [
                'totalExpense' => $totalExpense,
                'totalIncome' => $totalIncome,
                'balance' => $balance
            ]
        ];

        require_once __DIR__ . '/../views/dashboard.php';
    }
}