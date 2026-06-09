<?php

/**
 * Klasa AboutController
 * 
 * Odpowiada za przygotowanie publicznej strony informacyjnej "O nas".
 * Przekazuje statyczne dane do widoku opisującego misję, założenia
 * oraz wyróżniki aplikacji Spendly.
 */
class AboutController
{
    public function show()
    {
        // Dane widoku są statyczne i służą ustawieniu tytułu strony.
        $data = [
            'title' => 'O nas',
        ];

        require_once __DIR__ . '/../views/about.php';
    }
}
