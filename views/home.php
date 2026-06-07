<?php
/**
 * Widok: Strona główna
 * 
 * Prezentuje publiczną stronę startową aplikacji Spendly.
 * Zawiera sekcję powitalną, główne akcje rejestracji i informacji oraz slider funkcjonalności.
 */
$title = $data['title'] ?? 'Spendly - Mądrze zarządzaj swoimi finansami';
?>
<!DOCTYPE html>
<html lang="pl">

<?php include comp('head.php'); ?>

<body>

    <?php include comp('nav.php'); ?>

    <!-- Sekcja powitalna z głównym komunikatem i akcjami publicznymi. -->
    <section class="hero">
        <h1>Odzyskaj pełną kontrolę nad budżetem</h1>
        <p>Spendly to nowoczesne narzędzie, z którym w łatwy i przyjemny sposób przeanalizujesz swoje wydatki, ustalisz
            plany oszczędnościowe i osiągniesz wolność finansową.</p>
        <div class="hero-buttons">
            <a href="<?= url('register') ?>" class="btn-primary">Załóż darmowe konto</a>
            <a href="<?= url('about') ?>" class="btn-secondary">Dowiedz się więcej</a>
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
                        <h3>Planowanie i Cele</h3>
                        <p>Wyznaczaj realistyczne limity budżetowe oraz cele oszczędnościowe (np. na wymarzony wyjazd). Spendly przypilnuje, żebyś mieścił się w limitach.</p>
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
        <h2 class="bottom-highlight-heading">Spendly — bo świadomość finansowa zaczyna się od zrozumienia własnych wydatków.</h2>
    </section>

    <?php include comp('footer.php'); ?>

    <script>
        (function() {
            // Lokalna logika slidera strony głównej: slajdy, przyciski i automatyczna zmiana.
            const sliderInner = document.getElementById('featureSlider');
            const slides = document.querySelectorAll('#featureSlider .slide');
            const buttons = document.querySelectorAll('.slider-button');
            const prevButton = document.querySelector('.slider-prev');
            const nextButton = document.querySelector('.slider-next');
            let currentIndex = 0;
            let timer = null;

            function showSlide(index) {
                // Indeks jest zawijany, aby nawigacja działała cyklicznie.
                if (index < 0) index = slides.length - 1;
                if (index >= slides.length) index = 0;
                slides.forEach((slide, idx) => {
                    slide.style.display = idx === index ? 'flex' : 'none';
                    slide.classList.toggle('active', idx === index);
                });
                buttons.forEach((btn, idx) => btn.classList.toggle('active', idx === index));
                currentIndex = index;
            }

            function nextSlide() {
                showSlide(currentIndex + 1);
            }

            function resetTimer() {
                // Interwał jest resetowany po ręcznej zmianie slajdu.
                if (timer) clearInterval(timer);
                timer = setInterval(nextSlide, 7000);
            }

            buttons.forEach((button, index) => {
                button.addEventListener('click', () => {
                    showSlide(index);
                    resetTimer();
                });
            });

            if (prevButton) {
                prevButton.addEventListener('click', () => {
                    showSlide(currentIndex - 1);
                    resetTimer();
                });
            }

            if (nextButton) {
                nextButton.addEventListener('click', () => {
                    showSlide(currentIndex + 1);
                    resetTimer();
                });
            }

            if (slides.length) {
                showSlide(0);
                resetTimer();
            }
        })();
    </script>
</body>

</html>
