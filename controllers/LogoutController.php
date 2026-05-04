<?php
// controllers/LogoutController.php

/**
 * Klasa LogoutController
 * 
 * Zarządza procesem wylogowywania użytkownika z aplikacji. Upewnia się, że
 * sesja jest otwarta, a następnie niszczy wszystkie dane sesyjne i przekierowuje
 * użytkownika bezpiecznie z powrotem na stronę główną.
 */
class LogoutController
{
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        session_unset();
        session_destroy();
        
        header('Location: ' . url('home'));
        exit;
    }
}