<?php
require_once __DIR__ . '/../controllers/HomeController.php';
require_once __DIR__ . '/../controllers/AboutController.php';
require_once __DIR__ . '/../controllers/ContactController.php';
require_once __DIR__ . '/../controllers/ErrorController.php';
require_once __DIR__ . '/../controllers/LoginController.php';
require_once __DIR__ . '/../controllers/RegisterController.php';
require_once __DIR__ . '/../core/Database.php';

/**
 * Klasa Router
 * 
 * Odpowiada za mechanizm routingu w aplikacji. Analizuje przychodzące 
 * adresy URL, usuwa prefiksy folderów projektu i dopasowuje żądanie 
 * do właściwego kontrolera oraz akcji. Obsługuje metody GET i POST, 
 * zapewniając poprawne kierowanie ruchu wewnątrz systemu.
 */
class Router
{
    public function dispatch($uri)
    {
        // Wyciągnięcie samej ścieżki (bez parametrów zapytań ?param=wartosc)
        // 1. Pobieramy samą ścieżkę bez parametrów ?id=...
        $path = parse_url($uri, PHP_URL_PATH);

        // 2. Pobieramy ścieżkę do folderu, w którym znajduje się index.php
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);

        // 3. Usuwamy folder projektu z adresu URL
        if ($scriptName !== '/') {
            $path = str_replace($scriptName, '', $path);
        }

        // 4. Czyścimy ukośniki z początku i końca
        $path = trim($path, '/');
        // Router oparty o switch używający kontrolerów
        switch ($path) {
            case '':
            case 'home':
                (new HomeController())->show();
                break;

            case 'transactions':
                require_once __DIR__ . '/../controllers/TransactionsController.php';
                (new TransactionsController())->show();
                break;
            case 'transaction/delete':
                require_once __DIR__ . '/../controllers/TransactionsController.php';
                (new TransactionsController())->destroy();
                break;

            case 'about':
                (new AboutController())->show();
                break;

            case 'contact':
                (new ContactController())->show();
                break;

            case 'login':
                $controller = new LoginController();
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $controller->login();
                } else {
                    $controller->show();
                }
                break;

            case 'register':
                $regController = new RegisterController();
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $regController->register();
                } else {
                    $regController->show();
                }
                break;

            case 'dashboard':
                require_once __DIR__ . '/../controllers/DashboardController.php';
                (new DashboardController())->show();
                break;

            case 'transaction/add':
                require_once __DIR__ . '/../controllers/TransactionsController.php';
                (new TransactionsController())->store();
                break;

            case 'logout':
                require_once __DIR__ . '/../controllers/LogoutController.php';
                (new LogoutController())->index();
                break;


            default:
                // Jeśli nie znaleziono dopasowania wywołaj ErrorController
                (new ErrorController())->show404();
                break;
        }
    }
}
