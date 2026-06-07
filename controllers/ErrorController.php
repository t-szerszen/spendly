<?php

/**
 * Klasa ErrorController
 * 
 * Odpowiada za obsługę widoków błędów aplikacji.
 * Aktualnie przygotowuje odpowiedź 404 dla nieistniejących tras
 * oraz ładuje publiczny widok informujący o braku strony.
 */
class ErrorController
{
    public function show404()
    {
        // Dane widoku opisują błąd prezentowany użytkownikowi.
        $data = [
            'title' => 'Błąd 404',
            'content' => 'Nie znaleziono strony.'
        ];

        // Kod HTTP musi odpowiadać stanowi strony, aby przeglądarka i roboty poprawnie rozpoznały błąd.
        http_response_code(404);
        require_once __DIR__ . '/../views/404.php';
    }
}
