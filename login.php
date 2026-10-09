<?php
require __DIR__ . '/bootstrap.php';
Auth::requireGuest();

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = input_str($_POST, 'email');
    $password = input_raw($_POST, 'password');

    if ($email === '' || $password === '') {
        $errors[] = 'Email and password are required.';
    } else {
        $users = new UserRepository(Database::connect());
        $user = $users->findByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            Auth::login((int)$user['id'], $user['name']);
            redirect('index.php');
        }
        // One message for both cases, so nobody can find out which emails are registered.
        $errors[] = 'Invalid email or password.';
    }
}

$pageTitle = 'Login';
require __DIR__ . '/partials/header.php';
?>
<section class="form-card">
  <h1>Welcome back</h1>

  <?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endforeach; ?>

  <form method="post">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" value="<?= e($email) ?>" required>

    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>

    <button type="submit" class="btn">Log in</button>
  </form>
  <p>New here? <a href="register.php">Create an account</a></p>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>