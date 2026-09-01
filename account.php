<?php
require_once __DIR__ . '/config/database.php';

// --- Protection : accès réservé aux utilisateurs connectés ---
if (!isLoggedIn()) {
    header('Location: login.php?redirect=account.php');
    exit;
}

include 'includes/header.php';

$user_id = $_SESSION['user_id']; // doit correspondre à id_clients (voir login.php)

// --- Récupération des infos client (table CLIENTS) ---
try {
    $stmt = $pdo->prepare("SELECT * FROM CLIENTS WHERE id_clients = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $user = null;
    echo "<div class='container py-5'><div class='alert alert-danger'>Erreur lors du chargement du profil : " . htmlspecialchars($e->getMessage()) . "</div></div>";
}

// --- Récupération de l'adresse principale (table ADRESSES) ---
$adresse = null;
try {
    $stmtA = $pdo->prepare("SELECT * FROM ADRESSES WHERE id_clients = ? LIMIT 1");
    $stmtA->execute([$user_id]);
    $adresse = $stmtA->fetch();
} catch (PDOException $e) {
    $adresse = null;
}

// --- Récupération de l'historique des commandes (table COMMANDES) ---
$commandes = [];
try {
    $stmt2 = $pdo->prepare("SELECT * FROM COMMANDES WHERE id_clients = ? ORDER BY date_commandes DESC");
    $stmt2->execute([$user_id]);
    $commandes = $stmt2->fetchAll();
} catch (PDOException $e) {
    $commandes = [];
}

$totalCommandes = count($commandes);
$totalDepense = array_sum(array_map(static function ($commande) {
    return (float) ($commande['total_commandes'] ?? 0);
}, $commandes));
?>

<style>
.account-page { min-height:100vh; padding:150px 0 90px; background:radial-gradient(circle at 85% 10%, rgba(255,199,0,.1), transparent 27%), var(--bg-root); }
.account-hero { display:flex; align-items:flex-end; justify-content:space-between; gap:24px; margin-bottom:32px; }
.account-kicker { color:var(--gold); font-size:.68rem; font-weight:800; letter-spacing:2px; text-transform:uppercase; }
.account-title { margin:8px 0 5px; color:#fff; font-size:clamp(2rem,4vw,3rem); font-weight:900; letter-spacing:-1.5px; text-transform:uppercase; }
.account-welcome { margin:0; color:#888; font-size:.9rem; }
.account-logout { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border:1px solid rgba(255,255,255,.12); border-radius:999px; color:#aaa; font-size:.72rem; font-weight:800; text-decoration:none; transition:all .25s ease; }.account-logout:hover { border-color:var(--gold); background:var(--gold); color:#000; }
.account-stat { height:100%; padding:20px; border:1px solid var(--border); border-radius:16px; background:linear-gradient(145deg,#1d1d1d,#121212); }.account-stat-icon { display:flex; align-items:center; justify-content:center; width:40px; height:40px; margin-bottom:16px; border-radius:12px; background:rgba(255,199,0,.1); color:var(--gold); }.account-stat-value { display:block; overflow:hidden; color:#fff; font-size:1.15rem; font-weight:900; text-overflow:ellipsis; white-space:nowrap; }.account-stat-label { color:#777; font-size:.64rem; letter-spacing:1px; text-transform:uppercase; }
.account-panel,.account-orders { border:1px solid var(--border); border-radius:20px; background:rgba(255,255,255,.035); box-shadow:0 18px 45px rgba(0,0,0,.18); }.account-panel { height:100%; padding:26px; }.account-panel-heading { display:flex; align-items:center; gap:12px; padding-bottom:18px; margin-bottom:20px; border-bottom:1px solid var(--border); }.account-panel-heading i { color:var(--gold); }.account-panel-heading h3,.account-orders-head h3 { margin:0; color:#fff; font-size:.82rem; letter-spacing:1.5px; text-transform:uppercase; }
.account-identity { display:flex; align-items:center; gap:15px; margin-bottom:20px; }.account-avatar { display:flex; align-items:center; justify-content:center; width:58px; height:58px; flex:0 0 58px; border-radius:50%; background:var(--gold); color:#111; font-size:1.25rem; font-weight:900; }.account-name { color:#fff; font-size:1rem; font-weight:800; }.account-email { color:#777; font-size:.75rem; word-break:break-word; }.account-detail { display:flex; align-items:flex-start; gap:12px; padding:12px 0; border-top:1px solid rgba(255,255,255,.06); color:#999; font-size:.78rem; }.account-detail i { width:18px; color:var(--gold); text-align:center; }.account-detail strong { display:block; color:#ddd; font-size:.76rem; }.account-detail span { display:block; margin-top:2px; }
.account-actions { display:grid; gap:10px; }.account-action { display:flex; align-items:center; justify-content:space-between; padding:14px 15px; border:1px solid rgba(255,255,255,.1); border-radius:11px; color:#ddd; text-decoration:none; font-size:.78rem; font-weight:700; transition:all .25s ease; }.account-action span i { width:24px; color:var(--gold); }.account-action > i { color:#555; }.account-action:hover { border-color:var(--gold); background:rgba(255,199,0,.07); color:#fff; transform:translateX(3px); }
.account-orders { overflow:hidden; }.account-orders-head { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:24px 26px; border-bottom:1px solid var(--border); }.account-orders-head span { color:#777; font-size:.74rem; }.account-table { margin:0; color:#aaa; }.account-table thead th { padding:16px 26px; color:#666; border-bottom:1px solid var(--border); font-size:.62rem; letter-spacing:1.5px; text-transform:uppercase; }.account-table tbody td { padding:18px 26px; border-bottom:1px solid rgba(255,255,255,.05); font-size:.78rem; vertical-align:middle; }.account-table tbody tr:last-child td { border-bottom:0; }.account-order-id { color:#fff; font-weight:800; }.account-status { display:inline-block; padding:5px 9px; border:1px solid rgba(255,199,0,.25); border-radius:999px; color:var(--gold); font-size:.62rem; font-weight:800; text-transform:uppercase; }.account-empty { padding:45px 25px; text-align:center; color:#777; }.account-empty i { display:block; margin-bottom:12px; color:#444; font-size:2rem; }
@media (max-width:767px) { .account-page { padding-top:125px; }.account-hero { align-items:flex-start; flex-direction:column; }.account-table thead { display:none; }.account-table tbody tr { display:block; padding:14px 20px; border-bottom:1px solid rgba(255,255,255,.05); }.account-table tbody td { display:flex; justify-content:space-between; gap:15px; padding:8px 0; border:0; }.account-table tbody td::before { color:#666; content:attr(data-label); font-size:.62rem; letter-spacing:1px; text-transform:uppercase; } }

.account-logout:hover { color:#000; background:var(--gold); border-color:var(--gold); }
.account-stat { height:100%; padding:20px; border:1px solid var(--border); border-radius:16px; background:linear-gradient(145deg,#1c1c1c,#121212); }
.account-stat-icon { display:flex; align-items:center; justify-content:center; width:40px; height:40px; margin-bottom:18px; border-radius:12px; color:var(--gold); background:rgba(255,199,0,.1); }
.account-stat-value { display:block; color:#fff; font-size:1.2rem; font-weight:900; }
.account-stat-label { color:#777; font-size:.68rem; letter-spacing:1px; text-transform:uppercase; }
.account-panel { height:100%; padding:26px; border:1px solid var(--border); border-radius:20px; background:rgba(255,255,255,.035); box-shadow:0 18px 45px rgba(0,0,0,.18); }
.account-panel-heading { display:flex; align-items:center; gap:12px; padding-bottom:18px; margin-bottom:20px; border-bottom:1px solid var(--border); }
.account-panel-heading i { color:var(--gold); font-size:1.1rem; }.account-panel-heading h3 { margin:0; color:#fff; font-size:.82rem; letter-spacing:1.5px; text-transform:uppercase; }
.account-identity { display:flex; align-items:center; gap:15px; margin-bottom:22px; }.account-avatar { display:flex; align-items:center; justify-content:center; width:58px; height:58px; flex:0 0 58px; border-radius:50%; color:#111; background:var(--gold); font-size:1.25rem; font-weight:900; }
.account-name { color:#fff; font-size:1.05rem; font-weight:800; }.account-email { color:#777; font-size:.76rem; word-break:break-word; }
.account-detail { display:flex; align-items:flex-start; gap:12px; padding:12px 0; border-top:1px solid rgba(255,255,255,.06); color:#999; font-size:.8rem; }.account-detail i { width:18px; color:var(--gold); text-align:center; }.account-detail strong { display:block; color:#ddd; font-size:.78rem; }.account-detail span { display:block; margin-top:2px; }
.account-actions { display:grid; gap:10px; }.account-action { display:flex; align-items:center; justify-content:space-between; padding:14px 15px; border:1px solid rgba(255,255,255,.1); border-radius:11px; color:#ddd; text-decoration:none; font-size:.8rem; font-weight:700; transition:all .25s ease; }.account-action i:first-child { width:24px; color:var(--gold); }.account-action i:last-child { color:#555; }.account-action:hover { color:#fff; border-color:var(--gold); background:rgba(255,199,0,.07); transform:translateX(3px); }
.account-orders { overflow:hidden; border:1px solid var(--border); border-radius:20px; background:rgba(255,255,255,.035); }.account-orders-head { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:24px 26px; border-bottom:1px solid var(--border); }.account-orders-head h3 { margin:0; color:#fff; font-size:.9rem; letter-spacing:1.5px; text-transform:uppercase; }.account-orders-head span { color:#777; font-size:.75rem; }
.account-table { margin:0; color:#aaa; }.account-table thead th { padding:16px 26px; color:#666; border-bottom:1px solid var(--border); font-size:.65rem; letter-spacing:1.5px; text-transform:uppercase; }.account-table tbody td { padding:18px 26px; border-bottom:1px solid rgba(255,255,255,.05); font-size:.8rem; vertical-align:middle; }.account-table tbody tr:last-child td { border-bottom:0; }.account-order-id { color:#fff; font-weight:800; }.account-status { display:inline-block; padding:5px 9px; border:1px solid rgba(255,199,0,.25); border-radius:999px; color:var(--gold); font-size:.65rem; font-weight:800; text-transform:uppercase; }.account-empty { padding:45px 25px; text-align:center; color:#777; }.account-empty i { display:block; margin-bottom:12px; color:#444; font-size:2rem; }
@media (max-width:767px) { .account-page { padding-top:125px; }.account-hero { align-items:flex-start; flex-direction:column; }.account-table thead { display:none; }.account-table tbody tr { display:block; padding:14px 20px; border-bottom:1px solid rgba(255,255,255,.05); }.account-table tbody td { display:flex; justify-content:space-between; gap:15px; padding:8px 0; border:0; }.account-table tbody td::before { color:#666; content:attr(data-label); font-size:.65rem; letter-spacing:1px; text-transform:uppercase; } }
</style>

<main class="account-page"><div class="container">
    <div class="account-hero">
        <div><span class="account-kicker">Espace client</span><h1 class="account-title">Mon compte</h1><p class="account-welcome">Ravi de vous revoir, <?php echo htmlspecialchars($user['prenom_client'] ?? 'client'); ?>.</p></div>
        <a href="logout.php" class="account-logout"><i class="fas fa-sign-out-alt"></i> Se déconnecter</a>
    </div>

    <?php if ($user): ?>
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><div class="account-stat"><span class="account-stat-icon"><i class="fas fa-receipt"></i></span><span class="account-stat-value"><?php echo $totalCommandes; ?></span><span class="account-stat-label">Commande(s)</span></div></div>
        <div class="col-6 col-lg-3"><div class="account-stat"><span class="account-stat-icon"><i class="fas fa-wallet"></i></span><span class="account-stat-value"><?php echo formatPrice($totalDepense); ?></span><span class="account-stat-label">Total dépensé</span></div></div>
        <div class="col-6 col-lg-3"><div class="account-stat"><span class="account-stat-icon"><i class="fas fa-heart"></i></span><span class="account-stat-value">Favoris</span><span class="account-stat-label">Pièces sauvegardées</span></div></div>
        <div class="col-6 col-lg-3"><div class="account-stat"><span class="account-stat-icon"><i class="fas fa-headset"></i></span><span class="account-stat-value">24/7</span><span class="account-stat-label">Support client</span></div></div>
    </div>
    <div class="row g-4 mb-5">
        <div class="col-lg-7"><section class="account-panel"><div class="account-panel-heading"><i class="fas fa-user-circle"></i><h3>Informations personnelles</h3></div><div class="account-identity"><div class="account-avatar"><?php echo strtoupper(substr($user['prenom_client'] ?? 'C', 0, 1)); ?></div><div><div class="account-name"><?php echo htmlspecialchars(($user['prenom_client'] ?? '') . ' ' . ($user['nom_client'] ?? '')); ?></div><div class="account-email"><?php echo htmlspecialchars($user['email'] ?? '-'); ?></div></div></div><div class="account-detail"><i class="fas fa-map-marker-alt"></i><div><strong>Adresse de livraison</strong><span><?php echo $adresse ? htmlspecialchars(($adresse['rue'] ?? '-') . ', ' . ($adresse['ville'] ?? '-') . ' · ' . ($adresse['pays'] ?? '')) : 'Aucune adresse enregistrée'; ?></span></div></div><div class="account-detail"><i class="fas fa-shield-alt"></i><div><strong>Compte sécurisé</strong><span>Vos informations sont protégées</span></div></div></section></div>
        <div class="col-lg-5"><section class="account-panel"><div class="account-panel-heading"><i class="fas fa-bolt"></i><h3>Accès rapides</h3></div><div class="account-actions"><a class="account-action" href="wishlist.php"><span><i class="fas fa-heart"></i> Mes favoris</span><i class="fas fa-arrow-right"></i></a><a class="account-action" href="panier.php"><span><i class="fas fa-shopping-bag"></i> Mon panier</span><i class="fas fa-arrow-right"></i></a><a class="account-action" href="mailto:contact@dcd-gabon.com"><span><i class="fas fa-headset"></i> Contacter le support</span><i class="fas fa-arrow-right"></i></a></div></section></div>
    </div>
    <?php else: ?>
        <p class="text-muted">Profil introuvable. Vérifie la structure de la table UTILISATEURS.</p>
    <?php endif; ?>

    <section class="account-orders">
        <div class="account-orders-head"><h3>Historique des commandes</h3><span><?php echo $totalCommandes; ?> commande(s)</span></div>
    <?php if (empty($commandes)): ?>
        <div class="account-empty"><i class="fas fa-inbox"></i>Aucune commande pour le moment.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-dark table-borderless account-table">
                <thead>
                    <tr>
                        <th>N° Commande</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($commandes as $cmd): ?>
                    <tr>
                        <td data-label="Commande"><span class="account-order-id">#<?php echo htmlspecialchars($cmd['id_commandes']); ?></span></td>
                        <td data-label="Date"><?php echo htmlspecialchars($cmd['date_commandes']); ?></td>
                        <td data-label="Statut"><span class="account-status"><?php echo htmlspecialchars($cmd['statut_commandes'] ?? 'En cours'); ?></span></td>
                        <td data-label="Total"><?php echo formatPrice($cmd['total_commandes'] ?? 0); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    </section>
</div></main>

<?php include 'includes/footer.php'; ?>