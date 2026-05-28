<?php
/**
 * Globalne funkcje pomocnicze.
 */

if (!function_exists('url')) {
    /**
     * Generuje pełny, absolutny adres URL na podstawie podanej ścieżki.]
     * 
     * @param string $path Ścieżka wewnątrz aplikacji (np. 'rejestracja' lub 'css/main.css').
     * 
     * @return string Pełny adres URL.
     */
    function url(string $path = ''): string
    {
        // Usuwa ukośniki z końca BASE_URL i początku $path, aby uniknąć duplikatów
        return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    /**
     * Generuje pełny adres URL do zasobów (domyślnie w folderze images).
     * 
     * @param string $path Ścieżka do pliku wewnątrz folderu images (np. 'logo.png').
     * 
     * @return string Pełny adres URL do zasobu.
     */
    function asset(string $path = ''): string
    {
        return url('images/' . ltrim($path, '/'));
    }
}

if (!function_exists('comp')) {
    /**
     * Zwraca pełną ścieżkę do pliku komponentu widoku.
     *
     * @param string $path Nazwa pliku w views/components.
     *
     * @return string Pełna ścieżka systemowa do komponentu.
     */
    function comp(string $path = ''): string
    {
        return __DIR__ . '/views/components/' . ltrim($path, '/');
    }
}

if (!function_exists('absolute_url')) {
    /**
     * Generuje pełny adres URL z domeną.
     *
     * @param string $path Ścieżka wewnątrz aplikacji.
     *
     * @return string Pełny adres URL.
     */
    function absolute_url(string $path = ''): string
    {
        $base = $_ENV['APP_URL'] ?? '';

        if ($base === '') {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $scriptName = dirname($_SERVER['SCRIPT_NAME'] ?? '');
            $base = $scheme . '://' . $host . ($scriptName === '/' ? '' : $scriptName);
        }

        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}
