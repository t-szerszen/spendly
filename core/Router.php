<?php

/**
 * Klasa Router
 * 
 * Odpowiada za mapowanie adresów URL na kontrolery aplikacji.
 * Normalizuje ścieżkę żądania względem katalogu projektu, rozpoznaje wybraną trasę
 * oraz uruchamia odpowiednią akcję kontrolera dla metod GET i POST.
 */
class Router
{
    public function dispatch($uri)
    {
        // Pobiera wyłącznie ścieżkę adresu, bez parametrów query string.
        $path = parse_url($uri, PHP_URL_PATH);

        $path = trim($path, '/');

        // Główna tabela tras aplikacji. Każdy przypadek deleguje obsługę do właściwego kontrolera.
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
            case 'transaction/recurring/delete':
                (new TransactionsController())->destroyRecurring();
                break;

            case 'about':
                (new AboutController())->show();
                break;

            case 'contact':
                (new ContactController())->show();
                break;

            case 'login':
                $controller = new LoginController();
                // Logowanie korzysta z tej samej trasy dla wyświetlenia formularza i obsługi POST.
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $controller->login();
                } else {
                    $controller->show();
                }
                break;

            case 'register':
                $regController = new RegisterController();
                // Rejestracja rozróżnia widok formularza i zapis użytkownika na podstawie metody HTTP.
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
                // Brak dopasowania trasy przekazuje żądanie do widoku błędu 404.
                (new ErrorController())->show404();
                break;
        }
    }
}
