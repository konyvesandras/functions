<?php
// highlight_amp_runner.php

// --- Fájlnév ellenőrzés ---
$fajlnev = $_GET['fajl'] ?? 'elemzes.txt';
if (!preg_match('/^[a-zA-Z0-9_\-]+\.txt$/', $fajlnev)) {
    die("❌ Érvénytelen fájlnév.");
}

$eleresi_ut = __DIR__ . '/txt/' . $fajlnev;
if (!file_exists($eleresi_ut)) {
    die("❌ A fájl nem található: $fajlnev");
}

// --- Függvények betöltése ---
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/text/karaktercsere.php';

// --- Szöveg betöltése és előfeldolgozás ---
$szoveg = file_get_contents($eleresi_ut);
$szoveg = karaktercsere_folio($szoveg);

// --- Kiemelés (cache-el együtt) ---
$kiemelt = highlight_words_amp_safe($szoveg, $fajlnev);
?>
<!doctype html>
<html ⚡ lang="hu">
<head>
  <meta charset="utf-8">
  <title>Szövegkiemelés: <?= htmlspecialchars($fajlnev) ?></title>
  <link rel="canonical" href="">
  <meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
  <style amp-custom>
    body { font-family: sans-serif; padding: 2em; line-height: 1.6; }
    .repeated { font-weight: bold; color: #c00; }
    .embedded { background-color: #ff0; font-weight: bold; }
  </style>
  <script async src="https://cdn.ampproject.org/v0.js"></script>
</head>
<body>
  <h1>Szövegkiemelés: <?= htmlspecialchars($fajlnev) ?></h1>
  <div><?= nl2br($kiemelt) ?></div>
</body>
</html>
