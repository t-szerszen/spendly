<?php
$title = $data['title'] ?? 'Błąd 404 - Strona nie istnieje';
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&display=swap" rel="stylesheet">
    <!-- Nasz styl css -->
    <link rel="stylesheet" href="/styles/style.css?v=<?php echo time(); ?>">
</head>

<body>

    <!-- Nawigacja -->
    <header>
        <div class="nav-container">
            <a href="/" class="logo">
                <img src="/logo-napis.png" alt="Spendly Logo">
            </a>
            <nav class="nav-links">
                <a href="/">Strona Główna</a>
                <a href="/about">O nas</a>
                <a href="/contact">Kontakt</a>
                <a href="/login" class="btn-primary">Zaloguj się</a>
            </nav>
        </div>
    </header>

    <!-- Sekcja 404 -->
    <main class="error-section">
        <div class="error-card">
            <div class="error-code">404</div>
            <h1 class="error-title">Ups! Zgubiliśmy się...</h1>
            <p class="error-desc">Strona, której szukasz, mogła zostać usunięta, zmieniła swój adres lub po prostu nigdy
                nie istniała. Pieniądze potrafią znikać, ale podstron postaramy się pilnować!</p>

            <div class="error-actions">
                <a href="/" class="btn-primary">Wróć na stronę główną</a>
                <a href="/contact" class="btn-secondary">Zgłoś problem</a>
            </div>
        </div>
    </main>

    <!-- Stopka -->
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Spendly. Wszelkie prawa zastrzeżone.</p>
    </footer>

</body>

</html>