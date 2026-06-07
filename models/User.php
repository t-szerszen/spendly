<?php
// models/User.php

/**
 * Model User
 * 
 * Odpowiada za operacje na danych użytkowników.
 * Udostępnia metody wyszukiwania kont oraz tworzenia nowych rekordów
 * wykorzystywane przez AuthService.
 */
class User
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Pobiera użytkownika po adresie e-mail używanym przy logowaniu i rejestracji.
     */
    public function findByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function findById($id)
    {
        // Wyszukiwanie po identyfikatorze jest używane m.in. przy akceptacji zaproszeń.
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Tworzy nowe konto użytkownika z wcześniej przygotowanym hasłem.
     */
    public function create($data)
    {
        // Hasło przekazane do modelu powinno być już zahashowane w AuthService.
        $stmt = $this->db->prepare("INSERT INTO users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)");
        return $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['password']
        ]);
    }
}
