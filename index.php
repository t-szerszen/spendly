<?php

// Załączenie klasy Routera
require_once __DIR__ . '/core/Router.php';

// Inicjalizacja routera i przekazanie aktualnego URI
$router = new Router();
$router->dispatch($_SERVER['REQUEST_URI']);
