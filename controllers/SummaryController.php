<?php
// controllers/SummaryController.php

/**
 * Klasa SummaryController
 * 
 * Odpowiada za generowanie i wyświetlanie rocznych podsumowań finansowych użytkownika.
 * Przed załadowaniem danych weryfikuje status zalogowania za pomocą serwisu autoryzacji.
 * Pobiera z bazy danych sumaryczne wydatki z podziałem na kategorie dla wybranego roku,
 * oblicza łączny roczny bilans oraz bezpiecznie przekazuje te informacje do widoku,
 * umożliwiając dynamiczną nawigację i prezentację struktury wydatków na wykresie.
 */

class SummaryController
{
    private $db;
    private $authService;
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->authService = new AuthService();
    }

    public function show()
    {

        if (!$this->authService->isLoggedIn()) {
            header('Location: ' . url('login'));
            exit;
        }

        $userId = $_SESSION['user_id'];
        $currentYear = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

        // Pobieramy sumy wydatków dla wybranego roku
        $stmt = $this->db->prepare("
            SELECT c.name as category_name, SUM(t.amount) as total_amount
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ? 
              AND t.type = 'expense' 
              AND YEAR(t.date) = ?
            GROUP BY t.category_id, c.name
            ORDER BY total_amount DESC
        ");
        $stmt->execute([$userId, $currentYear]);
        $summaryData = $stmt->fetchAll();

        // Liczymy ogólną sumę
        $totalYearExpense = 0;
        foreach ($summaryData as $row) {
            $totalYearExpense += $row['total_amount'];
        }

        $data = [
            'title' => 'Podsumowanie roku ' . $currentYear,
            'currentYear' => $currentYear,
            'summary' => $summaryData,
            'totalYearExpense' => $totalYearExpense
        ];

        require_once __DIR__ . '/../views/summary.php';
    }
}
