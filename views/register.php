<?php
/**
 * Widok: Rejestracja
 * 
 * Prezentuje formularz tworzenia konta użytkownika.
 * Obsługuje komunikaty błędów walidacji przekazane przez RegisterController.
 */
?>
<!DOCTYPE html>
<html lang="pl">

<?php include comp('head.php'); ?>

<body>
    <?php include comp('nav.php'); ?>

    <main class="auth-section">
        <div class="auth-card">
            <h2>Dołącz do nas</h2>
            <p>Zacznij mądrze planować wydatki</p>

            <!-- Komunikat błędu przekazany przez RegisterController po nieudanej rejestracji. -->
            <?php if (isset($error)): ?>
                <div class="error-message"><?= $error ?></div>
            <?php endif; ?>

            <!-- Formularz tworzenia konta przesyłany do akcji RegisterController::register(). -->
            <form action="<?= url('register') ?>" method="POST" class="auth-form">
                <div class="form-row">
                    <input type="text" name="first_name" placeholder="Imię" required class="auth-input">
                    <input type="text" name="last_name" placeholder="Nazwisko" required class="auth-input">
                </div>
                <input type="email" name="email" placeholder="Adres e-mail" required class="auth-input">
                <input type="password" name="password" placeholder="Hasło" required class="auth-input">
                <button type="submit" class="btn-primary">Utwórz konto</button>
            </form>

            <!-- Link kierujący istniejących użytkowników do formularza logowania. -->
            <div class="auth-footer">
                Masz już konto? <a href="<?= url('login') ?>">Zaloguj się</a>
            </div>
        </div>
    </main>

    <?php include comp('footer.php'); ?>
</body>

</html>
