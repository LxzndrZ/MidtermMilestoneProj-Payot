<?php
require __DIR__ . '/bootstrap.php';
Auth::requireLogin();

$recipes = new RecipeRepository(Database::connect());
$results = $recipes->favoritesOf(Auth::id());

$pageTitle = 'My favorites';
require __DIR__ . '/partials/header.php';
?>
<h1>My favorites</h1>

<!-- data-remove-on-unfav: when you un-save a recipe here, its card disappears without reloading. -->
<div class="grid" id="favorites-grid" data-remove-on-unfav="1">
  <?php foreach ($results as $r): ?>
    <?php require __DIR__ . '/partials/recipe-card.php'; ?>
  <?php endforeach; ?>
</div>

<div class="empty" id="empty-favorites" <?= $results ? 'hidden' : '' ?>>
  You have no saved recipes yet. <a href="index.php">Browse recipes</a> and press Save on the ones you like.
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>