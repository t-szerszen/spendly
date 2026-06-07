<?php

/**
 * Model Settlement
 *
 * Odpowiada za wyliczanie sald członków wspólnego budżetu,
 * proponowanie minimalnych spłat oraz księgowanie wykonanych rozliczeń w portfelach.
 */
class Settlement
{
    private const CATEGORY_NAME = 'Rozliczenie wspolnego budzetu';

    private $db;
    private $memberModel;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->memberModel = new SharedBudgetMember();
    }

    public function getByMonth($sharedBudgetId, $periodMonth): array
    {
        // Pobiera historię zaksięgowanych spłat dla wskazanego miesiąca.
        $stmt = $this->db->prepare(
            'SELECT s.*,
                    from_user.first_name AS from_first_name,
                    from_user.last_name AS from_last_name,
                    to_user.first_name AS to_first_name,
                    to_user.last_name AS to_last_name
             FROM settlements s
             JOIN users from_user ON from_user.id = s.from_user_id
             JOIN users to_user ON to_user.id = s.to_user_id
             WHERE s.shared_budget_id = ?
               AND s.period_month = ?
             ORDER BY s.created_at DESC, s.id DESC'
        );
        $stmt->execute([$sharedBudgetId, $periodMonth]);

        return $stmt->fetchAll();
    }

    public function getMonthlyBalance($sharedBudgetId, $year, $month): array
    {
        // Łączy wydatki, udziały członków i wykonane spłaty w saldo netto każdej osoby.
        $total = $this->getSharedExpenseTotal($sharedBudgetId, $year, $month);
        $paidByUserId = $this->getPaidByUser($sharedBudgetId, $year, $month);
        $settlementTotals = $this->getPostedSettlementTotals($sharedBudgetId, sprintf('%04d-%02d', $year, $month));
        $settlementAdjustments = $this->getPostedSettlementAdjustments($sharedBudgetId, sprintf('%04d-%02d', $year, $month));
        $members = $this->memberModel->getMembers($sharedBudgetId);

        $balance = [];
        foreach ($members as $member) {
            $userId = (int) $member['user_id'];
            $paid = (float) ($paidByUserId[$userId] ?? 0);
            $shouldPay = round($total * ((float) $member['share_percent'] / 100), 2);
            $transferredOut = (float) ($settlementTotals[$userId]['transferred_out'] ?? 0);
            $receivedIn = (float) ($settlementTotals[$userId]['received_in'] ?? 0);
            $settlementAdjustment = (float) ($settlementAdjustments[$userId] ?? 0);

            // Zaksięgowane spłaty zmniejszają pozostały dług bez zmiany pierwotnych kosztów.
            $balance[] = [
                'user_id' => $userId,
                'name' => trim($member['first_name'] . ' ' . $member['last_name']),
                'share_percent' => (float) $member['share_percent'],
                'paid' => $paid,
                'should_pay' => $shouldPay,
                'transferred_out' => $transferredOut,
                'received_in' => $receivedIn,
                'balance' => round($paid - $shouldPay + $settlementAdjustment, 2),
            ];
        }

        return $balance;
    }

    public function getSuggestedTransfers($sharedBudgetId, $year, $month): array
    {
        // Buduje minimalną listę przelewów między dłużnikami i wierzycielami.
        $balances = $this->getMonthlyBalance($sharedBudgetId, $year, $month);
        $debtors = [];
        $creditors = [];

        foreach ($balances as $row) {
            $balance = round((float) $row['balance'], 2);

            if ($balance < -0.01) {
                $debtors[] = [
                    'user_id' => (int) $row['user_id'],
                    'name' => $row['name'],
                    'amount' => abs($balance),
                ];
            } elseif ($balance > 0.01) {
                $creditors[] = [
                    'user_id' => (int) $row['user_id'],
                    'name' => $row['name'],
                    'amount' => $balance,
                ];
            }
        }

        $transfers = [];
        $debtorIndex = 0;
        $creditorIndex = 0;

        while (isset($debtors[$debtorIndex], $creditors[$creditorIndex])) {
            $amount = round(min($debtors[$debtorIndex]['amount'], $creditors[$creditorIndex]['amount']), 2);

            if ($amount > 0.01) {
                $transfers[] = [
                    'from_user_id' => $debtors[$debtorIndex]['user_id'],
                    'from_name' => $debtors[$debtorIndex]['name'],
                    'to_user_id' => $creditors[$creditorIndex]['user_id'],
                    'to_name' => $creditors[$creditorIndex]['name'],
                    'amount' => $amount,
                ];
            }

            $debtors[$debtorIndex]['amount'] = round($debtors[$debtorIndex]['amount'] - $amount, 2);
            $creditors[$creditorIndex]['amount'] = round($creditors[$creditorIndex]['amount'] - $amount, 2);

            if ($debtors[$debtorIndex]['amount'] <= 0.01) {
                $debtorIndex++;
            }

            if ($creditors[$creditorIndex]['amount'] <= 0.01) {
                $creditorIndex++;
            }
        }

        return $transfers;
    }

    public function findSuggestedTransfer($sharedBudgetId, $year, $month, $fromUserId, $toUserId): ?array
    {
        // Weryfikuje pojedynczą spłatę względem aktualnych sugestii rozliczenia.
        foreach ($this->getSuggestedTransfers($sharedBudgetId, $year, $month) as $transfer) {
            if ((int) $transfer['from_user_id'] === $fromUserId && (int) $transfer['to_user_id'] === $toUserId) {
                return $transfer;
            }
        }

        return null;
    }

    public function post(array $data): int
    {
        // Księgowanie spłaty tworzy rekord settlement oraz dwie odpowiadające mu transakcje portfelowe.
        $this->db->beginTransaction();

        try {
            $categoryId = $this->getSettlementCategoryId();

            $stmt = $this->db->prepare(
                'INSERT INTO settlements
                    (shared_budget_id, period_month, from_user_id, to_user_id, amount, status, created_by)
                 VALUES (?, ?, ?, ?, ?, "posted", ?)'
            );
            $stmt->execute([
                $data['shared_budget_id'],
                $data['period_month'],
                $data['from_user_id'],
                $data['to_user_id'],
                $data['amount'],
                $data['created_by'],
            ]);

            $settlementId = (int) $this->db->lastInsertId();
            $description = 'Rozliczenie ' . $data['period_month'] . ' ' . $data['counterparty_name'];

            // Rozliczenie jest kompletne dopiero po utworzeniu wpisu wychodzącego i przychodzącego.
            $this->insertSettlementTransaction([
                'user_id' => $data['from_user_id'],
                'shared_budget_id' => $data['shared_budget_id'],
                'related_user_id' => $data['to_user_id'],
                'settlement_id' => $settlementId,
                'category_id' => $categoryId,
                'amount' => $data['amount'],
                'type' => 'expense',
                'entry_kind' => 'settlement_out',
                'description' => $description,
                'date' => $data['transaction_date'],
            ]);

            $this->insertSettlementTransaction([
                'user_id' => $data['to_user_id'],
                'shared_budget_id' => $data['shared_budget_id'],
                'related_user_id' => $data['from_user_id'],
                'settlement_id' => $settlementId,
                'category_id' => $categoryId,
                'amount' => $data['amount'],
                'type' => 'income',
                'entry_kind' => 'settlement_in',
                'description' => 'Rozliczenie ' . $data['period_month'] . ' ' . $data['payer_name'],
                'date' => $data['transaction_date'],
            ]);

            $this->db->commit();

            return $settlementId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    private function getSharedExpenseTotal($sharedBudgetId, $year, $month): float
    {
        // Suma obejmuje wyłącznie standardowe wydatki wspólnego budżetu.
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(amount), 0)
             FROM transactions
             WHERE shared_budget_id = ?
               AND type = "expense"
               AND entry_kind = "standard"
               AND YEAR(date) = ?
               AND MONTH(date) = ?'
        );
        $stmt->execute([$sharedBudgetId, $year, $month]);

        return (float) $stmt->fetchColumn();
    }

    private function getPaidByUser($sharedBudgetId, $year, $month): array
    {
        // Agreguje kwoty faktycznie opłacone przez poszczególnych członków.
        $stmt = $this->db->prepare(
            'SELECT user_id, COALESCE(SUM(amount), 0) AS paid
             FROM transactions
             WHERE shared_budget_id = ?
               AND type = "expense"
               AND entry_kind = "standard"
               AND YEAR(date) = ?
               AND MONTH(date) = ?
             GROUP BY user_id'
        );
        $stmt->execute([$sharedBudgetId, $year, $month]);

        $paidByUserId = [];
        foreach ($stmt->fetchAll() as $row) {
            $paidByUserId[(int) $row['user_id']] = (float) $row['paid'];
        }

        return $paidByUserId;
    }

    private function getPostedSettlementAdjustments($sharedBudgetId, $periodMonth): array
    {
        // Korekty salda wynikają z już zaksięgowanych spłat w danym miesiącu.
        $adjustments = [];

        foreach ($this->getByMonth($sharedBudgetId, $periodMonth) as $settlement) {
            if ($settlement['status'] !== 'posted') {
                continue;
            }

            $fromUserId = (int) $settlement['from_user_id'];
            $toUserId = (int) $settlement['to_user_id'];
            $amount = (float) $settlement['amount'];

            $adjustments[$fromUserId] = ($adjustments[$fromUserId] ?? 0) + $amount;
            $adjustments[$toUserId] = ($adjustments[$toUserId] ?? 0) - $amount;
        }

        return $adjustments;
    }

    private function getPostedSettlementTotals($sharedBudgetId, $periodMonth): array
    {
        // Sumy rozliczeń są prezentowane osobno jako przelewy wychodzące i otrzymane.
        $totals = [];

        foreach ($this->getByMonth($sharedBudgetId, $periodMonth) as $settlement) {
            if ($settlement['status'] !== 'posted') {
                continue;
            }

            $fromUserId = (int) $settlement['from_user_id'];
            $toUserId = (int) $settlement['to_user_id'];
            $amount = (float) $settlement['amount'];

            if (!isset($totals[$fromUserId])) {
                $totals[$fromUserId] = [
                    'transferred_out' => 0.0,
                    'received_in' => 0.0,
                ];
            }

            if (!isset($totals[$toUserId])) {
                $totals[$toUserId] = [
                    'transferred_out' => 0.0,
                    'received_in' => 0.0,
                ];
            }

            $totals[$fromUserId]['transferred_out'] += $amount;
            $totals[$toUserId]['received_in'] += $amount;
        }

        return $totals;
    }

    private function getSettlementCategoryId(): int
    {
        // Systemowa kategoria rozliczeń jest tworzona automatycznie przy pierwszym użyciu.
        $stmt = $this->db->prepare('SELECT id FROM categories WHERE name = ? LIMIT 1');
        $stmt->execute([self::CATEGORY_NAME]);
        $categoryId = $stmt->fetchColumn();

        if ($categoryId !== false) {
            return (int) $categoryId;
        }

        $insertStmt = $this->db->prepare('INSERT INTO categories (name, type) VALUES (?, "expense")');
        $insertStmt->execute([self::CATEGORY_NAME]);

        return (int) $this->db->lastInsertId();
    }

    private function insertSettlementTransaction(array $data): void
    {
        // Techniczny wpis portfelowy powiązany z zaksięgowaną spłatą.
        $stmt = $this->db->prepare(
            'INSERT INTO transactions
                (user_id, shared_budget_id, related_user_id, settlement_id, category_id, amount, type, entry_kind, description, date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $data['user_id'],
            $data['shared_budget_id'],
            $data['related_user_id'],
            $data['settlement_id'],
            $data['category_id'],
            $data['amount'],
            $data['type'],
            $data['entry_kind'],
            $data['description'],
            $data['date'],
        ]);
    }
}
