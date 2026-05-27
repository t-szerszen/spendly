<?php

class SharedBudgetInvitation
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
            'INSERT INTO shared_budget_invitations
                (shared_budget_id, invited_email, invite_token, invited_by, status, expires_at)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['shared_budget_id'],
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
             FROM shared_budget_invitations
             WHERE invite_token = ?
             LIMIT 1'
        );
        $stmt->execute([$token]);

        return $stmt->fetch();
    }

    public function markAccepted($id)
    {
        $stmt = $this->db->prepare(
            'UPDATE shared_budget_invitations
             SET status = "accepted"
             WHERE id = ?'
        );

        return $stmt->execute([$id]);
    }

    public function markExpired($id)
    {
        $stmt = $this->db->prepare(
            'UPDATE shared_budget_invitations
             SET status = "expired"
             WHERE id = ?'
        );

        return $stmt->execute([$id]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare(
            'DELETE FROM shared_budget_invitations
             WHERE id = ?'
        );

        return $stmt->execute([$id]);
    }

    public function emailAlreadyInvited($sharedBudgetId, $email)
    {
        $stmt = $this->db->prepare(
            'SELECT 1
             FROM shared_budget_invitations
             WHERE shared_budget_id = ?
               AND invited_email = ?
               AND status = "pending"
               AND expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([$sharedBudgetId, $email]);

        return (bool) $stmt->fetchColumn();
    }

    public function showInvitedToSharedBudget($sharedBudgetId)
    {
        $this->deleteAcceptedAndExpired();

        $stmt = $this->db->prepare(
            'SELECT id, invited_email, expires_at
            FROM shared_budget_invitations 
            WHERE shared_budget_id = ?
              AND status = "pending"
            ORDER BY created_at DESC, id DESC'
        );
        $stmt->execute([$sharedBudgetId]);
        return $stmt->fetchAll();
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare(
            'SELECT *
             FROM shared_budget_invitations
             WHERE id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    public function deleteInvitation($id)
    {
        $stmt = $this->db->prepare(
            'DELETE FROM shared_budget_invitations
            WHERE id = ?
        '
        );
        return $stmt->execute([$id]);
    }

    private function deleteAcceptedAndExpired(): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM shared_budget_invitations
             WHERE status = "accepted"
                OR expires_at <= NOW()'
        );
        $stmt->execute();
    }
}
