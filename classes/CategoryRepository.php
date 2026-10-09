<?php
// All SQL for the categories table.
class CategoryRepository
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function all(): array
    {
        return $this->db->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM categories WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return (bool)$stmt->fetchColumn();
    }
}