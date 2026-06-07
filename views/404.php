<?php
/**
 * Widok: Błąd 404
 * 
 * Prezentuje publiczny komunikat dla nieistniejącej trasy aplikacji.
 * Udostępnia akcje powrotu na stronę główną oraz przejścia do kontaktu.
 */
$title = $data['title'] ?? 'Błąd 404 - Strona nie istnieje';
$pageStyles = ['styles/pages/error.css'];
?>
<!DOCTYPE html>
<html lang="pl">

<?php include comp('head.php'); ?>

<body>

    <?php include comp('nav.php'); ?>

    <!-- Karta błędu z kodem HTTP i akcjami nawigacyjnymi. -->
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
