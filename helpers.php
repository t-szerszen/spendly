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