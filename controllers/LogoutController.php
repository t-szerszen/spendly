<?php
// controllers/LogoutController.php

/**
 * Klasa LogoutController
 * 
 * Odpowiada za zakończenie sesji użytkownika.
 * Deleguje usunięcie danych sesyjnych do AuthService, a następnie przekierowuje
 * użytkownika na stronę główną aplikacji.
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
        // Kończy bieżącą sesję użytkownika i wraca do publicznej części aplikacji.
        $this->authService->logout();
        header('Location: ' . url('home'));
        exit;
    }
}
