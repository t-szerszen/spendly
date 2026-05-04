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
    public function show()
    {
        $db = Database::getInstance()->getConnection();
        $userId = $_SESSION['user_id'];

        // 1. Pobieramy kategorie
        $stmtCats = $db->query("SELECT id, name FROM categories ORDER BY name ASC");
        $categories = $stmtCats->fetchAll();

        // 2. Pobieramy ostatnie 10 transakcji tego użytkownika
        $stmtRecent = $db->prepare("
        SELECT t.*, c.name as category_name 
        FROM transactions t 
        JOIN categories c ON t.category_id = c.id 
        WHERE t.user_id = ? 
        ORDER BY t.date DESC, t.id DESC 
        LIMIT 10
    ");
        $stmtRecent->execute([$userId]);
        $recentTransactions = $stmtRecent->fetchAll();

        // 3. Przekazujemy wszystko do $data
        $data = [
            'title' => 'Dashboard',
            'categories' => $categories,
            'recentTransactions' => $recentTransactions,
        ];

        require_once __DIR__ . '/../views/dashboard.php';
    }
}