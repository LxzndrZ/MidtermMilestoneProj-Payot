<?php
// Server-side rules. The browser also checks (HTML + JavaScript), but the browser can be
// bypassed, so these checks are the real protection. Each method returns a list of error messages.
class Validator
{
    const MAX_INGREDIENTS = 30;

    public static function register(string $name, string $email, string $password, string $confirm): array
    {
        $errors = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $errors[] = 'Name must be 2 to 100 characters.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
            $errors[] = 'Enter a valid email address.';
        }
        if (strlen($password) < 8 || strlen($password) > 72) {
            $errors[] = 'Password must be 8 to 72 characters.';
        } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            $errors[] = 'Password must have at least one letter and one number.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }
        return $errors;
    }

    // Turns the posted ingredient fields into clean rows. Fully blank rows are dropped.
    public static function cleanIngredients($names, $quantities): array
    {
        $rows = [];
        if (!is_array($names) || !is_array($quantities)) {
            return $rows;
        }
        foreach ($names as $i => $name) {
            $name = trim((string)$name);
            $qty = trim((string)($quantities[$i] ?? ''));
            if ($name === '' && $qty === '') {
                continue;
            }
            $rows[] = ['name' => $name, 'quantity' => $qty];
        }
        return $rows;
    }

    public static function recipe(string $title, string $description, int $categoryId, string $steps, array $ingredients, bool $categoryExists): array
    {
        $errors = [];
        if (mb_strlen($title) < 3 || mb_strlen($title) > 120) {
            $errors[] = 'Title must be 3 to 120 characters.';
        }
        if (mb_strlen($description) < 10 || mb_strlen($description) > 500) {
            $errors[] = 'Description must be 10 to 500 characters.';
        }
        if (!$categoryExists) {
            $errors[] = 'Please choose a category.';
        }
        if (mb_strlen($steps) < 10 || mb_strlen($steps) > 5000) {
            $errors[] = 'Cooking steps must be 10 to 5000 characters.';
        }
        if (count($ingredients) === 0) {
            $errors[] = 'Add at least one ingredient.';
        } elseif (count($ingredients) > self::MAX_INGREDIENTS) {
            $errors[] = 'A recipe can have at most ' . self::MAX_INGREDIENTS . ' ingredients.';
        } else {
            foreach ($ingredients as $row) {
                if ($row['name'] === '') {
                    $errors[] = 'Every ingredient needs a name.';
                    break;
                }
                if (mb_strlen($row['name']) > 100 || mb_strlen($row['quantity']) > 50) {
                    $errors[] = 'Ingredient name is max 100 characters and quantity is max 50.';
                    break;
                }
            }
        }
        return $errors;
    }

    public static function comment(string $content): array
    {
        $errors = [];
        if ($content === '') {
            $errors[] = 'Comment cannot be empty.';
        } elseif (mb_strlen($content) > 1000) {
            $errors[] = 'Comment is too long (max 1000 characters).';
        }
        return $errors;
    }
}