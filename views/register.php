<?php
/**
 * Widok: Rejestracja (Register)
 * 
 * Formularz tworzenia nowego konta użytkownika. Zbiera imię, nazwisko, 
 * adres e-mail i hasło. Wyświetla błędy walidacji
 * zajęty e-mail).
 */
?>
<!DOCTYPE html>
<html lang="pl">

<!-- Head -->
<?php include comp('head.php'); ?>

<body>
    <?php include comp('nav.php'); ?>

    <main class="auth-section">
        <div class="auth-card">
            <h2>Dołącz do nas</h2>
            <p>Zacznij mądrze planować wydatki</p>

            <?php if (isset($error)): ?>
                <div class="error-message"><?= $error ?></div>
            <?php endif; ?>

            <form action="<?= url('register') ?>" method="POST" class="auth-form">
                <div class="form-row">
                    <input type="text" name="first_name" placeholder="Imię" required class="auth-input" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                    <input type="text" name="last_name" placeholder="Nazwisko" required class="auth-input" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                </div>
                <input type="email" name="email" placeholder="Adres e-mail" required class="auth-input" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                <input type="password" name="password" placeholder="Hasło" required class="auth-input">
                <button type="submit" class="btn-primary">Utwórz konto</button>
            </form>

            <div class="auth-footer">
                Masz już konto? <a href="<?= url('login') ?>">Zaloguj się</a>
            </div>
        </div>
    </main>

    <?php include comp('footer.php'); ?>
</body>

</html>
