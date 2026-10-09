<?php
require __DIR__ . '/bootstrap.php';
Auth::requireGuest();

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = input_str($_POST, 'name');
    $email = input_str($_POST, 'email');
    $password = input_raw($_POST, 'password');
    $confirm = input_raw($_POST, 'confirm');

    $errors = Validator::register($name, $email, $password, $confirm);

    if (!$errors) {
        $users = new UserRepository(Database::connect());
        try {
            // Check-then-write, so both steps run in one transaction.
            $created = Database::transaction(function () use ($users, $name, $email, $password) {
                if ($users->findByEmail($email)) {
                    return false;
                }
                $users->create($name, $email, password_hash($password, PASSWORD_DEFAULT));
                return true;
            });
            if ($created) {
                Auth::flash('Account created. Please log in.');
                redirect('login.php');
            }
            $errors[] = 'That email is already registered.';
        } catch (PDOException $ex) {
            // The UNIQUE index on users.email is the last line of defense if two people race.
            $errors[] = 'Could not create the account. Please try again.';
        }
    }
}

$pageTitle = 'Register';
require __DIR__ . '/partials/header.php';
?>
<section class="form-card">
  <h1>Join the kitchen</h1>
  <p class="muted">Make an account to browse, share and save recipes.</p>

  <?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endforeach; ?>

  <form method="post" id="register-form">
    <label for="name">Name</label>
    <input type="text" id="name" name="name" value="<?= e($name) ?>" minlength="2" maxlength="100" required>

    <label for="email">Email</label>
    <input type="email" id="email" name="email" value="<?= e($email) ?>" maxlength="255" required>

    <label for="password">Password</label>
    <input type="password" id="password" name="password" minlength="8" maxlength="72"
           pattern="(?=.*[A-Za-z])(?=.*[0-9]).{8,72}"
           title="At least 8 characters with at least one letter and one number" required>
    <small class="muted">At least 8 characters, with a letter and a number.</small>

    <label for="confirm">Confirm password</label>
    <input type="password" id="confirm" name="confirm" required>

    <button type="submit" class="btn">Create account</button>
  </form>
  <p>Already a member? <a href="login.php">Log in</a></p>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>