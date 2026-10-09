<?php
// Top of every page. Set $pageTitle before including this file.
$flash = Auth::takeFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'Kusina ni Nanay') ?> | Kusina ni Nanay</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="site-header">
  <div class="container nav">
    <a class="brand" href="index.php">Kusina ni Nanay</a>
    <nav class="nav-links">
      <?php if (Auth::check()): ?>
        <a href="index.php">Recipes</a>
        <a href="recipe-form.php">Share a recipe</a>
        <a href="favorites.php">My favorites</a>
        <span class="who">Hi, <?= e(Auth::name()) ?></span>
        <a class="btn btn-small btn-light" href="logout.php">Logout</a>
      <?php else: ?>
        <a href="login.php">Login</a>
        <a class="btn btn-small btn-light" href="register.php">Register</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="container">
<?php if ($flash): ?>
  <div class="alert alert-<?= $flash['type'] === 'error' ? 'error' : 'ok' ?>" role="status"><?= e($flash['message']) ?></div>
<?php endif; ?>