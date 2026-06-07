<?php
/**
 * Widok: Historia transakcji
 * 
 * Prezentuje definicje płatności cyklicznych oraz pełną historię transakcji
 * zalogowanego użytkownika. Udostępnia akcje usuwania wpisów i przejście do portfela.
 */
?>
<!DOCTYPE html>
<html lang="pl">

<?php include comp('head.php'); ?>

<body>
    <?php include comp('navDashboard.php'); ?>

    <main class="auth-section transactions-section">
        <div class="container transactions-container">
            <!-- Nagłówek historii oraz link do formularza dodawania transakcji w portfelu. -->
            <div class="transactions-header">
                <h1 class="transactions-title">Historia transakcji</h1>
                <a href="<?= url('wallet') ?>" class="btn-secondary"> + Dodaj nową</a>
            </div>

            <div class="transactions-stack">
                <!-- Sekcja definicji płatności cyklicznych zapisanych przez użytkownika. -->
                <div class="auth-card transactions-card">
                    <div class="transactions-section-head">
                        <h2>Transakcje cykliczne</h2>
                    </div>
                    <?php if (!empty($data['recurringTransactions'])): ?>
                        <!-- Tabela pokazuje konfigurację płatności cyklicznych oraz ich status. -->
                        <table class="transactions-table">
                            <thead>
                                <tr>
                                    <th class="text-left">Nazwa</th>
                                    <th class="text-left">Start</th>
                                    <th class="text-left">Koniec</th>
                                    <th class="text-left">Powtarzanie</th>
                                    <th class="text-left">Kategoria</th>
                                    <th class="text-left">Budżet</th>
                                    <th class="text-right">Kwota</th>
                                    <th class="text-right">Akcja</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['recurringTransactions'] as $r): ?>
                                    <tr>
                                        <td class="desc-cell"><?= htmlspecialchars($r['description'] ?: 'Płatność cykliczna') ?></td>
                                        <td><?= htmlspecialchars($r['start_date']) ?></td>
                                        <td><?= !empty($r['end_date']) ? htmlspecialchars($r['end_date']) : 'Bez końca' ?></td>
                                        <td>
                                            <span class="category-badge">
                                            <?php
                                            // Etykiety zamieniają techniczne wartości frequency na tekst widoczny w interfejsie.
                                            $frequencyLabels = [
                                                    'weekly' => 'Tygodniowo',
                                                    'monthly' => 'Miesięcznie',
                                                    'quarterly' => 'Kwartalnie',
                                                    'yearly' => 'Rocznie',
                                                ];
                                                echo htmlspecialchars($frequencyLabels[$r['frequency']] ?? $r['frequency']);
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="category-badge">
                                                <?= htmlspecialchars($r['category_name']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($r['shared_budget_name'])): ?>
                                                <span class="transaction-budget-badge"><?= htmlspecialchars($r['shared_budget_name']) ?></span>
                                            <?php else: ?>
                                                <span class="transaction-budget-private">Prywatne</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="amount-cell <?= $r['type'] === 'expense' ? 'amount-expense' : 'amount-income' ?>">
                                            <?= $r['type'] === 'expense' ? '-' : '+' ?> <?= number_format($r['amount'], 2) ?> zł
                                        </td>
                                        <td class="action-cell">
                                            <?php if ($r['status'] === 'active'): ?>
                                                <form action="<?= url('transaction/recurring/delete') ?>" method="POST" class="delete-form">
                                                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                                    <button type="submit" class="btn-delete">
                                                        Usuń
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="transaction-status-muted">Zakończona</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-transactions no-transactions-compact">
                            <p class="no-transactions-text">Nie masz jeszcze żadnych transakcji cyklicznych.</p>
                            <a href="<?= url('wallet') ?>" class="btn-primary">Dodaj cykliczną</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sekcja pełnej historii pojedynczych i wygenerowanych transakcji. -->
                <div class="auth-card transactions-card transactions-history-card">
                    <div class="transactions-section-head">
                        <h2>Historia transakcji</h2>
                    </div>
                    <?php if (!empty($data['transactions'])): ?>
                        <!-- Tabela historii obejmuje kategorię, budżet, opis, kwotę i akcję usunięcia. -->
                        <table class="transactions-table">
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
                                        <td><?= $t['date'] ?></td>
                                        <td>
                                            <span class="category-badge">
                                                <?= htmlspecialchars($t['category_name']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($t['shared_budget_name'])): ?>
                                                <span class="transaction-budget-badge"><?= htmlspecialchars($t['shared_budget_name']) ?></span>
                                            <?php else: ?>
                                                <span class="transaction-budget-private">Prywatne</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="desc-cell"><?= htmlspecialchars($t['description']) ?></td>
                                        <td
                                            class="amount-cell <?= $t['type'] === 'expense' ? 'amount-expense' : 'amount-income' ?>">
                                            <?= $t['type'] === 'expense' ? '-' : '+' ?>         <?= number_format($t['amount'], 2) ?> zł
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
                            <a href="<?= url('wallet') ?>" class="btn-primary">Dodaj pierwszą</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include comp('footer.php'); ?>
</body>

</html>
