<?php

/**
 * Widok: Panel główny (Dashboard)
 * 
 * Główny widok dla zalogowanego użytkownika. Zawiera podsumowanie finansów 
 * (stan konta, wpływy, wydatki) pobierane z bazy danych, formularz szybkiego 
 * dodawania nowej transakcji oraz listę ostatnich operacji finansowych.
 */

$totalExpense = $data['stats']['totalExpense'];
$totalIncome = $data['stats']['totalIncome'];
$balance = $data['stats']['balance'];
?>
<!DOCTYPE html>
<html lang="pl">

<!-- Head -->
<?php include comp('head.php'); ?>

<body>

    <!-- Nawigacja -->
    <?php include comp('navDashboard.php'); ?>

    <main class="auth-section dashboard-section">
        <div class="container dashboard-container">
            <form action="<?= url('dashboard'); ?>" method="GET">
                <p> Wybierz miesiąc </p>
                <input type="month" name="month" value="<?= $data['selectedMonth']?>">
                <button type="submit">Załaduj dane z tego miesiąca</button>
            </form>
            <h1 class="dashboard-title">Witaj w Spendly, <?= $_SESSION['first_name'] ?>!</h1>

            <div class="stats-grid">
                <div class="auth-card stat-card stat-balance">
                    <h4>Stan konta</h4>
                    <h2 class="stat-amount stat-balance-amount"><?= number_format($balance, 2) ?> zł</h2>
                </div>
                <div class="auth-card stat-card stat-income">
                    <h4>Wpływy</h4>
                    <h2 class="stat-amount stat-income-amount"><?= number_format($totalIncome, 2) ?> zł</h2>
                </div>
                <div class="auth-card stat-card stat-expense">
                    <h4>Wydatki</h4>
                    <h2 class="stat-amount stat-expense-amount"><?= number_format($totalExpense, 2) ?> zł</h2>
                </div>
            </div>

            <div class="auth-card household-share-card">
                <h3>Gospodarstwa domowe</h3>
                <p>Twój udział w gospodarstwach domowych w tym miesiącu:</p>
                <strong class="household-share-value"><?= number_format($data['householdMonthlyCost'] ?? 0, 2) ?> zł</strong>
            </div>

            <?php include comp('quickAdd.php'); ?>
            <div class="auth-card dashboard-card recent-transactions-card">
                <h3>Ostatnie transakcje</h3>

                <?php if (!empty($data['recentTransactions'])): ?>
                    <table class="recent-transactions-table">
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
                            <?php foreach ($data['recentTransactions'] as $t): ?>
                                <tr>
                                    <td><?= $t['date'] ?></td>
                                    <td><?= htmlspecialchars($t['category_name']) ?></td>
                                    <td class="desc-cell"><?= htmlspecialchars($t['description']) ?></td>
                                    <td
                                        class="amount-cell <?= $t['type'] === 'expense' ? 'amount-expense' : 'amount-income' ?>">
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
                    <p class="no-recent-transactions">Brak zarejestrowanych transakcji.</p>
                <?php endif; ?>
            </div>
    </main>
</body>

</html>
