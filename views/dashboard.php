<?php
/**
 * Widok: Panel główny
 *
 * Prezentuje podsumowanie bieżącego miesiąca, formularz szybkiego dodawania,
 * skróty do głównych modułów aplikacji oraz podgląd ostatnich transakcji.
 */

$totalExpense = $data['stats']['totalExpense'];
$totalIncome = $data['stats']['totalIncome'];
$balance = $data['stats']['balance'];
$pageStyles = ['styles/pages/dashboard.css'];
?>
<!DOCTYPE html>
<html lang="pl">

<?php include comp('head.php'); ?>

<body>
    <?php include comp('navDashboard.php'); ?>

    <main class="auth-section app-section">
        <div class="container app-container">
            <section class="app-hero">
                <div>
                    <p class="app-eyebrow">Panel główny</p>
                    <h1 class="app-title">Cześć, <?= htmlspecialchars($_SESSION['first_name']) ?>.</h1>
                    <p class="app-subtitle">
                        Portfel jest miejscem zapisu wszystkich transakcji. Wspólne budżety służą do rozliczania udziałów i spłat.
                    </p>
                </div>

                <div class="app-hero-actions">
                    <a href="<?= url('wallet') ?>" class="btn-primary dashboard-hero-button">Otwórz portfel</a>
                    <a href="<?= url('shared_budgets') ?>" class="app-text-link">Rozliczenia</a>
                </div>
            </section>

            <!-- Podstawowe statystyki finansowe dla bieżącego miesiąca. -->
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

            <!-- Komunikaty zwrotne po próbie dodania transakcji z formularza szybkiego dodawania. -->
            <?php if (!empty($_GET['transaction']) && $_GET['transaction'] === 'invalid'): ?>
                <div class="form-error">Nie udało się dodać transakcji. Wspólny budżet można przypisać tylko do wydatku.</div>
            <?php elseif (!empty($_GET['transaction']) && $_GET['transaction'] === 'forbidden-budget'): ?>
                <div class="form-error">Nie możesz przypisać wydatku do budżetu, do którego nie należysz.</div>
            <?php elseif (!empty($_GET['transaction']) && $_GET['transaction'] === 'added'): ?>
                <div class="form-success">Transakcja została dodana.</div>
            <?php endif; ?>

            <!-- Krótki opis przepływu pracy między portfelem i modułem wspólnych budżetów. -->
            <section class="dashboard-flow">
                <div>
                    <span>1</span>
                    <strong>Dodaj w portfelu</strong>
                    <p>Prywatny wydatek albo koszt wspólnego budżetu zapisujesz w jednym formularzu.</p>
                </div>
                <div>
                    <span>2</span>
                    <strong>Przypisz budżet</strong>
                    <p>Wspólny koszt automatycznie trafia do rozliczenia wybranego miesiąca.</p>
                </div>
                <div>
                    <span>3</span>
                    <strong>Wyrównaj saldo</strong>
                    <p>Moduł wspólnego budżetu pokazuje, kto komu powinien przelać pieniądze.</p>
                </div>
            </section>

            <!-- Współdzielony komponent formularza szybkiego dodawania transakcji. -->
            <?php include comp('quickAdd.php'); ?>

            <!-- Skróty do najczęściej używanych modułów aplikacji. -->
            <section class="dashboard-shortcuts">
                <div class="app-section-heading">
                    <p class="app-eyebrow">Nawigacja</p>
                    <h2 class="app-section-title">Najczęstsze ścieżki</h2>
                </div>

                <div class="dashboard-shortcuts-grid">
                    <a href="<?= url('wallet') ?>" class="auth-card dashboard-shortcut-card">
                        <span>Portfel</span>
                        <strong>Dodawanie transakcji</strong>
                        <p>Centralne miejsce dla kosztów prywatnych i wspólnych.</p>
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

                    <a href="<?= url('shared_budgets') ?>" class="auth-card dashboard-shortcut-card">
                        <span>Wspólny budżet</span>
                        <strong>Rozliczenia</strong>
                        <p>Sprawdzaj salda, udziały i proponowane spłaty między członkami.</p>
                    </a>
                </div>
            </section>

            <!-- Uzupełniające informacje o aktywności użytkownika w bieżącym miesiącu. -->
            <div class="dashboard-info-grid">
                <div class="auth-card app-card app-info-card">
                    <h3>Transakcje w tym miesiącu</h3>
                    <strong><?= (int) $data['monthTransactionsCount'] ?></strong>
                    <p>Tyle operacji masz zapisanych w aktualnym miesiącu.</p>
                </div>

                <div class="auth-card app-card app-info-card">
                    <h3>Udział we wspólnych budżetach</h3>
                    <strong><?= number_format($data['sharedBudgetMonthlyCost'] ?? 0, 2) ?> zł</strong>
                    <p>Twoja część wspólnych kosztów w aktualnym miesiącu.</p>
                </div>
            </div>

            <div class="auth-card app-card recent-transactions-card">
                <div class="app-card-header">
                    <div>
                        <h3 class="app-card-header-title">Ostatnie transakcje</h3>
                        <p class="app-card-header-copy">Najnowsze operacje z całej historii.</p>
                    </div>
                    <a href="<?= url('wallet') ?>" class="app-text-link">Zobacz portfel</a>
                </div>

                <?php if (!empty($data['recentTransactions'])): ?>
                    <table class="recent-transactions-table">
                        <thead>
                            <tr>
                                <th class="text-left">Data</th>
                                <th class="text-left">Kategoria</th>
                                <th class="text-left">Budżet</th>
                                <th class="text-left">Opis</th>
                                <th class="text-right">Kwota</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['recentTransactions'] as $t): ?>
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

    <?php include comp('footer.php'); ?>
</body>

</html>
