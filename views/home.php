<?php
/**
 * Widok: Strona główna
 * 
 * Prezentuje publiczną stronę startową aplikacji Spendly.
 * Zawiera sekcję powitalną, główne akcje rejestracji i informacji oraz slider funkcjonalności.
 */
$title = $data['title'] ?? 'Spendly - Mądrze zarządzaj swoimi finansami';
$pageStyles = ['styles/pages/home.css'];
?>
<!DOCTYPE html>
<html lang="pl">

<?php include comp('head.php'); ?>

<body>

    <?php include comp('nav.php'); ?>

    <!-- Sekcja powitalna z głównym komunikatem i akcjami publicznymi. -->
    <section class="hero">
        <span class="hero-eyebrow">Finanse osobiste i wspólne rozliczenia</span>
        <h1>Odzyskaj kontrolę nad wydatkami</h1>
        <p>Spendly porządkuje codzienne transakcje, pokazuje realny obraz budżetu i ułatwia rozliczenia między kilkoma osobami. Wszystko w jednym, prostym panelu.</p>
        <div class="hero-buttons">
            <a href="<?= url('register') ?>" class="btn-primary">Załóż darmowe konto</a>
            <a href="<?= url('about') ?>" class="btn-secondary">Dowiedz się więcej</a>
        </div>
        <div class="hero-points">
            <div class="hero-point">
                <strong>Jedno miejsce</strong>
                <span>Wydatki prywatne, przychody i koszty wspólne bez przełączania się między narzędziami.</span>
            </div>
            <div class="hero-point">
                <strong>Czytelny obraz miesiąca</strong>
                <span>Szybko zobaczysz, kto zapłacił, ile wydał i jaki jest realny bilans po rozliczeniach.</span>
            </div>
            <div class="hero-point">
                <strong>Mniej ręcznego liczenia</strong>
                <span>System sam podpowiada, kto komu powinien przelać pieniądze we wspólnym budżecie.</span>
            </div>
        </div>
    </section>

    <!-- Slider prezentujący najważniejsze funkcjonalności aplikacji. -->
    <section class="features slider-section">
        <h2 class="section-title">Dlaczego Spendly?</h2>
        <div class="slider">
            <button class="slider-arrow slider-prev" aria-label="Poprzedni slajd">&#10094;</button>
            <div class="slider-inner" id="featureSlider">
                <div class="slide active" style="background-image: url('<?= asset('slajd-jeden.png') ?>'); display:flex;">
                    <div class="slide-overlay"></div>
                    <div class="slide-content">
                        <h3>Śledzenie wydatków</h3>
                        <p>Kategoryzuj swoje codzienne zakupy i w automatyczny sposób zobacz, gdzie uciekają Twoje pieniądze. Szybkie i bezbolesne wprowadzanie danych.</p>
                    </div>
                </div>
                <div class="slide" style="background-image: url('<?= asset('slajd-dwa.png') ?>'); display:none;">
                    <div class="slide-overlay"></div>
                    <div class="slide-content">
                        <h3>Dokładne Analizy</h3>
                        <p>Korzystaj z przejrzystych wykresów, które pomogą Ci zrozumieć Twoje nawyki finansowe i prognozować wydatki na nadchodzące miesiące.</p>
                    </div>
                </div>
                <div class="slide" style="background-image: url('<?= asset('slajd-trzy.png') ?>'); display:none;">
                    <div class="slide-overlay"></div>
                    <div class="slide-content">
                        <h3>Shared Budgets</h3>
                        <p>Twórz wspólne budżety z partnerem, znajomymi albo współlokatorami. Spendly pokazuje udział każdej osoby, historię wspólnych kosztów i proponuje przelewy potrzebne do wyrównania rozliczeń.</p>
                    </div>
                </div>
            </div>
            <button class="slider-arrow slider-next" aria-label="Następny slajd">&#10095;</button>
        </div>
        <div class="slider-controls">
            <button class="slider-button active" data-slide="0" aria-label="Slajd 1"></button>
            <button class="slider-button" data-slide="1" aria-label="Slajd 2"></button>
            <button class="slider-button" data-slide="2" aria-label="Slajd 3"></button>
        </div>
    </section>

    <section class="bottom-highlight">
        <h2 class="bottom-highlight-heading">Spendly — bo każda złotówka ma swoją historię.</h2>
    </section>

    <?php include comp('footer.php'); ?>

    <script src="<?= url('scripts/homeSlider.js') ?>"></script>
</body>

</html>
