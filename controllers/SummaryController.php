<?php
// controllers/SummaryController.php

/**
 * Klasa SummaryController
 *
 * Odpowiada za przygotowanie widoku raportów finansowych dla zalogowanego użytkownika.
 * Agreguje dane transakcji w wybranym zakresie dat.
 * przygotowuje porównania okresów oraz zestawy danych dla wykresów i kalendarza.
 */
class SummaryController
{
    private $db;
    private $authService;
    private $categoryModel;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->authService = new AuthService();
        $this->categoryModel = new Category();
    }

    public function show()
    {
        // Obsługuje żądanie strony raportów oraz przygotowuje komplet danych dla widoku.
        if (!$this->authService->isLoggedIn()) {
            header('Location: ' . url('login'));
            exit;
        }

        $userId = $_SESSION['user_id'];
        $today = date('Y-m-d');
        $range = $this->getSelectedRange();
        $previousRange = $this->getPreviousRange($range['start'], $range['days']);
        $calendarMonth = $this->getCalendarMonth($today);

        // Porównania bazują na wybranym zakresie oraz poprzednim okresie o tej samej długości.
        $stats = $this->getPeriodStats($userId, $range['start'], $range['end']);
        $previousStats = $this->getPeriodStats($userId, $previousRange['start'], $previousRange['end']);
        $paceTrend = $this->formatPaceTrend(
            $stats['dailySpendingPace'],
            $previousStats['dailySpendingPace'],
            $range['days']
        );

        // Zestawy danych wykresów są agregowane po stronie serwera i renderowane w warstwie JavaScript.
        $expenseChart = [
            'labels' => array_column($summary = $this->getCategorySummary($userId, $range['start'], $range['end']), 'category_name'),
            'data' => array_map('floatval', array_column($summary, 'total_amount')),
        ];
        $calendarTransactions = $this->getCalendarTransactions($userId);
        $dailyBalanceChart = $this->getDailyBalanceChart($userId, $range['start'], $range['end']);
        $monthlyBalanceChart = $this->getMonthlyBalanceChart($userId);

        $data = [
            'title' => 'Dashboard Raportów',
            'startDate' => $range['start'],
            'endDate' => $range['end'],
            'today' => $today,
            'currentMonth' => (int) (new DateTimeImmutable($range['start']))->format('m'),
            'currentYear' => (int) (new DateTimeImmutable($range['start']))->format('Y'),
            'calendarMonth' => (int) $calendarMonth->format('m'),
            'calendarYear' => (int) $calendarMonth->format('Y'),
            'stats' => array_merge($stats, [
                'periodDays' => $range['days'],
                'paceTrend' => $paceTrend,
                'paceProgress' => $this->calculatePaceProgress($stats['dailySpendingPace'], $previousStats['dailySpendingPace']),
            ]),
            'comparisons' => [
                'totalIncome' => $this->formatComparison($stats['totalIncome'], $previousStats['totalIncome'], false, $range['days']),
                'totalExpenses' => $this->formatComparison($stats['totalExpenses'], $previousStats['totalExpenses'], true, $range['days']),
                'cashFlow' => $this->formatCashFlowComparison($stats['cashFlow'], $previousStats['cashFlow'], $range['days']),
                'avgTransaction' => $this->formatComparison($stats['avgTransaction'], $previousStats['avgTransaction'], true, $range['days']),
            ],
            'summary' => $summary,
            'expenseChart' => $expenseChart,
            'calendarTransactions' => $calendarTransactions,
            'dailyBalanceChart' => $dailyBalanceChart,
            'monthlyBalanceChart' => $monthlyBalanceChart,
            'categories' => $this->categoryModel->getCategories(),
        ];

        // Dane serializowane do JSON i udostępniane skryptowi summary.js.
        $data['jsData'] = [
            'categories' => $expenseChart['labels'],
            'amounts' => $expenseChart['data'],
            'calendarTransactions' => $calendarTransactions,
            'currentMonth' => $data['currentMonth'],
            'currentYear' => $data['currentYear'],
            'calendarMonth' => $data['calendarMonth'],
            'calendarYear' => $data['calendarYear'],
            'today' => $today,
            'startDate' => $range['start'],
            'endDate' => $range['end'],
            'dailyBalanceLabels' => $dailyBalanceChart['labels'],
            'dailyBalanceData' => $dailyBalanceChart['data'],
            'monthlyBalanceTotals' => $monthlyBalanceChart['totals'],
            'monthlyBalanceCurrentMonth' => $monthlyBalanceChart['currentMonth'],
        ];

        require_once __DIR__ . '/../views/summary.php';
    }

    private function getSelectedRange(): array
    {
        // Wyznacza zakres raportu na podstawie parametrów GET.
        $month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('m');
        $year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
        $month = max(1, min(12, $month));
        $baseMonth = DateTimeImmutable::createFromFormat('!Y-n-j', "$year-$month-1") ?: new DateTimeImmutable('first day of this month');
        $defaultStart = $baseMonth->format('Y-m-01');
        $defaultEnd = $baseMonth->format('Y-m-t');

        $start = !empty($_GET['start_date']) ? $_GET['start_date'] : $defaultStart;
        $end = !empty($_GET['end_date']) ? $_GET['end_date'] : $defaultEnd;

        if ($start > $end) {
            $start = $end;
        }

        $startDate = new DateTimeImmutable($start);
        $endDate = new DateTimeImmutable($end);

        return [
            'start' => $start,
            'end' => $end,
            'days' => $startDate->diff($endDate)->days + 1,
        ];
    }

    private function getPreviousRange(string $startDate, int $days): array
    {
        // Wyznacza poprzedni okres referencyjny o tej samej liczbie dni.
        $start = new DateTimeImmutable($startDate);

        return [
            'start' => $start->modify("-$days days")->format('Y-m-d'),
            'end' => $start->modify('-1 day')->format('Y-m-d'),
        ];
    }

    private function getCalendarMonth(string $today): DateTimeImmutable
    {
        // Pobiera miesiąc widoczny w kalendarzu, niezależny od głównego zakresu raportu.
        $requested = $_GET['calendar_month'] ?? date('Y-m');
        $calendarMonth = DateTimeImmutable::createFromFormat('Y-m-d', $requested . '-01');

        if ($calendarMonth === false) {
            return new DateTimeImmutable('first day of this month');
        }

        return $calendarMonth;
    }

    private function getPeriodStats(int $userId, string $startDate, string $endDate): array
    {
        // Agreguje podstawowe wskaźniki finansowe dla wskazanego okresu.
        $totalIncome = $this->sumAmount($userId, $startDate, $endDate, 'income');
        $totalExpenses = $this->sumAmount($userId, $startDate, $endDate, 'expense');
        $avgTransaction = $this->avgAmount($userId, $startDate, $endDate, 'expense');
        $transactionCount = $this->countTransactions($userId, $startDate, $endDate);
        $periodDays = (new DateTimeImmutable($startDate))->diff(new DateTimeImmutable($endDate))->days + 1;
        $dailySpendingPace = $periodDays > 0 ? $totalExpenses / $periodDays : 0;

        return [
            'totalIncome' => $totalIncome,
            'totalExpenses' => $totalExpenses,
            'cashFlow' => $totalIncome - $totalExpenses,
            'avgTransaction' => $avgTransaction,
            'transactionCount' => $transactionCount,
            'dailySpendingPace' => $dailySpendingPace,
            'monthlyAverageExpense' => $dailySpendingPace * 30,
        ];
    }

    private function sumAmount(int $userId, string $startDate, string $endDate, string $type): float
    {
        $stmt = $this->db->prepare("
            SELECT SUM(amount)
            FROM transactions
            WHERE user_id = ? AND type = ? AND date BETWEEN ? AND ?
        ");
        $stmt->execute([$userId, $type, $startDate, $endDate]);

        return (float) ($stmt->fetchColumn() ?: 0);
    }

    private function avgAmount(int $userId, string $startDate, string $endDate, string $type): float
    {
        $stmt = $this->db->prepare("
            SELECT AVG(amount)
            FROM transactions
            WHERE user_id = ? AND type = ? AND date BETWEEN ? AND ?
        ");
        $stmt->execute([$userId, $type, $startDate, $endDate]);

        return (float) ($stmt->fetchColumn() ?: 0);
    }

    private function countTransactions(int $userId, string $startDate, string $endDate): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM transactions
            WHERE user_id = ? AND date BETWEEN ? AND ?
        ");
        $stmt->execute([$userId, $startDate, $endDate]);

        return (int) ($stmt->fetchColumn() ?: 0);
    }

    private function getCategorySummary(int $userId, string $startDate, string $endDate): array
    {
        // Agreguje wydatki według kategorii dla listy rankingowej i wykresu struktury kosztów.
        $stmt = $this->db->prepare("
            SELECT c.name as category_name, SUM(t.amount) as total_amount
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ? AND t.type = 'expense' AND t.date BETWEEN ? AND ?
            GROUP BY t.category_id, c.name
            ORDER BY total_amount DESC
        ");
        $stmt->execute([$userId, $startDate, $endDate]);

        return $stmt->fetchAll();
    }

    private function getCalendarTransactions(int $userId): array
    {
        // Przygotowuje dzienne sumy przychodów i wydatków używane w kalendarzu transakcji.
        $stmt = $this->db->prepare("
            SELECT date as date_key,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense_total,
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income_total
            FROM transactions
            WHERE user_id = ?
            GROUP BY date
            ORDER BY date ASC
        ");
        $stmt->execute([$userId]);

        $transactions = [];
        foreach ($stmt->fetchAll() as $row) {
            $transactions[$row['date_key']] = [
                'expense' => (float) $row['expense_total'],
                'income' => (float) $row['income_total'],
            ];
        }

        return $transactions;
    }

    private function getDailyBalanceChart(int $userId, string $startDate, string $endDate): array
    {
        // Tworzy serię bilansu dziennego, uzupełniając brakujące dni wartością zerową.
        $stmt = $this->db->prepare("
            SELECT date as date_key,
                SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as daily_balance
            FROM transactions
            WHERE user_id = ? AND date BETWEEN ? AND ?
            GROUP BY date
            ORDER BY date ASC
        ");
        $stmt->execute([$userId, $startDate, $endDate]);

        $totals = [];
        foreach ($stmt->fetchAll() as $row) {
            $totals[$row['date_key']] = (float) $row['daily_balance'];
        }

        $labels = [];
        $data = [];
        $start = new DateTimeImmutable($startDate);
        $end = new DateTimeImmutable($endDate);

        for ($day = $start; $day <= $end; $day = $day->modify('+1 day')) {
            $dateKey = $day->format('Y-m-d');
            $labels[] = $day->format('d.m');
            $data[] = $totals[$dateKey] ?? 0;
        }

        return ['labels' => $labels, 'data' => $data];
    }

    private function getMonthlyBalanceChart(int $userId): array
    {
        // Agreguje bilans miesięczny dla wykresu obejmującego także zaplanowane transakcje.
        $stmt = $this->db->prepare("
            SELECT DATE_FORMAT(date, '%Y-%m') as month_key,
                SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as monthly_balance
            FROM transactions
            WHERE user_id = ?
            GROUP BY DATE_FORMAT(date, '%Y-%m')
            ORDER BY month_key ASC
        ");
        $stmt->execute([$userId]);

        $totals = [];
        foreach ($stmt->fetchAll() as $row) {
            $totals[$row['month_key']] = (float) $row['monthly_balance'];
        }

        $lastMonth = !empty($totals) ? max(array_keys($totals)) : date('Y-m');
        $currentMonth = max(date('Y-m'), $lastMonth);

        return [
            'totals' => $totals,
            'currentMonth' => $currentMonth,
        ];
    }

    private function calculatePaceProgress(float $current, float $previous): float
    {
        if ($previous <= 0) {
            return min(100, max(8, $current > 0 ? 50 : 8));
        }

        return min(100, max(8, ($current / $previous) * 50));
    }

    private function formatComparison(float $current, float $previous, bool $lowerIsBetter, int $periodDays): array
    {
        // Formatuje wartość porównawczą oraz klasę statusu dla elementów KPI.
        if ($previous == 0.0) {
            return [
                'amount' => 'Brak danych',
                'percent' => $this->formatPeriodLabel($periodDays),
                'class' => 'neutral',
            ];
        }

        $moneyChange = $current - $previous;
        $percentChange = ($moneyChange / abs($previous)) * 100;
        $isPositive = $lowerIsBetter ? $percentChange <= 0 : $percentChange >= 0;

        return [
            'amount' => ($moneyChange >= 0 ? '+' : '') . number_format($moneyChange, 2) . ' zł',
            'percent' => $this->formatPercent($percentChange) . ' vs ' . $this->formatPeriodLabel($periodDays),
            'class' => $isPositive ? 'positive' : 'negative',
        ];
    }

    private function formatCashFlowComparison(float $current, float $previous, int $periodDays): array
    {
        if ($previous == 0.0) {
            return [
                'amount' => 'Brak danych',
                'percent' => $this->formatPeriodLabel($periodDays),
                'class' => 'neutral',
            ];
        }

        $change = $current - $previous;
        $percentChange = ($change / abs($previous)) * 100;

        return [
            'amount' => abs($change) < 0.01 ? '0,00 zł' : ($change > 0 ? '+' : '') . number_format($change, 2) . ' zł',
            'percent' => $this->formatPercent($percentChange) . ' vs ' . $this->formatPeriodLabel($periodDays),
            'class' => abs($change) < 0.01 ? 'neutral' : ($change > 0 ? 'positive' : 'negative'),
        ];
    }

    private function formatPaceTrend(float $current, float $previous, int $periodDays): array
    {
        // Określa zmianę tempa wydatków względem poprzedniego okresu.
        if ($previous == 0.0) {
            return [
                'text' => $current > 0 ? 'Brak punktu odniesienia' : 'Brak wydatków dziennych',
                'class' => 'neutral',
            ];
        }

        $change = (($current - $previous) / abs($previous)) * 100;
        if (abs($change) < 0.1) {
            return [
                'text' => 'Tak samo jak ' . $this->formatPeriodLabel($periodDays),
                'class' => 'neutral',
            ];
        }

        return [
            'text' => ($change > 0 ? 'Szybciej o ' : 'Wolniej o ') . $this->formatPercent(abs($change), false)
                . ' niż ' . $this->formatPeriodLabel($periodDays),
            'class' => $change > 0 ? 'negative' : 'positive',
        ];
    }

    private function formatPercent(float $value, bool $includeSign = true): string
    {
        if (abs($value) >= 10000) {
            return $value < 0 ? '<-9999%' : '>9999%';
        }

        return ($includeSign && $value >= 0 ? '+' : '') . number_format($value, 1) . '%';
    }

    private function formatPeriodLabel(int $periodDays): string
    {
        return $periodDays === 1 ? 'poprzedni dzień' : 'poprzednie ' . $periodDays . ' dni';
    }
}
