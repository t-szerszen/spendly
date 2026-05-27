<div class="auth-card dashboard-card">
    <h3>Szybkie dodawanie</h3>
    <form action="<?= url($data['quickAddPath']) ?>" method="POST" class="auth-form quick-add-form">
        <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($data['quickAddRedirect'] ?? 'dashboard') ?>">

        <input value="<?= htmlspecialchars($_SESSION['last_added_date'] ?? date('Y-m-d')) ?>" type="date" name="date" required 
            class="auth-input flex-1">
            
        <input type="number" step="0.01" name="amount" placeholder="Kwota" required
            class="auth-input flex-1">

        <select name="type" class="auth-input flex-1">
            <option value="expense">Wydatek</option>
            <option value="income">Przychód</option>
        </select>

        <select name="category_id" class="auth-input flex-1" required>
            <option value="" disabled selected>Wybierz kategorię</option>
            <?php foreach ($data['categories'] as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
            <?php endforeach; ?>
        </select>

        <input type="text" name="description" placeholder="Opis (opcjonalnie)" class="auth-input flex-2">

        <button type="submit" class="btn-primary">Dodaj</button>
    </form>
</div>
