<?php
require __DIR__ . '/includes/functions.php';
require_login();

$id = $_SESSION['pending'] ?? null;
$booking = null; $idx = null;
foreach ($_SESSION['bookings'] ?? [] as $i => $b) { if ($b['id'] === $id) { $booking = $b; $idx = $i; } }
if (!$booking) { redirect('index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $_SESSION['bookings'][$idx]['status'] = 'Confirmed';
    add_notification("Reservation {$booking['id']} is confirmed. See you at {$booking['locName']}!");
    unset($_SESSION['pending']);
    $_SESSION['flash'] = 'Reservation confirmed';
    redirect('bookings.php');
}

page_header('Reservation Received', 'find');
?>
<div class="confirm">
  <h1>We have received your reservation!</h1>
  <div class="receipt"><dl>
    <dt>Booking #</dt><dd><?= e($booking['id']) ?></dd>
    <dt>Location</dt><dd><?= e($booking['locName']) ?></dd>
    <dt>Date</dt><dd><?= fmt_date($booking['date']) ?></dd>
    <dt>Start</dt><dd><?= fmt_time($booking['time']) ?></dd>
    <dt>Duration</dt><dd><?= e(DURATIONS[$booking['duration']]) ?></dd>
    <dt>Total</dt><dd><?= money($booking['total']) ?></dd>
    <dt>Payment</dt><dd><?= e($booking['method']) ?></dd>
  </dl></div>
  <form method="post" action="confirmation.php" class="actions" style="margin-top:0">
    <?= csrf_field() ?>
    <a class="btn" href="index.php">Return to Home</a>
    <button class="btn alt" type="submit">Confirm</button>
  </form>
</div>
<?php page_footer();
