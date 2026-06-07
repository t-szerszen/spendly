<?php

/**
 * Model SharedBudgetInvitation
 *
 * Obsługuje zaproszenia email do wspólnych budżetów:
 * tworzenie tokenów, weryfikację statusu oraz czyszczenie nieaktywnych rekordów.
 */
class SharedBudgetInvitation
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data)
    {
        // Przed utworzeniem nowego zaproszenia usuwane są rekordy zaakceptowane i wygasłe.
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
        // Token jest głównym identyfikatorem procesu akceptacji zaproszenia.
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
        // Blokuje wysłanie drugiego aktywnego zaproszenia na ten sam adres.
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
        // Zwraca aktywne zaproszenia widoczne w panelu zarządzania członkami.
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
        // Utrzymuje listę zaproszeń w stanie obejmującym tylko rekordy wymagające obsługi.
        $stmt = $this->db->prepare(
            'DELETE FROM shared_budget_invitations
             WHERE status = "accepted"
                OR expires_at <= NOW()'
        );
        $stmt->execute();
    }
}
