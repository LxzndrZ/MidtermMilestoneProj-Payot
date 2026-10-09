<?php
require __DIR__ . '/bootstrap.php';
Auth::requireLogin();

$comments = new CommentRepository(Database::connect());
$me = Auth::id();

$id = (int)input_str($_GET, 'id');
$comment = $comments->findOwned($id, $me); // only the owner can open this page
if (!$comment) {
    Auth::flash('Comment not found, or it is not yours to edit.', 'error');
    redirect('index.php');
}

$content = $comment['content'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = input_str($_POST, 'content');
    $errors = Validator::comment($content);

    if (!$errors) {
        $changed = $comments->update($id, $me, $content);
        Auth::flash($changed ? 'Comment updated.' : 'No changes were made.');
        redirect('recipe.php?id=' . (int)$comment['recipe_id'] . '#comments');
    }
}

$pageTitle = 'Edit comment';
require __DIR__ . '/partials/header.php';
?>
<section class="form-card">
  <h1>Edit your comment</h1>

  <?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endforeach; ?>

  <form method="post" action="comment-edit.php?id=<?= (int)$comment['id'] ?>">
    <label for="content">Comment</label>
    <textarea id="content" name="content" rows="4" maxlength="1000" required><?= e($content) ?></textarea>
    <button type="submit" class="btn">Save changes</button>
    <a class="btn btn-light" href="recipe.php?id=<?= (int)$comment['recipe_id'] ?>#comments">Cancel</a>
  </form>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>