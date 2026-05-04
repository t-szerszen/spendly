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
    public function show() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    // Jeśli już jest zalogowany, przenieś go na dashboard
    if (isset($_SESSION['user_id'])) {
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

            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Sukces: Start sesji
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['first_name'] = $user['first_name'];
                
                header('Location: ' . url('dashboard'));
                exit;
            } else {
                $error = "Błędny email lub hasło.";
                require_once __DIR__ . '/../views/login.php';
            }
        }
    }
}