<?php

/**
 * Model Category
 *
 * Odpowiada za pobieranie kategorii transakcji używanych w formularzach,
 * raportach oraz filtrowaniu według typu operacji finansowej.
 */
class Category
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Pobiera listę kategorii, opcjonalnie ograniczoną do wskazanego typu transakcji.
     */
    public function getCategories(?string $type = null)
    {
        // Brak typu oznacza pobranie pełnej listy kategorii.
        if ($type === null) {
            $stmtCats = $this->db->query("SELECT id, name, type FROM categories ORDER BY name ASC");
            return $stmtCats->fetchAll();
        }

        // Filtrowanie po typie pozwala dopasować kategorie do przychodów lub wydatków.
        $stmtCats = $this->db->prepare(
            "SELECT id, name, type FROM categories WHERE type = ? ORDER BY name ASC"
        );
        $stmtCats->execute([$type]);

        return $stmtCats->fetchAll();
    }
}
