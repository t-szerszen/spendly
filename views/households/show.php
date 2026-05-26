<?php
$household = $data['household'];
$members = $data['members'];
$expenses = $data['expenses'];
$monthlyBalance = $data['monthlyBalance'];
$categories = $data['categories'];
$selectedPeriod = $data['selectedPeriod'];
$error = $data['error'] ?? null;
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
                <h1 class="dashboard-title"><?= htmlspecialchars($household['name']) ?></h1>
                <p class="households-subtitle">Zarządzanie członkami, wydatkami i miesięcznym bilansem.</p>
            </div>
            <a href="<?= url('households') ?>" class="btn-secondary">Wróć do listy</a>
        </div>

        <?php if (!empty($_GET['created'])): ?>
            <div class="form-success">Gospodarstwo zostało utworzone.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['invite']) && $_GET['invite'] === 'sent'): ?>
            <div class="form-success">Zaproszenie zostało wysłane.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['invite']) && $_GET['invite'] === 'accepted'): ?>
            <div class="form-success">Zaproszenie zostało zaakceptowane.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['shares']) && $_GET['shares'] === 'updated'): ?>
            <div class="form-success">Udziały zostały zapisane.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['expense']) && $_GET['expense'] === 'added'): ?>
            <div class="form-success">Wydatek został zapisany.</div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="form-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="auth-card households-summary-card">
            <div class="household-summary-row">
                <div>
                    <h3>Miesięczne rozliczenie</h3>
                    <p>Okres: <?= htmlspecialchars($selectedPeriod) ?></p>
                </div>
                <form action="<?= url('households/show') ?>" method="GET" class="household-period-form">
                    <input type="hidden" name="id" value="<?= (int) $household['id'] ?>">
                    <input type="month" name="period" value="<?= htmlspecialchars($selectedPeriod) ?>" class="auth-input">
                    <button type="submit" class="btn-primary">Pokaż</button>
                </form>
            </div>

            <div class="household-balance-grid">
                <?php foreach ($monthlyBalance as $row): ?>
                    <div class="household-balance-card">
                        <strong><?= htmlspecialchars($row['name']) ?></strong>
                        <p>Udział: <?= number_format($row['share_percent'], 2) ?>%</p>
                        <p>Zapłacono: <?= number_format($row['paid'], 2) ?> zł</p>
                        <p>Powinno wyjść: <?= number_format($row['should_pay'], 2) ?> zł</p>
                        <p class="<?= $row['balance'] >= 0 ? 'text-positive' : 'text-negative' ?>">
                            Saldo: <?= number_format($row['balance'], 2) ?> zł
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="auth-card household-total-card">
                <h4>Suma wydatków w tym miesiącu</h4>
                <strong><?= number_format($data['totalMonthExpense'], 2) ?> zł</strong>
            </div>
        </div>

        <div class="households-two-column">
            <div class="auth-card households-panel">
                <h3>Udziały członków</h3>
                <form action="<?= url('households/update-shares') ?>" method="POST" class="households-shares-form">
                    <input type="hidden" name="household_id" value="<?= (int) $household['id'] ?>">
                    <input type="hidden" name="period" value="<?= htmlspecialchars($selectedPeriod) ?>">
                    <div class="households-members-list">
                        <?php foreach ($members as $member): ?>
                            <label class="household-member-row">
                                <span><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?> <small>(<?= htmlspecialchars($member['role']) ?>)</small></span>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    name="shares[<?= (int) $member['user_id'] ?>]"
                                    value="<?= htmlspecialchars($member['share_percent']) ?>"
                                    class="auth-input"
                                >
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="btn-primary">Zapisz udziały</button>
                </form>
            </div>

            <div class="auth-card households-panel">
                <h3>Zaproś użytkownika</h3>
                <form action="<?= url('households/invite') ?>" method="POST" class="auth-form households-form">
                    <input type="hidden" name="household_id" value="<?= (int) $household['id'] ?>">
                    <input type="hidden" name="period" value="<?= htmlspecialchars($selectedPeriod) ?>">
                    <input type="email" name="email" placeholder="Adres email" required class="auth-input">
                    <button type="submit" class="btn-primary">Wyślij zaproszenie</button>
                </form>

                <h3>Dodaj wydatek</h3>
                <form action="<?= url('households/store-expense') ?>" method="POST" class="auth-form households-form">
                    <input type="hidden" name="household_id" value="<?= (int) $household['id'] ?>">
                    <input type="hidden" name="period" value="<?= htmlspecialchars($selectedPeriod) ?>">
                    <input type="number" step="0.01" min="0.01" name="amount" placeholder="Kwota" required class="auth-input">
                    <input type="date" name="expense_date" value="<?= date('Y-m-d') ?>" required class="auth-input">

                    <select name="paid_by_user_id" class="auth-input" required>
                        <option value="" disabled selected>Kto zapłacił?</option>
                        <?php foreach ($members as $member): ?>
                            <option value="<?= (int) $member['user_id'] ?>">
                                <?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="category_id" class="auth-input" required>
                        <option value="" disabled selected>Wybierz kategorię</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <input type="text" name="description" placeholder="Opis (opcjonalnie)" class="auth-input">
                    <button type="submit" class="btn-primary">Zapisz wydatek</button>
                </form>
            </div>
        </div>

        <div class="auth-card households-panel">
            <h3>Wydatki w tym miesiącu</h3>

            <?php if (!empty($expenses)): ?>
                <table class="recent-transactions-table">
                    <thead>
                        <tr>
                            <th class="text-left">Data</th>
                            <th class="text-left">Kto zapłacił</th>
                            <th class="text-left">Kategoria</th>
                            <th class="text-left">Opis</th>
                            <th class="text-right">Kwota</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $expense): ?>
                            <tr>
                                <td><?= htmlspecialchars($expense['expense_date']) ?></td>
                                <td><?= htmlspecialchars($expense['paid_by_first_name'] . ' ' . $expense['paid_by_last_name']) ?></td>
                                <td><?= htmlspecialchars($expense['category_name']) ?></td>
                                <td><?= htmlspecialchars($expense['description'] ?? '') ?></td>
                                <td class="text-right"><?= number_format($expense['amount'], 2) ?> zł</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Brak wydatków w tym okresie.</p>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>
