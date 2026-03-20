<?php
// Dynamiczne pobranie adresu URL z serwera, domyślnie localhost:8000
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$domain = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
define('BASE_URL', $protocol . '://' . $domain);