<?php
$households = $data['households'];
$householdCount = count($households);
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

        <section class="auth-card households-list-hero">
            <div class="households-list-copy">
                <p class="household-eyebrow">Twoje gospodarstwa</p>
                <h2>Wspólne budżety w jednym miejscu</h2>
                <p>Przeglądaj aktywne gospodarstwa, przechodź do rozliczeń i zarządzaj wspólnymi kosztami bez szukania po całej aplikacji.</p>
            </div>
            <div class="households-list-hero-meta">
                <div class="households-list-inline-stat">
                    <span class="household-kpi-label">Aktywne gospodarstwa</span>
                    <strong><?= $householdCount ?></strong>
                </div>
                <p><?= $householdCount === 1 ? 'Masz obecnie dostęp do 1 gospodarstwa.' : 'Masz obecnie dostęp do ' . $householdCount . ' gospodarstw.' ?></p>
            </div>
        </section>

        <?php if (!empty($_GET['invite']) && $_GET['invite'] === 'expired'): ?>
            <div class="form-error">To zaproszenie wygasło.</div>
        <?php elseif (!empty($_GET['invite']) && $_GET['invite'] === 'invalid'): ?>
            <div class="form-error">Nie znaleziono takiego zaproszenia.</div>
        <?php elseif (!empty($_GET['invite']) && $_GET['invite'] === 'wrong-account'): ?>
            <div class="form-error">To zaproszenie jest przypisane do innego adresu email.</div>
        <?php elseif (!empty($_GET['leave']) && $_GET['leave'] === 'success'): ?>
            <div class="form-success">Opuściłeś gospodarstwo domowe.</div>
        <?php elseif (!empty($_GET['delete']) && $_GET['delete'] === 'success'): ?>
            <div class="form-success">Gospodarstwo domowe zostało usunięte.</div>
        <?php endif; ?>

        <?php if (!empty($households)): ?>
            <div class="households-grid">
                <?php foreach ($households as $household): ?>
                    <a class="auth-card household-card" href="<?= url('households/show?id=' . (int) $household['id']) ?>">
                        <div class="household-card-top">
                            <span class="household-card-badge">Gospodarstwo</span>
                            <h3><?= htmlspecialchars($household['name']) ?></h3>
                        </div>
                        <div class="household-card-meta">
                            <div>
                                <span>Utworzone</span>
                                <strong><?= htmlspecialchars($household['created_at'] ?? '') ?></strong>
                            </div>
                            <div>
                                <span>Członkowie</span>
                                <strong><?= (int) ($household['member_count'] ?? 0) ?></strong>
                            </div>
                        </div>
                        <span class="household-card-link">Otwórz rozliczenie</span>
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
