<?php

class Category
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getCategories()
    {
        $stmtCats = $this->db->query("SELECT id, name FROM categories ORDER BY name ASC");
        return $stmtCats->fetchAll();
    }
}
