<?php
$error = $error ?? null;
?>
<!DOCTYPE html>
<html lang="pl">
<?php include comp('head.php'); ?>
<body>
<?php include comp('navDashboard.php'); ?>

<main class="auth-section shared_budgets-section">
    <div class="container shared_budgets-container narrow">
        <div class="auth-card shared_budgets-create-card">
            <div class="sharedBudget-create-copy">
                <p class="sharedBudget-eyebrow">Nowy budżet</p>
                <h1>Nowy wspólny budżet</h1>
                <p>Nadaj nazwę wspólnej przestrzeni, do której później zaprosisz członków i ustawisz udziały.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="form-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="<?= url('shared_budgets/store') ?>" method="POST" class="auth-form shared_budgets-form shared_budgets-create-form">
                <div class="sharedBudget-create-field">
                    <label for="sharedBudget-name" class="sharedBudget-field-label">Nazwa wspólnego budżetu</label>
                    <input id="sharedBudget-name" type="text" name="name" placeholder="Np. Mieszkanie na Piastów" required class="auth-input">
                </div>
                <div class="sharedBudget-create-actions">
                    <button type="submit" class="btn-primary">Utwórz wspólny budżet</button>
                    <a href="<?= url('shared_budgets') ?>" class="btn-secondary">Anuluj</a>
                </div>
            </form>
        </div>
    </div>
</main>
</body>
</html>
