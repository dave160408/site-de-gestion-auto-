<?php
require_once __DIR__ . '/config/database.php';

if (!isLoggedIn()) { header('Location: login.php'); exit; }

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

// --- Récupération commande + client + adresse (via LIVRAISONS -> ADRESSES) ---
$stmt = $pdo->prepare(
    "SELECT co.*, cl.nom_client, cl.prenom_client, cl.email, cl.telephone, cl.id_clients,
            ad.rue, ad.ville, ad.code_postal, ad.pays,
            liv.transporteur
     FROM COMMANDES co
     JOIN CLIENTS cl ON co.id_clients = cl.id_clients
     LEFT JOIN LIVRAISONS liv ON co.id_commandes = liv.id_commandes
     LEFT JOIN ADRESSES ad ON liv.id_adresse = ad.id_adresse
    WHERE co.id_commandes = ?
      AND (? = 'admin' OR co.id_clients = ?)"
);
$stmt->execute([$id, $_SESSION['user_role'] ?? '', $_SESSION['user_id']]);
$commande = $stmt->fetch();

if (!$commande) { header('Location: index.php'); exit; }

// --- Repli : si aucune adresse de livraison spécifique n'est liée à CETTE commande,
// on utilise l'adresse enregistrée par le client à son inscription ---
if (empty($commande['rue']) && empty($commande['ville'])) {
    $stmtAdresseDefaut = $pdo->prepare(
        "SELECT rue, ville, code_postal, pays FROM ADRESSES WHERE id_clients = ? ORDER BY id_adresse ASC LIMIT 1"
    );
    $stmtAdresseDefaut->execute([$commande['id_clients']]);
    $adresseDefaut = $stmtAdresseDefaut->fetch();
    if ($adresseDefaut) {
        $commande['rue']         = $adresseDefaut['rue'];
        $commande['ville']       = $adresseDefaut['ville'];
        $commande['code_postal'] = $adresseDefaut['code_postal'];
        $commande['pays']        = $adresseDefaut['pays'];
    }
}

// --- Lignes de commande (table DETAILS_COMMANDE, pas LIGNE_COMMANDES) ---
$stmtLignes = $pdo->prepare(
    "SELECT dc.*, p.nom_produit, p.reference_oem
     FROM DETAILS_COMMANDE dc
     JOIN PRODUITS p ON dc.id_produit = p.id_produit
     WHERE dc.id_commandes = ?"
);
$stmtLignes->execute([$id]);
$lignes = $stmtLignes->fetchAll();

// --- Calcul du sous-total à partir des lignes réelles (pas de frais fixe supposé) ---
$sousTotal = 0;
foreach ($lignes as $l) {
    $sousTotal += $l['prix_unitaire'] * $l['quantite'];
}
$fraisLivraison = max(0, $commande['total_commandes'] - $sousTotal);
$factureFinale = in_array($commande['statut_commandes'], ['payée', 'expédiée', 'livrée'], true);
$titreDocument = $factureFinale ? 'FACTURE FINALE - PAYÉE' : 'JUSTIFICATIF DE COMMANDE';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Facture #<?php echo str_pad($commande['id_commandes'], 4, '0', STR_PAD_LEFT); ?> — DUTCH COMPANY DIESEL GABON</title>
<style>
    body { font-family: Arial, sans-serif; color: #1a1a1a; max-width: 800px; margin: 40px auto; padding: 0 20px; }
    .facture-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #FFC700; padding-bottom: 20px; margin-bottom: 30px; }
    .facture-header h1 { font-size: 1.4rem; margin: 0; letter-spacing: 2px; }
    .facture-header .sub { color: #FFC700; font-size: 0.75rem; letter-spacing: 4px; font-weight: bold; }
    .facture-meta { text-align: right; font-size: 0.85rem; color: #555; }
    .facture-meta strong { color: #000; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th { background: #111; color: #fff; text-align: left; padding: 10px 12px; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 1px; }
    td { padding: 10px 12px; border-bottom: 1px solid #eee; font-size: 0.88rem; }
    .totaux { width: 300px; margin-left: auto; }
    .totaux tr td { border: none; padding: 6px 12px; }
    .totaux .total-final td { font-weight: 900; font-size: 1.1rem; border-top: 2px solid #111; padding-top: 12px; }
    .paiement-note { background: #fff8e1; border: 1px solid #FFC700; border-radius: 8px; padding: 14px 18px; margin: 24px 0; font-size: 0.85rem; }
    .signature-box { display:flex; justify-content:space-between; align-items:center; margin-top:35px; padding-top:20px; border-top:1px solid #ddd; font-size:.82rem; }
    .paid-stamp { border:3px solid #15803d; color:#15803d; border-radius:50%; width:100px; height:100px; display:flex; flex-direction:column; justify-content:center; align-items:center; font-weight:900; transform:rotate(-12deg); }
    .btn-print { background: #FFC700; color: #000; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 800; cursor: pointer; font-size: 0.85rem; margin-bottom: 20px; }
    @media print { .btn-print { display: none; } body { margin: 0; } }
</style>
</head>
<body>

<button class="btn-print" onclick="window.print()">🖨️ IMPRIMER / ENREGISTRER EN PDF</button>

<div class="facture-header">
    <div>
        <h1>DUTCH COMPANY</h1>
        <div class="sub">DIESEL GABON</div>
    </div>
    <div class="facture-meta">
        <div><?php echo $factureFinale ? 'FACTURE FINALE N°' : 'JUSTIFICATIF N°'; ?> <strong>#<?php echo str_pad($commande['id_commandes'], 4, '0', STR_PAD_LEFT); ?></strong></div>
        <div>Date : <?php echo date('d/m/Y', strtotime($commande['date_commandes'])); ?></div>
    </div>
</div>

<p>
    <strong>Client :</strong> <?php echo htmlspecialchars($commande['prenom_client'] . ' ' . $commande['nom_client']); ?><br>
    <strong>Email :</strong> <?php echo htmlspecialchars($commande['email']); ?><br>
    <strong>Téléphone / WhatsApp :</strong> <?php echo htmlspecialchars($commande['telephone'] ?? 'Non renseigné'); ?><br>
    <strong>Adresse de livraison :</strong>
    <?php if (!empty($commande['ville'])): ?>
        <?php echo htmlspecialchars(trim(($commande['rue'] ? $commande['rue'] . ', ' : '') . $commande['ville'])); ?><?php echo $commande['code_postal'] ? ' — ' . htmlspecialchars($commande['code_postal']) : ''; ?>
        <?php if ($commande['transporteur']): ?><br><strong>Transporteur :</strong> <?php echo htmlspecialchars($commande['transporteur']); ?><?php endif; ?>
    <?php else: ?>
        Retrait en magasin
    <?php endif; ?>
</p>

<table>
    <thead>
        <tr><th>Article</th><th>Réf. OEM</th><th>Qté</th><th>Prix unitaire</th><th>Total</th></tr>
    </thead>
    <tbody>
    <?php foreach ($lignes as $l): ?>
        <tr>
            <td><?php echo htmlspecialchars($l['nom_produit']); ?></td>
            <td><?php echo htmlspecialchars($l['reference_oem'] ?? 'N/A'); ?></td>
            <td><?php echo $l['quantite']; ?></td>
            <td><?php echo number_format($l['prix_unitaire'], 0, ',', ' '); ?> FCFA</td>
            <td><?php echo number_format($l['prix_unitaire'] * $l['quantite'], 0, ',', ' '); ?> FCFA</td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($lignes)): ?>
        <tr><td colspan="5" style="text-align:center;color:#999;">Aucun article détaillé pour cette commande.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<table class="totaux">
    <tr><td>Sous-total articles</td><td><?php echo number_format($sousTotal, 0, ',', ' '); ?> FCFA</td></tr>
    <tr><td>Livraison / autres frais</td><td><?php echo number_format($fraisLivraison, 0, ',', ' '); ?> FCFA</td></tr>
    <tr class="total-final"><td>TOTAL</td><td><?php echo number_format($commande['total_commandes'], 0, ',', ' '); ?> FCFA</td></tr>
</table>

<div class="paiement-note">
    <?php if ($factureFinale): ?>
        <strong>PAIEMENT CONFIRMÉ</strong><?php if (!empty($commande['payee_at'])): ?> le <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($commande['payee_at']))); ?><?php endif; ?>.
        Cette facture finale constitue la preuve de paiement.
    <?php else: ?>
        <strong>COMMANDE ENREGISTRÉE</strong> - preuve à présenter pour règlement.
        Code de paiement : <strong><?php echo htmlspecialchars($commande['code_paiement']); ?></strong>.
        Statut : <strong><?php echo htmlspecialchars($commande['statut_commandes']); ?></strong>.
    <?php endif; ?>
</div>

<?php if ($factureFinale): ?>
<div class="signature-box">
    <div><strong>Vendeur</strong><br>DUTCH COMPANY DIESEL GABON<br>Signature : __________________</div>
    <div class="paid-stamp">PAYÉ<br><small><?php echo htmlspecialchars($commande['code_paiement']); ?></small></div>
</div>
<?php endif; ?>

<p style="font-size:0.75rem;color:#888;text-align:center;margin-top:40px;">
    DUTCH COMPANY DIESEL GABON — Libreville, Port-Gentil, Franceville — WhatsApp +241 07 45 88 99
</p>

</body>
</html>