<?php
require_once __DIR__ . '/config/database.php';

if (!isLoggedIn()) {
    header('Location: login.php?redirect=panier.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: panier.php');
    exit;
}
verifyCsrf();

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header('Location: panier.php');
    exit;
}

$idClient       = $_SESSION['user_id'];
$fraisLivraison = 5000; // doit rester identique à celui affiché dans panier.php

// --- Récupération des produits du panier (avec vérification du stock) ---
$ids = array_keys($cart);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT * FROM PRODUITS WHERE id_produit IN ($placeholders)");
$stmt->execute($ids);
$produits = $stmt->fetchAll(PDO::FETCH_UNIQUE);

// --- Vérification que tous les articles existent encore et sont en stock suffisant ---
$erreurStock = [];
foreach ($cart as $pid => $item) {
    $p = $produits[$pid] ?? null;
    $qte = (int) $item['quantity'];
    if (!$p) {
        $erreurStock[] = "Un article de votre panier n'existe plus.";
        continue;
    }
    if ($p['stock_produit'] < $qte) {
        $erreurStock[] = "Stock insuffisant pour \"" . $p['nom_produit'] . "\" (disponible : " . $p['stock_produit'] . ").";
    }
}

if (!empty($erreurStock)) {
    $_SESSION['checkout_erreurs'] = $erreurStock;
    header('Location: panier.php');
    exit;
}

// --- Calcul du sous-total et du total ---
$sousTotal = 0;
foreach ($cart as $pid => $item) {
    $p = $produits[$pid];
    $sousTotal += $p['prix_produit'] * (int) $item['quantity'];
}
$total = $sousTotal + $fraisLivraison;
$codePaiement = 'DCD-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

try {
    $pdo->beginTransaction();

    // 1) Création de la commande
    $stmtCmd = $pdo->prepare(
           "INSERT INTO COMMANDES (id_clients, date_commandes, statut_commandes, total_commandes, code_paiement)
            VALUES (?, CURDATE(), 'en attente', ?, ?)"
    );
        $stmtCmd->execute([$idClient, $total, $codePaiement]);
    $idCommande = (int) $pdo->lastInsertId();

    // 2) Création d'une ligne DETAILS_COMMANDE pour CHAQUE article du panier
    $stmtDetail = $pdo->prepare(
        "INSERT INTO DETAILS_COMMANDE (id_commandes, id_produit, quantite, prix_unitaire)
         VALUES (?, ?, ?, ?)"
    );
    $stmtStock = $pdo->prepare(
        "UPDATE PRODUITS SET stock_produit = stock_produit - ? WHERE id_produit = ? AND stock_produit >= ?"
    );

    foreach ($cart as $pid => $item) {
        $p   = $produits[$pid];
        $qte = (int) $item['quantity'];

        $stmtDetail->execute([$idCommande, $pid, $qte, $p['prix_produit']]);

        // Décrément du stock au moment de la commande
        $stmtStock->execute([$qte, $pid, $qte]);
        if ($stmtStock->rowCount() !== 1) {
            throw new RuntimeException('Stock modifié pendant la commande.');
        }
    }

    // 3) Liaison avec l'adresse de livraison du client (la première trouvée)
    $stmtAdresse = $pdo->prepare("SELECT id_adresse FROM ADRESSES WHERE id_clients = ? ORDER BY id_adresse ASC LIMIT 1");
    $stmtAdresse->execute([$idClient]);
    $idAdresse = $stmtAdresse->fetchColumn();

    if ($idAdresse) {
        $stmtLivraison = $pdo->prepare(
            "INSERT INTO LIVRAISONS (id_commandes, id_adresse, statut_livraison, transporteur)
             VALUES (?, ?, 'en preparation', 'Livraison locale')"
        );
        $stmtLivraison->execute([$idCommande, $idAdresse]);
    }

    $pdo->commit();

    // 4) Panier vidé une fois la commande bien enregistrée
    unset($_SESSION['cart']);

    header('Location: commande_confirmee.php?id=' . $idCommande);
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['checkout_erreurs'] = ["Erreur lors de la création de la commande. Réessaie."];
    error_log('Erreur checkout : ' . $e->getMessage());
    header('Location: panier.php');
    exit;
}