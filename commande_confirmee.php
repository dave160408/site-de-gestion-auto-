<?php
require_once __DIR__ . '/config/database.php';

if (!isLoggedIn()) { header('Location: login.php'); exit; }

$id = (int)($_GET['id'] ?? 0);

// --- Jointure avec CLIENTS pour récupérer le téléphone (WhatsApp) ---
// La commande elle-même n'a pas de colonne "whatsapp_client" ; c'est CLIENTS.telephone
$stmt = $pdo->prepare(
    "SELECT co.*, cl.telephone
     FROM COMMANDES co
     JOIN CLIENTS cl ON co.id_clients = cl.id_clients
     WHERE co.id_commandes = ? AND co.id_clients = ?"
);
$stmt->execute([$id, $_SESSION['user_id']]);
$commande = $stmt->fetch();

if (!$commande) { header('Location: index.php'); exit; }

include 'includes/header.php';
?>

<div style="margin-top:130px;padding:80px 0;">
    <div class="container" style="max-width:600px;text-align:center;">
        <div style="width:80px;height:80px;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);
                    border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;">
            <i class="fas fa-check" style="font-size:2rem;color:#22c55e;"></i>
        </div>

        <h1 style="font-size:1.8rem;font-weight:900;text-transform:uppercase;margin-bottom:12px;">
            Commande confirmée !
        </h1>
        <p style="color:#555;margin-bottom:8px;">
            Commande N° <strong style="color:var(--gold);">#<?php echo str_pad($commande['id_commandes'], 4, '0', STR_PAD_LEFT); ?></strong>
        </p>
        <p style="color:#888;font-size:0.9rem;margin-bottom:32px;">
            Nous vous recontactons sur WhatsApp au
            <strong><?php echo htmlspecialchars($commande['telephone'] ?? 'votre numéro'); ?></strong>
            pour confirmer votre commande. Votre code de paiement est
            <strong style="color:var(--gold);letter-spacing:1px;"><?php echo htmlspecialchars($commande['code_paiement'] ?? 'En attente'); ?></strong>.
            Conservez ce justificatif pour le règlement.
        </p>

        <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
            <a href="facture.php?id=<?php echo $commande['id_commandes']; ?>" class="btn-gold" style="width:auto;display:inline-flex;">
                <i class="fas fa-file-invoice me-2"></i>VOIR MA FACTURE
            </a>
            <a href="mes_commandes.php" class="btn-outline" style="width:auto;display:inline-flex;">
                <i class="fas fa-truck me-2"></i>SUIVRE MA COMMANDE
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>