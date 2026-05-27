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

        <div class="quick-add-grid">
            <label>
                <span>Data</span>
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
                <select name="category_id" class="auth-input" required>
                    <option value="" disabled selected>Wybierz kategorię</option>
                    <?php foreach ($data['categories'] as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
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
        </div>

        <?php if (empty($sharedBudgets)): ?>
            <p class="quick-add-hint">Nie masz jeszcze wspólnego budżetu. Prywatne transakcje możesz dodawać od razu.</p>
        <?php else: ?>
            <p class="quick-add-hint quick-add-shared-copy">Wspólny budżet jest dostępny tylko dla wydatków. Przychody zawsze pozostają prywatne.</p>
        <?php endif; ?>

        <button type="submit" class="btn-primary quick-add-submit">Dodaj transakcję</button>
    </form>
</div>

<script>
document.querySelectorAll('.quick-add-form').forEach((form) => {
    const typeSelect = form.querySelector('.quick-add-type');
    const budgetSelect = form.querySelector('.quick-add-budget');
    const budgetWrap = form.querySelector('.quick-add-budget-wrap');
    const scopeFieldset = form.querySelector('.quick-add-scope');
    const scopeInputs = form.querySelectorAll('input[name="scope"]');
    const sharedInput = form.querySelector('input[name="scope"][value="shared"]');
    const sharedCopy = form.querySelector('.quick-add-shared-copy');

    if (!typeSelect || !budgetSelect || !budgetWrap) {
        return;
    }

    const syncBudgetState = () => {
        const isExpense = typeSelect.value === 'expense';
        const selectedScope = form.querySelector('input[name="scope"]:checked')?.value ?? 'private';
        const sharedAvailable = sharedInput && sharedInput.dataset.empty !== '1';
        const canUseShared = isExpense && sharedAvailable;
        const isShared = selectedScope === 'shared' && canUseShared;

        budgetWrap.hidden = !isShared;
        budgetSelect.disabled = !isShared;
        budgetSelect.required = isShared;

        if (sharedInput) {
            sharedInput.disabled = !isExpense || sharedInput.dataset.empty === '1';
        }

        if (scopeFieldset) {
            scopeFieldset.classList.toggle('is-income', !isExpense);
        }

        if (!isShared) {
            budgetSelect.value = '';
        }

        if (!isExpense && sharedInput) {
            form.querySelector('input[name="scope"][value="private"]').checked = true;
        }

        if (sharedCopy) {
            sharedCopy.hidden = !isShared;
        }
    };

    if (sharedInput && sharedInput.disabled) {
        sharedInput.dataset.empty = '1';
    }

    typeSelect.addEventListener('change', syncBudgetState);
    scopeInputs.forEach((input) => input.addEventListener('change', syncBudgetState));
    syncBudgetState();
});
</script>
