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
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            if ($this->authService->login($email, $password)) {
                if (!empty($_SESSION['pending_household_invite_token'])) {
                    $token = $_SESSION['pending_household_invite_token'];
                    header('Location: ' . url('households/accept?token=' . urlencode($token)));
                    exit;
                }

                header('Location: ' . url('dashboard'));
                exit;
            } else {
                $error = "Błędny email lub hasło.";
                $data = ['title' => 'Logowanie'];
                require_once __DIR__ . '/../views/login.php';
            }
        }
    }
}
