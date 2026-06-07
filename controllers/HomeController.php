<?php

/**
 * Klasa HomeController
 * 
 * Odpowiada za przygotowanie publicznej strony głównej aplikacji.
 * Przekazuje podstawowe dane widoku prezentującego opis produktu,
 * główne akcje oraz sekcję funkcjonalności.
 */
class HomeController
{
    public function show()
    {
        // Dane widoku strony głównej są statyczne i nie wymagają dostępu do bazy danych.
        $data = [
            'title' => 'Strona Główna',
            'content' => 'Witamy na naszej stronie!'
        ];

        require_once __DIR__ . '/../views/home.php';
    }
}
