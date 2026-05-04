<header>
    <div class="nav-container">
        <a href="<?= url('dashboard') ?>" class="logo">
            <img src="<?= url('logo-napis.png') ?>" alt="Spendly Logo">
        </a>
        <nav class="nav-links">
            <a href="<?= url('dashboard') ?>">Panel główny</a>
            <a href="<?= url('transactions') ?>">Transakcje</a>
            <div class="user-info">
                <span class="user-info-text">
                    Zalogowano jako: <strong class="user-info-name"><?= $_SESSION['first_name'] ?></strong>
                </span>
                <a href="<?= url('logout') ?>" class="btn-secondary btn-logout">Wyloguj</a>
            </div>
        </nav>
    </div>
</header>