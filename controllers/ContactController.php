<?php
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
