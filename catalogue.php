<?php
include 'includes/header.php';

// ============================================================
// 1. Catégories
// ============================================================
$toutesCategories = $pdo->query("SELECT * FROM CATEGORIES ORDER BY nom_categories")->fetchAll();

// ============================================================
// 2. Filtres
// ============================================================
$where  = ["1=1"];
$params = [];

if (!empty($_GET['search'])) {
    $where[]  = "(p.nom_produit LIKE ? OR p.description_produit LIKE ?)";
    $params[] = "%" . trim($_GET['search']) . "%";
    $params[] = "%" . trim($_GET['search']) . "%";
}
if (!empty($_GET['oem'])) {
    $where[]  = "p.reference_oem LIKE ?";
    $params[] = "%" . trim($_GET['oem']) . "%";
}

$tri = $_GET['sort'] ?? 'stock';
$trisAutorises = [
    'stock' => 'p.stock_produit DESC, p.nom_produit ASC',
    'recent' => 'p.id_produit DESC',
    'prix_asc' => 'p.prix_produit ASC, p.nom_produit ASC',
    'prix_desc' => 'p.prix_produit DESC, p.nom_produit ASC',
];
$ordreSql = $trisAutorises[$tri] ?? $trisAutorises['stock'];

if (($_GET['promo'] ?? '') === '1') {
    $where[] = "EXISTS (
        SELECT 1 FROM PROMOTIONS pr
        WHERE pr.actif = 1
          AND CURDATE() BETWEEN pr.date_debut AND pr.date_fin
          AND (pr.id_produit = p.id_produit OR pr.id_categories = p.id_categories)
    )";
}

$catSelectionnee = null;

if (!empty($_GET['cat'])) {
    $cat = trim($_GET['cat']);

    // Si l'URL contient un ID (ex: ?cat=3)
    if (is_numeric($cat)) {
        $catSelectionnee = (int)$cat;
        $where[] = "p.id_categories = ?";
        $params[] = $catSelectionnee;
    } else {
        // Si l'URL contient un nom (ex: ?cat=transmission)
        $where[] = "LOWER(REPLACE(c.nom_categories,' ','-')) = ?";
        $params[] = strtolower($cat);
    }
}

$prixMaxAffiche = 5000000;
if (isset($_GET['prix_max']) && is_numeric($_GET['prix_max'])) {
    $prixMaxAffiche = max(0, (int) $_GET['prix_max']);
    $where[]  = "p.prix_produit <= ?";
    $params[] = $prixMaxAffiche;
}
if (!empty($_GET['stock']) && $_GET['stock'] === '1') {
    $where[] = "p.stock_produit > 0";
}

$whereStr = implode(" AND ", $where);

// ============================================================
// 3. Produits groupés STRICTEMENT par catégorie
// ============================================================
$sql = "SELECT p.*, m.nom_marques, c.nom_categories 
        FROM PRODUITS p 
        JOIN MARQUES m ON p.id_marques = m.id_marques
        JOIN CATEGORIES c ON p.id_categories = c.id_categories
        WHERE $whereStr
        ORDER BY $ordreSql";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$allProduits = $stmt->fetchAll();

$produitsParCategorie = [];
foreach ($allProduits as $p) {
    $idCat = $p['id_categories'];
    $produitsParCategorie[$idCat]['nom'] = $p['nom_categories'];
    $produitsParCategorie[$idCat]['produits'][] = $p;
}
$total = count($allProduits);
?>

<style>
/* ============================================================
   Page Catalogue Sublimée
   ============================================================ */
.catalogue-page {
    background: #f4f5f7;
    min-height: 100vh;
    padding-top: 178px;
    padding-bottom: 80px;
}

.catalogue-header {
    margin-bottom: 30px;
}

.catalogue-header h1 {
    font-size: 1.75rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: -0.5px;
    margin: 0 0 6px 0;
    color: #111;
}

.catalogue-header p {
    color: #666;
    font-size: 0.92rem;
    margin: 0;
}

/* ============================================================
   Sidebar Filtres Pro
   ============================================================ */
.filter-sidebar {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    border: 1px solid rgba(0,0,0,0.06);
    box-shadow: 0 10px 30px rgba(0,0,0,0.03);
    position: relative;
    top: auto;
}

@media (max-width: 991.98px) {
    .catalogue-page {
        padding-top: 118px;
    }

    .filter-sidebar {
        margin-bottom: 4px;
    }
}

.filter-title {
    font-size: 0.9rem;
    font-weight: 800;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #111;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #f0f0f0;
    display: flex;
    align-items: center;
}

.filter-group {
    margin-bottom: 20px;
}

.filter-group-label {
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #888;
    letter-spacing: 0.5px;
    margin-bottom: 10px;
}

.form-input {
    width: 100%;
    padding: 10px 14px;
    background: #f9f9f9;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.88rem;
    color: #333;
    outline: none;
    transition: all 0.2s ease;
}

.form-input:focus {
    background: #fff;
    border-color: #c9a227;
    box-shadow: 0 0 0 3px rgba(201,162,39,0.15);
}

.filter-check {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: #444;
    cursor: pointer;
    margin-bottom: 8px;
    transition: color 0.2s;
}

.filter-check:hover {
    color: #c9a227;
}

.filter-check input {
    accent-color: #c9a227;
    width: 16px;
    height: 16px;
    cursor: pointer;
}

.price-display {
    display: flex;
    justify-content: space-between;
    font-size: 0.82rem;
    font-weight: 700;
    color: #111;
    margin-top: 6px;
}

/* ============================================================
   Blocs de Catégorie & Ligne Coulissante
   ============================================================ */
.category-block {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 28px;
    border: 1px solid rgba(0,0,0,0.05);
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
}

.category-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.category-top h2 {
    font-size: 1.2rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: -0.3px;
    margin: 0;
    color: #111;
}

.category-top a {
    font-size: 0.82rem;
    font-weight: 700;
    color: #c9a227;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: transform 0.2s, color 0.2s;
}

.category-top a:hover {
    color: #111;
    transform: translateX(3px);
}

.products-row {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
    gap: 16px;
    overflow: visible;
    padding-bottom: 10px;
    padding-top: 4px;
}

/* ============================================================
   Cartes Produits Raffinées
   ============================================================ */
.product-card {
    min-width: 0;
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid #eaedf1;
    transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    display: flex;
    flex-direction: column;
}

.product-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.08);
    border-color: #c9a227;
}

.product-link {
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.product-image-wrapper {
    position: relative;
    width: 100%;
    height: 160px;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.product-image-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    pointer-events: none;
    transition: transform 0.3s ease;
}

.product-card:hover .product-image-wrapper img {
    transform: scale(1.05);
}

.product-image-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #cbd5e1;
    font-size: 2.2rem;
}

.product-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    font-size: 0.6rem;
    font-weight: 800;
    padding: 3px 8px;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    z-index: 2;
}

.badge-stock { background: #dcfce7; color: #166534; }
.badge-promo { background: #fef9c3; color: #854d0e; }
.badge-rupture { background: #fee2e2; color: #991b1b; }

.product-wishlist {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
    z-index: 2;
}

.product-wishlist:hover {
    background: #fff;
    color: #ef4444;
    transform: scale(1.1);
}

.product-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.product-card:hover .product-overlay {
    opacity: 1;
}

.product-overlay-btn {
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 1px;
    color: #fff;
    border: 1px solid #fff;
    padding: 6px 12px;
    border-radius: 6px;
    text-transform: uppercase;
}

.product-body {
    padding: 12px 14px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.product-brand {
    font-size: 0.65rem;
    font-weight: 800;
    color: #c9a227;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
}

.product-name {
    font-size: 0.85rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.product-ref {
    font-size: 0.68rem;
    color: #64748b;
}

.product-footer {
    padding: 10px 14px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-top: 1px solid #f1f5f9;
}

.product-price {
    font-weight: 900;
    color: #0f172a;
    font-size: 0.9rem;
}

.btn-cart {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #f1f5f9;
    color: #0f172a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-cart:hover {
    background: #c9a227;
    color: #fff;
}

.see-more-card {
    flex: 0 0 160px;
    scroll-snap-align: start;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
    border: 2px dashed #cbd5e1;
    border-radius: 14px;
    text-decoration: none;
    color: #64748b;
    transition: all 0.25s ease;
}

.see-more-card:hover {
    border-color: #c9a227;
    color: #c9a227;
    background: #fff;
    transform: translateY(-4px);
}
</style>

<div class="catalogue-page">
    <div class="container">
        <div class="row g-4">

            <!-- FILTRES -->
            <div class="col-lg-3">
                <form method="GET" action="catalogue.php">
                    <div class="filter-sidebar">
                        <div class="filter-title"><i class="fas fa-sliders-h me-2"></i>Filtres</div>

                        <div class="filter-group">
                            <div class="filter-group-label">Recherche</div>
                            <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" class="form-input" placeholder="Nom, référence OEM...">
                        </div>

                        <div class="filter-group">
                            <div class="filter-group-label">Trier par</div>
                            <select name="sort" class="form-input">
                                <option value="stock" <?= $tri === 'stock' ? 'selected' : '' ?>>Disponibilité</option>
                                <option value="recent" <?= $tri === 'recent' ? 'selected' : '' ?>>Nouveautés</option>
                                <option value="prix_asc" <?= $tri === 'prix_asc' ? 'selected' : '' ?>>Prix croissant</option>
                                <option value="prix_desc" <?= $tri === 'prix_desc' ? 'selected' : '' ?>>Prix décroissant</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <div class="filter-group-label">Catégories</div>
                            <label class="filter-check">
                                <input type="radio" name="cat" value="" <?= $catSelectionnee === null ? 'checked' : '' ?>>
                                Toutes les catégories
                            </label>
                            <?php foreach ($toutesCategories as $cat): ?>
                                <label class="filter-check">
                                    <input type="radio" name="cat" value="<?= $cat['id_categories'] ?>"
                                           <?= $catSelectionnee === (int)$cat['id_categories'] ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($cat['nom_categories']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="filter-group">
                            <div class="filter-group-label">Prix Maximum</div>
                            <input type="range" id="priceRange" name="prix_max" min="0" max="5000000" step="50000"
                                   value="<?= $prixMaxAffiche ?>" style="width:100%; accent-color:#c9a227;"
                                   oninput="document.getElementById('priceVal').textContent = parseInt(this.value).toLocaleString('fr-FR') + ' FCFA'">
                            <div class="price-display">
                                <span>0 FCFA</span>
                                <span id="priceVal"><?= number_format($prixMaxAffiche, 0, ',', ' ') ?> FCFA</span>
                            </div>
                        </div>

                        <div class="filter-group">
                            <div class="filter-group-label">Disponibilité</div>
                            <label class="filter-check">
                                <input type="checkbox" name="stock" value="1" <?= ($_GET['stock'] ?? '') === '1' ? 'checked' : '' ?>>
                                En stock uniquement
                            </label>
                        </div>

                        <button type="submit" class="btn-gold w-100 py-2 mt-2" style="border-radius:10px; font-weight:800;">APPLIQUER</button>
                        <a href="catalogue.php" style="display:block;text-align:center;color:#888;font-size:0.78rem;margin-top:12px;text-decoration:none;">Réinitialiser les filtres</a>
                    </div>
                </form>
            </div>

            <!-- CONTENU -->
            <div class="col-lg-9">
                <div class="catalogue-header">
                    <h1><?= $total ?> référence<?= $total > 1 ? 's' : '' ?> disponible<?= $total > 1 ? 's' : '' ?></h1>
                    <p><?= ($_GET['promo'] ?? '') === '1' ? 'Promotions actuellement disponibles' : 'Comparez les références et trouvez la pièce adaptée à votre véhicule' ?></p>
                </div>

                <?php if ($total === 0): ?>
                    <div style="text-align:center;padding:80px 20px;background:#fff;border-radius:16px; border:1px solid #eaedf1;">
                        <i class="fas fa-search" style="font-size:3rem;margin-bottom:20px;color:#cbd5e1;"></i>
                        <p style="font-size:1.1rem;color:#475569; font-weight:600;">Aucune pièce trouvée pour votre recherche.</p>
                        <a href="catalogue.php" class="btn-gold" style="width:auto;margin-top:15px;display:inline-block; padding:10px 20px; border-radius:10px;">Voir tout le catalogue</a>
                    </div>
                <?php else: ?>

                    <?php foreach ($produitsParCategorie as $idCat => $section): 
                        $produits = $section['produits'];
                        $hasMore  = false;
                    ?>
                        <div class="category-block">
                            <div class="category-top">
                                <h2><?= htmlspecialchars($section['nom']) ?></h2>
                                <a href="catalogue.php?cat=<?= strtolower(str_replace(' ', '-', $section['nom'])) ?>">
                                    Voir tout (<?= count($section['produits']) ?>) <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>

                            <div class="products-row drag-scroll">
                                <?php foreach ($produits as $i => $p): 
                                    $aImage = !empty($p['image_produit']); ?>
                                    
                                    <div class="product-card">
                                        <a href="produit.php?id=<?= $p['id_produit'] ?>" class="product-link">
                                            <div class="product-image-wrapper">
                                                <span class="product-badge <?= $p['stock_produit'] > 5 ? 'badge-stock' : ($p['stock_produit'] > 0 ? 'badge-promo' : 'badge-rupture') ?>">
                                                    <?= $p['stock_produit'] > 5 ? 'EN STOCK' : ($p['stock_produit'] > 0 ? 'STOCK LIMITÉ' : 'RUPTURE') ?>
                                                </span>
                                                <span class="product-wishlist" aria-hidden="true">
                                                    <i class="fas fa-heart"></i>
                                                </span>

                                                <?php if ($aImage): ?>
                                                    <img src="assets/images/<?= htmlspecialchars($p['image_produit']) ?>"
                                                         alt="<?= htmlspecialchars($p['nom_produit']) ?>" loading="lazy"
                                                         onerror="this.replaceWith(Object.assign(document.createElement('div'), {className:'product-image-placeholder', innerHTML:'<i class=\'fas fa-cog\'></i>'}));">
                                                <?php else: ?>
                                                    <div class="product-image-placeholder"><i class="fas fa-cog"></i></div>
                                                <?php endif; ?>

                                                <div class="product-overlay">
                                                    <span class="product-overlay-btn"><i class="fas fa-eye me-1"></i>Voir</span>
                                                </div>
                                            </div>

                                            <div class="product-body">
                                                <div class="product-brand"><?= htmlspecialchars($p['nom_marques']) ?></div>
                                                <div class="product-name" title="<?= htmlspecialchars($p['nom_produit']) ?>"><?= htmlspecialchars($p['nom_produit']) ?></div>
                                                <div class="product-ref">OEM: <?= htmlspecialchars($p['reference_oem'] ?? 'N/A') ?></div>
                                            </div>
                                        </a>

                                        <div class="product-footer">
                                            <div class="product-price"><?= formatPrice($p['prix_produit']) ?></div>
                                            <form action="actions/add_to_cart.php" method="POST">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                                                <input type="hidden" name="product_id" value="<?= (int) $p['id_produit'] ?>">
                                                <input type="hidden" name="quantity" value="1">
                                                <input type="hidden" name="redirect" value="catalogue.php">
                                                <button type="submit" class="btn-cart" title="Ajouter au panier" aria-label="Ajouter au panier">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                <?php endforeach; ?>

                                <?php if ($hasMore): ?>
                                    <a href="catalogue.php?cat=<?= strtolower(str_replace(' ', '-', $section['nom'])) ?>" class="see-more-card">    
                                        <i class="fas fa-plus-circle" style="font-size:1.8rem;margin-bottom:8px;"></i>
                                        <span style="font-weight:700; font-size:0.9rem;">Voir plus</span>
                                        <small style="margin-top:4px;opacity:0.7; font-size:0.75rem;"><?= count($section['produits']) - 10 ?> autres</small>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPT DRAG TO SCROLL SOURIS -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const rows = document.querySelectorAll('.drag-scroll');

    rows.forEach(row => {
        let isDown = false;
        let startX;
        let scrollLeft;

        row.addEventListener('mousedown', (e) => {
            isDown = true;
            row.classList.add('active');
            startX = e.pageX - row.offsetLeft;
            scrollLeft = row.scrollLeft;
        });

        row.addEventListener('mouseleave', () => {
            isDown = false;
            row.classList.remove('active');
        });

        row.addEventListener('mouseup', () => {
            isDown = false;
            row.classList.remove('active');
        });

        row.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - row.offsetLeft;
            const walk = (x - startX) * 1.5;
            row.scrollLeft = scrollLeft - walk;
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>