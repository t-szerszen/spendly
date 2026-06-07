<?php
// controllers/LoginController.php

/**
 * Klasa LoginController
 * 
 * Zarządza procesem uwierzytelniania użytkowników. Wyświetla formularz logowania,
 * weryfikuje poprawność wprowadzonych danych (email i hasło weryfikowane
 * funkcją password_verify), a po udanym logowaniu inicjuje sesję i przekierowuje
 * na panel główny.
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
        if ($this->authService->isLoggedIn()) {
            header('Location: ' . url('dashboard'));
            exit;
        }

        $data = ['title' => 'Logowanie'];
        require_once __DIR__ . '/../views/login.php';
    }

    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            $result = $this->authService->login($email, $password);

            if ($result['success']) {
                if (!empty($_SESSION['pending_shared_budget_invite_token'])) {
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
