<?php
// controllers/LogoutController.php

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