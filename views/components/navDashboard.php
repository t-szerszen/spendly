<?php
/**
 * Komponent: Nawigacja Panelu Głównego (Zalogowany)
 * 
 * Górny pasek nawigacyjny wyświetlany w widokach, które wymagają zalogowania
 * (jak Dashboard, Transakcje). Wyświetla powitanie, odnośniki do głównych sekcji
 * zarządzania budżetem oraz przycisk wylogowania.
 */
?>
<header>
    <div class="nav-container">
        <a href="<?= url('dashboard') ?>" class="logo">
            <img src="<?= asset('logo_napis.png') ?>" alt="Spendly Logo">
        </a>
        <nav class="nav-links">
            <a href="<?= url('dashboard') ?>">Panel główny</a>
            <a href="<?= url('wallet') ?>">Portfel</a>
            <a href="<?= url('transactions') ?>">Transakcje</a>
            <a href="<?= url('summary') ?>">Podsumowanie</a>
            <a href="<?= url('shared_budgets') ?>">Wspólne budżety</a>
            <div class="user-info">
                <span class="user-info-text">
                    Zalogowano jako: <strong class="user-info-name"><?= $_SESSION['first_name'] ?></strong>
                </span>
                <a href="<?= url('logout') ?>" class="btn-secondary btn-logout">Wyloguj</a>
            </div>
        </nav>
    </div>
</header>
