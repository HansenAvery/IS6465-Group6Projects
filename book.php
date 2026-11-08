<?php
require __DIR__ . '/includes/functions.php';
require_login();

// Entering via Reserve link starts a draft for that location
if (isset($_GET['loc']) && isset(locations()[$_GET['loc']])) {
    $_SESSION['draft'] = ['loc' => (string)$_GET['loc'], 'date' => '', 'time' => '', 'duration' => ''];
    redirect('book.php');
}
$d = $_SESSION['draft'] ?? ['loc' => (string)(array_key_first(locations()) ?? ''), 'date' => '', 'time' => '', 'duration' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $d = [
        'loc'      => (string)($_POST['loc'] ?? ''),
        'date'     => (string)($_POST['date'] ?? ''),
        'time'     => (string)($_POST['time'] ?? ''),
        'duration' => (string)($_POST['duration'] ?? ''),
    ];
    if (!isset(locations()[$d['loc']])) { $errors['loc'] = 'Please choose a parking location.'; }

    $dt = DateTime::createFromFormat('Y-m-d', $d['date']);
    if (!$dt || $dt->format('Y-m-d') !== $d['date']) { $errors['date'] = 'Please choose a valid booking date.'; }
    elseif ($d['date'] < date('Y-m-d')) { $errors['date'] = 'Date cannot be in the past.'; }

    if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $d['time'])) { $errors['time'] = 'Please choose a start time.'; }
    elseif (!isset($errors['date']) && $d['date'] === date('Y-m-d') && $d['time'] < date('H:i')) {
        $errors['time'] = 'Start time has already passed today.';
    }
    if (!isset(DURATIONS[$d['duration']])) { $errors['duration'] = 'Please select a duration.'; }

    if (!$errors) {
        $_SESSION['draft'] = $d;
        redirect('payment.php');
    }
}

page_header('Reserve Your Parking Spot', 'find');
$err = fn(string $k) => isset($errors[$k]) ? '<div class="error invalid-feedback d-block" role="alert">' . e($errors[$k]) . '</div>' : '<div class="error"></div>';
$cls = fn(string $k) => isset($errors[$k]) ? ' is-invalid' : '';
?>
<h1>Reserve Your Parking Spot</h1>
<p class="sub">Select the time, date, and duration for your reservation</p>

<form id="bookForm" method="post" action="book.php" novalidate>
  <?= csrf_field() ?>
  <div class="card border-0 shadow-sm p-3 p-md-4">
    <h2 class="card-title mb-3">Booking Details</h2>
    <div class="form-row">
      <label class="form-label" for="bLoc">Select Parking Location</label>
      <select id="bLoc" class="form-select<?= $cls('loc') ?>" name="loc">
        <?php foreach (locations() as $id => $l): ?>
          <option value="<?= e($id) ?>"<?= (string)$d['loc'] === (string)$id ? ' selected' : '' ?>><?= e($l['name']) ?> — <?= money($l['rate']) ?>/hr</option>
        <?php endforeach; ?>
      </select>
      <?= $err('loc') ?>
    </div>
    <div class="row g-3">
      <div class="form-row col-md-6">
        <label class="form-label" for="bDate">Booking Date</label>
        <input id="bDate" class="form-control<?= $cls('date') ?>" type="date" name="date" min="<?= date('Y-m-d') ?>" value="<?= e($d['date']) ?>">
        <?= $err('date') ?>
      </div>
      <div class="form-row col-md-6">
        <label class="form-label" for="bTime">Start Time</label>
        <input id="bTime" class="form-control<?= $cls('time') ?>" type="time" name="time" value="<?= e($d['time']) ?>">
        <?= $err('time') ?>
      </div>
    </div>
    <div class="form-row">
      <label class="form-label" for="bDur">Parking Duration</label>
      <select id="bDur" class="form-select<?= $cls('duration') ?>" name="duration">
        <option value="">Select Duration</option>
        <?php foreach (DURATIONS as $v => $t): ?>
          <option value="<?= e($v) ?>"<?= (string)$d['duration'] === (string)$v ? ' selected' : '' ?>><?= e($t) ?></option>
        <?php endforeach; ?>
      </select>
      <?= $err('duration') ?>
    </div>
  </div>
  <div class="actions">
    <a class="btn btn-outline-secondary" href="index.php">Cancel</a>
    <button class="btn btn-primary" type="submit">Proceed to Payment</button>
  </div>
</form>
<?php page_footer();
