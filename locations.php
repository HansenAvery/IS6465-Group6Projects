<?php
require __DIR__ . '/includes/functions.php';
require_login();

// DELETE (button on this view page)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $st = db()->prepare('DELETE FROM locations WHERE id = ?');
    $st->execute([(int)($_POST['delete'] ?? 0)]);
    unset($_SESSION['draft']);                      // a draft may point at a deleted location
    $_SESSION['flash'] = $st->rowCount() ? 'Parking location deleted' : 'Parking location not found';
    redirect('locations.php');
}

// READ
$locs = locations();
page_header('Parking Locations', 'locations');
?>
<div class="page-top"><div><h1>Parking Locations</h1><p class="sub">View, add, update and delete the locations drivers can reserve</p></div>
  <a class="btn-rect" href="location_form.php">Add Location</a></div>
<table class="data-table">
  <thead><tr><th>Name</th><th>Address</th><th>Rate</th><th>Map position</th><th><span class="sr-only">Actions</span></th></tr></thead>
  <tbody>
  <?php if (!$locs): ?><tr><td colspan="5" class="empty">No parking locations yet. Add one so drivers can reserve it.</td></tr><?php endif; ?>
  <?php foreach ($locs as $id => $l): ?>
    <tr>
      <td><?= e($l['name']) ?></td>
      <td><?= e($l['address']) ?></td>
      <td><?= money($l['rate']) ?> / HR</td>
      <td><?= $l['x'] ?>%, <?= $l['y'] ?>%</td>
      <td class="row-actions">
        <a class="btn-sm" href="location_form.php?id=<?= $id ?>" aria-label="Edit <?= e($l['name']) ?>">Edit</a>
        <form method="post" action="locations.php" onsubmit="return confirm('Delete <?= e(addslashes($l['name'])) ?>? This cannot be undone.');">
          <?= csrf_field() ?><input type="hidden" name="delete" value="<?= $id ?>">
          <button class="btn-sm danger" type="submit" aria-label="Delete <?= e($l['name']) ?>">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php page_footer();
