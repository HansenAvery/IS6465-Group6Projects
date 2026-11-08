<?php
declare(strict_types=1);

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();
require_once __DIR__ . '/db.php';

const DURATIONS = [
    '0.5' => '30 minutes', '1' => '1 hour', '2' => '2 hours', '3' => '3 hours',
    '4' => '4 hours', '8' => '8 hours', '24' => 'All day (24 hours)',
];

function e(mixed $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function money(float $n): string { return '$' . number_format($n, 2); }
function redirect(string $url): never { header('Location: ' . $url); exit; }
function fmt_date(string $iso): string { return date('m/d/Y', strtotime($iso)); }
function fmt_time(string $hhmm): string { return date('g:i A', strtotime($hhmm)); }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
    $ok = isset($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', (string)$_POST['csrf']);
    if (!$ok) { http_response_code(400); exit('Invalid or expired form. Please go back and try again.'); }
}

function add_notification(string $text): void {
    $_SESSION['notifs'] ??= [];
    array_unshift($_SESSION['notifs'], ['text' => $text, 'at' => date('c'), 'read' => false]);
}
function unread_count(): int {
    return count(array_filter($_SESSION['notifs'] ?? [], fn($n) => !$n['read']));
}

function luhn_ok(string $num): bool {
    $sum = 0; $alt = false;
    for ($i = strlen($num) - 1; $i >= 0; $i--) {
        $n = (int)$num[$i];
        if ($alt) { $n *= 2; if ($n > 9) { $n -= 9; } }
        $sum += $n; $alt = !$alt;
    }
    return $sum % 10 === 0;
}

function is_logged_in(): bool { return !empty($_SESSION['user']); }
function require_login(): void {
    if (!is_logged_in()) { redirect('login.php'); }
}

function page_header(string $title, string $active = '', bool $showNav = true): void {
    $unread = unread_count();
    $nav = fn(string $key, string $href, string $label) =>
        '<a href="' . $href . '"' . ($active === $key ? ' class="active" aria-current="page"' : '') . '>' . $label . '</a>';
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<title>' . e($title) . ' | SmartPark Solutions</title>'
       . '<link rel="stylesheet" href="css/styles.css"></head><body>'
       . '<header class="site-header"><a class="logo" href="' . ($showNav ? 'index.php' : 'login.php') . '">SmartPark Solutions</a>'
       . ($showNav ? '<nav class="main-nav" aria-label="Main">'
       . $nav('bookings', 'bookings.php', 'My Bookings')
       . $nav('find', 'index.php', 'Find Parking')
       . $nav('locations', 'locations.php', 'Locations')
       . $nav('users', 'users.php', 'Users')
       . $nav('notifications', 'notifications.php', 'Notifications' . ($unread ? ' <span class="badge">' . $unread . '</span>' : ''))
       . '<span class="avatar" role="img" aria-label="Account"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7z"/></svg></span>'
       . '<form method="post" action="logout.php" class="logout-form">' . csrf_field()
       . '<button type="submit" class="link-btn nav-logout" title="Signed in as ' . e($_SESSION['user']['email'] ?? '') . '">Log out</button></form>'
       . '</nav>' : '')
       . '</header><main id="app">';
    if (!empty($_SESSION['flash'])) {
        echo '<div class="toast" role="status">' . e($_SESSION['flash']) . '</div>';
        unset($_SESSION['flash']);
    }
}
function page_footer(): void { echo '</main></body></html>'; }
