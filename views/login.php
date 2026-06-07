<?php
/**
 * Widok: Logowanie
 * 
 * Prezentuje formularz uwierzytelniania użytkownika.
 * Obsługuje komunikat po udanej rejestracji oraz błąd logowania przekazany z kontrolera.
 */
?>
<!DOCTYPE html>
<html lang="pl">

<?php include comp('head.php'); ?>

<body>
    <?php include comp('nav.php'); ?>

    <main class="auth-section">
        <div class="auth-card">
            <h2>Witaj ponownie</h2>
            <p>Zaloguj się, aby zarządzać finansami</p>
            <!-- Komunikat potwierdzający utworzenie konta po przekierowaniu z rejestracji. -->
            <?php if (isset($_GET['registered']) && $_GET['registered'] === 'success'): ?>
                <div class="register-success">
                    Konto zostało utworzone! Możesz się teraz zalogować.
                </div>
            <?php endif; ?>
            <!-- Komunikat błędu przekazany przez LoginController po nieudanej próbie logowania. -->
            <?php if (isset($error)): ?>
                <div class="error-message"><?= $error ?></div>
            <?php endif; ?>

            <!-- Formularz przesyła dane logowania do akcji LoginController::login(). -->
            <form action="<?= url('login') ?>" method="POST" class="auth-form">
                <input type="text" name="email" placeholder="Adres e-mail" required class="auth-input">
                <input type="password" name="password" placeholder="Hasło" required class="auth-input">
                <button type="submit" class="btn-primary">Zaloguj się</button>
            </form>

            <!-- Link kierujący nowych użytkowników do procesu rejestracji. -->
            <div class="auth-footer">
                Nie masz konta? <a href="<?= url('register') ?>">Załóż je teraz</a>
            </div>
        </div>
    </main>

    <?php include comp('footer.php'); ?>
</body>

</html>
