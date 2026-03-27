<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title><?= $data['title'] ?></title>
    <link rel="stylesheet" href="<?= url('styles/style.css') ?>">
</head>
<body>
    <?php include 'components/navDashboard.php'; ?>

    <main class="auth-section" style="align-items: flex-start; padding-top: 4rem;">
        <div class="container" style="max-width: 1200px; margin: 0 auto; width: 100%;">
            <h1 style="color: var(--color-blue); margin-bottom: 2rem;">Witaj w Spendly, <?= $_SESSION['first_name'] ?>!</h1>
            
            <div class="auth-card" style="max-width: 100%; text-align: left; padding: 2rem;">
                <h3>Twój stan konta</h3>
                <p>Tutaj niedługo pojawią się Twoje wykresy i podsumowania wydatków.</p>
            </div>
        </div>
    </main>
</body>
</html>