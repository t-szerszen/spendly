<?php
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
