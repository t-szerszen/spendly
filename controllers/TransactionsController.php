<?php
// controllers/TransactionsController.php

class TransactionsController {
    public function show() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . url('login'));
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $userId = $_SESSION['user_id'];

        // Pobieramy wszystkie transakcje z nazwami kategorii
        $stmt = $db->prepare("
            SELECT t.*, c.name as category_name 
            FROM transactions t 
            JOIN categories c ON t.category_id = c.id 
            WHERE t.user_id = ? 
            ORDER BY t.date DESC, t.id DESC
        ");
        $stmt->execute([$userId]);
        $transactions = $stmt->fetchAll();

        $data = [
            'title' => 'Wszystkie transakcje',
            'transactions' => $transactions
        ];
        require_once __DIR__ . '/../views/transactions.php';
    }
        public function store() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . url('login'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = Database::getInstance()->getConnection();
            
            $stmt = $db->prepare("INSERT INTO transactions (user_id, category_id, amount, type, description, date) VALUES (?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $_POST['category_id'],
                $_POST['amount'],
                $_POST['type'], // 'income' lub 'expense'
                $_POST['description'],
                date('Y-m-d') // dzisiejsza data
            ]);

            header('Location: ' . url('dashboard'));
            exit;
        }
    }
    // controllers/TransactionController.php

    public function destroy() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . url('login'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $transactionId = $_POST['id'] ?? null;
            $userId = $_SESSION['user_id'];

            if ($transactionId) {
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
                $stmt->execute([$transactionId, $userId]);
            }
        }

        // Po usunięciu wracamy tam, skąd przyszliśmy (dashboard lub transakcje)
        $referer = $_SERVER['HTTP_REFERER'] ?? url('dashboard');
        header('Location: ' . $referer);
        exit;
    }
}