<?php
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
