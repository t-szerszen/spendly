<?php
// models/Transaction.php

/**
 * Model Transaction
 * 
 * Odpowiada za komunikację z tabelą 'transactions'.
 */
class Transaction
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getRecord($tId)
    {
        $stmt = $this->db->prepare("SELECT * FROM transactions WHERE id = ?");
        $stmt->execute([$tId]);
        return $stmt->fetch();
    }

    public function getRecordByUser($tId, $userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM transactions WHERE id = ? AND user_id = ?");
        $stmt->execute([$tId, $userId]);
        return $stmt->fetch();
    }

    /**
     * Pobiera sumę transakcji danego typu (income/expense) dla użytkownika.
     */
    public function getTotalByType($userId, $type)
    {
        $stmt = $this->db->prepare("SELECT SUM(amount) as total FROM transactions WHERE user_id = ? AND type = ?");
        $stmt->execute([$userId, $type]);
        return $stmt->fetch()['total'] ?? 0;
    }

    /**
     * Pobiera ostatnie transakcje użytkownika z nazwami kategorii.
     */
    public function getRecent($userId, $limit = 10)
    {
        $stmt = $this->db->prepare("
            SELECT t.*, c.name as category_name, h.name as shared_budget_name
            FROM transactions t 
            JOIN categories c ON t.category_id = c.id 
            LEFT JOIN shared_budgets h ON h.id = t.shared_budget_id
            WHERE t.user_id = ? 
            ORDER BY t.date DESC, t.id DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    public function getTotalByTypeAndMonth($userId, $month, $type)
    {
        $stmt = $this->db->prepare("
            SELECT SUM(amount) as total
            FROM transactions
            WHERE user_id = ?
              AND date LIKE ?
              AND type = ?
        ");
        $stmt->execute([$userId, $month . '%', $type]);
        return $stmt->fetch()['total'] ?? 0;
    }

    public function getByMonth($userId, $month)
    {
        $stmt = $this->db->prepare("
            SELECT t.*, c.name as category_name, h.name as shared_budget_name
            FROM transactions t 
            JOIN categories c ON t.category_id = c.id 
            LEFT JOIN shared_budgets h ON h.id = t.shared_budget_id
            WHERE t.user_id = ? AND t.date LIKE ?
            ORDER BY t.date DESC, t.id DESC 
        ");

        $stmt->execute([$userId, $month . '%']);
        return $stmt->fetchAll();
    }

    /**
     * Pobiera wszystkie transakcje użytkownika.
     */
    public function getAllByUser($userId)
    {
        $stmt = $this->db->prepare("
            SELECT t.*, c.name as category_name, h.name as shared_budget_name
            FROM transactions t 
            JOIN categories c ON t.category_id = c.id 
            LEFT JOIN shared_budgets h ON h.id = t.shared_budget_id
            WHERE t.user_id = ? 
            ORDER BY t.date DESC, t.id DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getSharedBudgetTotalByMonth($sharedBudgetId, $year, $month)
    {
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

    public function getSharedBudgetExpensesByMonth($sharedBudgetId, $year, $month)
    {
        $stmt = $this->db->prepare(
            'SELECT t.id,
                    t.shared_budget_id,
                    t.user_id AS paid_by_user_id,
                    t.category_id,
                    t.amount,
                    t.description,
                    t.date AS expense_date,
                    t.created_at,
                    u.first_name AS paid_by_first_name,
                    u.last_name AS paid_by_last_name,
                    c.name AS category_name
             FROM transactions t
             JOIN users u ON u.id = t.user_id
             JOIN categories c ON c.id = t.category_id
             WHERE t.shared_budget_id = ?
               AND t.type = "expense"
               AND t.entry_kind = "standard"
               AND YEAR(t.date) = ?
               AND MONTH(t.date) = ?
             ORDER BY t.date DESC, t.id DESC'
        );
        $stmt->execute([$sharedBudgetId, $year, $month]);

        return $stmt->fetchAll();
    }

    public function getUserSharedBudgetCostByMonth($userId, $year, $month)
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(t.amount * (hm.share_percent / 100)), 0)
             FROM transactions t
             JOIN shared_budget_members hm ON hm.shared_budget_id = t.shared_budget_id AND hm.user_id = ?
             WHERE t.shared_budget_id IS NOT NULL
               AND t.type = "expense"
               AND t.entry_kind = "standard"
               AND YEAR(t.date) = ?
               AND MONTH(t.date) = ?'
        );
        $stmt->execute([$userId, $year, $month]);

        return (float) $stmt->fetchColumn();
    }

    /**
     * Dodaje nową transakcję.
     */
    public function add($data)
    {
        $stmt = $this->db->prepare(
            'INSERT INTO transactions
                (user_id, shared_budget_id, recurring_transaction_id, category_id, amount, type, entry_kind, description, date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        return $stmt->execute([
            $data['user_id'],
            $data['shared_budget_id'] ?? null,
            $data['recurring_transaction_id'] ?? null,
            $data['category_id'],
            $data['amount'],
            $data['type'],
            $data['entry_kind'] ?? 'standard',
            $data['description'],
            $data['date']
        ]);
    }

    public function recurringEntryExists(int $recurringTransactionId, string $date): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM transactions
             WHERE recurring_transaction_id = ? AND date = ?'
        );
        $stmt->execute([$recurringTransactionId, $date]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Usuwa transakcję.
     */
    public function delete($id, $userId)
    {
        $stmt = $this->db->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");

        return $stmt->execute([$id, $userId]);
    }
}
