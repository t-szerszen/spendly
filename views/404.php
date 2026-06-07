<?php
/**
 * Widok: Błąd 404 (Not Found)
 * 
 * Strona błędu wyświetlana, gdy użytkownik próbuje wejść na nieistniejący URL.
 * Oferuje przyciski pozwalające wrócić na stronę główną lub skontaktować się z pomocą.
 */
$title = $data['title'] ?? 'Błąd 404 - Strona nie istnieje';
$pageStyles = ['styles/pages/error.css'];
?>
<!DOCTYPE html>
<html lang="pl">

<!-- Head -->
<?php include comp('head.php'); ?>

<body>

    <!-- Nawigacja -->
    <?php include comp('nav.php'); ?>

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

    <?php include comp('footer.php'); ?>

</body>

</html>
