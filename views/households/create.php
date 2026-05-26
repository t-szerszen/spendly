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
        <div class="auth-card households-form-card">
            <h1>Nowe gospodarstwo domowe</h1>

            <?php if (!empty($error)): ?>
                <div class="form-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="<?= url('households/store') ?>" method="POST" class="auth-form households-form">
                <input type="text" name="name" placeholder="Nazwa gospodarstwa" required class="auth-input">
                <button type="submit" class="btn-primary">Utwórz</button>
                <a href="<?= url('households') ?>" class="btn-secondary">Anuluj</a>
            </form>
        </div>
    </div>
</main>
</body>
</html>
