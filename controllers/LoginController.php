<?php
// controllers/LoginController.php

/**
 * Klasa LoginController
 * 
 * Odpowiada za obsługę procesu logowania użytkownika.
 * Wyświetla formularz uwierzytelniania, przekazuje dane do AuthService
 * oraz kieruje zalogowanego użytkownika do panelu głównego lub zaproszenia budżetowego.
 */
class LoginController
{
    private $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function show()
    {
        // Zalogowany użytkownik nie powinien ponownie widzieć formularza logowania.
        if ($this->authService->isLoggedIn()) {
            header('Location: ' . url('dashboard'));
            exit;
        }

        $data = ['title' => 'Logowanie'];
        require_once __DIR__ . '/../views/login.php';
    }

    public function login()
    {
        // Obsługuje wyłącznie dane przesłane metodą POST z formularza logowania.
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            $result = $this->authService->login($email, $password);

            if ($result['success']) {
                if (!empty($_SESSION['pending_shared_budget_invite_token'])) {
                    // Po logowaniu użytkownik wraca do oczekującego zaproszenia do wspólnego budżetu.
                    $token = $_SESSION['pending_shared_budget_invite_token'];
                    header('Location: ' . url('shared_budgets/accept?token=' . urlencode($token)));
                    exit;
                }

                header('Location: ' . url('dashboard'));
                exit;
            } else {
                $error = $result['error'];
                $data = ['title' => 'Logowanie'];
                require_once __DIR__ . '/../views/login.php';
            }
        }
    }
}
