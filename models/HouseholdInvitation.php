<?php

class HouseholdInvitation
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data)
    {
        $this->deleteAcceptedAndExpired();

        $stmt = $this->db->prepare(
            'INSERT INTO household_invitations
                (household_id, invited_email, invite_token, invited_by, status, expires_at)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['household_id'],
            $data['invited_email'],
            $data['invite_token'],
            $data['invited_by'],
            $data['status'] ?? 'pending',
            $data['expires_at'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findByToken($token)
    {
        $stmt = $this->db->prepare(
            'SELECT *
             FROM household_invitations
             WHERE invite_token = ?
             LIMIT 1'
        );
        $stmt->execute([$token]);

        return $stmt->fetch();
    }

    public function markAccepted($id)
    {
        $stmt = $this->db->prepare(
            'UPDATE household_invitations
             SET status = "accepted"
             WHERE id = ?'
        );

        return $stmt->execute([$id]);
    }

    public function markExpired($id)
    {
        $stmt = $this->db->prepare(
            'UPDATE household_invitations
             SET status = "expired"
             WHERE id = ?'
        );

        return $stmt->execute([$id]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare(
            'DELETE FROM household_invitations
             WHERE id = ?'
        );

        return $stmt->execute([$id]);
    }

    public function emailAlreadyInvited($householdId, $email)
    {
        $stmt = $this->db->prepare(
            'SELECT 1
             FROM household_invitations
             WHERE household_id = ?
               AND invited_email = ?
               AND status = "pending"
               AND expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([$householdId, $email]);

        return (bool) $stmt->fetchColumn();
    }

    private function deleteAcceptedAndExpired(): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM household_invitations
             WHERE status = "accepted"
                OR expires_at <= NOW()'
        );
        $stmt->execute();
    }
}
