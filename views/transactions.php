<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title><?= $data['title'] ?></title>
    <link rel="stylesheet" href="<?= url('styles/style.css') ?>">
</head>
<body>
    <?php include 'components/navDashboard.php'; ?>

    <main class="auth-section transactions-section">
        <div class="container transactions-container">
            <div class="transactions-header">
                <h1 class="transactions-title">Historia transakcji</h1>
                <a href="<?= url('dashboard') ?>" class="btn-secondary"> + Dodaj nową</a>
            </div>

            <div class="auth-card transactions-card">
                <?php if (!empty($data['transactions'])): ?>
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th class="text-left">Data</th>
                                <th class="text-left">Kategoria</th>
                                <th class="text-left">Opis</th>
                                <th class="text-right">Kwota</th>
                                <th class="text-right">Akcja</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['transactions'] as $t): ?>
                                <tr>
                                    <td><?= $t['date'] ?></td>
                                    <td>
                                        <span class="category-badge">
                                            <?= htmlspecialchars($t['category_name']) ?>
                                        </span>
                                    </td>
                                    <td class="desc-cell"><?= htmlspecialchars($t['description']) ?></td>
                                    <td class="amount-cell <?= $t['type'] === 'expense' ? 'amount-expense' : 'amount-income' ?>">
                                        <?= $t['type'] === 'expense' ? '-' : '+' ?> <?= number_format($t['amount'], 2) ?> zł
                                    </td>
                                    <td class="action-cell">
                                        <form action="<?= url('transaction/delete') ?>" method="POST" class="delete-form">
                                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                            <button type="submit" class="btn-delete">
                                                Usuń
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-transactions">
                        <p class="no-transactions-text">Nie masz jeszcze żadnych transakcji.</p>
                        <br>
                        <a href="<?= url('dashboard') ?>" class="btn-primary">Dodaj pierwszą</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>