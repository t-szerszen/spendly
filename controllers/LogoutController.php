<?php
// controllers/LogoutController.php

require_once __DIR__ . '/../services/AuthService.php';

/**
 * Klasa LogoutController
 * 
 * Zarządza procesem wylogowywania użytkownika z aplikacji. Upewnia się, że
 * sesja jest otwarta, a następnie niszczy wszystkie dane sesyjne i przekierowuje
 * użytkownika bezpiecznie z powrotem na stronę główną.
 */
class LogoutController
{
    private $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function index()
    {
        $this->authService->logout();
        header('Location: ' . url('home'));
        exit;
    }
}