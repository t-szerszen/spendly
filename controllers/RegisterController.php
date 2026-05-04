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

            // Hashowanie hasła (BCRYPT)
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            try {
                $db = Database::getInstance()->getConnection();

                $stmt = $db->prepare("INSERT INTO users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)");
                $stmt->execute([$firstName, $lastName, $email, $hashedPassword]);

                // Po sukcesie przekieruj do logowania
                header('Location: ' . url('login?registered=success'));
                exit;

            } catch (PDOException $e) {
                $error = "Ten email jest już zajęty.";
                require_once __DIR__ . '/../views/register.php';
            }
        }
    }
}