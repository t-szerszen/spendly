<?php
/**
 * Widok: Panel główny
 *
 * Dashboard jest szybkim startem aplikacji. Pokazuje aktualny miesiąc,
 * szybkie dodawanie, skróty do modułów i ostatnie transakcje.
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

    <main class="auth-section dashboard-section">
        <div class="container dashboard-container">
            <section class="dashboard-hero">
                <div>
                    <p class="dashboard-eyebrow">Panel główny</p>
                    <h1 class="dashboard-title">Cześć, <?= htmlspecialchars($_SESSION['first_name']) ?>.</h1>
                    <p class="dashboard-subtitle">
                        Dodaj szybko transakcję, sprawdź bieżący miesiąc albo przejdź do portfela.
                    </p>
                </div>

                <a href="<?= url('wallet') ?>" class="btn-primary dashboard-hero-button">Otwórz portfel</a>
            </section>

            <div class="stats-grid">
                <div class="auth-card stat-card stat-balance">
                    <h4>Bilans miesiąca</h4>
                    <h2 class="stat-amount stat-balance-amount"><?= number_format($balance, 2) ?> zł</h2>
                    <p>Przychody minus wydatki w <?= htmlspecialchars($data['selectedMonth']) ?>.</p>
                </div>

                <div class="auth-card stat-card stat-income">
                    <h4>Przychody</h4>
                    <h2 class="stat-amount stat-income-amount"><?= number_format($totalIncome, 2) ?> zł</h2>
                    <p>Suma wpływów w aktualnym miesiącu.</p>
                </div>

                <div class="auth-card stat-card stat-expense">
                    <h4>Wydatki</h4>
                    <h2 class="stat-amount stat-expense-amount"><?= number_format($totalExpense, 2) ?> zł</h2>
                    <p>Suma kosztów w aktualnym miesiącu.</p>
                </div>
            </div>

            <?php include comp('quickAdd.php'); ?>

            <section class="dashboard-shortcuts">
                <div class="dashboard-section-heading">
                    <p class="dashboard-eyebrow">Szybkie akcje</p>
                    <h2>Co chcesz zrobić?</h2>
                </div>

                <div class="dashboard-shortcuts-grid">
                    <a href="<?= url('wallet') ?>" class="auth-card dashboard-shortcut-card">
                        <span>Portfel</span>
                        <strong>Dodawanie i miesiące</strong>
                        <p>Pełny widok transakcji dla wybranego miesiąca.</p>
                    </a>

                    <a href="<?= url('transactions') ?>" class="auth-card dashboard-shortcut-card">
                        <span>Transakcje</span>
                        <strong>Historia operacji</strong>
                        <p>Lista wszystkich zapisanych przychodów i wydatków.</p>
                    </a>

                    <a href="<?= url('summary') ?>" class="auth-card dashboard-shortcut-card">
                        <span>Podsumowanie</span>
                        <strong>Statystyki</strong>
                        <p>Sprawdź większy obraz swoich finansów.</p>
                    </a>

                    <a href="<?= url('households') ?>" class="auth-card dashboard-shortcut-card">
                        <span>Gospodarstwo</span>
                        <strong>Wspólne koszty</strong>
                        <p>Rozliczaj wydatki domowe z innymi osobami.</p>
                    </a>
                </div>
            </section>

            <div class="dashboard-info-grid">
                <div class="auth-card dashboard-card dashboard-info-card">
                    <h3>Transakcje w tym miesiącu</h3>
                    <strong><?= (int) $data['monthTransactionsCount'] ?></strong>
                    <p>Tyle operacji masz zapisanych w aktualnym miesiącu.</p>
                </div>

                <div class="auth-card dashboard-card dashboard-info-card">
                    <h3>Udział w gospodarstwach</h3>
                    <strong><?= number_format($data['householdMonthlyCost'] ?? 0, 2) ?> zł</strong>
                    <p>Twoja część wspólnych kosztów w aktualnym miesiącu.</p>
                </div>
            </div>

            <div class="auth-card dashboard-card recent-transactions-card">
                <div class="dashboard-card-header">
                    <div>
                        <h3>Ostatnie transakcje</h3>
                        <p>Najnowsze operacje z całej historii.</p>
                    </div>
                    <a href="<?= url('wallet') ?>" class="dashboard-text-link">Zobacz portfel</a>
                </div>

                <?php if (!empty($data['recentTransactions'])): ?>
                    <table class="recent-transactions-table">
                        <thead>
                            <tr>
                                <th class="text-left">Data</th>
                                <th class="text-left">Kategoria</th>
                                <th class="text-left">Opis</th>
                                <th class="text-right">Kwota</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['recentTransactions'] as $t): ?>
                                <tr>
                                    <td><?= htmlspecialchars($t['date']) ?></td>
                                    <td><?= htmlspecialchars($t['category_name']) ?></td>
                                    <td class="desc-cell"><?= htmlspecialchars($t['description']) ?></td>
                                    <td class="amount-cell <?= $t['type'] === 'expense' ? 'amount-expense' : 'amount-income' ?>">
                                        <?= $t['type'] === 'expense' ? '-' : '+' ?> <?= number_format($t['amount'], 2) ?> zł
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-recent-transactions">Brak zarejestrowanych transakcji.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>

</html>
