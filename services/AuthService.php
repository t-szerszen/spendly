<?php
// services/AuthService.php

/**
 * Klasa AuthService
 * 
 * Odpowiada za logikę uwierzytelniania i obsługę sesji użytkownika.
 * Pośredniczy między kontrolerami a modelem User w procesach rejestracji,
 * logowania, wylogowania oraz sprawdzania statusu sesji.
 */
class AuthService
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Rejestruje nowego użytkownika po sprawdzeniu unikalności adresu e-mail.
     */
    public function register($data)
    {
        // Adres e-mail jest identyfikatorem logowania, dlatego musi być unikalny.
        if ($this->userModel->findByEmail($data['email'])) {
            return ['success' => false, 'error' => 'Ten email jest już zajęty.'];
        }

        // Hasło jest zapisywane wyłącznie w postaci skrótu BCRYPT.
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);

        $result = $this->userModel->create($data);

        if ($result) {
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'Wystąpił błąd podczas rejestracji.'];
    }

    /**
     * Weryfikuje poświadczenia i zapisuje podstawowe dane użytkownika w sesji.
     */
    public function login($email, $password)
    {
        $user = $this->userModel->findByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            // Sesja przechowuje tylko dane potrzebne do identyfikacji aktywnego użytkownika.
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['email'] = $user['email'];
            return true;
        }

        return false;
    }

    /**
     * Czyści dane sesji i kończy aktywne uwierzytelnienie użytkownika.
     */
    public function logout()
    {
        // session_start() jest wymagane, aby można było bezpiecznie usunąć dane sesyjne.
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_unset();
        session_destroy();
    }

    /**
     * Sprawdza, czy w sesji istnieje identyfikator zalogowanego użytkownika.
     */
    public function isLoggedIn()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return isset($_SESSION['user_id']);
    }
}
