<?php
// controllers/RegisterController.php

/**
 * Klasa RegisterController
 * 
 * Odpowiada za obsługę procesu rejestracji nowego użytkownika.
 * Wyświetla formularz tworzenia konta, wykonuje podstawową walidację danych
 * oraz przekazuje zapis użytkownika do warstwy AuthService.
 */
class RegisterController
{
    private $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function show()
    {
        // Wyświetla formularz rejestracji nowego konta.
        require_once __DIR__ . '/../views/register.php';
    }

    public function register()
    {
        // Obsługuje wyłącznie dane przesłane metodą POST z formularza rejestracji.
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $firstName = $_POST['first_name'] ?? '';
            $lastName = $_POST['last_name'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            // Minimalna walidacja wymaganych pól przed przekazaniem danych do serwisu.
            if (empty($firstName) || empty($email) || $password === '') {
                $error = "Wypełnij wszystkie wymagane pola.";
                require_once __DIR__ . '/../views/register.php';
                return;
            }

            // AuthService odpowiada za zapis użytkownika oraz reguły unikalności adresu e-mail.
            $result = $this->authService->register([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password' => $password
            ]);

            if ($result['success']) {
                // Po poprawnej rejestracji użytkownik przechodzi do formularza logowania.
                header('Location: ' . url('login?registered=success'));
                exit;
            } else {
                // Błąd rejestracji jest prezentowany w tym samym widoku formularza.
                $error = $result['error'];
                require_once __DIR__ . '/../views/register.php';
            }
        }
    }
}
