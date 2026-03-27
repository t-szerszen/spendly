<?php
// controllers/DashboardController.php

class DashboardController
{
    public function show()
    {
        // Start sesji, jeśli jeszcze nie ruszyła
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Proste zabezpieczenie - jeśli nie ma ID w sesji, wyrzuć do logowania
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . url('login'));
            exit;
        }

        $data = [
            'title' => 'Dashboard - Spendly'
        ];

        require_once __DIR__ . '/../views/dashboard.php';
    }
}