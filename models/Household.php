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
            'SELECT h.*,
                    (SELECT COUNT(*) FROM household_members hm WHERE hm.household_id = h.id) AS member_count
             FROM households h
             JOIN household_members hm ON hm.household_id = h.id
             WHERE hm.user_id = ?
             ORDER BY h.created_at DESC, h.id DESC'
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    public function userHasAccess($householdId, $userId)
    {
        $stmt = $this->db->prepare(
            'SELECT 1
             FROM household_members
             WHERE household_id = ?
               AND user_id = ?
             LIMIT 1'
        );
        $stmt->execute([$householdId, $userId]);

        return (bool) $stmt->fetchColumn();
    }

    public function delete($householdId)
    {
        $stmt = $this->db->prepare(
            'DELETE FROM households
             WHERE id = ?'
        );

        return $stmt->execute([$householdId]);
    }
}
