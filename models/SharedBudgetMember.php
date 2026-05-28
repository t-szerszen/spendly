<?php

class SharedBudgetMember
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function addMember($data)
    {
        $stmt = $this->db->prepare(
            'INSERT INTO shared_budget_members (shared_budget_id, user_id, share_percent, role)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                share_percent = VALUES(share_percent),
                role = VALUES(role)'
        );

        return $stmt->execute([
            $data['shared_budget_id'],
            $data['user_id'],
            $data['share_percent'] ?? 0,
            $data['role'] ?? 'member',
        ]);
    }

    public function getMembers($sharedBudgetId)
    {
        $stmt = $this->db->prepare(
            'SELECT hm.id,
                    hm.shared_budget_id,
                    hm.user_id,
                    hm.share_percent,
                    hm.role,
                    hm.created_at,
                    u.first_name,
                    u.last_name,
                    u.email
             FROM shared_budget_members hm
             JOIN users u ON u.id = hm.user_id
             WHERE hm.shared_budget_id = ?
             ORDER BY hm.role DESC, u.first_name ASC, u.last_name ASC'
        );
        $stmt->execute([$sharedBudgetId]);

        return $stmt->fetchAll();
    }

    public function getMember($sharedBudgetId, $userId)
    {
        $stmt = $this->db->prepare(
            'SELECT hm.id,
                    hm.shared_budget_id,
                    hm.user_id,
                    hm.share_percent,
                    hm.role,
                    hm.created_at,
                    u.first_name,
                    u.last_name,
                    u.email
             FROM shared_budget_members hm
             JOIN users u ON u.id = hm.user_id
             WHERE hm.shared_budget_id = ? AND hm.user_id = ?
             LIMIT 1'
        );
        $stmt->execute([$sharedBudgetId, $userId]);

        return $stmt->fetch();
    }

    public function updateShare($sharedBudgetId, $userId, $sharePercent)
    {
        $stmt = $this->db->prepare(
            'UPDATE shared_budget_members
             SET share_percent = ?
             WHERE shared_budget_id = ? AND user_id = ?'
        );

        return $stmt->execute([$sharePercent, $sharedBudgetId, $userId]);
    }

    public function getUserShare($sharedBudgetId, $userId)
    {
        $stmt = $this->db->prepare(
            'SELECT share_percent
             FROM shared_budget_members
             WHERE shared_budget_id = ? AND user_id = ?'
        );
        $stmt->execute([$sharedBudgetId, $userId]);

        return $stmt->fetchColumn();
    }

    public function countOwners($sharedBudgetId)
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM shared_budget_members
             WHERE shared_budget_id = ? AND role = "owner"'
        );
        $stmt->execute([$sharedBudgetId]);

        return (int) $stmt->fetchColumn();
    }

    public function deleteMember($sharedBudgetId, $userId)
    {
        $stmt = $this->db->prepare(
            'DELETE FROM shared_budget_members
             WHERE shared_budget_id = ? AND user_id = ?'
        );

        return $stmt->execute([$sharedBudgetId, $userId]);
    }
}
