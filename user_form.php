<?php
require __DIR__ . '/includes/functions.php';
require_login();

// CREATE (no id) and UPDATE (?id=)
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editing = $id > 0;
$row = ['name' => '', 'email' => '', 'phone' => ''];
if ($editing) {
    $st = db()->prepare('SELECT id, name, email, phone FROM users WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) { $_SESSION['flash'] = 'User not found'; redirect('users.php'); }
}
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $row['name']  = trim((string)($_POST['name'] ?? ''));
    $row['email'] = strtolower(trim((string)($_POST['email'] ?? '')));
    $row['phone'] = trim((string)($_POST['phone'] ?? ''));
    $pw  = (string)($_POST['password'] ?? '');
    $pw2 = (string)($_POST['password2'] ?? '');

    if (mb_strlen($row['name']) < 2 || mb_strlen($row['name']) > 80) { $errors['name'] = 'Enter a name (2–80 characters).'; }
    if (!filter_var($row['email'], FILTER_VALIDATE_EMAIL) || strlen($row['email']) > 120) { $errors['email'] = 'Enter a valid email address.'; }
    else {
        $st = db()->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
        $st->execute([$row['email'], $id]);
        if ($st->fetch()) { $errors['email'] = 'That email is already in use.'; }
    }
    if ($row['phone'] !== '' && !preg_match('/^[0-9 ()+\-.]{7,20}$/', $row['phone'])) { $errors['phone'] = 'Enter a valid phone number or leave blank.'; }
    if (!$editing || $pw !== '') {
        if (strlen($pw) < 8) { $errors['password'] = 'Password must be at least 8 characters.'; }
        elseif ($pw !== $pw2) { $errors['password2'] = 'Passwords do not match.'; }
    }

    if (!$errors) {
        if ($editing) {
            if ($pw !== '') {
                db()->prepare('UPDATE users SET name=?, email=?, phone=?, password_hash=? WHERE id=?')
                    ->execute([$row['name'], $row['email'], $row['phone'], password_hash($pw, PASSWORD_DEFAULT), $id]);
            } else {
                db()->prepare('UPDATE users SET name=?, email=?, phone=? WHERE id=?')
                    ->execute([$row['name'], $row['email'], $row['phone'], $id]);
            }
            if ($id === (int)$_SESSION['user']['id']) {   // keep header/session in sync
                $_SESSION['user']['name'] = $row['name']; $_SESSION['user']['email'] = $row['email'];
            }
            $_SESSION['flash'] = 'User updated';
        } else {
            db()->prepare('INSERT INTO users (name, email, phone, password_hash) VALUES (?, ?, ?, ?)')
                ->execute([$row['name'], $row['email'], $row['phone'], password_hash($pw, PASSWORD_DEFAULT)]);
            $_SESSION['flash'] = 'User added';
        }
        redirect('users.php');
    }
}

$err = fn(string $k) => isset($errors[$k]) ? '<div class="error" role="alert">' . e($errors[$k]) . '</div>' : '<div class="error"></div>';
$cls = fn(string $k) => isset($errors[$k]) ? ' class="invalid"' : '';
page_header($editing ? 'Update User' : 'Add User', 'users');
?>
<h1><?= $editing ? 'Update User' : 'Add User' ?></h1>
<p class="sub"><?= $editing ? 'Change the details below. Leave the password blank to keep the current one.' : 'Create a new user account' ?></p>
<form method="post" action="user_form.php<?= $editing ? '?id=' . $id : '' ?>" novalidate>
  <?= csrf_field() ?>
  <div class="panel">
    <h2><?= $editing ? 'User Details' : 'New User' ?></h2>
    <div class="form-row"><label class="field-label" for="name">Name</label>
      <input id="name" name="name" type="text" maxlength="80" value="<?= e($row['name']) ?>"<?= $cls('name') ?>><?= $err('name') ?></div>
    <div class="form-grid">
      <div class="form-row"><label class="field-label" for="email">Email</label>
        <input id="email" name="email" type="text" inputmode="email" maxlength="120" value="<?= e($row['email']) ?>"<?= $cls('email') ?>><?= $err('email') ?></div>
      <div class="form-row"><label class="field-label" for="phone">Phone (optional)</label>
        <input id="phone" name="phone" type="tel" maxlength="20" value="<?= e($row['phone']) ?>"<?= $cls('phone') ?>><?= $err('phone') ?></div>
    </div>
    <div class="form-grid">
      <div class="form-row"><label class="field-label" for="password"><?= $editing ? 'New Password' : 'Password' ?></label>
        <input id="password" name="password" type="password" autocomplete="new-password"<?= $cls('password') ?>><?= $err('password') ?></div>
      <div class="form-row"><label class="field-label" for="password2">Confirm Password</label>
        <input id="password2" name="password2" type="password" autocomplete="new-password"<?= $cls('password2') ?>><?= $err('password2') ?></div>
    </div>
  </div>
  <div class="actions"><a class="btn" href="users.php">Cancel</a>
    <button class="btn alt" type="submit"><?= $editing ? 'Save Changes' : 'Add User' ?></button></div>
</form>
<?php page_footer();
