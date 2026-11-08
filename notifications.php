<?php
require __DIR__ . '/includes/functions.php';
require_login();

$notifs = $_SESSION['notifs'] ?? [];
page_header('Notifications', 'notifications');
?>
<h1>Notifications</h1>
<p class="sub">Updates about your reservations</p>
<div class="rows">
<?php if (!$notifs): ?><div class="alert alert-light border">No notifications yet.</div><?php endif; ?>
<?php foreach ($notifs as $n): ?>
  <div class="card row-card shadow-sm">
    <div><?= e($n['text']) ?><div class="meta"><?= e(date('M j, Y g:i A', strtotime($n['at']))) ?></div></div>
    <?php if (!$n['read']): ?><span class="badge text-bg-primary">New</span><?php endif; ?>
  </div>
<?php endforeach; ?>
</div>
<?php
foreach ($_SESSION['notifs'] ?? [] as &$n) { $n['read'] = true; }
unset($n);
page_footer();
