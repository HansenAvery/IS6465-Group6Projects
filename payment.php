<?php
require __DIR__ . '/includes/functions.php';
require_login();

$d = $_SESSION['draft'] ?? null;
if (!$d || empty($d['date']) || !isset(locations()[$d['loc']], DURATIONS[$d['duration']])) { redirect('index.php'); }
$loc = locations()[$d['loc']];
$total = $loc['rate'] * (float)$d['duration'];

$method = ($_POST['method'] ?? $_GET['method'] ?? 'card') === 'wallet' ? 'wallet' : 'card';
$errors = [];
$in = ['name' => '', 'number' => '', 'exp' => '', 'wallet' => 'Apple Pay'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $paymentLabel = 'Mobile Wallet';
    if ($method === 'card') {
        $in['name']   = trim((string)($_POST['name'] ?? ''));
        $in['number'] = (string)($_POST['number'] ?? '');
        $in['exp']    = (string)($_POST['exp'] ?? '');
        $cvv          = (string)($_POST['cvv'] ?? '');
        $digits       = preg_replace('/\D/', '', $in['number']);
        $expDigits    = preg_replace('/\D/', '', $in['exp']);

        if ($in['name'] === '' || mb_strlen($in['name']) > 80) { $errors['name'] = 'Enter the name on the card.'; }
        if ($digits === '') { $errors['number'] = 'Enter your card number.'; }
        elseif (strlen($digits) < 13 || strlen($digits) > 19 || !luhn_ok($digits)) { $errors['number'] = 'Card number is not valid.'; }

        if (strlen($expDigits) !== 4) { $errors['exp'] = 'Use MM / YY.'; }
        else {
            $mm = (int)substr($expDigits, 0, 2); $yy = 2000 + (int)substr($expDigits, 2);
            if ($mm < 1 || $mm > 12) { $errors['exp'] = 'Month must be 01–12.'; }
            elseif ($yy < (int)date('Y') || ($yy === (int)date('Y') && $mm < (int)date('n'))) { $errors['exp'] = 'Card has expired.'; }
        }
        if (!preg_match('/^\d{3,4}$/', $cvv)) { $errors['cvv'] = 'Enter 3 or 4 digits.'; }
        if (!$errors) { $paymentLabel = 'Card ending ' . substr($digits, -4); }   // full number is never stored
    } else {
        $w = (string)($_POST['wallet'] ?? '');
        $in['wallet'] = in_array($w, ['Apple Pay', 'Google Pay'], true) ? $w : 'Apple Pay';
        $paymentLabel = $in['wallet'];
    }

    if (!$errors) {
        $booking = [
            'id' => 'SP' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6)),
            'loc' => $d['loc'], 'locName' => $loc['name'], 'address' => $loc['address'],
            'date' => $d['date'], 'time' => $d['time'], 'duration' => $d['duration'],
            'total' => $total, 'method' => $paymentLabel, 'status' => 'Pending',
        ];
        $_SESSION['bookings'] ??= [];
        array_unshift($_SESSION['bookings'], $booking);
        $_SESSION['pending'] = $booking['id'];
        add_notification("Reservation {$booking['id']} received for {$loc['name']} on " . fmt_date($d['date']) . '.');
        unset($_SESSION['draft']);
        redirect('confirmation.php');
    }
}

page_header('Payment', 'find');
$arrow = '<svg class="arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3l16 9-16 9z"/></svg>';
$err = fn(string $k) => isset($errors[$k]) ? '<div class="error invalid-feedback d-block" role="alert">' . e($errors[$k]) . '</div>' : '<div class="error"></div>';
$cls = fn(string $k) => isset($errors[$k]) ? ' is-invalid' : '';
?>
<div class="pay-head">
  <div><h1>Reserve Your Parking Spot</h1><p class="sub">Please select your payment method</p></div>
  <div class="rate"><?= number_format($loc['rate'], 2) ?> / HR</div>
</div>
<p class="summary"><?= e($loc['name']) ?> · <?= fmt_date($d['date']) ?> at <?= fmt_time($d['time']) ?> · <?= e(DURATIONS[$d['duration']]) ?> · <strong>Total <?= money($total) ?></strong></p>

<form method="post" action="payment.php?method=<?= e($method) ?>" novalidate>
  <?= csrf_field() ?>
  <input type="hidden" name="method" value="<?= e($method) ?>">

  <a class="accordion-head btn btn-light border" href="payment.php?method=wallet" aria-expanded="<?= $method === 'wallet' ? 'true' : 'false' ?>"><?= $arrow ?><span class="title">Mobile Wallet</span></a>
  <?php if ($method === 'wallet'): ?>
    <div class="accordion-body">
      <div class="wallets" role="radiogroup" aria-label="Mobile wallet">
        <label class="wallet"><input class="form-check-input" type="radio" name="wallet" value="Apple Pay" <?= $in['wallet'] === 'Apple Pay' ? 'checked' : '' ?>> Apple Pay</label>
        <label class="wallet"><input class="form-check-input" type="radio" name="wallet" value="Google Pay" <?= $in['wallet'] === 'Google Pay' ? 'checked' : '' ?>> Google Pay</label>
      </div>
    </div>
  <?php endif; ?>

  <a class="accordion-head btn btn-light border" href="payment.php?method=card" aria-expanded="<?= $method === 'card' ? 'true' : 'false' ?>"><?= $arrow ?><span class="title">Credit/Debit</span></a>
  <?php if ($method === 'card'): ?>
    <div class="accordion-body">
      <h2 style="text-align:center;margin:8px 0 12px">Payment Details</h2>
      <div class="form-row">
        <label class="form-label" for="cName">Name On Card</label>
        <input id="cName" class="form-control<?= $cls('name') ?>" type="text" name="name" placeholder="Enter Name" autocomplete="cc-name" maxlength="80" value="<?= e($in['name']) ?>">
        <?= $err('name') ?>
      </div>
      <div class="form-row">
        <label class="form-label" for="cNum">Card Number</label>
        <input id="cNum" class="form-control<?= $cls('number') ?>" type="text" name="number" inputmode="numeric" placeholder="Enter card number" autocomplete="cc-number" maxlength="23" value="<?= e($in['number']) ?>">
        <?= $err('number') ?>
      </div>
      <div class="row g-3">
        <div class="form-row col-md-6">
          <label class="form-label" for="cExp">Expiration Date</label>
          <input id="cExp" class="form-control<?= $cls('exp') ?>" type="text" name="exp" inputmode="numeric" placeholder="MM / YY" autocomplete="cc-exp" maxlength="7" value="<?= e($in['exp']) ?>">
          <?= $err('exp') ?>
        </div>
        <div class="form-row col-md-6">
          <label class="form-label" for="cCvv">Security Code</label>
          <input id="cCvv" class="form-control<?= $cls('cvv') ?>" type="password" name="cvv" inputmode="numeric" placeholder="CVV" autocomplete="cc-csc" maxlength="4">
          <?= $err('cvv') ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="actions">
    <a class="btn btn-outline-secondary" href="book.php">Cancel</a>
    <button class="btn btn-primary" type="submit">Confirm</button>
  </div>
</form>
<?php page_footer();
