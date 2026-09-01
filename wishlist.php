<?php
require_once __DIR__ . '/config/database.php';

// --- Protection : accès réservé aux utilisateurs connectés ---
if (!isLoggedIn()) {
    header('Location: login.php?redirect=wishlist.php');
    exit;
}

include 'includes/header.php';

$user_id = $_SESSION['user_id'];
$favoris = [];

// --- Récupération des favoris avec les infos produit ---
try {
    $stmt = $pdo->prepare("
        SELECT p.*, m.nom_marques, f.id_favori
        FROM FAVORIS f
        JOIN PRODUITS p ON f.id_produit = p.id_produit
        JOIN MARQUES m ON p.id_marques = m.id_marques
        WHERE f.id_clients = ?
        ORDER BY f.id_favori DESC
    ");
    $stmt->execute([$user_id]);
    $favoris = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "<div class='container py-5'><div class='alert alert-danger'>Erreur lors du chargement des favoris : " . htmlspecialchars($e->getMessage()) . "</div></div>";
}
?>

<div class="container py-5">
    <div class="section-header">
        <div>
            <span class="section-tag">Mon espace</span>
            <h2 class="section-title">MES FAVORIS</h2>
        </div>
    </div>

    <?php if (empty($favoris)): ?>
        <div class="text-center py-5">
            <i class="fas fa-heart-broken mb-3" style="font-size:48px; color:var(--border);"></i>
            <p class="text-muted">Tu n'as encore ajouté aucun produit à tes favoris.</p>
            <a href="catalogue.php" class="btn-gold" style="width:auto; display:inline-block;">VOIR LE CATALOGUE</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($favoris as $p): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="product-card">
                    <div class="product-image-wrapper">
                        <span class="product-badge <?php echo $p['stock_produit'] > 0 ? 'badge-stock' : 'badge-rupture'; ?>">
                            <?php echo $p['stock_produit'] > 0 ? 'EN STOCK' : 'RUPTURE'; ?>
                        </span>
                        <img src="assets/images/<?php echo htmlspecialchars($p['image_produit'] ?? 'default.png'); ?>" alt="<?php echo htmlspecialchars($p['nom_produit']); ?>">
                    </div>
                    <div class="product-body">
                        <div class="product-brand"><?php echo htmlspecialchars($p['nom_marques']); ?></div>
                        <div class="product-name"><?php echo htmlspecialchars($p['nom_produit']); ?></div>
                        <div class="product-ref">OEM: <?php echo htmlspecialchars($p['reference_oem'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="product-footer">
                        <div class="product-price"><?php echo formatPrice($p['prix_produit']); ?></div>
                        <form action="actions/add_to_cart.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                            <input type="hidden" name="product_id" value="<?php echo $p['id_produit']; ?>">
                            <input type="hidden" name="redirect" value="wishlist.php">
                            <button type="submit" class="btn-cart"><i class="fas fa-plus me-1"></i>PANIER</button>
                        </form>
                    </div>
                    <!-- Retirer des favoris -->
                    <form action="actions/remove_from_wishlist.php" method="POST" class="mt-2 px-2 pb-2">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                        <input type="hidden" name="id_favori" value="<?php echo $p['id_favori']; ?>">
                        <button type="submit" class="btn-outline w-100" style="font-size:12px; padding:8px;">
                            <i class="fas fa-times me-1"></i>RETIRER
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>