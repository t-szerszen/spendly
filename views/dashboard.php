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
            
            <?php
// Na początku widoku dashboard.php pobierzemy sumy (uproszczone dla przykładu)
$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];

// Liczymy sumę wydatków
$stmtExp = $db->prepare("SELECT SUM(amount) as total FROM transactions WHERE user_id = ? AND type = 'expense'");
$stmtExp->execute([$userId]);
$totalExpense = $stmtExp->fetch()['total'] ?? 0;

// Liczymy sumę przychodów
$stmtInc = $db->prepare("SELECT SUM(amount) as total FROM transactions WHERE user_id = ? AND type = 'income'");
$stmtInc->execute([$userId]);
$totalIncome = $stmtInc->fetch()['total'] ?? 0;

$balance = $totalIncome - $totalExpense;
?>

<div class="stats-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
    <div class="auth-card" style="border-left: 5px solid var(--color-blue);">
        <h4>Stan konta</h4>
        <h2 style="color: var(--color-blue);"><?= number_format($balance, 2) ?> zł</h2>
    </div>
    <div class="auth-card" style="border-left: 5px solid var(--color-green);">
        <h4>Wpływy</h4>
        <h2 style="color: var(--color-green);"><?= number_format($totalIncome, 2) ?> zł</h2>
    </div>
    <div class="auth-card" style="border-left: 5px solid #ff4d4d;">
        <h4>Wydatki</h4>
        <h2 style="color: #ff4d4d;"><?= number_format($totalExpense, 2) ?> zł</h2>
    </div>
</div>

<div class="auth-card" style="max-width: 100%; text-align: left;">
    <h3>Szybkie dodawanie</h3>
    <form action="<?= url('transaction/add') ?>" method="POST" class="auth-form" style="display: flex; flex-direction: row; gap: 1rem; flex-wrap: wrap;">
        <input type="number" step="0.01" name="amount" placeholder="Kwota" required class="auth-input" style="flex: 1;">
        
        <select name="type" class="auth-input" style="flex: 1;">
            <option value="expense">Wydatek</option>
            <option value="income">Przychód</option>
        </select>

        <select name="category_id" class="auth-input" style="flex: 1;" required>
            <option value="" disabled selected>Wybierz kategorię</option>
            <?php foreach ($data['categories'] as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
            <?php endforeach; ?>
        </select>

        <input type="text" name="description" placeholder="Opis (opcjonalnie)" class="auth-input" style="flex: 2;">
        
        <button type="submit" class="btn-primary">Dodaj</button>
    </form>
</div>
<div class="auth-card" style="max-width: 100%; text-align: left; margin-top: 2rem;">
    <h3>Ostatnie transakcje</h3>
    
    <?php if (!empty($data['recentTransactions'])): ?>
        <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
            <thead>
                <tr style="border-bottom: 1px solid var(--glass-border); color: var(--color-text-mutated);">
                    <th style="padding: 1rem; text-align: left;">Data</th>
                    <th style="padding: 1rem; text-align: left;">Kategoria</th>
                    <th style="padding: 1rem; text-align: left;">Opis</th>
                    <th style="padding: 1rem; text-align: right;">Kwota</th>
                    <th style="padding: 1rem; text-align: right;">Akcja</th> </tr>
            </thead>
            <tbody>
                <?php foreach ($data['recentTransactions'] as $t): ?>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <td style="padding: 1rem;"><?= $t['date'] ?></td>
                        <td style="padding: 1rem;"><?= htmlspecialchars($t['category_name']) ?></td>
                        <td style="padding: 1rem; color: var(--color-text-mutated);"><?= htmlspecialchars($t['description']) ?></td>
                        <td style="padding: 1rem; text-align: right; font-weight: bold; color: <?= $t['type'] === 'expense' ? '#ff4d4d' : 'var(--color-green)' ?>;">
                            <?= $t['type'] === 'expense' ? '-' : '+' ?> <?= number_format($t['amount'], 2) ?> zł
                        </td>
                        <td style="padding: 1rem; text-align: right;">
                            <form action="<?= url('transaction/delete') ?>" method="POST" style="margin: 0;">
                                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                <button type="submit" style="background: none; border: none; color: #ff4d4d; cursor: pointer; font-size: 0.85rem; text-decoration: underline; padding: 0;">
                                    Usuń
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="margin-top: 1rem; color: var(--color-text-mutated);">Brak zarejestrowanych transakcji.</p>
    <?php endif; ?>
</div>
    </main>
</body>
</html>