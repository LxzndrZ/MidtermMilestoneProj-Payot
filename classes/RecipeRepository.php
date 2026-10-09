<?php
// All SQL for recipes and their ingredients.
class RecipeRepository
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // The SELECT used by every recipe list and detail page.
    // It joins users (author) and categories, counts how many members saved the recipe,
    // and says whether the current member (:me) saved it.
    private function baseSql(): string
    {
        return 'SELECT r.id, r.user_id, r.category_id, r.title, r.description, r.steps,
                       r.created_at, r.edited_at,
                       u.name AS author, c.name AS category,
                       (SELECT COUNT(*) FROM favorites f WHERE f.recipe_id = r.id) AS saves,
                       EXISTS (SELECT 1 FROM favorites f2
                               WHERE f2.recipe_id = r.id AND f2.user_id = :me) AS is_fav
                FROM recipes r
                INNER JOIN users u ON u.id = r.user_id
                INNER JOIN categories c ON c.id = r.category_id';
    }

    // Home page: newest first, optional keyword and category filters, optional "most saved" sort.
    public function search(int $me, string $keyword, int $categoryId, string $sort): array
    {
        $sql = $this->baseSql();
        $params = ['me' => $me];
        $where = [];

        if ($keyword !== '') {
            // The keyword is matched against the title, the description and the ingredient names.
            // It travels as a parameter, never inside the SQL text. We escape % and _ so they
            // are searched as normal characters.
            $like = '%' . addcslashes($keyword, '%_\\') . '%';
            $where[] = '(r.title LIKE :q1 OR r.description LIKE :q2
                         OR EXISTS (SELECT 1 FROM ingredients i WHERE i.recipe_id = r.id AND i.name LIKE :q3))';
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
        }
        if ($categoryId > 0) {
            $where[] = 'r.category_id = :cat';
            $params['cat'] = $categoryId;
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        // Only these two fixed orders are possible. User input never goes into ORDER BY.
        if ($sort === 'saved') {
            $sql .= ' ORDER BY saves DESC, r.created_at DESC, r.id DESC';
        } else {
            $sql .= ' ORDER BY r.created_at DESC, r.id DESC';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // The member's favorites list, most recently saved first.
    public function favoritesOf(int $userId): array
    {
        $sql = $this->baseSql() . '
                INNER JOIN favorites mine ON mine.recipe_id = r.id AND mine.user_id = :mine
                ORDER BY mine.created_at DESC, r.id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['me' => $userId, 'mine' => $userId]);
        return $stmt->fetchAll();
    }

    public function find(int $id, int $me)
    {
        $stmt = $this->db->prepare($this->baseSql() . ' WHERE r.id = :id');
        $stmt->execute(['me' => $me, 'id' => $id]);
        return $stmt->fetch(); // the row, or false
    }

    // The recipe, but only if it belongs to this member.
    public function findOwned(int $id, int $userId)
    {
        $stmt = $this->db->prepare('SELECT * FROM recipes WHERE id = :id AND user_id = :uid');
        $stmt->execute(['id' => $id, 'uid' => $userId]);
        return $stmt->fetch();
    }

    public function ingredientsOf(int $recipeId): array
    {
        $stmt = $this->db->prepare('SELECT name, quantity FROM ingredients WHERE recipe_id = :rid ORDER BY id');
        $stmt->execute(['rid' => $recipeId]);
        return $stmt->fetchAll();
    }

    // Saves the recipe and all its ingredients together. If anything fails, nothing is saved.
    public function create(int $userId, array $data, array $ingredients): int
    {
        return Database::transaction(function () use ($userId, $data, $ingredients) {
            $stmt = $this->db->prepare(
                'INSERT INTO recipes (user_id, category_id, title, description, steps)
                 VALUES (:uid, :cat, :title, :description, :steps)'
            );
            $stmt->execute([
                'uid' => $userId,
                'cat' => $data['category_id'],
                'title' => $data['title'],
                'description' => $data['description'],
                'steps' => $data['steps'],
            ]);
            $recipeId = (int)$this->db->lastInsertId();
            $this->insertIngredients($recipeId, $ingredients);
            return $recipeId;
        });
    }

    // Returns 'missing' (not yours or not found), 'unchanged', or 'updated'.
    // edited_at is only set when something really changed.
    public function update(int $id, int $userId, array $data, array $ingredients): string
    {
        return Database::transaction(function () use ($id, $userId, $data, $ingredients) {
            $stmt = $this->db->prepare('SELECT * FROM recipes WHERE id = :id AND user_id = :uid FOR UPDATE');
            $stmt->execute(['id' => $id, 'uid' => $userId]);
            $current = $stmt->fetch();
            if (!$current) {
                return 'missing';
            }

            $same = $current['title'] === $data['title']
                && $current['description'] === $data['description']
                && (int)$current['category_id'] === (int)$data['category_id']
                && $current['steps'] === $data['steps']
                && $this->ingredientsOf($id) === $ingredients;
            if ($same) {
                return 'unchanged';
            }

            $stmt = $this->db->prepare(
                'UPDATE recipes
                 SET category_id = :cat, title = :title, description = :description,
                     steps = :steps, edited_at = NOW()
                 WHERE id = :id AND user_id = :uid'
            );
            $stmt->execute([
                'cat' => $data['category_id'],
                'title' => $data['title'],
                'description' => $data['description'],
                'steps' => $data['steps'],
                'id' => $id,
                'uid' => $userId,
            ]);

            $stmt = $this->db->prepare('DELETE FROM ingredients WHERE recipe_id = :rid');
            $stmt->execute(['rid' => $id]);
            $this->insertIngredients($id, $ingredients);
            return 'updated';
        });
    }

    // Deletes only if the recipe is the member's. Its ingredients, comments and favorites
    // are removed by ON DELETE CASCADE.
    public function delete(int $id, int $userId): bool
    {
        return Database::transaction(function () use ($id, $userId) {
            $stmt = $this->db->prepare('DELETE FROM recipes WHERE id = :id AND user_id = :uid');
            $stmt->execute(['id' => $id, 'uid' => $userId]);
            return $stmt->rowCount() > 0;
        });
    }

    private function insertIngredients(int $recipeId, array $ingredients): void
    {
        $stmt = $this->db->prepare('INSERT INTO ingredients (recipe_id, name, quantity) VALUES (:rid, :name, :qty)');
        foreach ($ingredients as $row) {
            $stmt->execute(['rid' => $recipeId, 'name' => $row['name'], 'qty' => $row['quantity']]);
        }
    }
}