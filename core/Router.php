<?php
require_once __DIR__ . '/../controllers/HomeController.php';
require_once __DIR__ . '/../controllers/AboutController.php';
require_once __DIR__ . '/../controllers/ContactController.php';
require_once __DIR__ . '/../controllers/ErrorController.php';

class Router
{
    public function dispatch($uri)
    {
        // Wyciągnięcie samej ścieżki (bez parametrów zapytań ?param=wartosc)
        $path = parse_url($uri, PHP_URL_PATH);
        $path = trim($path, '/');

        // Najprostszy router oparty o switch używający kontrolerów
        switch ($path) {
            case '':
            case 'home':
                (new HomeController())->show();
                break;

            case 'about':
                (new AboutController())->show();
                break;

            case 'contact':
                (new ContactController())->show();
                break;

            default:
                // Jeśli nie znaleziono dopasowania wywołaj ErrorController
                (new ErrorController())->show404();
                break;
        }
    }
}
