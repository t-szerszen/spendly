<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title><?= $data['title'] ?></title>
    <link rel="stylesheet" href="<?= url('styles/style.css') ?>">
</head>
<body>
    <?php include 'components/navDashboard.php'; ?>

    <main class="auth-section" style="align-items: flex-start; padding-top: 2rem;">
        <div class="container" style="max-width: 1100px; margin: 0 auto; width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h1 style="color: var(--color-blue);">Historia transakcji</h1>
                <a href="<?= url('dashboard') ?>" class="btn-secondary"> + Dodaj nową</a>
            </div>

            <div class="auth-card" style="max-width: 100%; text-align: left;">
                <?php if (!empty($data['transactions'])): ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--glass-border); color: var(--color-text-mutated);">
                                <th style="padding: 1rem; text-align: left;">Data</th>
                                <th style="padding: 1rem; text-align: left;">Kategoria</th>
                                <th style="padding: 1rem; text-align: left;">Opis</th>
                                <th style="padding: 1rem; text-align: right;">Kwota</th>
                                <th style="padding: 1rem; text-align: right;">Akcja</th> </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['transactions'] as $t): ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'" onmouseout="this.style.background='none'">
                                    <td style="padding: 1rem;"><?= $t['date'] ?></td>
                                    <td style="padding: 1rem;">
                                        <span style="background: var(--glass-border); padding: 0.3rem 0.6rem; border-radius: 6px; font-size: 0.85rem;">
                                            <?= htmlspecialchars($t['category_name']) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 1rem; color: var(--color-text-mutated);"><?= htmlspecialchars($t['description']) ?></td>
                                    <td style="padding: 1rem; text-align: right; font-weight: bold; color: <?= $t['type'] === 'expense' ? '#ff4d4d' : 'var(--color-green)' ?>;">
                                        <?= $t['type'] === 'expense' ? '-' : '+' ?> <?= number_format($t['amount'], 2) ?> zł
                                    </td>
                                    <td style="padding: 1rem; text-align: right;">
                                        <form action="<?= url('transaction/delete') ?>" method="POST" style="margin: 0; display: inline;">
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
                    <div style="text-align: center; padding: 3rem;">
                        <p style="color: var(--color-text-mutated);">Nie masz jeszcze żadnych transakcji.</p>
                        <br>
                        <a href="<?= url('dashboard') ?>" class="btn-primary">Dodaj pierwszą</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>