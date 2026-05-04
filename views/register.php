<?php
/**
 * Widok: Rejestracja (Register)
 * 
 * Formularz tworzenia nowego konta użytkownika. Zbiera imię, nazwisko, 
 * adres e-mail i hasło. Wyświetla błędy walidacji (np. za krótkie hasło, 
 * zajęty e-mail).
 */
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <title><?= $data['title'] ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&display=swap" rel="stylesheet">
    <!-- Styl CSS -->
    <link rel="stylesheet" href="<?= url('styles/style.css') ?>?v=<?= time() ?>">
</head>

<body>
    <?php include 'components/nav.php'; ?>

    <main class="auth-section">
        <div class="auth-card">
            <h2>Dołącz do nas</h2>
            <p>Zacznij mądrze planować wydatki</p>

            <?php if (isset($error)): ?>
                <div class="error-message"><?= $error ?></div>
            <?php endif; ?>

            <form action="<?= url('register') ?>" method="POST" class="auth-form">
                <div class="form-row">
                    <input type="text" name="first_name" placeholder="Imię" required class="auth-input">
                    <input type="text" name="last_name" placeholder="Nazwisko" required class="auth-input">
                </div>
                <input type="email" name="email" placeholder="Adres e-mail" required class="auth-input">
                <input type="password" name="password" placeholder="Hasło (min. 8 znaków)" required class="auth-input">
                <button type="submit" class="btn-primary">Utwórz konto</button>
            </form>

            <div class="auth-footer">
                Masz już konto? <a href="<?= url('login') ?>">Zaloguj się</a>
            </div>
        </div>
    </main>
</body>

</html>