<?php
require __DIR__ . '/bootstrap.php';
Auth::requireLogin();

$db = Database::connect();
$recipes = new RecipeRepository($db);
$categories = new CategoryRepository($db);

// Search filters come from the URL (GET), so a search can be bookmarked or shared.
$q = input_str($_GET, 'q');
$categoryId = (int)input_str($_GET, 'category');
$sort = input_str($_GET, 'sort') === 'saved' ? 'saved' : 'latest';

$results = $recipes->search(Auth::id(), $q, $categoryId, $sort);
$allCategories = $categories->all();

$pageTitle = 'Recipes';
require __DIR__ . '/partials/header.php';
?>
<h1>Recipes from our kitchens</h1>

<form method="get" class="filter-bar" role="search">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search by keyword or ingredient..." maxlength="100" aria-label="Keyword">
  <select name="category" aria-label="Category">
    <option value="0">All categories</option>
    <?php foreach ($allCategories as $cat): ?>
      <option value="<?= (int)$cat['id'] ?>" <?= $categoryId === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="sort" aria-label="Sort">
    <option value="latest" <?= $sort === 'latest' ? 'selected' : '' ?>>Latest first</option>
    <option value="saved" <?= $sort === 'saved' ? 'selected' : '' ?>>Most saved</option>
  </select>
  <button type="submit" class="btn">Search</button>
  <?php if ($q !== '' || $categoryId > 0 || $sort === 'saved'): ?>
    <a class="btn btn-light" href="index.php">Clear</a>
  <?php endif; ?>
</form>

<p class="muted"><?= count($results) ?> recipe<?= count($results) === 1 ? '' : 's' ?> found</p>

<?php if (!$results): ?>
  <div class="empty">No recipes found. <a href="recipe-form.php">Be the first to share one!</a></div>
<?php else: ?>
  <div class="grid">
    <?php foreach ($results as $r): ?>
      <?php require __DIR__ . '/partials/recipe-card.php'; ?>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>