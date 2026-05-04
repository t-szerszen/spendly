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
            SELECT t.*, c.name as category_name 
            FROM transactions t 
            JOIN categories c ON t.category_id = c.id 
            WHERE t.user_id = ? 
            ORDER BY t.date DESC, t.id DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Pobiera wszystkie transakcje użytkownika.
     */
    public function getAllByUser($userId)
    {
        $stmt = $this->db->prepare("
            SELECT t.*, c.name as category_name 
            FROM transactions t 
            JOIN categories c ON t.category_id = c.id 
            WHERE t.user_id = ? 
            ORDER BY t.date DESC, t.id DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Dodaje nową transakcję.
     */
    public function add($data)
    {
        $stmt = $this->db->prepare("INSERT INTO transactions (user_id, category_id, amount, type, description, date) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['user_id'],
            $data['category_id'],
            $data['amount'],
            $data['type'],
            $data['description'],
            $data['date']
        ]);
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
