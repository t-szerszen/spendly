<?php

/**
 * Klasa AboutController
 * 
 * Odpowiada za wyświetlenie statycznej strony "O nas". Przygotowuje
 * dane z tytułem i treścią, po czym przekazuje je do widoku 'about.php'.
 */
class AboutController
{
    public function show()
    {
        $data = [
            'title' => 'O nas',
            'content' => 'Dowiedz się o nas więcej.'
        ];

        require_once __DIR__ . '/../views/about.php';
    }
}
