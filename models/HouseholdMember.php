<?php

class HouseholdMember
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function addMember($data)
    {
        $stmt = $this->db->prepare(
            'INSERT INTO household_members (household_id, user_id, share_percent, role)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                share_percent = VALUES(share_percent),
                role = VALUES(role)'
        );

        return $stmt->execute([
            $data['household_id'],
            $data['user_id'],
            $data['share_percent'] ?? 0,
            $data['role'] ?? 'member',
        ]);
    }

    public function getMembers($householdId)
    {
        $stmt = $this->db->prepare(
            'SELECT hm.id,
                    hm.household_id,
                    hm.user_id,
                    hm.share_percent,
                    hm.role,
                    hm.created_at,
                    u.first_name,
                    u.last_name,
                    u.email
             FROM household_members hm
             JOIN users u ON u.id = hm.user_id
             WHERE hm.household_id = ?
             ORDER BY hm.role DESC, u.first_name ASC, u.last_name ASC'
        );
        $stmt->execute([$householdId]);

        return $stmt->fetchAll();
    }

    public function getMember($householdId, $userId)
    {
        $stmt = $this->db->prepare(
            'SELECT hm.id,
                    hm.household_id,
                    hm.user_id,
                    hm.share_percent,
                    hm.role,
                    hm.created_at,
                    u.first_name,
                    u.last_name,
                    u.email
             FROM household_members hm
             JOIN users u ON u.id = hm.user_id
             WHERE hm.household_id = ? AND hm.user_id = ?
             LIMIT 1'
        );
        $stmt->execute([$householdId, $userId]);

        return $stmt->fetch();
    }

    public function updateShare($householdId, $userId, $sharePercent)
    {
        $stmt = $this->db->prepare(
            'UPDATE household_members
             SET share_percent = ?
             WHERE household_id = ? AND user_id = ?'
        );

        return $stmt->execute([$sharePercent, $householdId, $userId]);
    }

    public function getUserShare($householdId, $userId)
    {
        $stmt = $this->db->prepare(
            'SELECT share_percent
             FROM household_members
             WHERE household_id = ? AND user_id = ?'
        );
        $stmt->execute([$householdId, $userId]);

        return $stmt->fetchColumn();
    }

    public function countOwners($householdId)
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM household_members
             WHERE household_id = ? AND role = "owner"'
        );
        $stmt->execute([$householdId]);

        return (int) $stmt->fetchColumn();
    }

    public function deleteMember($householdId, $userId)
    {
        $stmt = $this->db->prepare(
            'DELETE FROM household_members
             WHERE household_id = ? AND user_id = ?'
        );

        return $stmt->execute([$householdId, $userId]);
    }
}
