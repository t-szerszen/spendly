<?php

/**
 * Klasa ContactController
 * 
 * Obsługuje wyświetlanie strony kontaktowej. Przekazuje odpowiednie zmienne
 * z tytułem i treścią do widoku 'contact.php'.
 */
class ContactController
{
    public function show()
    {
        $data = [
            'title' => 'Kontakt',
            'content' => 'Skontaktuj się z nami.'
        ];

        require_once __DIR__ . '/../views/contact.php';
    }
}
