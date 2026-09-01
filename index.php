<?php
// --- Vérification de sécurité : connexion DB ---
if (!isset($pdo)) {
    if (file_exists(__DIR__ . '/config/database.php')) {
        require __DIR__ . '/config/database.php';
    } else {
        die("Erreur critique : fichier config/database.php introuvable.");
    }
}

// --- Vérification de sécurité : formatPrice() ---
if (!function_exists('formatPrice')) {
    function formatPrice($prix) {
        return number_format($prix, 0, ',', ' ') . ' FCFA';
    }
}

include 'includes/header.php';

$categoriesAccueil = [];
$nbReferencesAccueil = 0;
$nbClientsAccueil = 0;
$photosAccueil = ['prod_6a6c75c0d3e2f.jpg', 'prod_6a8ef1f11d932.jpg', 'default.jpg.jpg'];
try {
    $nbReferencesAccueil = (int) $pdo->query("SELECT COUNT(*) FROM PRODUITS")->fetchColumn();
    $nbClientsAccueil = (int) $pdo->query("SELECT COUNT(*) FROM CLIENTS WHERE role = 'client'")->fetchColumn();
    $categoriesAccueil = $pdo->query(
        "SELECT c.id_categories, c.nom_categories, COUNT(p.id_produit) AS nb_produits,
                MIN(NULLIF(p.image_produit, '')) AS image_categorie
         FROM CATEGORIES c
         LEFT JOIN PRODUITS p ON p.id_categories = c.id_categories
         GROUP BY c.id_categories, c.nom_categories
         HAVING COUNT(p.id_produit) > 0
         ORDER BY nb_produits DESC, c.nom_categories ASC
         LIMIT 8"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categoriesAccueil = [];
}
?>

<!-- STYLE SPÉCIFIQUE & RESPONSIVE -->
<style>
/* ============================================================
   Ajustements UI & Correctifs Responsive
   ============================================================ */
.hero-section {
    position: relative;
    overflow: hidden;
}

.search-select-custom {
    width: 100%;
    border-radius: 12px;
    padding: 14px 18px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: #fff;
    outline: none;
    transition: border-color 0.2s ease, background 0.2s ease;
}

.search-select-custom:focus {
    border-color: var(--gold, #FFC700);
    background: rgba(255, 255, 255, 0.08);
}

.search-select-custom option {
    background: #111;
    color: #fff;
}

/* ============================================================
   Carrousel Produits Coulissant
   ============================================================ */
.btn-carousel-arrow {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #1c1c1c;
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: #FFC700;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-carousel-arrow:hover {
    background: #FFC700;
    color: #000;
    border-color: #FFC700;
}

.pv-carousel-viewport {
    overflow-x: auto;
    overflow-y: hidden;
    position: relative;
    padding-bottom: 15px;
    scroll-behavior: smooth;
    user-select: none;
    cursor: grab;
    scrollbar-width: thin;
    scrollbar-color: #FFC700 #1b1b1b;
    padding-left: 2px;
    padding-right: 2px;
}

.pv-carousel-viewport.active {
    cursor: grabbing;
    scroll-behavior: auto; /* Supprime le délai pendant le drag manuel */
}

.pv-carousel-viewport::-webkit-scrollbar {
    height: 6px;
}

.pv-carousel-viewport::-webkit-scrollbar-thumb {
    background: #FFC700;
    border-radius: 10px;
}

.pv-carousel-viewport::-webkit-scrollbar-track {
    background: #1b1b1b;
}

.pv-carousel-track {
    display: flex;
    gap: 20px;
    width: max-content;
}

.pv-card {
    display: block;
    width: 270px;
    flex: 0 0 auto;
    background: linear-gradient(160deg, #1b1b1b, #111);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 18px;
    overflow: hidden;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 12px 25px rgba(0,0,0,0.18);
    transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
}

.pv-card:hover {
    transform: translateY(-8px);
    border-color: rgba(255,199,0,0.7);
    box-shadow: 0 18px 35px rgba(0,0,0,0.35);
    color: inherit;
}

.pv-card-image {
    position: relative;
    width: 100%;
    height: 190px;
    background: #1c1c1c;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.pv-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    pointer-events: none; /* Évite de décomposer l'image au glisser */
    transition: transform 0.45s ease, filter 0.45s ease;
}

.pv-card:hover .pv-card-image img { transform: scale(1.06); filter: brightness(1.1); }

.pv-placeholder, .pv-no-image {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.pv-placeholder i, .pv-no-image i {
    font-size: 2.4rem;
    color: #3a3a3a;
}

.pv-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    z-index: 2;
    font-size: 0.62rem;
    font-weight: 800;
    letter-spacing: 0.5px;
    padding: 4px 9px;
    border-radius: 6px;
    text-transform: uppercase;
}

.pv-badge-stock { background: rgba(34,197,94,0.15); color: #22c55e; }
.pv-badge-rupture { background: rgba(239,68,68,0.15); color: #ef4444; }

.pv-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.55);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.pv-card:hover .pv-overlay { opacity: 1; }

.pv-overlay-btn {
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 1px;
    color: #FFC700;
    border: 1px solid #FFC700;
    padding: 8px 14px;
    border-radius: 8px;
    background: rgba(0,0,0,0.45);
}

.pv-card-body { padding: 17px 18px 18px; }

.pv-brand {
    font-size: 0.65rem;
    letter-spacing: 0.5px;
    color: #FFC700;
    text-transform: uppercase;
    margin-bottom: 4px;
}

.pv-name {
    font-size: 0.96rem;
    font-weight: 700;
    color: #eee;
    margin-bottom: 7px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.pv-ref { font-size: 0.68rem; color: #777; margin-bottom: 16px; }

.pv-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.pv-price { font-weight: 900; color: #FFC700; font-size: 0.95rem; }

.pv-detail-link {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: #111;
    background: #FFC700;
    border-radius: 999px;
    padding: 8px 11px 8px 13px;
    font-size: 0.68rem;
    font-weight: 900;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    transition: transform 0.2s ease, background 0.2s ease;
}
.pv-card:hover .pv-detail-link { transform: translateX(3px); background: #fff; }
.pv-detail-link i {
    font-size: 0.7rem;
}
.pv-cart-btn {
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Correctif split-screen / petits écrans */
@media (max-width: 768px) {
    .vehicle-tab-content .col-sm-12 {
        width: 100%;
    }
}

.home-category-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
}

.home-category-card {
    position: relative;
    min-height: 240px;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 18px;
    background: #151515;
    color: #fff;
    text-decoration: none;
    box-shadow: 0 10px 28px rgba(0,0,0,0.18);
    transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
}

.home-category-card::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(0,0,0,0.12), rgba(0,0,0,0.82));
}

.home-category-visual,
.home-category-placeholder {
    width: 100%;
    height: 100%;
    min-height: 240px;
}

.home-category-visual img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform .4s ease, filter .4s ease;
}

.home-category-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #555;
    font-size: 2.8rem;
    background: linear-gradient(135deg, #0f0f0f, #1a1a1a);
}

.home-category-card:hover {
    transform: translateY(-6px);
    border-color: rgba(255,199,0,0.5);
    box-shadow: 0 16px 35px rgba(0,0,0,0.25);
}

.home-category-card:hover img {
    transform: scale(1.08);
    filter: brightness(1.08);
}

.home-category-content {
    position: absolute;
    z-index: 1;
    left: 18px;
    right: 18px;
    bottom: 18px;
}

.home-category-badge {
    display: inline-block;
    margin-bottom: 8px;
    background: rgba(255, 199, 0, 0.12);
    border: 1px solid rgba(255, 199, 0, 0.35);
    color: #ffd54a;
    font-size: 0.62rem;
    font-weight: 800;
    letter-spacing: 1.5px;
    padding: 5px 9px;
    border-radius: 999px;
    text-transform: uppercase;
}

.home-category-content strong {
    display: block;
    font-size: 1rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.home-category-content span {
    color: #e5e7eb;
    font-size: 0.72rem;
    opacity: 0.9;
}

@media (max-width: 992px) {
    .home-category-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 768px) {
    .home-category-grid { grid-template-columns: 1fr; }
}
</style>

<!-- HERO -->
<section class="hero-section">
    <div class="hero-bg"></div>
    <div class="hero-grid-overlay"></div>
    <div class="container position-relative">
        <div class="row align-items-center">
            <div class="col-lg-7" data-aos="fade-right" data-aos-duration="1000">
                <div class="hero-badge">
                    <span class="pulse-dot" style="width:6px;height:6px;background:var(--gold, #FFC700);border-radius:50%;display:inline-block;"></span>
                    N°1 DE LA PIÈCE DIESEL AU GABON
                </div>
                <h1 class="hero-title">
                    <span class="line-white">ENGINEERED</span>
                    <span class="line-gold">POWER.</span>
                </h1>
                <p class="hero-subtitle">
                    Injecteurs, turbos, pompes haute pression, pièces pour poids lourds et engins de chantier. Qualité OEM certifiée, livraison express Libreville.
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="catalogue.php" class="btn-gold" style="width:auto;">
                        <i class="fas fa-th-large me-1"></i> VOIR LE CATALOGUE
                    </a>
                    <a href="#recherche" class="btn-outline" style="color:#aaa;">
                        TROUVER MA PIÈCE
                    </a>
                </div>
            </div>

            <!-- Search Hub -->
            <div class="col-lg-5 mt-5 mt-lg-0" data-aos="fade-left" data-aos-duration="1000" data-aos-delay="200">
                <div class="search-hub" id="recherche">
                    <div class="search-tabs">
                        <button class="search-tab-btn active" onclick="switchTab('oem', this)">RÉFÉRENCE OEM</button>
                        <button class="search-tab-btn" onclick="switchTab('vehicle', this)">MON VÉHICULE</button>
                    </div>

                    <!-- Tab OEM -->
                    <div id="tab-oem" class="oem-tab-content">
                        <form action="catalogue.php" method="GET" class="search-input-group">
                            <input type="text" name="oem" placeholder="Ex: 0445110153..." required>
                            <button type="submit" class="btn-search-main"><i class="fas fa-search me-1"></i>TROUVER</button>
                        </form>
                    </div>

                    <!-- Tab Véhicule -->
                    <div id="tab-vehicle" class="vehicle-tab-content" style="display:none;">
                        <form action="catalogue.php" method="GET" style="width:100%;">
                            <div class="row g-2">
                                <div class="col-12 col-sm-6">
                                    <select class="search-select-custom" name="marque">
                                        <option value="">MARQUE</option>
                                        <option>Toyota</option>
                                        <option>Mitsubishi</option>
                                        <option>Komatsu</option>
                                        <option>Caterpillar</option>
                                        <option>Mercedes</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <select class="search-select-custom" name="modele">
                                        <option value="">MODÈLE</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <select class="search-select-custom" name="annee">
                                        <option value="">ANNÉE</option>
                                        <?php for($y=date('Y'); $y>=2000; $y--): ?>
                                            <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <select class="search-select-custom" name="motorisation">
                                        <option value="">MOTORISATION</option>
                                        <option>2.4 D-4D</option>
                                        <option>3.0 Di-D</option>
                                        <option>2.8 TDI</option>
                                    </select>
                                </div>
                                <div class="col-12 mt-3">
                                    <button type="submit" class="btn-search-main" style="width:100%;">AFFICHER LES PIÈCES</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- STATS -->
<section class="stats-section py-4" data-aos="fade-up">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-6 col-md-3 stat-item">
                <span class="stat-number" data-count="<?php echo $nbReferencesAccueil; ?>">0</span>
                <span class="stat-label">Références au catalogue</span>
            </div>
            <div class="col-6 col-md-3 stat-item">
                <span class="stat-number" data-count="<?php echo $nbClientsAccueil; ?>">0</span>
                <span class="stat-label">Clients satisfaits</span>
            </div>
            <div class="col-6 col-md-3 stat-item">
                <span class="stat-number" data-count="12">0</span>
                <span class="stat-label">Années d'expérience</span>
            </div>
            <div class="col-6 col-md-3 stat-item">
                <span class="stat-number" data-count="24">0</span>
                <span class="stat-label">Livraison en heures</span>
            </div>
        </div>
    </div>
</section>

<!-- VEHICULES POPULAIRES -->
<section class="py-5">
    <div class="container" data-aos="fade-up">
        <div class="section-header mb-4">
            <div>
                <span class="section-tag">Compatibilité</span>
                <h2 class="section-title">VOS VÉHICULES</h2>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="catalogue.php?vehicule=toyota-hilux" class="brand-pill"><i class="fas fa-truck-pickup me-1"></i> TOYOTA HILUX</a>
            <a href="catalogue.php?vehicule=mitsubishi-l200" class="brand-pill"><i class="fas fa-shuttle-van me-1"></i> MITSUBISHI L200</a>
            <a href="catalogue.php?vehicule=caterpillar" class="brand-pill"><i class="fas fa-tractor me-1"></i> CATERPILLAR</a>
            <a href="catalogue.php?vehicule=komatsu" class="brand-pill"><i class="fas fa-industry me-1"></i> KOMATSU</a>
            <a href="catalogue.php?vehicule=mercedes" class="brand-pill"><i class="fas fa-truck me-1"></i> MERCEDES ACTROS</a>
            <a href="catalogue.php?vehicule=volvo" class="brand-pill"><i class="fas fa-bus me-1"></i> VOLVO FH</a>
            <a href="catalogue.php?vehicule=generateurs" class="brand-pill"><i class="fas fa-bolt me-1"></i> GÉNÉRATEURS</a>
        </div>
    </div>
</section>

<!-- CATEGORIES -->
<?php if (!empty($categoriesAccueil)): ?>
<section class="py-5" style="background:#111;">
    <div class="container" data-aos="fade-up">
        <div class="section-header mb-4">
            <div>
                <span class="section-tag">Trouvez plus vite</span>
                <h2 class="section-title">EXPLORER PAR CATÉGORIE</h2>
            </div>
            <a href="catalogue.php" class="section-link d-none d-sm-inline-block">TOUT LE CATALOGUE →</a>
        </div>
        <div class="home-category-grid">
            <?php foreach ($categoriesAccueil as $categorie): ?>
                <?php
                    $imageCategorie = '';
                    if (!empty($categorie['image_categorie'])) {
                        $imageCategorie = basename($categorie['image_categorie']);
                    }
                    $imageCategorie = $imageCategorie !== '' ? $imageCategorie : 'default.jpg.jpg';
                ?>
                <a class="home-category-card" href="catalogue.php?cat=<?php echo (int) $categorie['id_categories']; ?>">
                    <div class="home-category-visual">
                        <?php if (!empty($imageCategorie) && file_exists(__DIR__ . '/assets/images/' . $imageCategorie)): ?>
                            <img src="assets/images/<?php echo htmlspecialchars($imageCategorie); ?>"
                                 alt="<?php echo htmlspecialchars($categorie['nom_categories']); ?>" loading="lazy">
                        <?php else: ?>
                            <div class="home-category-placeholder"><i class="fas fa-cog"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="home-category-content">
                        <span class="home-category-badge">Catégorie</span>
                        <strong><?php echo htmlspecialchars($categorie['nom_categories']); ?></strong>
                        <span><?php echo (int) $categorie['nb_produits']; ?> référence(s)</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- PRODUITS EN VEDETTE -->
<section class="py-5">
    <div class="container">
        <div class="section-header mb-4 d-flex justify-content-between align-items-end" data-aos="fade-up">
            <div>
                <span class="section-tag">Meilleures ventes</span>
                <h2 class="section-title">PIÈCES DIESEL</h2>
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="catalogue.php" class="section-link d-none d-sm-inline-block">TOUT VOIR →</a>
                <div class="carousel-nav-btns d-flex gap-2">
                    <button id="pvPrev" class="btn-carousel-arrow" aria-label="Précédent"><i class="fas fa-chevron-left"></i></button>
                    <button id="pvNext" class="btn-carousel-arrow" aria-label="Suivant"><i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
        </div>
    </div>

    <?php
    try {
        $stmt = $pdo->query("
            SELECT
                p.*,
                m.nom_marques,
                c.nom_categories
            FROM PRODUITS p
            INNER JOIN MARQUES m ON p.id_marques = m.id_marques
            INNER JOIN CATEGORIES c ON p.id_categories = c.id_categories
            WHERE p.stock_produit > 0
            ORDER BY p.stock_produit DESC, p.nom_produit ASC
        ");
        $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $produits = [];
        echo "<div class='container'><div class='alert alert-danger'>Erreur lors du chargement des produits : " . htmlspecialchars($e->getMessage()) . "</div></div>";
    }
    ?>

    <?php if (empty($produits)): ?>
        <div class="container"><p class="text-muted">Aucun produit disponible pour le moment.</p></div>
    <?php else: ?>
    <?php $produitsCarousel = array_merge($produits, $produits); ?>

    <div class="container-fluid px-lg-5">
        <div class="pv-carousel-viewport" id="pvViewport">
            <div class="pv-carousel-track" id="pvTrack">
                <?php foreach($produitsCarousel as $p):
                    $aImage = !empty($p['image_produit']);
                ?>
                <a class="pv-card" href="produit.php?id=<?php echo $p['id_produit']; ?>">
                    <div class="pv-card-image">
                        <span class="pv-badge pv-badge-stock">
                            Stock : <?php echo (int)$p['stock_produit']; ?>
                        </span>
                        <?php if ($aImage): ?>
                            <img src="assets/images/<?php echo htmlspecialchars(basename($p['image_produit'])); ?>"
                                 alt="<?php echo htmlspecialchars($p['nom_produit']); ?>"
                                 onerror="this.closest('.pv-card-image').classList.add('pv-no-image'); this.remove();">
                        <?php else: ?>
                            <div class="pv-placeholder"><i class="fas fa-cog"></i></div>
                        <?php endif; ?>
                        <div class="pv-overlay">
                            <span class="pv-overlay-btn"><i class="fas fa-eye me-2"></i>VOIR DÉTAILS</span>
                        </div>
                    </div>
                    <div class="pv-card-body">
                        <div class="pv-brand"><?php echo htmlspecialchars($p['nom_marques']); ?> · <?php echo htmlspecialchars($p['nom_categories']); ?></div>
                        <div class="pv-name" title="<?php echo htmlspecialchars($p['nom_produit']); ?>"><?php echo htmlspecialchars($p['nom_produit']); ?></div>
                        <div class="pv-ref">OEM : <?php echo htmlspecialchars($p['reference_oem'] ?? 'N/A'); ?></div>
                        <div class="pv-footer">
                            <span class="pv-price"><?php echo formatPrice($p['prix_produit']); ?></span>
                            <span class="pv-detail-link">Détails <i class="fas fa-arrow-right"></i></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php endif; ?>
</section>

<!-- SCRIPTS (TABS, STATS, DRAG & SCROLL) -->
<script>
function switchTab(tab, el) {
    document.querySelectorAll('.search-tab-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
    
    const tabOem = document.getElementById('tab-oem');
    const tabVehicle = document.getElementById('tab-vehicle');
    
    if (tab === 'oem') {
        tabOem.style.display = 'block';
        tabVehicle.style.display = 'none';
    } else {
        tabOem.style.display = 'none';
        tabVehicle.style.display = 'block';
    }
}

document.addEventListener("DOMContentLoaded", function () {
    // 1. Compteur animé des stats
    const counters = document.querySelectorAll('[data-count]');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if(entry.isIntersecting) {
                const target = parseInt(entry.target.getAttribute('data-count'));
                let count = 0;
                const step = target / 60;
                const timer = setInterval(() => {
                    count = Math.min(count + step, target);
                    entry.target.textContent = Math.floor(count).toLocaleString('fr-FR') + (target === 24 ? 'h' : '+');
                    if(count >= target) clearInterval(timer);
                }, 30);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach(c => observer.observe(c));

    // 2. Gestion du carrousel (Boutons + Drag & Scroll à la souris)
    const slider = document.getElementById('pvViewport');
    const prevBtn = document.getElementById('pvPrev');
    const nextBtn = document.getElementById('pvNext');

    if (slider) {
        const scrollAmount = 270;
        let autoScrollFrame;
        let autoScrollActive = false;
        let lastFrameTime = 0;
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const stopAutoScroll = () => {
            autoScrollActive = false;
            window.cancelAnimationFrame(autoScrollFrame);
            lastFrameTime = 0;
        };
        const startAutoScroll = () => {
            if (reducedMotion || slider.scrollWidth <= slider.clientWidth || autoScrollActive) return;
            autoScrollActive = true;
            const animate = (time) => {
                if (!autoScrollActive) return;
                const elapsed = lastFrameTime ? Math.min(time - lastFrameTime, 50) : 16;
                lastFrameTime = time;
                slider.scrollLeft += elapsed * 0.035;
                const loopPoint = slider.scrollWidth / 2;
                if (slider.scrollLeft >= loopPoint) slider.scrollLeft -= loopPoint;
                autoScrollFrame = window.requestAnimationFrame(animate);
            };
            autoScrollFrame = window.requestAnimationFrame(animate);
        };

        slider.addEventListener('mouseenter', stopAutoScroll);
        slider.addEventListener('mouseleave', startAutoScroll);
        slider.addEventListener('focusin', stopAutoScroll);
        slider.addEventListener('focusout', startAutoScroll);
        startAutoScroll();

        if(nextBtn) {
            nextBtn.addEventListener('click', () => {
                stopAutoScroll();
                slider.scrollBy({ left: scrollAmount, behavior: 'smooth' });
                window.setTimeout(startAutoScroll, 500);
            });
        }

        if(prevBtn) {
            prevBtn.addEventListener('click', () => {
                stopAutoScroll();
                slider.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
                window.setTimeout(startAutoScroll, 500);
            });
        }

        let isDown = false;
        let startX;
        let scrollLeft;

        slider.addEventListener('mousedown', (e) => {
            isDown = true;
            slider.classList.add('active');
            startX = e.pageX - slider.offsetLeft;
            scrollLeft = slider.scrollLeft;
        });

        slider.addEventListener('mouseleave', () => {
            isDown = false;
            slider.classList.remove('active');
        });

        slider.addEventListener('mouseup', () => {
            isDown = false;
            slider.classList.remove('active');
        });

        slider.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - slider.offsetLeft;
            const walk = (x - startX) * 1.5;
            slider.scrollLeft = scrollLeft - walk;
        });
    }

    const categoryRail = document.querySelector('.home-category-grid');
    if (categoryRail && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        let categoryTimer = window.setInterval(() => {
            const atEnd = categoryRail.scrollLeft + categoryRail.clientWidth >= categoryRail.scrollWidth - 4;
            categoryRail.scrollTo({ left: atEnd ? 0 : categoryRail.scrollLeft + 256, behavior: 'smooth' });
        }, 4800);
        const pauseCategories = () => window.clearInterval(categoryTimer);
        const resumeCategories = () => {
            pauseCategories();
            categoryTimer = window.setInterval(() => {
                const atEnd = categoryRail.scrollLeft + categoryRail.clientWidth >= categoryRail.scrollWidth - 4;
                categoryRail.scrollTo({ left: atEnd ? 0 : categoryRail.scrollLeft + 256, behavior: 'smooth' });
            }, 4800);
        };
        categoryRail.addEventListener('mouseenter', pauseCategories);
        categoryRail.addEventListener('mouseleave', resumeCategories);
        categoryRail.addEventListener('focusin', pauseCategories);
        categoryRail.addEventListener('focusout', resumeCategories);
    }
});
</script>

<?php include 'includes/footer.php'; ?>