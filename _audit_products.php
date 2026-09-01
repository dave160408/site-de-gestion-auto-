<?php
$pdo = new PDO('mysql:host=localhost;dbname=sitweb;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$rows = $pdo->query("SELECT id_produit, nom_produit, image_produit, id_categories FROM PRODUITS ORDER BY id_produit")->fetchAll();
$base = __DIR__ . '/assets/images';
foreach ($rows as $r) {
    $img = trim((string)($r['image_produit'] ?? ''));
    $exists = $img !== '' && file_exists($base . '/' . basename($img));
    echo $r['id_produit'] . '|'. $r['id_categories'] . '|'. $r['nom_produit'] . '|'. $img . '|' . ($exists ? 'OK' : 'MISSING') . PHP_EOL;
}
