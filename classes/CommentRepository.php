<?php
// All SQL for the comments table.
class CommentRepository
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function forRecipe(int $recipeId): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.id, c.user_id, c.content, c.created_at, c.edited_at, u.name AS author
             FROM comments c
             INNER JOIN users u ON u.id = c.user_id
             WHERE c.recipe_id = :rid
             ORDER BY c.created_at ASC, c.id ASC'
        );
        $stmt->execute(['rid' => $recipeId]);
        return $stmt->fetchAll();
    }

    // The comment, but only if it belongs to this member.
    public function findOwned(int $id, int $userId)
    {
        $stmt = $this->db->prepare('SELECT * FROM comments WHERE id = :id AND user_id = :uid');
        $stmt->execute(['id' => $id, 'uid' => $userId]);
        return $stmt->fetch();
    }

    // Returns false if the recipe no longer exists.
    public function create(int $recipeId, int $userId, string $content): bool
    {
        return Database::transaction(function () use ($recipeId, $userId, $content) {
            $stmt = $this->db->prepare('SELECT 1 FROM recipes WHERE id = :id');
            $stmt->execute(['id' => $recipeId]);
            if (!$stmt->fetchColumn()) {
                return false;
            }
            $stmt = $this->db->prepare(
                'INSERT INTO comments (recipe_id, user_id, content) VALUES (:rid, :uid, :content)'
            );
            $stmt->execute(['rid' => $recipeId, 'uid' => $userId, 'content' => $content]);
            return true;
        });
    }

    // Only the owner can change it, and edited_at is only set when the text really changed.
    // Returns true if it was changed, false if nothing changed (or it is not the member's).
    public function update(int $id, int $userId, string $content): bool
    {
        return Database::transaction(function () use ($id, $userId, $content) {
            $stmt = $this->db->prepare('SELECT content FROM comments WHERE id = :id AND user_id = :uid FOR UPDATE');
            $stmt->execute(['id' => $id, 'uid' => $userId]);
            $current = $stmt->fetchColumn();
            if ($current === false || $current === $content) {
                return false;
            }
            $stmt = $this->db->prepare(
                'UPDATE comments SET content = :content, edited_at = NOW()
                 WHERE id = :id AND user_id = :uid'
            );
            $stmt->execute(['content' => $content, 'id' => $id, 'uid' => $userId]);
            return true;
        });
    }

    public function delete(int $id, int $userId): bool
    {
        return Database::transaction(function () use ($id, $userId) {
            $stmt = $this->db->prepare('DELETE FROM comments WHERE id = :id AND user_id = :uid');
            $stmt->execute(['id' => $id, 'uid' => $userId]);
            return $stmt->rowCount() > 0;
        });
    }
}