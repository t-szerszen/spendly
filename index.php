<?php
session_start();
// Załączenie klasy Routera
require_once __DIR__ . '/core/Router.php';

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

// Inicjalizacja routera i przekazanie aktualnego URI
$router = new Router();
$router->dispatch($_SERVER['REQUEST_URI']);
