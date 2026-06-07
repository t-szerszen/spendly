<?php
$sharedBudgets = $data['sharedBudgets'] ?? [];
$quickAddId = 'quick-add-' . uniqid();
?>
<div class="auth-card dashboard-card quick-add-card">
    <div class="quick-add-head">
        <div>
            <p class="dashboard-eyebrow">Centrum zapisu</p>
            <h3>Szybkie dodawanie</h3>
        </div>
        <span class="quick-add-status">Portfel zapisuje wszystko</span>
    </div>
    <form action="<?= url($data['quickAddPath']) ?>" method="POST" class="auth-form quick-add-form">
        <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($data['quickAddRedirect'] ?? 'dashboard') ?>">

        <fieldset class="quick-add-scope" aria-label="Zakres transakcji">
            <input id="<?= $quickAddId ?>-private" type="radio" name="scope" value="private" checked>
            <label for="<?= $quickAddId ?>-private">Prywatne</label>
            <input id="<?= $quickAddId ?>-shared" type="radio" name="scope" value="shared" <?= empty($sharedBudgets) ? 'disabled' : '' ?>>
            <label for="<?= $quickAddId ?>-shared">Wspólny budżet</label>
        </fieldset>

        <input type="hidden" name="transaction_mode" value="single">
        <label class="quick-add-repeat-toggle" for="<?= $quickAddId ?>-recurring">
            <input id="<?= $quickAddId ?>-recurring" type="checkbox" name="transaction_mode" value="recurring">
            <span class="quick-add-repeat-switch" aria-hidden="true"></span>
            <span>
                <strong>Płatność cykliczna</strong>
            </span>
        </label>

        <div class="quick-add-grid">
            <label>
                <span class="quick-add-date-label">Data</span>
                <input value="<?= htmlspecialchars($_SESSION['last_added_date'] ?? date('Y-m-d')) ?>" type="date" name="date" required class="auth-input">
            </label>

            <label>
                <span>Kwota</span>
                <input type="number" step="0.01" name="amount" placeholder="0,00" required class="auth-input">
            </label>

            <label>
                <span>Typ</span>
                <select name="type" class="auth-input quick-add-type">
                    <option value="expense">Wydatek</option>
                    <option value="income">Przychód</option>
                </select>
            </label>

            <label>
                <span>Kategoria</span>
                <select name="category_id" class="auth-input quick-add-category" required>
                    <option value="" disabled selected>Wybierz kategorię</option>
                    <?php foreach ($data['categories'] as $cat): ?>
                        <option
                            value="<?= (int) $cat['id'] ?>"
                            data-type="<?= htmlspecialchars($cat['type'] ?? 'expense') ?>"
                        >
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="quick-add-budget-wrap">
                <span>Budżet</span>
                <select name="shared_budget_id" class="auth-input quick-add-budget">
                    <option value="">Wybierz wspólny budżet</option>
                    <?php foreach ($sharedBudgets as $budget): ?>
                        <option value="<?= (int) $budget['id'] ?>"><?= htmlspecialchars($budget['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <small>Wydatek zapisze się w portfelu i trafi do miesięcznego rozliczenia.</small>
            </label>
            <label class="quick-add-description">
                <span>Opis</span>
                <input type="text" name="description" placeholder="Opcjonalnie" class="auth-input">
            </label>
            <label class="quick-add-recurring-field" style="display: none;">
                <span>Powtarzaj</span>
                <select name="frequency" class="auth-input quick-add-frequency" disabled>
                    <option value="weekly">Tygodniowo</option>
                    <option value="monthly" selected>Miesięcznie</option>
                    <option value="quarterly">Kwartalnie</option>
                    <option value="yearly">Rocznie</option>
                </select>
            </label>

            <label class="quick-add-recurring-field" style="display: none;">
                <span>Data końcowa</span>
                <input type="date" name="end_date" class="auth-input quick-add-end-date" disabled>
            </label>
        </div>

        <?php if (empty($sharedBudgets)): ?>
            <p class="quick-add-hint">Nie masz jeszcze wspólnego budżetu. Prywatne transakcje możesz dodawać od razu.</p>
        <?php else: ?>
            <p class="quick-add-hint quick-add-shared-copy">Wspólny budżet jest dostępny tylko dla wydatków. Przychody zawsze pozostają prywatne.</p>
        <?php endif; ?>

        <button type="submit" class="btn-primary quick-add-submit">Dodaj transakcję</button>
    </form>
</div>

<script src="<?= url('scripts/quickAddForm.js') ?>"></script>
