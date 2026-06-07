<?php

/**
 * Klasa ContactController
 * 
 * Odpowiada za przygotowanie publicznej strony kontaktowej.
 * Przekazuje statyczne dane do widoku prezentującego dostępne kanały kontaktu
 * i informacje organizacyjne zespołu.
 */
class ContactController
{
    public function show()
    {
        // Dane widoku są statyczne i służą ustawieniu tytułu strony.
        $data = [
            'title' => 'Kontakt',
            'content' => 'Skontaktuj się z nami.'
        ];

        require_once __DIR__ . '/../views/contact.php';
    }
}
