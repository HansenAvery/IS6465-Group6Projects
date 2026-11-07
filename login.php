<?php
require __DIR__ . '/includes/functions.php';

if (is_logged_in()) { redirect('index.php'); }

const MAX_ATTEMPTS = 5;
const LOCK_SECONDS = 60;

$users = require __DIR__ . '/includes/users.php';
$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $lockedUntil = (int)($_SESSION['lock_until'] ?? 0);

    if ($lockedUntil > time()) {
        $error = 'Too many failed attempts. Try again in ' . ($lockedUntil - time()) . ' seconds.';
    } elseif ($email === '' || $password === '') {
        $error = 'Enter your email and password.';
    } else {
        $user = $users[$email] ?? null;
        // Verify against a dummy hash when the user is unknown so timing doesn't reveal valid emails.
        $hash = $user['hash'] ?? '$2y$10$usesomesillystringforsaltuQ0Qm0oQ3bq7mQmP1c8y6y1o4y8Z2e';
        if (password_verify($password, $hash) && $user) {
            session_regenerate_id(true);               // prevent session fixation
            $_SESSION['user'] = ['email' => $email, 'name' => $user['name']];
            unset($_SESSION['attempts'], $_SESSION['lock_until']);
            redirect('index.php');
        }
        $_SESSION['attempts'] = (int)($_SESSION['attempts'] ?? 0) + 1;
        if ($_SESSION['attempts'] >= MAX_ATTEMPTS) {
            $_SESSION['lock_until'] = time() + LOCK_SECONDS;
            $_SESSION['attempts'] = 0;
        }
        $error = 'Invalid email or password.';
    }
}

page_header('Log in', '', false);
?>
<div class="login-wrap">
  <h1>Log in to SmartPark</h1>
  <p class="sub">Sign in to find and reserve parking</p>
  <form method="post" action="login.php" class="panel" novalidate>
    <?= csrf_field() ?>
    <h2>Sign In</h2>
    <?php if ($error): ?><div class="form-error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <div class="form-row">
      <label class="field-label" for="email">Email</label>
      <input id="email" type="text" name="email" inputmode="email" autocomplete="username" value="<?= e($email) ?>" autofocus<?= $error ? ' class="invalid"' : '' ?>>
    </div>
    <div class="form-row">
      <label class="field-label" for="password">Password</label>
      <input id="password" type="password" name="password" autocomplete="current-password"<?= $error ? ' class="invalid"' : '' ?>>
    </div>
    <div class="actions" style="margin-top:8px"><button class="btn alt" type="submit">Log In</button></div>
  </form>
  <p class="demo-note">Demo account: <code>demo@smartpark.com</code> / <code>Park1234!</code></p>
</div>
<?php page_footer();
