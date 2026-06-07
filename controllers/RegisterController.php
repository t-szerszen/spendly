<?php
// controllers/RegisterController.php

/**
 * Klasa RegisterController
 * 
 * Odpowiada za proces rejestracji nowych użytkowników. Wyświetla formularz,
 * sprawdza podstawową kompletność danych, a następnie bezpiecznie hashuje
 * hasło za pomocą algorytmu BCRYPT i zapisuje dane użytkownika do bazy.
 * Obsługuje przypadek, gdy podany adres e-mail już istnieje w systemie.
 */
class RegisterController
{
    private $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function show()
    {
        require_once __DIR__ . '/../views/register.php';
    }

    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $firstName = trim((string) ($_POST['first_name'] ?? ''));
            $lastName = trim((string) ($_POST['last_name'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            $result = $this->authService->register([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password' => $password
            ]);

            if ($result['success']) {
                header('Location: ' . url('login?registered=success'));
                exit;
            } else {
                $error = $result['error'];
                require_once __DIR__ . '/../views/register.php';
            }
        }
    }
}
