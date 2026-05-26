<?php
session_start();
require_once __DIR__ . '/config.php';
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}
require_once __DIR__ . '/helpers.php';

// Inicjalizacja routera i przekazanie aktualnego URI
$router = new Router();
$router->dispatch($_SERVER['REQUEST_URI']);
