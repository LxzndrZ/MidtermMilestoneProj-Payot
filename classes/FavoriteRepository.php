<?php
// All SQL for the favorites table (which member saved which recipe).
class FavoriteRepository
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Saves the recipe if it is not saved yet, removes it if it is.
    // Returns true (now saved), false (now removed), or null (recipe does not exist).
    public function toggle(int $userId, int $recipeId): ?bool
    {
        return Database::transaction(function () use ($userId, $recipeId) {
            $stmt = $this->db->prepare('SELECT 1 FROM recipes WHERE id = :rid');
            $stmt->execute(['rid' => $recipeId]);
            if (!$stmt->fetchColumn()) {
                return null;
            }

            $stmt = $this->db->prepare('SELECT 1 FROM favorites WHERE user_id = :uid AND recipe_id = :rid');
            $stmt->execute(['uid' => $userId, 'rid' => $recipeId]);
            if ($stmt->fetchColumn()) {
                $stmt = $this->db->prepare('DELETE FROM favorites WHERE user_id = :uid AND recipe_id = :rid');
                $stmt->execute(['uid' => $userId, 'rid' => $recipeId]);
                return false;
            }

            $stmt = $this->db->prepare('INSERT INTO favorites (user_id, recipe_id) VALUES (:uid, :rid)');
            $stmt->execute(['uid' => $userId, 'rid' => $recipeId]);
            return true;
        });
    }

    public function countFor(int $recipeId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM favorites WHERE recipe_id = :rid');
        $stmt->execute(['rid' => $recipeId]);
        return (int)$stmt->fetchColumn();
    }
}