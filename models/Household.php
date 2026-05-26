<?php

class Household
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare(
            'INSERT INTO households (name, owner_id, created_by)
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
                    (SELECT COUNT(*) FROM household_members hm WHERE hm.household_id = h.id) AS member_count
             FROM households h
             WHERE h.id = ?'
        );
        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    public function findByUser($userId)
    {
        $stmt = $this->db->prepare(
            'SELECT DISTINCT h.*,
                    (SELECT COUNT(*) FROM household_members hm WHERE hm.household_id = h.id) AS member_count
             FROM households h
             LEFT JOIN household_members hm ON hm.household_id = h.id
             WHERE h.created_by = ? OR hm.user_id = ?
             ORDER BY h.created_at DESC, h.id DESC'
        );
        $stmt->execute([$userId, $userId]);

        return $stmt->fetchAll();
    }

    public function userHasAccess($householdId, $userId)
    {
        $stmt = $this->db->prepare(
            'SELECT 1
             FROM households h
             LEFT JOIN household_members hm ON hm.household_id = h.id AND hm.user_id = ?
             WHERE h.id = ?
               AND (h.created_by = ? OR hm.user_id IS NOT NULL)
             LIMIT 1'
        );
        $stmt->execute([$userId, $householdId, $userId]);

        return (bool) $stmt->fetchColumn();
    }
}
