<?php
require __DIR__ . '/includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $cancel = (string)($_POST['cancel'] ?? '');
    $_SESSION['bookings'] = array_values(array_filter($_SESSION['bookings'] ?? [], fn($b) => $b['id'] !== $cancel));
    $_SESSION['flash'] = 'Booking cancelled';
    redirect('bookings.php');
}
$bookings = $_SESSION['bookings'] ?? [];
page_header('My Bookings', 'bookings');
?>
<h1>My Bookings</h1>
<p class="sub">Your upcoming and past reservations</p>
<div class="rows">
<?php if (!$bookings): ?>
  <div class="alert alert-light border">You have no bookings yet. <a href="index.php">Find parking</a> to make one.</div>
<?php endif; ?>
<?php foreach ($bookings as $b): ?>
  <div class="card row-card shadow-sm">
    <div class="flex-grow-1">
      <strong><?= e($b['locName']) ?></strong> <span class="meta">· #<?= e($b['id']) ?></span>
      <div class="meta"><?= fmt_date($b['date']) ?> at <?= fmt_time($b['time']) ?> · <?= e(DURATIONS[$b['duration']]) ?> · <?= money($b['total']) ?> · <?= e($b['method']) ?></div>
    </div>
    <form method="post" action="bookings.php">
      <?= csrf_field() ?><input type="hidden" name="cancel" value="<?= e($b['id']) ?>">
      <span class="badge <?= strtolower($b['status']) === 'confirmed' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e($b['status']) ?></span>
      <button class="btn btn-link btn-sm text-danger" type="submit">Cancel booking</button>
    </form>
  </div>
<?php endforeach; ?>
</div>
<?php page_footer();
