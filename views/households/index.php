<?php
$households = $data['households'];
?>
<!DOCTYPE html>
<html lang="pl">
<?php include comp('head.php'); ?>
<body>
<?php include comp('navDashboard.php'); ?>

<main class="auth-section households-section">
    <div class="container households-container">
        <div class="households-header">
            <div>
                <h1 class="dashboard-title">Gospodarstwa domowe</h1>
                <p class="households-subtitle">Lista gospodarstw, do których masz dostęp.</p>
            </div>
            <a href="<?= url('households/create') ?>" class="btn-primary">+ Nowe gospodarstwo</a>
        </div>

        <?php if (!empty($_GET['invite']) && $_GET['invite'] === 'expired'): ?>
            <div class="form-error">To zaproszenie wygasło.</div>
        <?php elseif (!empty($_GET['invite']) && $_GET['invite'] === 'invalid'): ?>
            <div class="form-error">Nie znaleziono takiego zaproszenia.</div>
        <?php elseif (!empty($_GET['invite']) && $_GET['invite'] === 'wrong-account'): ?>
            <div class="form-error">To zaproszenie jest przypisane do innego adresu email.</div>
        <?php endif; ?>

        <?php if (!empty($households)): ?>
            <div class="households-grid">
                <?php foreach ($households as $household): ?>
                    <a class="auth-card household-card" href="<?= url('households/show?id=' . (int) $household['id']) ?>">
                        <h3><?= htmlspecialchars($household['name']) ?></h3>
                        <p>Utworzone: <?= htmlspecialchars($household['created_at'] ?? '') ?></p>
                        <p>Członków: <?= (int) ($household['member_count'] ?? 0) ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="auth-card household-empty">
                <p>Nie masz jeszcze żadnego gospodarstwa domowego.</p>
                <a href="<?= url('households/create') ?>" class="btn-primary">Utwórz pierwsze</a>
            </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
