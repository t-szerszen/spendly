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

            case 'wallet':
                (new WalletController())->show();
                break;

            case 'shared_budgets':
                (new SharedBudgetController())->index();
                break;

            case 'shared_budgets/create':
                (new SharedBudgetController())->create();
                break;

            case 'shared_budgets/store':
                (new SharedBudgetController())->store();
                break;

            case 'shared_budgets/show':
                (new SharedBudgetController())->show();
                break;

            case 'shared_budgets/invite':
                (new SharedBudgetController())->invite();
                break;

            case 'shared_budgets/accept':
                (new SharedBudgetController())->acceptInvite();
                break;

            case 'shared_budgets/update-shares':
                (new SharedBudgetController())->updateShares();
                break;

            case 'shared_budgets/settle':
                (new SharedBudgetController())->settle();
                break;

            case 'shared_budgets/delete-invitation':
                (new SharedBudgetController())->deleteInvitation();
                break;

            case 'shared_budgets/leave':
                (new SharedBudgetController())->leave();
                break;

            case 'shared_budgets/remove-member':
                (new SharedBudgetController())->removeMember();
                break;

            case 'shared_budgets/delete':
                (new SharedBudgetController())->delete();
                break;

            case 'transaction/add':
                (new TransactionsController())->store();
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
