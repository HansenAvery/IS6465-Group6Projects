<?php
declare(strict_types=1);

/** Shared PDO connection (SQLite file in /data). Creates tables and seed rows on first run. */
function db(): PDO {
    static $pdo = null;
    if ($pdo) { return $pdo; }
    $dir = __DIR__ . '/../data';
    if (!is_dir($dir)) { mkdir($dir, 0775, true); }
    $pdo = new PDO('sqlite:' . $dir . '/smartpark.sqlite', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        phone TEXT NOT NULL DEFAULT "",
        password_hash TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS locations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        address TEXT NOT NULL,
        rate REAL NOT NULL,
        map_x INTEGER NOT NULL DEFAULT 50,
        map_y INTEGER NOT NULL DEFAULT 50
    )');

    if ((int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
        $pdo->prepare('INSERT INTO users (name, email, phone, password_hash) VALUES (?, ?, ?, ?)')
            ->execute(['Demo Driver', 'demo@smartpark.com', '303-555-0100', password_hash('Park1234!', PASSWORD_DEFAULT)]);
    }
    if ((int)$pdo->query('SELECT COUNT(*) FROM locations')->fetchColumn() === 0) {
        $ins = $pdo->prepare('INSERT INTO locations (name, address, rate, map_x, map_y) VALUES (?, ?, ?, ?, ?)');
        foreach ([
            ['Parking Location A', '123 Main St, Denver, CO 80202', 3.00, 24, 62],
            ['Parking Location B', '456 Market St, Denver, CO 80203', 5.00, 61, 44],
            ['Parking Location C', '789 Colfax Ave, Denver, CO 80204', 2.00, 83, 82],
        ] as $row) { $ins->execute($row); }
    }
    return $pdo;
}

/** All parking locations keyed by id, in the shape the pages already use. */
function locations(): array {
    $out = [];
    foreach (db()->query('SELECT * FROM locations ORDER BY id') as $r) {
        $out[(int)$r['id']] = ['name' => $r['name'], 'address' => $r['address'], 'rate' => (float)$r['rate'],
                               'x' => (int)$r['map_x'], 'y' => (int)$r['map_y']];
    }
    return $out;
}
