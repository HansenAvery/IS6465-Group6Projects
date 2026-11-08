<?php
require __DIR__ . '/includes/functions.php';
require_login();

// DELETE (button on this view page)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['delete'] ?? 0);
    if ($id === (int)$_SESSION['user']['id']) {
        $_SESSION['flash'] = 'You cannot delete the account you are signed in with.';
    } else {
        $st = db()->prepare('DELETE FROM users WHERE id = ?');
        $st->execute([$id]);
        $_SESSION['flash'] = $st->rowCount() ? 'User deleted' : 'User not found';
    }
    redirect('users.php');
}

// READ
$users = db()->query('SELECT id, name, email, phone, created_at FROM users ORDER BY name COLLATE NOCASE')->fetchAll();
page_header('Users', 'users');
?>
<div class="page-top"><div><h1>Users</h1><p class="sub">View, add, update and delete user accounts</p></div>
  <a class="btn btn-primary" href="user_form.php">Add User</a></div>
<div class="table-responsive bg-white rounded shadow-sm">
<table class="table table-striped table-hover align-middle data-table mb-0">
  <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Created</th><th><span class="sr-only">Actions</span></th></tr></thead>
  <tbody>
  <?php if (!$users): ?><tr><td colspan="5" class="empty">No users yet.</td></tr><?php endif; ?>
  <?php foreach ($users as $u): $self = (int)$u['id'] === (int)$_SESSION['user']['id']; ?>
    <tr>
      <td><?= e($u['name']) ?><?= $self ? ' <span class="badge text-bg-secondary">You</span>' : '' ?></td>
      <td><?= e($u['email']) ?></td>
      <td><?= e($u['phone'] ?: '—') ?></td>
      <td><?= e(date('M j, Y', strtotime($u['created_at']))) ?></td>
      <td class="row-actions">
        <a class="btn btn-outline-primary btn-sm" href="user_form.php?id=<?= (int)$u['id'] ?>" aria-label="Edit <?= e($u['name']) ?>">Edit</a>
        <?php if (!$self): ?>
        <form method="post" action="users.php" onsubmit="return confirm('Delete <?= e(addslashes($u['name'])) ?>? This cannot be undone.');">
          <?= csrf_field() ?><input type="hidden" name="delete" value="<?= (int)$u['id'] ?>">
          <button class="btn btn-outline-danger btn-sm" type="submit" aria-label="Delete <?= e($u['name']) ?>">Delete</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php page_footer();
