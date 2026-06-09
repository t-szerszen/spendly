<?php

declare(strict_types=1);

/**
 * Demo seed for the local Spendly database.
 *
 * Usage:
 *   php scripts/seed_demo.php
 */

const DEMO_START = '2026-01-01';
$rootDir = dirname(__DIR__);
loadEnv($rootDir . '/.env');

$pdo = connect();

try {
    resetDatabase($pdo);
    $pdo->beginTransaction();
    $categories = ensureCategories($pdo);

    $tobiaszId = upsertUser($pdo, [
        'first_name' => 'Tobiasz',
        'last_name' => 'Szerszeń',
        'email' => 'tobiaszszerszen@gmail.com',
        'password' => 'Tobiasz123&',
    ]);

    $bartoszId = upsertUser($pdo, [
        'first_name' => 'Bartosz',
        'last_name' => 'Linke',
        'email' => 'barteklinke1@wp.pl',
        'password' => 'Bartosz123&',
    ]);

    $studiaBudgetId = createSharedBudget($pdo, 'Studia', $tobiaszId);
    addSharedBudgetMember($pdo, $studiaBudgetId, $tobiaszId, 50.00, 'owner');
    addSharedBudgetMember($pdo, $studiaBudgetId, $bartoszId, 50.00, 'member');

    $recurringIds = createRecurringTransactions($pdo, $categories, $studiaBudgetId, $tobiaszId, $bartoszId);
    seedTransactions($pdo, $categories, $studiaBudgetId, $tobiaszId, $bartoszId, $recurringIds);

    $pdo->commit();

    echo "Demo seed completed.\n";
    echo "- Tobiasz Szerszeń: tobiaszszerszen@gmail.com / Tobiasz123&\n";
    echo "- Bartosz Linke: barteklinke1@wp.pl / Bartosz123&\n";
    echo "- Shared budget: Studia\n";
    echo "- Database data was cleared before seeding.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, "Demo seed failed: {$e->getMessage()}\n");
    exit(1);
}

function loadEnv(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        $value = trim($value, "\"'");

        $_ENV[$key] = $value;
        putenv($key . '=' . $value);
    }
}

function connect(): PDO
{
    $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
    $dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'spendly';
    $username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
    $password = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';

    $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    return $pdo;
}

function ensureCategories(PDO $pdo): array
{
    $required = [
        ['Kieszonkowe', 'income'],
        ['Zwrot kosztow', 'income'],
        ['Sprzedaz rzeczy', 'income'],
        ['Premia rodzinna', 'income'],
        ['Mieszkanie', 'expense'],
        ['Rachunki', 'expense'],
        ['Jedzenie', 'expense'],
        ['Zakupy spozywcze', 'expense'],
        ['Chemia i dom', 'expense'],
        ['Transport', 'expense'],
        ['Edukacja', 'expense'],
        ['Zdrowie', 'expense'],
        ['Rozrywka', 'expense'],
        ['Subskrypcje', 'expense'],
        ['Ubrania', 'expense'],
        ['Elektronika', 'expense'],
        ['Sport', 'expense'],
        ['Podroze', 'expense'],
        ['Prezenty', 'expense'],
        ['Oszczednosci', 'expense'],
        ['Rozliczenie wspolnego budzetu', 'expense'],
    ];

    foreach ($required as [$name, $type]) {
        $stmt = $pdo->prepare('SELECT id FROM categories WHERE name = ? AND type = ? LIMIT 1');
        $stmt->execute([$name, $type]);

        if (!$stmt->fetchColumn()) {
            $insert = $pdo->prepare('INSERT INTO categories (name, type) VALUES (?, ?)');
            $insert->execute([$name, $type]);
        }
    }

    $categories = [];
    $stmt = $pdo->query('SELECT id, name, type FROM categories');

    foreach ($stmt->fetchAll() as $category) {
        $categories[$category['name']] = (int) $category['id'];
    }

    return $categories;
}

function resetDatabase(PDO $pdo): void
{
    $tables = [
        'transactions',
        'recurring_transactions',
        'settlements',
        'shared_budget_invitations',
        'shared_budgets_users',
        'shared_budget_members',
        'shared_budgets',
        'users',
        'categories',
    ];

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

    foreach ($tables as $table) {
        if (tableExists($pdo, $table)) {
            $pdo->exec("TRUNCATE TABLE `{$table}`");
        }
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);

    return (int) $stmt->fetchColumn() > 0;
}

function upsertUser(PDO $pdo, array $user): int
{
    $hash = password_hash($user['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$user['email']]);
    $id = $stmt->fetchColumn();

    if ($id) {
        $update = $pdo->prepare(
            'UPDATE users
             SET first_name = ?, last_name = ?, password = ?
             WHERE id = ?'
        );
        $update->execute([$user['first_name'], $user['last_name'], $hash, $id]);

        return (int) $id;
    }

    $insert = $pdo->prepare(
        'INSERT INTO users (first_name, last_name, email, password, created_at)
         VALUES (?, ?, ?, ?, ?)'
    );
    $insert->execute([
        $user['first_name'],
        $user['last_name'],
        $user['email'],
        $hash,
        DEMO_START . ' 09:00:00',
    ]);

    return (int) $pdo->lastInsertId();
}

function createSharedBudget(PDO $pdo, string $name, int $ownerId): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO shared_budgets (name, created_by, owner_id, created_at)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$name, $ownerId, $ownerId, DEMO_START . ' 10:00:00']);

    return (int) $pdo->lastInsertId();
}

function addSharedBudgetMember(PDO $pdo, int $budgetId, int $userId, float $sharePercent, string $role): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO shared_budget_members (shared_budget_id, user_id, share_percent, role, created_at)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$budgetId, $userId, $sharePercent, $role, DEMO_START . ' 10:05:00']);
}

function createRecurringTransactions(PDO $pdo, array $categories, int $budgetId, int $tobiaszId, int $bartoszId): array
{
    $definitions = [
        'tobiasz_allowance' => [$tobiaszId, null, $categories['Kieszonkowe'], 3500.00, 'income', 'Przelew od rodzicow na miesiac', 'monthly', '2026-01-03', null, '2026-07-03'],
        'bartosz_allowance' => [$bartoszId, null, $categories['Kieszonkowe'], 3500.00, 'income', 'Przelew od rodzicow na miesiac', 'monthly', '2026-01-03', null, '2026-07-03'],
        'rent' => [$tobiaszId, $budgetId, $categories['Mieszkanie'], 2600.00, 'expense', 'Czynsz za mieszkanie studenckie', 'monthly', '2026-01-05', null, '2026-07-05'],
        'internet' => [$bartoszId, $budgetId, $categories['Rachunki'], 89.99, 'expense', 'Internet swiatlowodowy', 'monthly', '2026-01-10', null, '2026-07-10'],
        'streaming' => [$bartoszId, $budgetId, $categories['Subskrypcje'], 59.99, 'expense', 'Pakiet streamingowy do mieszkania', 'monthly', '2026-01-12', null, '2026-07-12'],
        'tobiasz_savings_transfer' => [$tobiaszId, null, $categories['Oszczednosci'], 450.00, 'expense', 'Przelew na konto oszczednosciowe', 'monthly', '2026-01-07', null, '2026-07-07'],
        'bartosz_savings_transfer' => [$bartoszId, null, $categories['Oszczednosci'], 400.00, 'expense', 'Przelew na wakacje', 'monthly', '2026-01-07', null, '2026-07-07'],
    ];

    $insert = $pdo->prepare(
        'INSERT INTO recurring_transactions
            (user_id, shared_budget_id, category_id, amount, type, description, frequency, start_date, end_date, next_due_date, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "active", ?)'
    );

    $ids = [];

    foreach ($definitions as $key => $definition) {
        $insert->execute([...$definition, DEMO_START . ' 10:15:00']);
        $ids[$key] = (int) $pdo->lastInsertId();
    }

    return $ids;
}

function seedTransactions(PDO $pdo, array $categories, int $budgetId, int $tobiaszId, int $bartoszId, array $recurringIds): void
{
    $rows = [];

    foreach (['2026-01', '2026-02', '2026-03', '2026-04', '2026-05', '2026-06'] as $month) {
        $rows[] = tx($tobiaszId, null, $recurringIds['tobiasz_allowance'], $categories['Kieszonkowe'], 3500.00, 'income', 'Przelew od rodzicow na miesiac', "{$month}-03");
        $rows[] = tx($bartoszId, null, $recurringIds['bartosz_allowance'], $categories['Kieszonkowe'], 3500.00, 'income', 'Przelew od rodzicow na miesiac', "{$month}-03");

        $rows[] = tx($tobiaszId, $budgetId, $recurringIds['rent'], $categories['Mieszkanie'], 2600.00, 'expense', 'Czynsz za mieszkanie studenckie', "{$month}-05");
        $rows[] = tx($bartoszId, $budgetId, $recurringIds['internet'], $categories['Rachunki'], 89.99, 'expense', 'Internet swiatlowodowy', "{$month}-10");
        $rows[] = tx($bartoszId, $budgetId, $recurringIds['streaming'], $categories['Subskrypcje'], 59.99, 'expense', 'Pakiet streamingowy do mieszkania', "{$month}-12");

        $rows[] = tx($tobiaszId, null, $recurringIds['tobiasz_savings_transfer'], $categories['Oszczednosci'], 450.00, 'expense', 'Przelew na konto oszczednosciowe', "{$month}-07");
        $rows[] = tx($bartoszId, null, $recurringIds['bartosz_savings_transfer'], $categories['Oszczednosci'], 400.00, 'expense', 'Przelew na wakacje', "{$month}-07");
    }

    $extra = [
        ['2026-01-04', $tobiaszId, null, 'Transport', 119.00, 'expense', 'Bilet miesieczny MPK'],
        ['2026-01-06', $bartoszId, null, 'Transport', 119.00, 'expense', 'Bilet miesieczny MPK'],
        ['2026-01-08', $bartoszId, $budgetId, 'Zakupy spozywcze', 214.35, 'expense', 'Pierwsze wieksze zakupy do mieszkania'],
        ['2026-01-11', $tobiaszId, null, 'Edukacja', 86.00, 'expense', 'Podrecznik do statystyki'],
        ['2026-01-15', $tobiaszId, $budgetId, 'Zakupy spozywcze', 168.70, 'expense', 'Biedronka - zakupy do mieszkania'],
        ['2026-01-19', $bartoszId, null, 'Rozrywka', 42.00, 'expense', 'Kino ze znajomymi'],
        ['2026-01-23', $tobiaszId, null, 'Jedzenie', 31.50, 'expense', 'Lunch miedzy zajeciami'],
        ['2026-01-28', $bartoszId, $budgetId, 'Rachunki', 382.40, 'expense', 'Prad i ogrzewanie za styczen'],

        ['2026-02-04', $tobiaszId, null, 'Transport', 119.00, 'expense', 'Bilet miesieczny MPK'],
        ['2026-02-08', $bartoszId, $budgetId, 'Chemia i dom', 146.20, 'expense', 'Chemia domowa i papier'],
        ['2026-02-11', $tobiaszId, $budgetId, 'Zakupy spozywcze', 191.45, 'expense', 'Lidl - zakupy na tydzien'],
        ['2026-02-14', $bartoszId, null, 'Rozrywka', 74.90, 'expense', 'Pizza i planszowki u znajomych'],
        ['2026-02-18', $tobiaszId, null, 'Zdrowie', 58.99, 'expense', 'Apteka'],
        ['2026-02-21', $bartoszId, null, 'Edukacja', 39.00, 'expense', 'Notatki i materialy na laboratoria'],
        ['2026-02-25', $tobiaszId, $budgetId, 'Rachunki', 421.15, 'expense', 'Prad i ogrzewanie za luty'],
        ['2026-02-27', $bartoszId, null, 'Jedzenie', 28.00, 'expense', 'Kebab po zajeciach'],
        ['2026-02-28', $tobiaszId, null, 'Rozrywka', 68.00, 'expense', 'Wyjscie do pubu ze znajomymi'],

        ['2026-03-02', $tobiaszId, null, 'Transport', 119.00, 'expense', 'Bilet miesieczny MPK'],
        ['2026-03-06', $bartoszId, $budgetId, 'Zakupy spozywcze', 236.80, 'expense', 'Auchan - zapas makaronu i ryzu'],
        ['2026-03-09', $tobiaszId, null, 'Ubrania', 129.99, 'expense', 'Bluza na wiosne'],
        ['2026-03-13', $tobiaszId, $budgetId, 'Zakupy spozywcze', 156.30, 'expense', 'Wspolne zakupy spozywcze'],
        ['2026-03-17', $bartoszId, null, 'Rozrywka', 35.00, 'expense', 'Bilard ze znajomymi'],
        ['2026-03-20', $tobiaszId, null, 'Edukacja', 64.00, 'expense', 'Ksero skryptow'],
        ['2026-03-25', $bartoszId, $budgetId, 'Rachunki', 356.70, 'expense', 'Prad i gaz za marzec'],
        ['2026-03-29', $tobiaszId, null, 'Jedzenie', 46.70, 'expense', 'Ramen po zajeciach'],

        ['2026-04-02', $bartoszId, null, 'Transport', 119.00, 'expense', 'Bilet miesieczny MPK'],
        ['2026-04-06', $tobiaszId, $budgetId, 'Chemia i dom', 173.25, 'expense', 'Rossmann i srodki czystosci'],
        ['2026-04-10', $bartoszId, null, 'Zdrowie', 92.50, 'expense', 'Wizyta u dentysty - zaliczka'],
        ['2026-04-14', $bartoszId, $budgetId, 'Zakupy spozywcze', 204.10, 'expense', 'Kaufland - wspolne zakupy'],
        ['2026-04-18', $tobiaszId, null, 'Rozrywka', 59.00, 'expense', 'Koncert studencki ze znajomymi'],
        ['2026-04-22', $bartoszId, null, 'Edukacja', 79.90, 'expense', 'Kurs online do projektu'],
        ['2026-04-25', $tobiaszId, $budgetId, 'Rachunki', 318.65, 'expense', 'Prad i woda za kwiecien'],
        ['2026-04-29', $bartoszId, null, 'Jedzenie', 33.80, 'expense', 'Lunch w barze mlecznym'],

        ['2026-05-02', $tobiaszId, null, 'Transport', 119.00, 'expense', 'Bilet miesieczny MPK'],
        ['2026-05-07', $bartoszId, $budgetId, 'Zakupy spozywcze', 188.40, 'expense', 'Zakupy na grilla i mieszkanie'],
        ['2026-05-11', $tobiaszId, null, 'Edukacja', 110.00, 'expense', 'Oplata za certyfikat studencki'],
        ['2026-05-14', $tobiaszId, $budgetId, 'Zakupy spozywcze', 226.55, 'expense', 'Wspolne zakupy przed kolokwiami'],
        ['2026-05-17', $bartoszId, null, 'Ubrania', 89.99, 'expense', 'Koszula na prezentacje'],
        ['2026-05-21', $tobiaszId, null, 'Rozrywka', 48.00, 'expense', 'Kregle ze znajomymi'],
        ['2026-05-25', $bartoszId, $budgetId, 'Rachunki', 301.20, 'expense', 'Prad i woda za maj'],
        ['2026-05-30', $bartoszId, null, 'Jedzenie', 24.90, 'expense', 'Sniadanie na uczelni'],

        ['2026-06-01', $tobiaszId, null, 'Transport', 119.00, 'expense', 'Bilet miesieczny MPK'],
        ['2026-06-04', $bartoszId, null, 'Transport', 58.00, 'expense', 'Dojazdy na konsultacje'],
        ['2026-06-06', $bartoszId, $budgetId, 'Zakupy spozywcze', 132.70, 'expense', 'Zakupy na weekend'],
        ['2026-06-07', $tobiaszId, null, 'Rozrywka', 97.40, 'expense', 'Wyjscie ze znajomymi do pubu'],
        ['2026-06-08', $bartoszId, null, 'Edukacja', 45.00, 'expense', 'Wydruk materialow na zaliczenie'],
    ];

    foreach ($extra as [$date, $userId, $sharedBudgetId, $categoryName, $amount, $type, $description]) {
        $rows[] = tx($userId, $sharedBudgetId, null, $categories[$categoryName], $amount, $type, $description, $date);
    }

    $insert = $pdo->prepare(
        'INSERT INTO transactions
            (user_id, shared_budget_id, recurring_transaction_id, category_id, amount, type, entry_kind, description, date, created_at)
         VALUES (?, ?, ?, ?, ?, ?, "standard", ?, ?, ?)'
    );

    foreach ($rows as $row) {
        $insert->execute($row);
    }
}

function tx(
    int $userId,
    ?int $sharedBudgetId,
    ?int $recurringTransactionId,
    int $categoryId,
    float $amount,
    string $type,
    string $description,
    string $date
): array {
    return [
        $userId,
        $sharedBudgetId,
        $recurringTransactionId,
        $categoryId,
        $amount,
        $type,
        $description,
        $date,
        $date . ' 12:00:00',
    ];
}
