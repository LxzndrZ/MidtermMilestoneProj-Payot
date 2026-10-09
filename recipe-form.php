<?php
require __DIR__ . '/bootstrap.php';
Auth::requireLogin();

$db = Database::connect();
$recipes = new RecipeRepository($db);
$categories = new CategoryRepository($db);
$me = Auth::id();

// recipe-form.php = share a new recipe. recipe-form.php?id=5 = edit recipe 5.
$id = (int)input_str($_GET, 'id');
$editing = $id > 0;

$title = '';
$description = '';
$categoryId = 0;
$steps = '';
$rows = [];

if ($editing) {
    $existing = $recipes->findOwned($id, $me); // only the owner can open the edit form
    if (!$existing) {
        Auth::flash('Recipe not found, or it is not yours to edit.', 'error');
        redirect('index.php');
    }
    $title = $existing['title'];
    $description = $existing['description'];
    $categoryId = (int)$existing['category_id'];
    $steps = $existing['steps'];
    $rows = $recipes->ingredientsOf($id);
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = input_str($_POST, 'title');
    $description = input_str($_POST, 'description');
    $categoryId = (int)input_str($_POST, 'category_id');
    $steps = input_str($_POST, 'steps');
    $rows = Validator::cleanIngredients($_POST['ingredient_name'] ?? [], $_POST['ingredient_qty'] ?? []);

    $errors = Validator::recipe($title, $description, $categoryId, $steps, $rows, $categories->exists($categoryId));

    if (!$errors) {
        $data = [
            'category_id' => $categoryId,
            'title' => $title,
            'description' => $description,
            'steps' => $steps,
        ];
        if ($editing) {
            $result = $recipes->update($id, $me, $data, $rows);
            if ($result === 'missing') {
                Auth::flash('You can only edit your own recipes.', 'error');
                redirect('index.php');
            }
            Auth::flash($result === 'updated' ? 'Recipe updated.' : 'No changes were made.');
            redirect('recipe.php?id=' . $id);
        }
        $newId = $recipes->create($me, $data, $rows);
        Auth::flash('Recipe shared. Thank you!');
        redirect('recipe.php?id=' . $newId);
    }
}

if (!$rows) {
    $rows = [['name' => '', 'quantity' => ''], ['name' => '', 'quantity' => ''], ['name' => '', 'quantity' => '']];
}
$allCategories = $categories->all();

$pageTitle = $editing ? 'Edit recipe' : 'Share a recipe';
require __DIR__ . '/partials/header.php';
?>
<section class="form-card wide">
  <h1><?= $editing ? 'Edit your recipe' : 'Share a recipe' ?></h1>

  <?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endforeach; ?>

  <form method="post" id="recipe-form" action="recipe-form.php<?= $editing ? '?id=' . $id : '' ?>">
    <label for="title">Recipe title</label>
    <input type="text" id="title" name="title" value="<?= e($title) ?>" minlength="3" maxlength="120" required>

    <label for="description">Short description</label>
    <textarea id="description" name="description" rows="2" minlength="10" maxlength="500" required><?= e($description) ?></textarea>

    <label for="category_id">Category</label>
    <select id="category_id" name="category_id" required>
      <option value="">Choose a category...</option>
      <?php foreach ($allCategories as $cat): ?>
        <option value="<?= (int)$cat['id'] ?>" <?= $categoryId === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <fieldset>
      <legend>Ingredients</legend>
      <p class="muted">Amount first (like "2 cups"), then the ingredient. Press "Add ingredient" for more rows.</p>
      <div id="ingredient-rows">
        <?php foreach ($rows as $row): ?>
          <div class="ingredient-row">
            <input type="text" name="ingredient_qty[]" value="<?= e($row['quantity']) ?>" placeholder="Amount" maxlength="50" aria-label="Amount">
            <input type="text" name="ingredient_name[]" value="<?= e($row['name']) ?>" placeholder="Ingredient" maxlength="100" aria-label="Ingredient">
            <button type="button" class="btn btn-light btn-small remove-ingredient" aria-label="Remove this ingredient">&times;</button>
          </div>
        <?php endforeach; ?>
      </div>
      <p id="ingredient-error" class="field-error" role="alert" hidden></p>
      <button type="button" id="add-ingredient" class="btn btn-light btn-small">+ Add ingredient</button>
    </fieldset>

    <label for="steps">Cooking steps (one step per line)</label>
    <textarea id="steps" name="steps" rows="7" minlength="10" maxlength="5000" required><?= e($steps) ?></textarea>

    <button type="submit" class="btn"><?= $editing ? 'Save changes' : 'Post recipe' ?></button>
    <a class="btn btn-light" href="<?= $editing ? 'recipe.php?id=' . $id : 'index.php' ?>">Cancel</a>
  </form>

  <template id="ingredient-template">
    <div class="ingredient-row">
      <input type="text" name="ingredient_qty[]" placeholder="Amount" maxlength="50" aria-label="Amount">
      <input type="text" name="ingredient_name[]" placeholder="Ingredient" maxlength="100" aria-label="Ingredient">
      <button type="button" class="btn btn-light btn-small remove-ingredient" aria-label="Remove this ingredient">&times;</button>
    </div>
  </template>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>