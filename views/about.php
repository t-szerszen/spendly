<?php
/**
 * Widok: O nas
 * 
 * Prezentuje publiczną stronę informacyjną opisującą misję,
 * założenia produktu oraz wyróżniki aplikacji Spendly.
 */
$title = $data['title'] ?? 'O nas - Spendly';
$pageStyles = ['styles/pages/about.css'];
?>
<!DOCTYPE html>
<html lang="pl">

<?php include comp('head.php'); ?>

<body>

    <?php include comp('nav.php'); ?>

    <!-- Główna treść informacyjna strony "O nas". -->
    <main class="about-section">
        <!-- Nagłówek strony wyjaśniający ogólny cel aplikacji. -->
        <div class="about-header">
            <h1>Czym jest Spendly?</h1>
            <p>Spendly to nowoczesna aplikacja do zarządzania finansami osobistymi, stworzona z myślą o osobach, które chcą mieć pełną kontrolę nad swoimi pieniędzmi bez konieczności spędzania godzin nad skomplikowanymi arkuszami kalkulacyjnymi.</p>
        </div>

        <!-- Bloki tekstowe opisujące funkcje, misję i wizję produktu. -->
        <div class="about-content">
            <div class="about-text-block">
                <h2>Co oferuje Spendly?</h2>
                <p>Projekt narodził się z codziennej potrzeby łatwego, przejrzystego i możliwie najbardziej zautomatyzowanego monitorowania wydatków. Dzięki Spendly możesz w prosty sposób śledzić przepływ pieniędzy, analizować wydatki i podejmować lepsze decyzje finansowe na podstawie rzeczywistych danych.</p>
            </div>

            <div class="about-text-block">
                <h2>Nasza misja</h2>
                <p>Naszą misją jest umożliwienie każdemu świadomego i odpowiedzialnego zarządzania własnymi finansami. Chcemy, aby użytkownicy dokładnie wiedzieli, na co przeznaczają swoje środki, mogli skutecznie planować budżet oraz budować zdrowe nawyki finansowe.</p>
            </div>

            <div class="about-text-block">
                <h2>Dlaczego stworzyliśmy Spendly?</h2>
                <p>Podczas poszukiwania idealnego narzędzia do kontroli wydatków zauważyliśmy, że większość dostępnych rozwiązań znajduje się na dwóch skrajnościach: albo są to rozbudowane systemy finansowe pełne zaawansowanych funkcji i skomplikowanych raportów, albo bardzo proste aplikacje, które pozwalają jedynie zapisywać wydatki. Postanowiliśmy stworzyć rozwiązanie, które wypełni tę lukę.</p>
            </div>

            <div class="about-text-block">
                <h2>Co wyróżnia Spendly?</h2>
                <ul>
                    <li>Intuicyjny i nowoczesny interfejs, który nie wymaga długiego wdrażania.</li>
                    <li>Automatyczna organizacja wydatków w przejrzyste i konfigurowalne kategorie.</li>
                    <li>Analizy i statystyki w czasie rzeczywistym, dostępne zawsze wtedy, gdy ich potrzebujesz.</li>
                    <li>Przejrzyste wizualizacje danych, które pomagają zrozumieć strukturę wydatków.</li>
                    <li>Bezpieczeństwo danych jako jeden z fundamentów projektu.</li>
                    <li>Skupienie na tym, co najważniejsze — bez zbędnych funkcji i złożoności.</li>
                </ul>
            </div>

            <div class="about-text-block">
                <h2>Nasza wizja</h2>
                <p>Chcemy, aby Spendly stało się codziennym narzędziem wspierającym użytkowników w podejmowaniu świadomych decyzji finansowych. W świecie, w którym coraz więcej płatności odbywa się cyfrowo, łatwo stracić kontrolę nad przepływem pieniędzy. Naszą wizją jest stworzenie miejsca, w którym każda transakcja ma swoje znaczenie, każdy wydatek jest zrozumiały, a zarządzanie budżetem staje się prostym i naturalnym elementem codziennego życia.</p>
            </div>

            <div class="about-text-block about-highlight">
                <h2>Spendly</h2>
                <p>Świadomość finansowa zaczyna się od zrozumienia własnych wydatków. Spendly pomaga zamienić chaotyczne zestawienia liczb w jasne, wartościowe informacje, które wspierają lepsze decyzje każdego dnia.</p>
            </div>
        </div>
    </main>

    <?php include comp('footer.php'); ?>

</body>

</html>
