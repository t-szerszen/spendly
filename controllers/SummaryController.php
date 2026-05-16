<?php
// controllers/SummaryController.php

class SummaryController
{
    public function show()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . url('login'));
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $userId = $_SESSION['user_id'];

        // Pobieramy rok z adresu URL (np. ?year=2025), a jeśli go nie ma, bierzemy obecny
        $currentYear = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

        // Pobieramy sumy wydatków dla wybranego roku
        $stmt = $db->prepare("
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