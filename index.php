<?php
require __DIR__ . '/includes/functions.php';
require_login();

unset($_SESSION['draft']);                       // starting over from Find Parking
$q = trim((string)($_GET['q'] ?? ''));
$selected = isset($_GET['select']) && isset(locations()[$_GET['select']]) ? (int)$_GET['select'] : null;

$results = array_filter(locations(), function (array $l) use ($q) {
    return $q === '' || stripos($l['name'] . ' ' . $l['address'], $q) !== false;
});
$count = count($results);

// decorative map background lines
$lines = '';
for ($i = 0; $i < 14; $i++) {
    $x = $i * 8 - 10; $y = ($i % 4) * 22 + 6;
    $lines .= "<line x1=\"{$x}%\" y1=\"{$y}%\" x2=\"" . ($x + 22) . "%\" y2=\"" . ($y + 40) . "%\"/>";
    $lines .= "<line x1=\"" . ($x + 30) . "%\" y1=\"" . ($y + 40) . "%\" x2=\"" . ($x + 8) . "%\" y2=\"{$y}%\"/>";
}

page_header('Find Parking', 'find');
?>
<h1>Available Parking</h1>
<p class="sub">Enter a location to see parking locations and available spaces</p>

<form class="row g-3 align-items-end mb-4" method="get" action="index.php" role="search">
  <div class="col">
    <label class="form-label" for="loc">Location</label>
    <input id="loc" class="form-control" type="search" name="q" placeholder="Enter an address, city, or zip code" value="<?= e($q) ?>" autocomplete="off">
  </div>
  <div class="col-auto"><button class="btn btn-primary" type="submit">Search Availability</button></div>
</form>

<div class="row g-4">
  <section class="col-lg-6" aria-labelledby="mapH">
    <h2 id="mapH" style="margin-bottom:12px">Parking Map</h2>
    <div class="map">
      <span class="chip">Search area</span>
      <svg preserveAspectRatio="none" aria-hidden="true" stroke="#d3d7dc" stroke-width="2"><?= $lines ?></svg>
      <?php foreach ($results as $id => $l): ?>
        <a class="pin<?= $selected === $id ? ' selected' : '' ?>"
           style="left:<?= $l['x'] ?>%;top:<?= $l['y'] ?>%"
           href="index.php?<?= e(http_build_query(['q' => $q, 'select' => $id])) ?>"
           aria-label="<?= e($l['name']) ?>, <?= money($l['rate']) ?> per hour">
          <span class="tip"><?= e($l['name']) ?></span>
          <svg viewBox="0 0 30 30" aria-hidden="true"><polygon points="3,28 15,3 27,28" fill="#f2f3f5" stroke="#333" stroke-width="1.5"/><circle cx="15" cy="17" r="4.5" fill="#6b7480"/></svg>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="col-lg-6" aria-labelledby="listH">
    <div class="list-head">
      <h2 id="listH">Available Parking</h2>
      <span class="found"><?= $count ?> location<?= $count === 1 ? '' : 's' ?> found</span>
    </div>
    <?php if (!$results): ?>
      <div class="empty">No parking found for “<?= e($q) ?>”. Try another address, city, or zip code.</div>
    <?php endif; ?>
    <?php foreach ($results as $id => $l): ?>
      <div class="card parking-card rounded shadow-sm<?= $selected === $id ? ' selected' : '' ?>">
        <div>
          <div class="name"><?= e($l['name']) ?></div>
          <div class="addr"><?= e($l['address']) ?></div>
          <a class="btn btn-outline-primary btn-sm" href="book.php?loc=<?= e($id) ?>" aria-label="Reserve <?= e($l['name']) ?>">Reserve</a>
        </div>
        <div class="price"><?= number_format($l['rate'], 2) ?> / HR</div>
      </div>
    <?php endforeach; ?>
  </section>
</div>
<?php page_footer();
