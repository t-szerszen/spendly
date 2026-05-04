<?php
// controllers/TransactionsController.php

require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../services/AuthService.php';

/**
 * Klasa TransactionsController
 * 
 * Obsługuje operacje związane z transakcjami zalogowanego użytkownika.
 * Umożliwia wyświetlanie pełnej historii transakcji, dodawanie nowych
 * rekordów (przychodów i wydatków) oraz usuwanie istniejących transakcji.
 * Chroni akcje przed dostępem niezalogowanych osób.
 */
class TransactionsController
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
        $transactions = $this->transactionModel->getAllByUser($userId);

        $data = [
            'title' => 'Wszystkie transakcje',
            'transactions' => $transactions
        ];
        require_once __DIR__ . '/../views/transactions.php';
    }

    public function store()
    {
        if (!$this->authService->isLoggedIn()) {
            header('Location: ' . url('login'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->transactionModel->add([
                'user_id' => $_SESSION['user_id'],
                'category_id' => $_POST['category_id'],
                'amount' => $_POST['amount'],
                'type' => $_POST['type'],
                'description' => $_POST['description'],
                'date' => date('Y-m-d')
            ]);

            header('Location: ' . url('dashboard'));
            exit;
        }
    }

    public function destroy()
    {
        if (!$this->authService->isLoggedIn()) {
            header('Location: ' . url('login'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $transactionId = $_POST['id'] ?? null;
            $userId = $_SESSION['user_id'];

            if ($transactionId) {
                $this->transactionModel->delete($transactionId, $userId);
            }
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? url('dashboard');
        header('Location: ' . $referer);
        exit;
    }
}