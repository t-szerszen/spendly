<?php
/**
 * Widok: Lista wspólnych budżetów
 *
 * Prezentuje budżety dostępne dla zalogowanego użytkownika,
 * komunikaty po akcjach zaproszeń i członkostwa oraz przejście do tworzenia nowego budżetu.
 */

$shared_budgets = $data['shared_budgets'];
$sharedBudgetCount = count($shared_budgets);
?>
<!DOCTYPE html>
<html lang="pl">
<?php include comp('head.php'); ?>
<body>
<?php include comp('navDashboard.php'); ?>

<main class="auth-section shared_budgets-section">
    <div class="container shared_budgets-container">
        <!-- Nagłówek listy oraz główna akcja utworzenia nowego budżetu. -->
        <div class="shared_budgets-header">
            <div>
                <h1 class="dashboard-title">Wspólne budżety</h1>
                <p class="shared_budgets-subtitle">Lista wspólnych budżetów, do których masz dostęp.</p>
            </div>
            <a href="<?= url('shared_budgets/create') ?>" class="btn-primary">+ Nowy budżet</a>
        </div>

        <!-- Podsumowanie liczby budżetów dostępnych dla bieżącego użytkownika. -->
        <section class="auth-card shared_budgets-list-hero">
            <div class="shared_budgets-list-copy">
                <p class="sharedBudget-eyebrow">Twoje budżety</p>
                <h2>Wspólne budżety w jednym miejscu</h2>
                <p>Przeglądaj aktywne budżety, ustawiaj udziały i sprawdzaj, kto komu powinien oddać pieniądze.</p>
            </div>
            <div class="shared_budgets-list-hero-meta">
                <div class="shared_budgets-list-inline-stat">
                    <span class="sharedBudget-kpi-label">Aktywne budżety</span>
                    <strong><?= $sharedBudgetCount ?></strong>
                </div>
                <p><?= $sharedBudgetCount === 1 ? 'Masz obecnie dostęp do 1 wspólnego budżetu.' : 'Masz obecnie dostęp do ' . $sharedBudgetCount . ' wspólnych budżetów.' ?></p>
            </div>
        </section>

        <!-- Komunikaty po akcjach wykonywanych poza widokiem szczegółowym budżetu. -->
        <?php if (!empty($_GET['invite']) && $_GET['invite'] === 'expired'): ?>
            <div class="form-error">To zaproszenie wygasło.</div>
        <?php elseif (!empty($_GET['invite']) && $_GET['invite'] === 'invalid'): ?>
            <div class="form-error">Nie znaleziono takiego zaproszenia.</div>
        <?php elseif (!empty($_GET['invite']) && $_GET['invite'] === 'wrong-account'): ?>
            <div class="form-error">To zaproszenie jest przypisane do innego adresu email.</div>
        <?php elseif (!empty($_GET['leave']) && $_GET['leave'] === 'success'): ?>
            <div class="form-success">Opuściłeś wspólny budżet.</div>
        <?php elseif (!empty($_GET['delete']) && $_GET['delete'] === 'success'): ?>
            <div class="form-success">Wspólny budżet został usunięty.</div>
        <?php endif; ?>

        <!-- Karty prowadzące do szczegółów poszczególnych wspólnych budżetów. -->
        <?php if (!empty($shared_budgets)): ?>
            <div class="shared_budgets-grid">
                <?php foreach ($shared_budgets as $sharedBudget): ?>
                    <a class="auth-card sharedBudget-card" href="<?= url('shared_budgets/show?id=' . (int) $sharedBudget['id']) ?>">
                        <div class="sharedBudget-card-top">
                            <span class="sharedBudget-card-badge">Wspólny budżet</span>
                            <h3><?= htmlspecialchars($sharedBudget['name']) ?></h3>
                        </div>
                        <div class="sharedBudget-card-meta">
                            <div>
                                <span>Utworzone</span>
                                <strong><?= htmlspecialchars($sharedBudget['created_at'] ?? '') ?></strong>
                            </div>
                            <div>
                                <span>Członkowie</span>
                                <strong><?= (int) ($sharedBudget['member_count'] ?? 0) ?></strong>
                            </div>
                        </div>
                        <span class="sharedBudget-card-link">Otwórz rozliczenie</span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Stan pusty zachęca do utworzenia pierwszego wspólnego budżetu. -->
            <div class="auth-card sharedBudget-empty">
                <p>Nie masz jeszcze żadnego wspólnego budżetu.</p>
                <a href="<?= url('shared_budgets/create') ?>" class="btn-primary">Utwórz pierwszy</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include comp('footer.php'); ?>
</body>
</html>
