<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/payments.php';

if (!isLoggedIn()) {
    header('Location: login.php?redirect=mes_commandes.php');
    exit;
}

$idCommande = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$idCommande || $idCommande < 1) {
    header('Location: mes_commandes.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT co.*, cl.nom_client, cl.prenom_client, cl.telephone
     FROM COMMANDES co
     JOIN CLIENTS cl ON cl.id_clients = co.id_clients
     WHERE co.id_commandes = ? AND co.id_clients = ?"
);
$stmt->execute([$idCommande, $_SESSION['user_id']]);
$commande = $stmt->fetch();

if (!$commande || $commande['statut_commandes'] === 'annulée') {
    header('Location: mes_commandes.php');
    exit;
}

$message = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $methode = trim((string) ($_POST['methode_paiement'] ?? ''));
    $reference = trim((string) ($_POST['reference_paiement'] ?? ''));
    $methodesAutorisees = ['Airtel Money', 'Moov Money', 'Virement bancaire', 'Paiement en local'];

    if (!in_array($methode, $methodesAutorisees, true)) {
        $erreur = 'Choisissez une méthode de paiement valide.';
    } elseif (in_array($methode, ['Airtel Money', 'Moov Money'], true) && trim((string) $commande['telephone']) === '') {
        $erreur = 'Ajoutez un numéro de téléphone à votre compte avant de choisir un paiement mobile.';
    } elseif (strlen($reference) > 100) {
        $erreur = 'La référence de paiement est trop longue.';
    } else {
        try {
            $verification = $pdo->prepare(
                "SELECT id_paiement FROM PAIEMENTS WHERE id_commandes = ? LIMIT 1"
            );
            $verification->execute([$idCommande]);
            if ($verification->fetchColumn()) {
                $message = 'Une demande de paiement existe déjà pour cette commande. Elle sera vérifiée par notre équipe.';
            } else {
                $referenceApi = $reference ?: null;
                if ($methode === 'Airtel Money') {
                    $apiResult = initiateAirtelPayment((float) $commande['total_commandes'], $commande['code_paiement'], (string) $commande['telephone']);
                    if ($apiResult['ok']) {
                        $referenceApi = (string) ($apiResult['data']['transaction']['id'] ?? $commande['code_paiement']);
                        $message = 'La demande Airtel Money a été envoyée. Attendez la validation de notre équipe.';
                    } elseif ($apiResult['configured'] ?? false) {
                        $erreur = $apiResult['message'];
                    }
                } elseif ($methode === 'Moov Money') {
                    $apiResult = initiateMoovPayment((float) $commande['total_commandes'], $commande['code_paiement'], (string) $commande['telephone']);
                    if ($apiResult['ok']) {
                        $referenceApi = (string) ($apiResult['data']['transaction_id'] ?? $apiResult['data']['reference'] ?? $commande['code_paiement']);
                        $message = 'La demande Moov Money a été envoyée. Attendez la validation de notre équipe.';
                    } elseif ($apiResult['configured'] ?? false) {
                        $erreur = $apiResult['message'];
                    }
                }

                if ($erreur === '') {
                    $insert = $pdo->prepare(
                    "INSERT INTO PAIEMENTS (id_commandes, montant, methode_paiement, reference_paiement, date_paiement, statut_paiement)
                     VALUES (?, ?, ?, ?, NOW(), 'en attente')"
                    );
                    $insert->execute([$idCommande, $commande['total_commandes'], $methode, $referenceApi]);
                    if ($message === '') {
                        $message = 'Votre demande de paiement a été transmise. La confirmation finale sera faite après vérification.';
                    }
                }
            }
        } catch (PDOException $e) {
            error_log('Erreur demande paiement : ' . $e->getMessage());
            $erreur = 'Impossible d’enregistrer la demande pour le moment.';
        }
    }
}

$paymentStmt = $pdo->prepare("SELECT * FROM PAIEMENTS WHERE id_commandes = ? LIMIT 1");
$paymentStmt->execute([$idCommande]);
$paiement = $paymentStmt->fetch();

include 'includes/header.php';
?>

<div class="container" style="padding-top:160px;padding-bottom:90px;max-width:760px;">
    <div style="margin-bottom:28px;">
        <span class="section-tag">Règlement sécurisé</span>
        <h1 class="section-title" style="color:#111;">PAYER MAINTENANT</h1>
    </div>

    <?php if ($message): ?>
        <div style="background:#dcfce7;color:#166534;border:1px solid #bbf7d0;border-radius:12px;padding:16px;margin-bottom:20px;font-weight:700;">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    <?php if ($erreur): ?>
        <div style="background:#fee2e2;color:#991b1b;border:1px solid #fecaca;border-radius:12px;padding:16px;margin-bottom:20px;font-weight:700;">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($erreur); ?>
        </div>
    <?php endif; ?>

    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:28px;box-shadow:0 8px 24px rgba(15,23,42,.06);">
        <div style="display:flex;justify-content:space-between;gap:18px;flex-wrap:wrap;border-bottom:1px solid #e2e8f0;padding-bottom:20px;margin-bottom:24px;">
            <div>
                <div style="color:#64748b;font-size:.8rem;text-transform:uppercase;font-weight:800;">Commande</div>
                <strong style="font-size:1.25rem;color:#0f172a;">#<?php echo str_pad($commande['id_commandes'], 4, '0', STR_PAD_LEFT); ?></strong>
            </div>
            <div style="text-align:right;">
                <div style="color:#64748b;font-size:.8rem;text-transform:uppercase;font-weight:800;">Montant à régler</div>
                <strong style="font-size:1.35rem;color:#b48600;"><?php echo formatPrice((float) $commande['total_commandes']); ?></strong>
            </div>
        </div>

        <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:16px;margin-bottom:24px;">
            <div style="font-size:.75rem;color:#92400e;text-transform:uppercase;font-weight:800;">Code de paiement</div>
            <div style="font-size:1.4rem;letter-spacing:2px;font-weight:900;color:#78350f;margin-top:4px;"><?php echo htmlspecialchars($commande['code_paiement']); ?></div>
            <p style="margin:8px 0 0;color:#92400e;font-size:.82rem;">Présentez ce code à notre équipe. Ne versez jamais d’argent à un numéro non confirmé par Dutch Company Diesel Gabon.</p>
        </div>

        <?php if ($paiement): ?>
            <div style="background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe;border-radius:12px;padding:16px;font-size:.9rem;">
                <strong>Demande enregistrée</strong><br>
                Méthode : <?php echo htmlspecialchars($paiement['methode_paiement']); ?><br>
                Statut : <?php echo htmlspecialchars($paiement['statut_paiement']); ?>
            </div>
        <?php elseif ($commande['statut_commandes'] === 'payée'): ?>
            <div style="background:#dcfce7;color:#166534;border:1px solid #bbf7d0;border-radius:12px;padding:16px;">Cette commande est déjà payée. Consultez votre facture finale.</div>
        <?php else: ?>
            <form method="POST" action="payer_commande.php?id=<?php echo (int) $idCommande; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                <label for="methode_paiement" style="display:block;color:#334155;font-weight:800;margin-bottom:8px;">Méthode de paiement</label>
                <select id="methode_paiement" name="methode_paiement" required style="width:100%;padding:13px;border:1px solid #cbd5e1;border-radius:10px;margin-bottom:18px;">
                    <option value="">Choisir une méthode</option>
                    <option>Airtel Money</option>
                    <option>Moov Money</option>
                    <option>Virement bancaire</option>
                    <option>Paiement en local</option>
                </select>

                <label for="reference_paiement" style="display:block;color:#334155;font-weight:800;margin-bottom:8px;">Référence de transaction (facultatif)</label>
                <input id="reference_paiement" type="text" name="reference_paiement" maxlength="100" placeholder="Ex. référence Airtel, Moov ou virement" style="width:100%;padding:13px;border:1px solid #cbd5e1;border-radius:10px;margin-bottom:20px;">

                <button type="submit" class="btn-gold" style="width:100%;border:0;">ENVOYER LA DEMANDE DE PAIEMENT</button>
            </form>
        <?php endif; ?>
    </div>

    <div style="text-align:center;margin-top:22px;">
        <a href="facture.php?id=<?php echo (int) $idCommande; ?>" style="color:#b48600;font-weight:800;text-decoration:none;margin-right:16px;">Voir le justificatif</a>
        <a href="mes_commandes.php" style="color:#64748b;font-weight:700;text-decoration:none;">Retour à mes commandes</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
