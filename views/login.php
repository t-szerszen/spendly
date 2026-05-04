<?php
/**
 * Widok: Logowanie (Login)
 * 
 * Wyświetla formularz logowania dla użytkowników (email i hasło).
 * Odbiera błędy z kontrolera w przypadku niepowodzenia (zmienna $error) 
 * i komunikaty po udanej rejestracji.
 */
?>
<!DOCTYPE html>
<html lang="pl">

<!-- Head -->
<?php include 'components/head.php'; ?>

<body>
    <?php include 'components/nav.php'; ?>

    <main class="auth-section">
        <div class="auth-card">
            <h2>Witaj ponownie</h2>
            <p>Zaloguj się, aby zarządzać finansami</p>
            <?php if (isset($_GET['registered']) && $_GET['registered'] === 'success'): ?>
                <div class="register-success">
                    Konto zostało utworzone! Możesz się teraz zalogować.
                </div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="error-message"><?= $error ?></div>
            <?php endif; ?>

            <form action="<?= url('login') ?>" method="POST" class="auth-form">
                <input type="email" name="email" placeholder="Adres e-mail" required class="auth-input">
                <input type="password" name="password" placeholder="Hasło" required class="auth-input">
                <button type="submit" class="btn-primary">Zaloguj się</button>
            </form>

            <div class="auth-footer">
                Nie masz konta? <a href="<?= url('register') ?>">Załóż je teraz</a>
            </div>
        </div>
    </main>
</body>

</html>