<?php
/**
 * Widok: Portfel
 *
 * Tutaj jest pełna praca z miesiącem: wybór okresu, quick add,
 * podsumowanie i lista transakcji z wybranego miesiąca.
 */

$totalExpense = $data['stats']['totalExpense'];
$totalIncome = $data['stats']['totalIncome'];
$balance = $data['stats']['balance'];
?>
<!DOCTYPE html>
<html lang="pl">

<?php include comp('head.php'); ?>

<body>
    <?php include comp('navDashboard.php'); ?>

    <main class="auth-section dashboard-section wallet-section">
        <div class="container dashboard-container">
            <section class="dashboard-hero wallet-hero">
                <div>
                    <p class="dashboard-eyebrow">Portfel</p>
                    <h1 class="dashboard-title">Wszystkie transakcje zaczynają się tutaj</h1>
                    <p class="dashboard-subtitle">
                        Dodawaj prywatne wydatki, przychody i koszty wspólnego budżetu z jednego miejsca.
                    </p>
                </div>

                <form action="<?= url('wallet') ?>" method="GET" class="wallet-month-form">
                    <label for="wallet-month">Wybrany miesiąc</label>
                    <div class="wallet-month-controls">
                        <input id="wallet-month" type="month" name="month" value="<?= htmlspecialchars($data['selectedMonth']) ?>" class="auth-input">
                        <button type="submit" class="btn-primary">Załaduj</button>
                    </div>
                </form>
            </section>

            <div class="stats-grid">
                <div class="auth-card stat-card stat-balance">
                    <h4>Bilans miesiąca</h4>
                    <h2 class="stat-amount stat-balance-amount"><?= number_format($balance, 2) ?> zł</h2>
                </div>

                <div class="auth-card stat-card stat-income">
                    <h4>Przychody</h4>
                    <h2 class="stat-amount stat-income-amount"><?= number_format($totalIncome, 2) ?> zł</h2>
                </div>

                <div class="auth-card stat-card stat-expense">
                    <h4>Wydatki</h4>
                    <h2 class="stat-amount stat-expense-amount"><?= number_format($totalExpense, 2) ?> zł</h2>
                </div>
            </div>

            <div class="auth-card dashboard-card dashboard-info-card wallet-sharedBudget-card">
                <h3>Twój udział we wspólnych budżetach</h3>
                <strong><?= number_format($data['sharedBudgetMonthlyCost'] ?? 0, 2) ?> zł</strong>
                <p>Twój udział we wspólnych kosztach dla wybranego miesiąca.</p>
            </div>

            <?php if (!empty($_GET['transaction']) && $_GET['transaction'] === 'invalid'): ?>
                <div class="form-error">Nie udało się dodać transakcji. Sprawdź kwotę, datę, kategorię i typ wpisu.</div>
            <?php elseif (!empty($_GET['transaction']) && $_GET['transaction'] === 'forbidden-budget'): ?>
                <div class="form-error">Nie możesz przypisać wydatku do budżetu, do którego nie należysz.</div>
            <?php elseif (!empty($_GET['transaction']) && $_GET['transaction'] === 'added'): ?>
                <div class="form-success">Transakcja została dodana.</div>
            <?php endif; ?>

            <?php include comp('quickAdd.php'); ?>

            <div class="auth-card dashboard-card recent-transactions-card">
                <div class="dashboard-card-header">
                    <div>
                        <h3>Transakcje w wybranym miesiącu</h3>
                        <p>Prywatne wpisy i koszty przypisane do wspólnych budżetów dla <?= htmlspecialchars($data['selectedMonth']) ?>.</p>
                    </div>
                    <a href="<?= url('transactions') ?>" class="dashboard-text-link">Pełna historia</a>
                </div>

                <?php if (!empty($data['transactions'])): ?>
                    <table class="recent-transactions-table">
                        <thead>
                            <tr>
                                <th class="text-left">Data</th>
                                <th class="text-left">Kategoria</th>
                                <th class="text-left">Budżet</th>
                                <th class="text-left">Opis</th>
                                <th class="text-right">Kwota</th>
                                <th class="text-right">Akcja</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['transactions'] as $t): ?>
                                <tr>
                                    <td><?= htmlspecialchars($t['date']) ?></td>
                                    <td><?= htmlspecialchars($t['category_name']) ?></td>
                                    <td>
                                        <?php if (!empty($t['shared_budget_name'])): ?>
                                            <span class="transaction-budget-badge"><?= htmlspecialchars($t['shared_budget_name']) ?></span>
                                        <?php else: ?>
                                            <span class="transaction-budget-private">Prywatne</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="desc-cell"><?= htmlspecialchars($t['description']) ?></td>
                                    <td class="amount-cell <?= $t['type'] === 'expense' ? 'amount-expense' : 'amount-income' ?>">
                                        <?= $t['type'] === 'expense' ? '-' : '+' ?> <?= number_format($t['amount'], 2) ?> zł
                                    </td>
                                    <td class="action-cell">
                                        <form action="<?= url('transaction/delete') ?>" method="POST" class="delete-form">
                                            <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                                            <input type="hidden" name="redirect_to" value="wallet">
                                            <button type="submit" class="btn-delete">Usuń</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-recent-transactions">Brak transakcji w wybranym miesiącu.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>

</html>
