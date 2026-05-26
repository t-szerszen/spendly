<?php
// controllers/RegisterController.php

/**
 * Klasa RegisterController
 * 
 * Odpowiada za proces rejestracji nowych użytkowników. Wyświetla formularz,
 * przeprowadza podstawową walidację (m.in. sprawdzanie długości hasła), a następnie
 * bezpiecznie hashuje hasło za pomocą algorytmu BCRYPT i zapisuje dane użytkownika do bazy.
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
            $firstName = $_POST['first_name'] ?? '';
            $lastName = $_POST['last_name'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            // Walidacja
            if (empty($firstName) || empty($email) || strlen($password) < 8) {
                $error = "Wypełnij wszystkie pola. Hasło musi mieć min. 8 znaków.";
                require_once __DIR__ . '/../views/register.php';
                return;
            }

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
