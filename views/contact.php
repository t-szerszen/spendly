<?php
$title = $data['title'] ?? 'Kontakt - Spendly';
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
    <link rel="stylesheet" href="<?= url('styles/style.css') ?>?v=<?= time() ?>">
</head>

<body>

    <!-- Nawigacja -->
    <?php include 'components/nav.php'; ?>

    <!-- Sekcja Kontakt -->
    <main class="contact-section">
        <div class="contact-header">
            <h1>Skontaktuj się z nami</h1>
            <p>Masz pytania, propozycje współpracy a może znalazłeś błąd? Jesteśmy do Twojej dyspozycji.</p>
        </div>

        <div class="contact-grid">
            <!-- Karta Email -->
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

            <!-- Karta Serwer Discord / Media społecznościowe -->
            <div class="contact-card">
                <div class="contact-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z">
                        </path>
                    </svg>
                </div>
                <h3>Społeczność</h3>
                <p>Dołącz do naszego kanału:</p>
                <a href="#">Discord Spendly</a>
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
                <p>Zespół pracuje zdalnie,<br>głównie w obrębie Warszawy.</p>
            </div>
        </div>
    </main>

    <!-- Stopka -->
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Spendly. Wszelkie prawa zastrzeżone.</p>
    </footer>

</body>

</html>