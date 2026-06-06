<?php

class RecurringTransaction
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function add(array $data): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO recurring_transactions
                (user_id, shared_budget_id, category_id, amount, type, description, frequency, start_date, end_date, next_due_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        return $stmt->execute([
            $data['user_id'],
            $data['shared_budget_id'] ?? null,
            $data['category_id'],
            $data['amount'],
            $data['type'],
            $data['description'],
            $data['frequency'],
            $data['start_date'],
            $data['end_date'] ?? null,
            $data['start_date'],
        ]);
    }

    public function getAllByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT r.*, c.name AS category_name, h.name AS shared_budget_name
             FROM recurring_transactions r
             JOIN categories c ON c.id = r.category_id
             LEFT JOIN shared_budgets h ON h.id = r.shared_budget_id
             WHERE r.user_id = ?
             ORDER BY r.status = "active" DESC, r.next_due_date ASC, r.id DESC'
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    public function generateDueForUser(int $userId, Transaction $transactionModel): void
    {
        $today = (new DateTimeImmutable('today'))->format('Y-m-d');
        $stmt = $this->db->prepare(
            'SELECT *
             FROM recurring_transactions
             WHERE user_id = ?
               AND status = "active"
               AND next_due_date <= ?
               AND (end_date IS NULL OR next_due_date <= end_date)'
        );
        $stmt->execute([$userId, $today]);

        foreach ($stmt->fetchAll() as $recurring) {
            $this->generateDueEntries($recurring, $transactionModel, $today);
        }
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM recurring_transactions
             WHERE id = ? AND user_id = ?'
        );

        return $stmt->execute([$id, $userId]);
    }

    private function generateDueEntries(array $recurring, Transaction $transactionModel, string $today): void
    {
        $nextDueDate = DateTimeImmutable::createFromFormat('!Y-m-d', $recurring['next_due_date']);
        $endDate = !empty($recurring['end_date'])
            ? DateTimeImmutable::createFromFormat('!Y-m-d', $recurring['end_date'])
            : null;
        $todayDate = DateTimeImmutable::createFromFormat('!Y-m-d', $today);

        if ($nextDueDate === false || $todayDate === false) {
            return;
        }

        while ($nextDueDate <= $todayDate && ($endDate === null || $nextDueDate <= $endDate)) {
            if (!$transactionModel->recurringEntryExists((int) $recurring['id'], $nextDueDate->format('Y-m-d'))) {
                $transactionModel->add([
                    'user_id' => $recurring['user_id'],
                    'shared_budget_id' => $recurring['shared_budget_id'],
                    'category_id' => $recurring['category_id'],
                    'amount' => $recurring['amount'],
                    'type' => $recurring['type'],
                    'entry_kind' => 'standard',
                    'description' => $recurring['description'],
                    'date' => $nextDueDate->format('Y-m-d'),
                    'recurring_transaction_id' => $recurring['id'],
                ]);
            }

            $nextDueDate = $this->advanceDate($nextDueDate, $recurring['frequency']);
        }

        $status = ($endDate !== null && $nextDueDate > $endDate) ? 'completed' : 'active';
        $stmt = $this->db->prepare(
            'UPDATE recurring_transactions
             SET next_due_date = ?, status = ?
             WHERE id = ?'
        );
        $stmt->execute([$nextDueDate->format('Y-m-d'), $status, $recurring['id']]);
    }

    private function advanceDate(DateTimeImmutable $date, string $frequency): DateTimeImmutable
    {
        $intervals = [
            'weekly' => 'P1W',
            'monthly' => 'P1M',
            'quarterly' => 'P3M',
            'yearly' => 'P1Y',
        ];

        return $date->add(new DateInterval($intervals[$frequency] ?? 'P1M'));
    }
}
