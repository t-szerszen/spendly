<header>
    <div class="nav-container">
        <a href="<?= url('dashboard') ?>" class="logo">
            <img src="<?= url('logo-napis.png') ?>" alt="Spendly Logo">
        </a>
        <nav class="nav-links">
            <a href="<?= url('dashboard') ?>">Panel główny</a>
            <a href="<?= url('transactions') ?>">Transakcje</a>
            <div class="user-info" style="display: flex; align-items: center; gap: 1.5rem; margin-left: 1rem;">
                <span style="color: var(--color-text-mutated); font-size: 0.9rem;">
                    Zalogowano jako: <strong style="color: var(--color-blue)"><?= $_SESSION['first_name'] ?></strong>
                </span>
                <a href="<?= url('logout') ?>" class="btn-secondary" style="padding: 0.4rem 1rem; font-size: 0.85rem;">Wyloguj</a>
            </div>
        </nav>
    </div>
</header>