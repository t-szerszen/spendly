<?php
$error = $error ?? null;
?>
<!DOCTYPE html>
<html lang="pl">
<?php include comp('head.php'); ?>
<body>
<?php include comp('navDashboard.php'); ?>

<main class="auth-section households-section">
    <div class="container households-container narrow">
        <div class="auth-card households-create-card">
            <div class="household-create-copy">
                <p class="household-eyebrow">Nowe gospodarstwo</p>
                <h1>Nowe gospodarstwo domowe</h1>
                <p>Nadaj nazwę wspólnej przestrzeni, do której później zaprosisz domowników i dodasz wspólne wydatki.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="form-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="<?= url('households/store') ?>" method="POST" class="auth-form households-form households-create-form">
                <div class="household-create-field">
                    <label for="household-name" class="household-field-label">Nazwa gospodarstwa</label>
                    <input id="household-name" type="text" name="name" placeholder="Np. Mieszkanie na Piastów" required class="auth-input">
                </div>
                <div class="household-create-actions">
                    <button type="submit" class="btn-primary">Utwórz gospodarstwo</button>
                    <a href="<?= url('households') ?>" class="btn-secondary">Anuluj</a>
                </div>
            </form>
        </div>
    </div>
</main>
</body>
</html>
