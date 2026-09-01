<?php
require_once __DIR__ . '/config/database.php';

if (!isLoggedIn()) { header('Location: login.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM COMMANDES WHERE id_clients = ? ORDER BY date_commandes DESC");
$stmt->execute([$_SESSION['user_id']]);
$commandes = $stmt->fetchAll();

include 'includes/header.php';

function statutClientLabel($s) {
    return match ($s) {
        'payée', 'en préparation', 'expédiée' => 'Confirmée',
        'livrée'  => 'Livrée',
        'annulée' => 'Annulée',
        default   => 'En attente de paiement',
    };
}

function statutClientStep($s) {
    return match ($s) {
        'payée', 'en préparation', 'expédiée' => 1,
        'livrée' => 2,
        default  => 0,
    };
}
?>

<div style="margin-top:140px; padding:50px 0; background:#f8fafc; min-height:80vh;">
    <div class="container">
        
        <?php if(isset($_GET['success'])): ?>
            <div style="background:#dcfce7; color:#166534; padding:16px; border-radius:12px; margin-bottom:24px; font-weight:700; border:1px solid #bbf7d0;">
                <i class="fas fa-check-circle me-2"></i> Votre commande a bien été enregistrée ! Vous pouvez la régler ci-dessous.
            </div>
        <?php endif; ?>

        <h1 style="font-size:1.8rem; font-weight:900; text-transform:uppercase; margin-bottom:30px; color:#0f172a;">
            MES <span style="color:#c9a227;">COMMANDES</span>
        </h1>

        <?php if (empty($commandes)): ?>
        <div style="text-align:center; padding:80px 20px; background:#fff; border-radius:16px; border:1px solid #e2e8f0;">
            <i class="fas fa-box-open" style="font-size:3.5rem; color:#cbd5e1; margin-bottom:15px; display:block;"></i>
            <p style="color:#475569; font-size:1rem; font-weight:600;">Vous n'avez pas encore passé de commande.</p>
        </div>
        <?php else: ?>

        <?php foreach ($commandes as $cmd):
            $step        = statutClientStep($cmd['statut_commandes']);
            $label       = statutClientLabel($cmd['statut_commandes']);
            $isCancelled = $cmd['statut_commandes'] === 'annulée';
            $isPending   = in_array($cmd['statut_commandes'], ['en attente', 'confirmée'], true);
        ?>
        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:24px; margin-bottom:20px; box-shadow:0 4px 15px rgba(0,0,0,0.02);">
            
            <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:20px; align-items:center;">
                <div>
                    <div style="font-weight:900; font-size:1.05rem; color:#0f172a;">
                        Commande #<?php echo str_pad($cmd['id_commandes'], 4, '0', STR_PAD_LEFT); ?>
                    </div>
                    <div style="color:#64748b; font-size:0.82rem; margin-top:2px;">
                        <?php echo date('d/m/Y à H:i', strtotime($cmd['date_commandes'])); ?> ·
                        <strong style="color:#0f172a;"><?php echo number_format($cmd['total_commandes'], 0, ',', ' '); ?> FCFA</strong>
                    </div>
                </div>

                <div style="display:flex; gap:10px; align-items:center;">
                    <!-- Bouton Payer si la commande est toujours en attente -->
                    <?php if($isPending): ?>
                        <a href="payer_commande.php?id=<?php echo $cmd['id_commandes']; ?>" 
                           style="background:#22c55e; color:#fff; padding:8px 16px; border-radius:8px; font-weight:800; font-size:0.8rem; text-decoration:none;">
                            <i class="fas fa-wallet me-1"></i> PAYER MAINTENANT
                        </a>
                    <?php endif; ?>

                    <a href="facture.php?id=<?php echo $cmd['id_commandes']; ?>" style="color:#c9a227; font-size:0.82rem; font-weight:800; text-decoration:none; padding:8px 12px; background:#fefce8; border-radius:8px;">
                        <i class="fas fa-file-invoice me-1"></i> Facture
                    </a>
                </div>
            </div>

            <?php if ($isCancelled): ?>
                <span style="color:#ef4444; font-weight:800; font-size:0.85rem;">
                    <i class="fas fa-times-circle"></i> Commande annulée
                </span>
            <?php else: ?>
                <!-- Suivi de progression -->
                <div style="display:flex; align-items:center; margin-top:15px;">
                    <?php
                    $steps = ['En attente', 'Confirmée / En cours', 'Livrée'];
                    foreach ($steps as $i => $stepLabel):
                        $done = $i <= $step;
                    ?>
                    <div style="flex:1; text-align:center; position:relative;">
                        <div style="width:30px; height:30px; border-radius:50%; margin:0 auto 6px; display:flex; align-items:center; justify-content:center; font-size:0.75rem; font-weight:800; background:<?php echo $done ? '#c9a227' : '#e2e8f0'; ?>; color:<?php echo $done ? '#fff' : '#64748b'; ?>;">
                            <?php echo $done ? '<i class="fas fa-check"></i>' : ($i + 1); ?>
                        </div>
                        <div style="font-size:0.75rem; color:<?php echo $done ? '#0f172a' : '#94a3b8'; ?>; font-weight:700;">
                            <?php echo $stepLabel; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
        <?php endforeach; ?>

        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>