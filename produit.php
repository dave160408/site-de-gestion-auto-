<?php
include 'includes/header.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if(!$id) { header("Location: catalogue.php"); exit; }

$stmt = $pdo->prepare("SELECT p.*, m.nom_marques, c.nom_categories FROM PRODUITS p JOIN MARQUES m ON p.id_marques = m.id_marques JOIN CATEGORIES c ON p.id_categories = c.id_categories WHERE p.id_produit = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();

if(!$p) { header("Location: catalogue.php"); exit; }

// Images produit
$imgStmt = $pdo->prepare("SELECT * FROM IMAGES_PRODUIT WHERE id_produit = ? ORDER BY ordre");
$imgStmt->execute([$id]);
$images = $imgStmt->fetchAll();

// Avis
$avisStmt = $pdo->prepare("SELECT a.*, c.prenom_client FROM AVIS a JOIN CLIENTS c ON a.id_clients = c.id_clients WHERE a.id_produit = ? ORDER BY a.date_avis DESC LIMIT 5");
$avisStmt->execute([$id]);
$avis = $avisStmt->fetchAll();
$avgNote = $pdo->prepare("SELECT AVG(note) as avg FROM AVIS WHERE id_produit = ?");
$avgNote->execute([$id]);
$avg = round($avgNote->fetchColumn(), 1);

// Compatibilites verifiees dans le referentiel vehicules.
$compatibilites = [];
try {
    $compatStmt = $pdo->prepare("SELECT v.model_vehicules, v.annee, v.serie, m.nom_marques
        FROM COMPATIBILITE cp
        INNER JOIN VEHICULES v ON v.id_vehicules = cp.id_vehicules
        INNER JOIN MARQUES m ON m.id_marques = v.id_marques
        WHERE cp.id_produit = ?
        ORDER BY m.nom_marques, v.model_vehicules, v.annee");
    $compatStmt->execute([$id]);
    $compatibilites = $compatStmt->fetchAll();
} catch (PDOException $e) {
    $compatibilites = [];
}
?>

<style>
.compatibility-panel {
    margin: 24px 0 28px;
    padding: 18px;
    border: 1px solid rgba(255,199,0,0.28);
    border-radius: 16px;
    background: linear-gradient(135deg, rgba(255,199,0,0.1), rgba(255,255,255,0.03));
}
.compatibility-heading { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
.compatibility-heading i { color:var(--gold); }
.compatibility-heading strong { color:#fff; font-size:.82rem; letter-spacing:1.5px; text-transform:uppercase; }
.compatibility-heading span { margin-left:auto; color:#22c55e; font-size:.68rem; font-weight:800; text-transform:uppercase; }
.compatibility-search { width:100%; margin-bottom:12px; padding:9px 11px; border:1px solid rgba(255,255,255,.1); border-radius:8px; background:#111; color:#fff; outline:none; }
.compatibility-search:focus { border-color:var(--gold); }
.compatibility-list { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; }
.compatibility-item { padding:9px 10px; border:1px solid rgba(255,255,255,.08); border-radius:9px; background:rgba(0,0,0,.18); color:#ddd; font-size:.76rem; }
.compatibility-item small { display:block; margin-top:2px; color:#777; }
.compatibility-empty { color:#999; font-size:.78rem; margin:0; }
@media (max-width:576px) { .compatibility-list { grid-template-columns:1fr; } }
.product-page-shell { padding:150px 0 90px; background:radial-gradient(circle at 8% 18%, rgba(255,199,0,.08), transparent 28%), var(--bg-root); }
.product-breadcrumb { display:flex; align-items:center; gap:10px; margin-bottom:34px; color:#666; font-size:.75rem; }
.product-breadcrumb a { color:#777; text-decoration:none; transition:color .2s ease; }
.product-breadcrumb a:hover { color:var(--gold); }
.product-breadcrumb .current { max-width:340px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:var(--gold); }
.product-gallery { position:sticky; top:125px; }
.product-gallery .main-img { position:relative; min-height:500px; margin:0; border-radius:24px; padding:34px; background:linear-gradient(145deg,#202020,#101010); border:1px solid rgba(255,199,0,.18); box-shadow:0 24px 60px rgba(0,0,0,.3); overflow:hidden; }
.product-gallery .main-img::before { content:'DUTCH / OEM'; position:absolute; top:22px; left:24px; color:rgba(255,199,0,.55); font-size:.65rem; font-weight:900; letter-spacing:2px; }
.product-gallery .main-img img { max-width:100%; max-height:410px; border-radius:14px; object-fit:contain; filter:drop-shadow(0 18px 24px rgba(0,0,0,.35)); }
.product-gallery .thumbs { margin-top:14px; gap:12px; flex-wrap:wrap; }
.product-gallery .thumb { width:76px; height:76px; border-radius:12px; padding:7px; background:#171717; }
.product-info-panel { height:100%; padding:8px 4px 0 20px; }
.product-detail-brand { display:inline-flex; padding:7px 11px; border:1px solid rgba(255,199,0,.28); border-radius:999px; font-size:.66rem; letter-spacing:1.6px; }
.product-detail-name { max-width:650px; margin:18px 0 10px; font-size:clamp(2rem,4vw,3.5rem); line-height:1.02; letter-spacing:-1.5px; }
.product-detail-ref { color:#777; font-size:.76rem; letter-spacing:.5px; }
.product-price-wrap { display:flex; align-items:end; justify-content:space-between; gap:16px; margin:26px 0 20px; padding:20px 0; border-top:1px solid var(--border); border-bottom:1px solid var(--border); }
.product-detail-price { font-size:clamp(2rem,4vw,2.8rem); }
.product-price-note { color:#777; font-size:.68rem; text-align:right; }
.product-buy-box { display:flex; align-items:center; gap:14px; margin:20px 0 24px; }
.product-buy-box .btn-gold { min-height:48px; border-radius:11px; }
.product-buy-box .qty-selector { border:1px solid #303030; background:#171717; border-radius:11px; }
.product-buy-box .qty-btn, .product-buy-box .qty-input { color:#fff; }
.product-spec-card { margin-top:24px !important; padding:21px !important; border-radius:18px !important; background:#151515 !important; border:1px solid var(--border) !important; }
.product-spec-card h5 { margin-bottom:14px !important; }
.product-spec-card .specs-table td { padding:10px 0; border-bottom:1px solid rgba(255,255,255,.05); }
.product-spec-card .specs-table tr:last-child td { border-bottom:0; }
.product-reviews { margin-top:76px !important; padding-top:34px; border-top:1px solid var(--border); }
.product-reviews h3 { font-size:1.35rem !important; }
@media (max-width:991px) { .product-gallery { position:static; } .product-info-panel { padding:25px 0 0; } }
@media (max-width:576px) { .product-page-shell { padding-top:125px; } .product-gallery .main-img { min-height:330px; } .product-buy-box { align-items:stretch; flex-wrap:wrap; } .product-buy-box .btn-gold { flex:1; } .product-price-wrap { align-items:start; flex-direction:column; } .product-price-note { text-align:left; } }
</style>

<div class="product-page-shell">
    <div class="container">
        <!-- Breadcrumb -->
        <nav class="product-breadcrumb" aria-label="Fil d'Ariane">
            <a href="index.php">Accueil</a>
            <span class="mx-2">/</span>
            <a href="catalogue.php">Catalogue</a>
            <span class="mx-2">/</span>
            <span class="current"><?php echo htmlspecialchars($p['nom_produit']); ?></span>
        </nav>

        <div class="row g-5">
            <!-- GALERIE IMAGES -->
            <div class="col-lg-6" data-aos="fade-right">
                <div class="product-gallery">
                    <div class="main-img">
                        <img id="mainProductImg"
                             src="assets/images/<?php echo htmlspecialchars($p['image_produit'] ?? 'default.png'); ?>"
                             alt="<?php echo htmlspecialchars($p['nom_produit']); ?>">
                    </div>
                    <?php if(count($images) > 0): ?>
                    <div class="thumbs">
                        <div class="thumb active">
                            <img src="assets/images/<?php echo htmlspecialchars($p['image_produit'] ?? 'default.png'); ?>" onclick="changeImg(this)">
                        </div>
                        <?php foreach($images as $img): ?>
                        <div class="thumb">
                            <img src="assets/images/<?php echo htmlspecialchars($img['url_image']); ?>" onclick="changeImg(this)">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- DÉTAILS -->
            <div class="col-lg-6" data-aos="fade-left">
                <div class="product-info-panel">
                <div class="product-detail-brand"><?php echo htmlspecialchars($p['nom_marques']); ?> · <?php echo htmlspecialchars($p['nom_categories']); ?></div>
                <h1 class="product-detail-name"><?php echo htmlspecialchars($p['nom_produit']); ?></h1>
                <div class="product-detail-ref">REF OEM : <?php echo htmlspecialchars($p['reference_oem'] ?? 'N/A'); ?></div>

                <!-- Note globale -->
                <?php if($avg > 0): ?>
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="star-rating">
                        <?php for($i=1;$i<=5;$i++) echo $i<=$avg ? '<i class="fas fa-star filled"></i>' : '<i class="far fa-star"></i>'; ?>
                    </div>
                    <span style="font-size:0.8rem;color:#666;">(<?php echo count($avis); ?> avis)</span>
                </div>
                <?php endif; ?>

                <!-- Stock -->
                <div class="stock-indicator <?php echo $p['stock_produit'] > 5 ? 'stock-ok' : ($p['stock_produit'] > 0 ? 'stock-low' : 'stock-no'); ?>">
                    <i class="fas fa-<?php echo $p['stock_produit'] > 0 ? 'check-circle' : 'times-circle'; ?>"></i>
                    <?php
                    if($p['stock_produit'] > 5) echo 'EN STOCK — Expédition sous 24h';
                    elseif($p['stock_produit'] > 0) echo 'STOCK LIMITÉ — ' . $p['stock_produit'] . ' restant(s)';
                    else echo 'RUPTURE DE STOCK';
                    ?>
                </div>

                <!-- Compatibilite verifiee -->
                <section class="compatibility-panel" aria-labelledby="compatibilityTitle">
                    <div class="compatibility-heading">
                        <i class="fas fa-robot"></i>
                        <strong id="compatibilityTitle">Assistant compatibilité</strong>
                        <?php if (!empty($compatibilites)): ?><span><i class="fas fa-check-circle"></i> Données vérifiées</span><?php endif; ?>
                    </div>
                    <?php if (!empty($compatibilites)): ?>
                        <input class="compatibility-search" type="search" placeholder="Rechercher votre modèle..." aria-label="Rechercher un véhicule compatible" data-compatibility-search>
                        <div class="compatibility-list" data-compatibility-list>
                            <?php foreach ($compatibilites as $compat): ?>
                                <div class="compatibility-item" data-compatibility-item>
                                    <strong><?php echo htmlspecialchars($compat['nom_marques'] . ' ' . $compat['model_vehicules']); ?></strong>
                                    <small><?php echo htmlspecialchars((string)$compat['annee']); ?><?php echo !empty($compat['serie']) ? ' · ' . htmlspecialchars($compat['serie']) : ''; ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="compatibility-empty">Aucune compatibilité confirmée pour cette pièce. Vérifiez la référence OEM et demandez confirmation à notre équipe avant commande.</p>
                    <?php endif; ?>
                </section>

                <!-- Prix -->
                <div class="product-price-wrap">
                    <div class="product-detail-price"><?php echo formatPrice($p['prix_produit']); ?></div>
                    <div class="product-price-note">PRIX TTC<br>Livraison calculée à la commande</div>
                </div>

                <!-- Quantité & Panier -->
                <form action="actions/add_to_cart.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                    <input type="hidden" name="product_id" value="<?php echo $p['id_produit']; ?>">
                    <input type="hidden" name="redirect" value="produit.php?id=<?php echo $p['id_produit']; ?>">
                    <div class="d-flex gap-3 align-items-center mb-4 product-buy-box">
                        <div class="qty-selector">
                            <button type="button" class="qty-btn" onclick="updateQty(-1)">−</button>
                            <input type="number" class="qty-input" id="qtyInput" name="quantity" value="1" min="1" max="<?php echo $p['stock_produit']; ?>">
                            <button type="button" class="qty-btn" onclick="updateQty(1)">+</button>
                        </div>
                        <button type="submit" class="btn-gold" <?php echo $p['stock_produit'] == 0 ? 'disabled' : ''; ?>>
                            <i class="fas fa-shopping-bag"></i> AJOUTER AU PANIER
                        </button>
                    </div>
                </form>

                <!-- Specs -->
                <div class="product-spec-card" style="background:var(--bg-card);border:1px solid var(--border);border-radius:16px;padding:24px;margin-top:20px;">
                    <h5 style="font-size:0.75rem;letter-spacing:3px;color:var(--gold);margin-bottom:16px;text-transform:uppercase;">SPÉCIFICATIONS TECHNIQUES</h5>
                    <table class="specs-table">
                        <tr><td>Type d'injection</td><td>Common Rail Direct</td></tr>
                        <tr><td>Référence OEM</td><td><?php echo htmlspecialchars($p['reference_oem'] ?? 'N/A'); ?></td></tr>
                        <tr><td>Poids</td><td>1.2 kg</td></tr>
                        <tr><td>Garantie</td><td>12 mois</td></tr>
                        <tr><td>Origine</td><td>Europe / OEM</td></tr>
                        <tr><td>Stock</td><td><?php echo $p['stock_produit']; ?> unité(s)</td></tr>
                    </table>
                </div>
                </div>
            </div>
        </div>

        <!-- AVIS CLIENTS -->
        <div class="product-reviews" data-aos="fade-up">
            <h3 style="font-size:1.2rem;font-weight:900;text-transform:uppercase;letter-spacing:-1px;margin-bottom:30px;">
                AVIS CLIENTS <span style="color:var(--gold);">(<?php echo count($avis); ?>)</span>
            </h3>
            <?php if(count($avis) === 0): ?>
            <p style="color:#555;">Aucun avis pour l'instant. Soyez le premier !</p>
            <?php else: ?>
            <div class="row g-3">
                <?php foreach($avis as $av): ?>
                <div class="col-md-6">
                    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:16px;padding:20px;">
                        <div class="d-flex justify-content-between mb-2">
                            <strong><?php echo htmlspecialchars($av['prenom_client']); ?></strong>
                            <span style="font-size:0.75rem;color:#555;"><?php echo $av['date_avis']; ?></span>
                        </div>
                        <div class="star-rating mb-2" style="font-size:0.85rem;">
                            <?php for($i=1;$i<=5;$i++) echo $i<=$av['note'] ? '<i class="fas fa-star filled"></i>' : '<i class="far fa-star"></i>'; ?>
                        </div>
                        <p style="font-size:0.85rem;color:#aaa;margin:0;"><?php echo htmlspecialchars($av['commentaire']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function changeImg(el) {
    document.getElementById('mainProductImg').src = el.src;
    document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
    el.closest('.thumb').classList.add('active');
}
function updateQty(delta) {
    const input = document.getElementById('qtyInput');
    const newVal = Math.max(1, parseInt(input.value) + delta);
    input.value = newVal;
}

const compatibilitySearch = document.querySelector('[data-compatibility-search]');
if (compatibilitySearch) {
    compatibilitySearch.addEventListener('input', function() {
        const query = this.value.trim().toLowerCase();
        document.querySelectorAll('[data-compatibility-item]').forEach(function(item) {
            item.hidden = query !== '' && !item.textContent.toLowerCase().includes(query);
        });
    });
}
</script>

<?php include 'includes/footer.php'; ?>