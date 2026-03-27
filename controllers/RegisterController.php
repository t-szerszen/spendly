<?php
// controllers/RegisterController.php

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

            // Prosta walidacja
            if (empty($firstName) || empty($email) || strlen($password) < 8) {
                $error = "Wypełnij wszystkie pola. Hasło musi mieć min. 8 znaków.";
                require_once __DIR__ . '/../views/register.php';
                return;
            }

            // Hashowanie hasła - to jest ten BCRYPT, o który pytałeś!
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