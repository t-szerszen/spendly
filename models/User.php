<?php
// models/User.php

/**
 * Model User
 * 
 * Odpowiada za komunikację z tabelą 'users' w bazie danych.
 */
class User
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Znajduje użytkownika po adresie email.
     */
    public function findByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    /**
     * Tworzy nowego użytkownika.
     */
    public function create($data)
    {
        $stmt = $this->db->prepare("INSERT INTO users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)");
        return $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['password']
        ]);
    }
}
