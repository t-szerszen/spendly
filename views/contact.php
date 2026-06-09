<?php
/**
 * Widok: Kontakt
 * 
 * Prezentuje publiczną stronę kontaktową z kanałami komunikacji,
 * społecznością oraz informacją o trybie pracy zespołu.
 */
$pageStyles = ['styles/pages/contact.css'];
?>
<!DOCTYPE html>
<html lang="pl">

<?php include comp('head.php'); ?>

<body>

    <?php include comp('nav.php'); ?>

    <!-- Główna sekcja kontaktowa z kartami kanałów komunikacji. -->
    <main class="contact-section">
        <!-- Nagłówek strony z krótkim wprowadzeniem dla użytkownika. -->
        <div class="contact-header">
            <h1>Skontaktuj się z nami</h1>
            <p>Masz pytania, propozycje współpracy a może znalazłeś błąd? Jesteśmy do Twojej dyspozycji.</p>
        </div>

        <div class="contact-grid">
            <!-- Karta kontaktu mailowego. -->
            <div class="contact-card">
                <div class="contact-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                        </path>
                    </svg>
                </div>
                <h3>E-mail</h3>
                <p>Napisz do nas bezpośrednio:</p>
                <a href="mailto:kontakt@spendly.pl">kontakt@spendly.pl</a>
            </div>

            <!-- Karta Lokalizacja -->
            <div class="contact-card">
                <div class="contact-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                        </path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <h3>Siedziba</h3>
                <p>Zespół pracuje zdalnie,<br>głównie w obrębie Leszna.</p>
            </div>
        </div>
    </main>

    <?php include comp('footer.php'); ?>

</body>

</html>
