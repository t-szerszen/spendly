<?php

/**
 * Klasa HomeController
 * 
 * Odpowiada za wyświetlenie strony głównej serwisu (tzw. landing page).
 * Przygotowuje podstawowe dane, takie jak tytuł strony i treść powitalną,
 * i przekazuje je do widoku głównego 'home.php'.
 */
class HomeController
{
    public function show()
    {
        $data = [
            'title' => 'Strona Główna',
            'content' => 'Witamy na naszej stronie!'
        ];

        require_once __DIR__ . '/../views/home.php';
    }
}