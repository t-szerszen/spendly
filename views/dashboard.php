<?php
/**
 * Widok: Panel główny (Dashboard)
 * 
 * Główny widok dla zalogowanego użytkownika. Zawiera podsumowanie finansów 
 * (stan konta, wpływy, wydatki) pobierane z bazy danych, formularz szybkiego 
 * dodawania nowej transakcji oraz listę ostatnich operacji finansowych.
 */
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <title><?= $data['title'] ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&display=swap" rel="stylesheet">
    <!-- Styl CSS -->
    <link rel="stylesheet" href="<?= url('styles/style.css') ?>?v=<?= time() ?>">
</head>

<body>

    <!-- Nawigacja -->
    <?php include 'components/navDashboard.php'; ?>

    <main class="auth-section dashboard-section">
        <div class="container dashboard-container">
            <h1 class="dashboard-title">Witaj w Spendly, <?= $_SESSION['first_name'] ?>!</h1>

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

            <div class="auth-card dashboard-card">
                <h3>Szybkie dodawanie</h3>
                <form action="<?= url('transaction/add') ?>" method="POST" class="auth-form quick-add-form">
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
                    <p class="no-recent-transactions">Brak zarejestrowanych transakcji.</p>
                <?php endif; ?>
            </div>
    </main>
</body>

</html>