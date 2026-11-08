<?php
require __DIR__ . '/includes/functions.php';
require_login();

// CREATE (no id) and UPDATE (?id=)
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editing = $id > 0;
$row = ['name' => '', 'address' => '', 'rate' => '', 'map_x' => '50', 'map_y' => '50'];
if ($editing) {
    $st = db()->prepare('SELECT * FROM locations WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) { $_SESSION['flash'] = 'Parking location not found'; redirect('locations.php'); }
}
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $row['name']    = trim((string)($_POST['name'] ?? ''));
    $row['address'] = trim((string)($_POST['address'] ?? ''));
    $row['rate']    = trim((string)($_POST['rate'] ?? ''));
    $row['map_x']   = trim((string)($_POST['map_x'] ?? ''));
    $row['map_y']   = trim((string)($_POST['map_y'] ?? ''));

    if ($row['name'] === '' || mb_strlen($row['name']) > 80) { $errors['name'] = 'Enter a name (up to 80 characters).'; }
    if (mb_strlen($row['address']) < 5 || mb_strlen($row['address']) > 120) { $errors['address'] = 'Enter an address (5–120 characters).'; }
    if (!is_numeric($row['rate']) || (float)$row['rate'] < 0.5 || (float)$row['rate'] > 100) { $errors['rate'] = 'Enter an hourly rate between 0.50 and 100.00.'; }
    foreach (['map_x', 'map_y'] as $k) {
        if (!ctype_digit($row[$k]) || (int)$row[$k] > 100) { $errors[$k] = 'Enter a whole number from 0 to 100.'; }
    }

    if (!$errors) {
        $vals = [$row['name'], $row['address'], round((float)$row['rate'], 2), (int)$row['map_x'], (int)$row['map_y']];
        if ($editing) {
            db()->prepare('UPDATE locations SET name=?, address=?, rate=?, map_x=?, map_y=? WHERE id=?')->execute([...$vals, $id]);
            $_SESSION['flash'] = 'Parking location updated';
        } else {
            db()->prepare('INSERT INTO locations (name, address, rate, map_x, map_y) VALUES (?, ?, ?, ?, ?)')->execute($vals);
            $_SESSION['flash'] = 'Parking location added';
        }
        redirect('locations.php');
    }
}

$err = fn(string $k) => isset($errors[$k]) ? '<div class="error" role="alert">' . e($errors[$k]) . '</div>' : '<div class="error"></div>';
$cls = fn(string $k) => isset($errors[$k]) ? ' class="invalid"' : '';
page_header($editing ? 'Update Location' : 'Add Location', 'locations');
?>
<h1><?= $editing ? 'Update Parking Location' : 'Add Parking Location' ?></h1>
<p class="sub">Map position places the pin on the Find Parking map (0% is the left/top edge, 100% the right/bottom edge).</p>
<form method="post" action="location_form.php<?= $editing ? '?id=' . $id : '' ?>" novalidate>
  <?= csrf_field() ?>
  <div class="panel">
    <h2>Location Details</h2>
    <div class="form-row"><label class="field-label" for="name">Name</label>
      <input id="name" name="name" type="text" maxlength="80" value="<?= e($row['name']) ?>"<?= $cls('name') ?>><?= $err('name') ?></div>
    <div class="form-row"><label class="field-label" for="address">Address</label>
      <input id="address" name="address" type="text" maxlength="120" value="<?= e($row['address']) ?>"<?= $cls('address') ?>><?= $err('address') ?></div>
    <div class="form-row"><label class="field-label" for="rate">Rate per hour ($)</label>
      <input id="rate" name="rate" type="text" inputmode="decimal" value="<?= e($row['rate']) ?>"<?= $cls('rate') ?>><?= $err('rate') ?></div>
    <div class="form-grid">
      <div class="form-row"><label class="field-label" for="map_x">Map position – left (%)</label>
        <input id="map_x" name="map_x" type="text" inputmode="numeric" value="<?= e($row['map_x']) ?>"<?= $cls('map_x') ?>><?= $err('map_x') ?></div>
      <div class="form-row"><label class="field-label" for="map_y">Map position – top (%)</label>
        <input id="map_y" name="map_y" type="text" inputmode="numeric" value="<?= e($row['map_y']) ?>"<?= $cls('map_y') ?>><?= $err('map_y') ?></div>
    </div>
  </div>
  <div class="actions"><a class="btn" href="locations.php">Cancel</a>
    <button class="btn alt" type="submit"><?= $editing ? 'Save Changes' : 'Add Location' ?></button></div>
</form>
<?php page_footer();
