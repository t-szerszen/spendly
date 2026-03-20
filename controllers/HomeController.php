<?php 
class HomeController
{
    public function show()
    {
        // Tutaj można dodać logikę, np. pobieranie danych z modelu
        $data = [
            'title' => 'Strona Główna',
            'content' => 'Witamy na naszej stronie!'
        ];

        // Załaduj widok i przekaż dane
        require_once __DIR__ . '/../views/home.php';
    }
}