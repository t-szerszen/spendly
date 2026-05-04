<?php
/**
 * Widok: O nas (About)
 * 
 * Statyczna strona informacyjna przedstawiająca misję projektu Spendly oraz 
 * powody jego powstania. Prezentowana niezalogowanym użytkownikom.
 */
$title = $data['title'] ?? 'O nas - Spendly';
?>
<!DOCTYPE html>
<html lang="pl">

<!-- Head -->
<?php include 'components/head.php'; ?>

<body>

    <!-- Nawigacja -->
    <?php include 'components/nav.php'; ?>

    <!-- Sekcja O nas -->
    <main class="about-section">
        <div class="about-header">
            <h1>Czym jest Spendly?</h1>
            <p>Projekt zrodzony z potrzeby prostego i zautomatyzowanego kontrolowania finansów osobistych bez ogromnych,
                przytłaczających tabel w Excelu.</p>
        </div>

        <div class="about-content">
            <div class="about-text-block">
                <h2>Nasza Misja</h2>
                <p>Uważamy, że każdy powinien mieć możliwość świadomego zarządzania swoimi pieniędzmi. Spendly powstało,
                    aby zapewnić przyjazny, szybki i przede wszystkim bezpieczny sposób na monitorowanie wydatków.
                    Koniec z zastanawianiem się "Gdzie podziały się moje pieniądze?". Z nami każdy grosz ma swoje
                    miejsce.</p>
            </div>

            <div class="about-text-block">
                <h2>Dlaczego stworzyliśmy aplikację?</h2>
                <p>Istniejące rozwiązania były albo zbyt skomplikowane i naszpikowane setkami zbędnych ustawień, albo
                    zbyt proste, by cokolwiek z nich wywnioskować. Zdecydowaliśmy się zbudować system "dokładnie w
                    punkt" – piękny, nowoczesny, przypisujący transakcje do elastycznych kategorii i dający klarowne,
                    wykresowe analizy w czasie rzeczywistym.</p>
            </div>
        </div>
    </main>

    <!-- Stopka -->
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Spendly. Wszelkie prawa zastrzeżone.</p>
    </footer>

</body>

</html>