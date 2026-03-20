<?php
$title = $data['title'] ?? 'Spendly - Mądrze zarządzaj swoimi finansami';
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <!-- Google Fonts for modern typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&display=swap" rel="stylesheet">
    <!-- Nasz styl css -->
    <link rel="stylesheet" href="/styles/style.css?v=<?php echo time(); ?>">
</head>

<body>

    <!-- Nawigacja -->
    <?php include 'components/nav.php'; ?>

    <!-- Sekcja Hero -->
    <section class="hero">
        <h1>Odzyskaj pełną kontrolę nad budżetem</h1>
        <p>Spendly to nowoczesne narzędzie, z którym w łatwy i przyjemny sposób przeanalizujesz swoje wydatki, ustalisz
            plany oszczędnościowe i osiągniesz wolność finansową.</p>
        <div class="hero-buttons">
            <a href="/register" class="btn-primary">Załóż darmowe konto</a>
            <a href="/about" class="btn-secondary">Dowiedz się więcej</a>
        </div>
    </section>

    <!-- Sekcja Funkcjonalności / O projekcie -->
    <section class="features">
        <h2 class="section-title">Dlaczego Spendly?</h2>
        <div class="features-grid">

            <!-- Karta 1 -->
            <div class="feature-card">
                <div class="feature-icon">
                    <!-- Ikona SVG (przykładowy portfel) -->
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
                        </path>
                    </svg>
                </div>
                <h3>Śledzenie wydatków</h3>
                <p>Kategoryzuj swoje codzienne zakupy i w automatyczny sposób zobacz, gdzie uciekają Twoje pieniądze.
                    Szybkie i bezbolesne wprowadzanie danych.</p>
            </div>

            <!-- Karta 2 -->
            <div class="feature-card">
                <div class="feature-icon">
                    <!-- Ikona SVG (wykres) -->
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                    </svg>
                </div>
                <h3>Dokładne Analizy</h3>
                <p>Korzystaj z przejrzystych wykresów, które pomogą Ci zrozumieć Twoje nawyki finansowe i prognozować
                    wydatki na nadchodzące miesiące.</p>
            </div>

            <!-- Karta 3 -->
            <div class="feature-card">
                <div class="feature-icon">
                    <!-- Ikona SVG (cel/tarcza) -->
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3>Planowanie i Cele</h3>
                <p>Wyznaczaj realistyczne limity budżetowe oraz cele oszczędnościowe (np. na wymarzony wyjazd). Spendly
                    przypilnuje, żebyś mieścił się w limitach.</p>
            </div>

        </div>
    </section>

    <!-- Stopka -->
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Spendly. Wszelkie prawa zastrzeżone.</p>
    </footer>

</body>

</html>