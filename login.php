<?php
require __DIR__ . '/includes/functions.php';

if (is_logged_in()) { redirect('index.php'); }

const MAX_ATTEMPTS = 5;
const LOCK_SECONDS = 60;

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
        $st = db()->prepare('SELECT id, name, password_hash FROM users WHERE email = ?');
        $st->execute([$email]);
        $user = $st->fetch() ?: null;
        // Verify against a dummy hash when the user is unknown so timing doesn't reveal valid emails.
        $hash = $user['password_hash'] ?? '$2y$10$usesomesillystringforsaltuQ0Qm0oQ3bq7mQmP1c8y6y1o4y8Z2e';
        if (password_verify($password, $hash) && $user) {
            session_regenerate_id(true);               // prevent session fixation
            $_SESSION['user'] = ['id' => (int)$user['id'], 'email' => $email, 'name' => $user['name']];
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
  <form method="post" action="login.php" class="card border-0 shadow-sm p-4" novalidate>
    <?= csrf_field() ?>
    <h2 class="card-title text-center mb-3">Sign In</h2>
    <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
    <div class="form-row">
      <label class="form-label" for="email">Email</label>
      <input id="email" class="form-control<?= $error ? ' is-invalid' : '' ?>" type="text" name="email" inputmode="email" autocomplete="username" value="<?= e($email) ?>" autofocus>
    </div>
    <div class="form-row">
      <label class="form-label" for="password">Password</label>
      <input id="password" class="form-control<?= $error ? ' is-invalid' : '' ?>" type="password" name="password" autocomplete="current-password">
    </div>
    <div class="actions mt-2"><button class="btn btn-primary" type="submit">Log In</button></div>
  </form>
  <p class="demo-note">Demo account: <code>demo@smartpark.com</code> / <code>Park1234!</code></p>
</div>
<?php page_footer();
