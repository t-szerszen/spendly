<?php

class Category
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getCategories(?string $type = null)
    {
        if ($type === null) {
            $stmtCats = $this->db->query("SELECT id, name, type FROM categories ORDER BY name ASC");
            return $stmtCats->fetchAll();
        }

        $stmtCats = $this->db->prepare(
            "SELECT id, name, type FROM categories WHERE type = ? ORDER BY name ASC"
        );
        $stmtCats->execute([$type]);

        return $stmtCats->fetchAll();
    }
}
