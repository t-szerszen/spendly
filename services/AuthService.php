<?php
// services/AuthService.php

require_once __DIR__ . '/../models/User.php';

/**
 * Klasa AuthService
 * 
 * Odpowiada za logikę biznesową związaną z autoryzacją i uwierzytelnianiem.
 * Stanowi łącznik między kontrolerami a modelem User.
 */
class AuthService
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Rejestruje nowego użytkownika.
     */
    public function register($data)
    {
        // Sprawdzenie czy email już istnieje
        if ($this->userModel->findByEmail($data['email'])) {
            return ['success' => false, 'error' => 'Ten email jest już zajęty.'];
        }

        // Hashowanie hasła
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);

        $result = $this->userModel->create($data);

        if ($result) {
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'Wystąpił błąd podczas rejestracji.'];
    }

    /**
     * Loguje użytkownika.
     */
    public function login($email, $password)
    {
        $user = $this->userModel->findByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['first_name'] = $user['first_name'];
            return true;
        }

        return false;
    }

    /**
     * Wylogowuje użytkownika.
     */
    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_unset();
        session_destroy();
    }

    /**
     * Sprawdza czy użytkownik jest zalogowany.
     */
    public function isLoggedIn()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return isset($_SESSION['user_id']);
    }
}