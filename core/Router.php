<?php

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
        $path = parse_url($uri, PHP_URL_PATH);

        $scriptName = dirname($_SERVER['SCRIPT_NAME']);

        if ($scriptName !== '/') {
            $path = str_replace($scriptName, '', $path);
        }

        $path = trim($path, '/');
        switch ($path) {
            case '':
            case 'home':
                (new HomeController())->show();
                break;

            case 'transactions':
                (new TransactionsController())->show();
                break;
            case 'transaction/delete':
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
                (new DashboardController())->show();
                break;

            case 'households':
                (new HouseholdController())->index();
                break;

            case 'households/create':
                (new HouseholdController())->create();
                break;

            case 'households/store':
                (new HouseholdController())->store();
                break;

            case 'households/show':
                (new HouseholdController())->show();
                break;

            case 'households/invite':
                (new HouseholdController())->invite();
                break;

            case 'households/accept':
                (new HouseholdController())->acceptInvite();
                break;

            case 'households/update-shares':
                (new HouseholdController())->updateShares();
                break;

            case 'households/store-expense':
                (new HouseholdController())->storeExpense();
                break;

            case 'transaction/add':
                (new TransactionsController())->store();
                break;

            case 'household':
                header('Location: ' . url('households'));
                exit;
                break;

            case 'logout':
                (new LogoutController())->index();
                break;
            
            case 'summary':
                (new SummaryController())->show();
                break;


            default:
                // Jeśli nie znaleziono dopasowania wywołaj ErrorController
                (new ErrorController())->show404();
                break;
        }
    }
}
