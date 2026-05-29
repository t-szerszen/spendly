<?php

class SharedBudget
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare(
            'INSERT INTO shared_budgets (name, owner_id, created_by)
             VALUES (?, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            $data['created_by'],
            $data['created_by'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function find($id)
    {
        $stmt = $this->db->prepare(
            'SELECT h.*,
                    (SELECT COUNT(*) FROM shared_budget_members hm WHERE hm.shared_budget_id = h.id) AS member_count
             FROM shared_budgets h
             WHERE h.id = ?'
        );
        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    public function findByUser($userId)
    {
        $stmt = $this->db->prepare(
            'SELECT h.*,
                    (SELECT COUNT(*) FROM shared_budget_members hm WHERE hm.shared_budget_id = h.id) AS member_count
             FROM shared_budgets h
             JOIN shared_budget_members hm ON hm.shared_budget_id = h.id
             WHERE hm.user_id = ?
             ORDER BY h.created_at DESC, h.id DESC'
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    public function userHasAccess($sharedBudgetId, $userId)
    {
        $stmt = $this->db->prepare(
            'SELECT 1
             FROM shared_budget_members
             WHERE shared_budget_id = ?
               AND user_id = ?
             LIMIT 1'
        );
        $stmt->execute([$sharedBudgetId, $userId]);

        return (bool) $stmt->fetchColumn();
    }

    public function delete($sharedBudgetId)
    {
        $stmt = $this->db->prepare(
            'DELETE FROM shared_budgets
             WHERE id = ?'
        );

        return $stmt->execute([$sharedBudgetId]);
    }
}
