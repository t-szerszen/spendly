<?php
/**
 * Widok: Błąd 404 (Not Found)
 * 
 * Strona błędu wyświetlana, gdy użytkownik próbuje wejść na nieistniejący URL.
 * Oferuje przyciski pozwalające wrócić na stronę główną lub skontaktować się z pomocą.
 */
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
    <!-- Styl CSS -->
    <link rel="stylesheet" href="<?= url('styles/style.css') ?>?v=<?= time() ?>">
</head>

<body>

    <!-- Nawigacja -->
    <?php include 'components/nav.php'; ?>

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