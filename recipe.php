<?php
require __DIR__ . '/bootstrap.php';
Auth::requireLogin();

$db = Database::connect();
$recipes = new RecipeRepository($db);
$comments = new CommentRepository($db);

$id = (int)input_str($_GET, 'id');
$me = Auth::id();

$recipe = $recipes->find($id, $me);
if (!$recipe) {
    Auth::flash('That recipe was not found.', 'error');
    redirect('index.php');
}

$commentErrors = [];
$newComment = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = input_str($_POST, 'action');

    if ($action === 'add_comment') {
        $newComment = input_str($_POST, 'content');
        $commentErrors = Validator::comment($newComment);
        if (!$commentErrors) {
            if ($comments->create($id, $me, $newComment)) {
                Auth::flash('Comment posted.');
                redirect('recipe.php?id=' . $id . '#comments');
            }
            $commentErrors[] = 'That recipe no longer exists.';
        }

    } elseif ($action === 'delete_comment') {
        // The SQL also checks that the comment belongs to the logged-in member.
        $commentId = (int)input_str($_POST, 'comment_id');
        $comments->delete($commentId, $me);
        Auth::flash('Comment deleted.');
        redirect('recipe.php?id=' . $id . '#comments');

    } elseif ($action === 'delete_recipe') {
        // Only the owner's recipe is deleted. Its ingredients, comments and favorites go with it.
        if ($recipes->delete($id, $me)) {
            Auth::flash('Recipe deleted.');
        } else {
            Auth::flash('You can only delete your own recipes.', 'error');
        }
        redirect('index.php');
    }
}

$ingredients = $recipes->ingredientsOf($id);
$steps = array_values(array_filter(preg_split('/\r\n|\r|\n/', $recipe['steps']), function ($line) {
    return trim($line) !== '';
}));
$allComments = $comments->forRecipe($id);
$isOwner = (int)$recipe['user_id'] === $me;

$pageTitle = $recipe['title'];
require __DIR__ . '/partials/header.php';
?>
<article class="recipe-detail">
  <p><a href="index.php">&larr; All recipes</a></p>
  <span class="tag"><?= e($recipe['category']) ?></span>
  <h1><?= e($recipe['title']) ?></h1>
  <p class="muted">
    by <?= e($recipe['author']) ?> &middot; posted <?= e(show_date($recipe['created_at'])) ?>
    <?php if ($recipe['edited_at']): ?>&middot; <strong>edited <?= e(show_date($recipe['edited_at'])) ?></strong><?php endif; ?>
  </p>
  <p class="lead"><?= e($recipe['description']) ?></p>

  <div class="actions">
    <button type="button" class="fav-btn<?= $recipe['is_fav'] ? ' is-on' : '' ?>"
            data-recipe-id="<?= (int)$recipe['id'] ?>"
            aria-pressed="<?= $recipe['is_fav'] ? 'true' : 'false' ?>">
      <span class="fav-icon" aria-hidden="true"><?= $recipe['is_fav'] ? '&#9829;' : '&#9825;' ?></span>
      <span class="fav-label"><?= $recipe['is_fav'] ? 'Saved' : 'Save' ?></span>
    </button>
    <span class="muted"><span class="save-count" data-recipe-id="<?= (int)$recipe['id'] ?>"><?= (int)$recipe['saves'] ?></span> saved</span>

    <?php if ($isOwner): ?>
      <a class="btn btn-light btn-small" href="recipe-form.php?id=<?= (int)$recipe['id'] ?>">Edit</a>
      <form method="post" action="recipe.php?id=<?= (int)$recipe['id'] ?>" class="inline" data-confirm="Delete this recipe and all its comments?">
        <input type="hidden" name="action" value="delete_recipe">
        <button type="submit" class="btn btn-danger btn-small">Delete</button>
      </form>
    <?php endif; ?>
  </div>

  <div class="two-col">
    <section>
      <h2>Ingredients</h2>
      <ul class="ingredients">
        <?php foreach ($ingredients as $ing): ?>
          <li><?php if ($ing['quantity'] !== ''): ?><strong><?= e($ing['quantity']) ?></strong> <?php endif; ?><?= e($ing['name']) ?></li>
        <?php endforeach; ?>
      </ul>
    </section>
    <section>
      <h2>Cooking steps</h2>
      <ol class="steps">
        <?php foreach ($steps as $step): ?>
          <li><?= e($step) ?></li>
        <?php endforeach; ?>
      </ol>
    </section>
  </div>
</article>

<section id="comments" class="comments">
  <h2>Comments (<?= count($allComments) ?>)</h2>

  <?php if (!$allComments): ?>
    <p class="muted">No comments yet. Tell the cook what you think!</p>
  <?php endif; ?>

  <?php foreach ($allComments as $c): ?>
    <div class="comment">
      <p class="comment-head">
        <strong><?= e($c['author']) ?></strong>
        <span class="muted"><?= e(show_date($c['created_at'])) ?>
          <?php if ($c['edited_at']): ?>&middot; <strong>edited <?= e(show_date($c['edited_at'])) ?></strong><?php endif; ?>
        </span>
      </p>
      <p><?= nl2br(e($c['content'])) ?></p>
      <?php if ((int)$c['user_id'] === $me): ?>
        <div class="actions">
          <a class="btn btn-light btn-small" href="comment-edit.php?id=<?= (int)$c['id'] ?>">Edit</a>
          <form method="post" action="recipe.php?id=<?= (int)$recipe['id'] ?>" class="inline" data-confirm="Delete this comment?">
            <input type="hidden" name="action" value="delete_comment">
            <input type="hidden" name="comment_id" value="<?= (int)$c['id'] ?>">
            <button type="submit" class="btn btn-danger btn-small">Delete</button>
          </form>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

  <form method="post" action="recipe.php?id=<?= (int)$recipe['id'] ?>#comments" class="form-card">
    <input type="hidden" name="action" value="add_comment">
    <label for="content">Add a comment</label>
    <?php foreach ($commentErrors as $error): ?>
      <div class="alert alert-error"><?= e($error) ?></div>
    <?php endforeach; ?>
    <textarea id="content" name="content" rows="3" maxlength="1000" required><?= e($newComment) ?></textarea>
    <button type="submit" class="btn">Post comment</button>
  </form>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>