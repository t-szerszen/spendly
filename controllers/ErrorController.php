<?php

/**
 * Klasa ErrorController
 * 
 * Obsługuje wyświetlanie stron błędów, w szczególności błędu 404 (nie znaleziono strony).
 * Odpowiada za prawidłowe ustawienie nagłówka HTTP (np. 404 Not Found) i załadowanie 
 * odpowiedniego widoku ('404.php').
 */
class ErrorController
{
    public function show404()
    {
        $data = [
            'title' => 'Błąd 404',
            'content' => 'Nie znaleziono strony.'
        ];

        http_response_code(404);
        require_once __DIR__ . '/../views/404.php';
    }
}
