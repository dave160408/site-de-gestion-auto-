<?php
include 'includes/header.php';

// ============================================================
// 1. Initialisation du Panier
// ============================================================
$cart = $_SESSION['cart'] ?? [];
$subtotal = 0;
$frais_livraison = 5000;
$whatsapp_number = '24107458899'; // Numéro entreprise (sans le +)
$products = [];
$menuCategories = $pdo->query("SELECT id_categories, nom_categories FROM CATEGORIES ORDER BY nom_categories")->fetchAll();

if (count($cart) > 0) {
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM PRODUITS WHERE id_produit IN ($placeholders)");
    $stmt->execute($ids);
    $products = $stmt->fetchAll(PDO::FETCH_UNIQUE);
}

// ============================================================
// 2. Construction du Message WhatsApp
// ============================================================
$whatsapp_message = "Bonjour DUTCH COMPANY 👋, je souhaite passer la commande suivante :\n\n";

if (!empty($cart) && !empty($products)) {
    foreach ($cart as $pid => $item) {
        $p = $products[$pid] ?? null;
        if (!$p) continue;
        $qty = (int)$item['quantity'];
        $line_total = $p['prix_produit'] * $qty;
        $subtotal += $line_total;
        
        $whatsapp_message .= "• " . $qty . " x " . $p['nom_produit']
                           . " — " . number_format($line_total, 0, ',', ' ') . " FCFA\n";
    }
}

$whatsapp_total = $subtotal + $frais_livraison;
$whatsapp_message .= "\nSous-total : " . number_format($subtotal, 0, ',', ' ') . " FCFA";
$whatsapp_message .= "\nLivraison : " . number_format($frais_livraison, 0, ',', ' ') . " FCFA";
$whatsapp_message .= "\nTOTAL : " . number_format($whatsapp_total, 0, ',', ' ') . " FCFA";
$whatsapp_message .= "\n\nMerci de me recontacter pour confirmer mon adresse de livraison et valider la commande (paiement à la livraison).";

$whatsapp_url = "https://wa.me/{$whatsapp_number}?text=" . urlencode($whatsapp_message);

// Fonction de secours pour le nombre d'articles
$total_articles = function_exists('getCartCount') ? getCartCount() : array_sum(array_column($cart, 'quantity'));
?>

<style>
/* ============================================================
   Styles Origine Rehaussés & Optimisés
   ============================================================ */
.cart-page-wrapper {
    padding-top: 150px;
    padding-bottom: 80px;
    background: #f8fafc;
    min-height: 100vh;
}

.cart-title {
    font-size: 1.8rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: -0.5px;
    margin-bottom: 30px;
    color: #0f172a;
}

/* Articles */
.cart-item-card {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 16px;
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.02);
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.cart-item-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 6px 20px rgba(0,0,0,0.04);
}

.cart-item-img {
    width: 85px;
    height: 85px;
    border-radius: 12px;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
}

.cart-item-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.cart-item-details {
    flex: 1;
    min-width: 0;
}

.cart-item-name {
    font-size: 0.95rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cart-item-ref {
    font-size: 0.72rem;
    color: #64748b;
    margin-bottom: 6px;
}

.cart-item-price {
    font-size: 0.85rem;
    font-weight: 700;
    color: #c9a227;
}

/* Quantité Formulaire */
.cart-qty-form {
    display: flex;
    align-items: center;
    gap: 8px;
}

.qty-selector-custom {
    display: flex;
    align-items: center;
    background: #f1f5f9;
    border-radius: 8px;
    padding: 2px;
}

.qty-btn-custom {
    width: 28px;
    height: 28px;
    border: none;
    background: transparent;
    color: #334155;
    font-weight: 800;
    cursor: pointer;
    border-radius: 6px;
    transition: background 0.2s;
}

.qty-btn-custom:hover {
    background: #e2e8f0;
}

.qty-input-custom {
    width: 36px;
    border: none;
    background: transparent;
    text-align: center;
    font-weight: 700;
    font-size: 0.85rem;
    color: #0f172a;
    outline: none;
}

.btn-update-qty {
    background: transparent;
    border: 1px solid #c9a227;
    color: #c9a227;
    border-radius: 8px;
    padding: 5px 10px;
    font-size: 0.72rem;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-update-qty:hover {
    background: #c9a227;
    color: #fff;
}

.cart-line-total {
    font-weight: 900;
    color: #0f172a;
    font-size: 1rem;
    min-width: 110px;
    text-align: right;
}

.btn-remove-item {
    background: #fef2f2;
    border: none;
    color: #ef4444;
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-remove-item:hover {
    background: #ef4444;
    color: #fff;
}

/* Récapitulatif */
.order-summary-box {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 25px rgba(0,0,0,0.03);
    position: sticky;
    top: 130px;
}

.summary-title {
    font-size: 0.9rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #0f172a;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #f1f5f9;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    font-size: 0.88rem;
    color: #475569;
    margin-bottom: 12px;
}

.summary-row.total {
    font-size: 1.1rem;
    font-weight: 900;
    color: #0f172a;
    padding-top: 14px;
    border-top: 1px dashed #e2e8f0;
    margin-top: 14px;
}

.summary-row.total span:last-child {
    color: #c9a227;
}

/* Formulaire Promo */
.promo-input-group {
    display: flex;
    gap: 8px;
    margin-top: 20px;
    margin-bottom: 20px;
}

.promo-input-group input {
    flex: 1;
    padding: 10px 14px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.82rem;
    outline: none;
    text-transform: uppercase;
}

.promo-input-group input:focus {
    border-color: #c9a227;
}

.btn-apply-promo {
    background: #0f172a;
    color: #fff;
    border: none;
    padding: 0 16px;
    border-radius: 10px;
    font-size: 0.78rem;
    font-weight: 800;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-apply-promo:hover {
    background: #c9a227;
}

/* Bouton Paiement Direct Site */
.btn-site-checkout {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    background: #0f172a;
    color: #fff;
    border: none;
    border-radius: 12px;
    padding: 14px;
    font-weight: 800;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(15,23,42,0.15);
}

.btn-site-checkout:hover {
    background: #c9a227;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(201,162,39,0.3);
}

/* Séparateur */
.checkout-separator {
    display: flex;
    align-items: center;
    text-align: center;
    margin: 16px 0;
    color: #94a3b8;
    font-size: 0.75rem;
    font-weight: 700;
}

.checkout-separator::before,
.checkout-separator::after {
    content: '';
    flex: 1;
    border-bottom: 1px solid #e2e8f0;
}

.checkout-separator span {
    padding: 0 10px;
}

/* Bloc WhatsApp */
.whatsapp-box {
    padding: 18px;
    background: rgba(37,211,102,0.06);
    border: 1px solid rgba(37,211,102,0.25);
    border-radius: 14px;
}

.btn-whatsapp-valider {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background: #25d366;
    color: #fff;
    border-radius: 12px;
    padding: 14px;
    text-decoration: none;
    font-weight: 800;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(37,211,102,0.2);
}

.btn-whatsapp-valider:hover {
    background: #1eb857;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(37,211,102,0.3);
}

/* Responsive */
@media (max-width: 768px) {
    .cart-page-wrapper {
        padding-top: 110px;
    }
    .cart-item-card {
        flex-wrap: wrap;
        gap: 12px;
    }
    .cart-line-total {
        text-align: left;
        min-width: auto;
    }
}
</style>

<div class="cart-page-wrapper">
    <div class="container">
        <h1 class="cart-title">
            <?php if (!empty($_SESSION['checkout_erreurs'])): ?>
<div style="background:#fef2f2;border:1px solid #ef4444;border-radius:12px;padding:16px 20px;margin-bottom:20px;color:#dc2626;font-size:0.85rem;">
    <strong><i class="fas fa-triangle-exclamation"></i> Impossible de valider la commande :</strong>
    <ul style="margin:8px 0 0;padding-left:20px;">
        <?php foreach ($_SESSION['checkout_erreurs'] as $e): ?>
            <li><?php echo htmlspecialchars($e); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php unset($_SESSION['checkout_erreurs']); ?>
<?php endif; ?>
            MON PANIER <span style="color:#c9a227;">(<?php echo $total_articles; ?> article<?php echo $total_articles > 1 ? 's' : ''; ?>)</span>
        </h1>

        <?php if(empty($cart)): ?>
            <div style="text-align:center; padding:80px 20px; background:#fff; border-radius:16px; border:1px solid #e2e8f0;">
                <i class="fas fa-shopping-bag" style="font-size:3.5rem; color:#cbd5e1; margin-bottom:20px; display:block;"></i>
                <p style="color:#475569; font-size:1.1rem; font-weight:600; margin-bottom:24px;">Votre panier est actuellement vide.</p>
                <a href="catalogue.php" class="btn-gold" style="width:auto; display:inline-flex; padding:12px 28px; border-radius:10px; text-decoration:none;">DÉCOUVRIR LE CATALOGUE</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                
                <!-- Liste des articles -->
                <div class="col-lg-8">
                    <?php foreach($cart as $pid => $item):
                        $p = $products[$pid] ?? null;
                        if(!$p) continue;
                        $line_total = $p['prix_produit'] * $item['quantity'];
                    ?>
                    <div class="cart-item-card">
                        <div class="cart-item-img">
                            <img src="assets/images/<?php echo htmlspecialchars($p['image_produit'] ?? 'default.png'); ?>" alt="<?php echo htmlspecialchars($p['nom_produit']); ?>">
                        </div>
                        
                        <div class="cart-item-details">
                            <div class="cart-item-name"><?php echo htmlspecialchars($p['nom_produit']); ?></div>
                            <div class="cart-item-ref">OEM : <?php echo htmlspecialchars($p['reference_oem'] ?? 'N/A'); ?></div>
                            <div class="cart-item-price"><?php echo formatPrice($p['prix_produit']); ?></div>
                        </div>

                        <!-- Modifier la quantité -->
                        <form action="actions/update_cart.php" method="POST" class="cart-qty-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                            <input type="hidden" name="product_id" value="<?php echo $pid; ?>">
                            <div class="qty-selector-custom">
                                <button type="button" class="qty-btn-custom" onclick="this.form.quantity.value=Math.max(1,+this.form.quantity.value-1)">−</button>
                                <input type="number" class="qty-input-custom" name="quantity" value="<?php echo (int)$item['quantity']; ?>" min="1">
                                <button type="button" class="qty-btn-custom" onclick="this.form.quantity.value=+this.form.quantity.value+1">+</button>
                            </div>
                            <button type="submit" class="btn-update-qty">OK</button>
                        </form>

                        <!-- Prix total ligne -->
                        <div class="cart-line-total">
                            <?php echo formatPrice($line_total); ?>
                        </div>

                        <!-- Supprimer l'article -->
                        <form action="actions/remove_from_cart.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                            <input type="hidden" name="product_id" value="<?php echo $pid; ?>">
                            <button type="submit" class="btn-remove-item" title="Supprimer l'article">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Récapitulatif Commande -->
                <div class="col-lg-4">
                    <div class="order-summary-box">
                        <div class="summary-title">RÉCAPITULATIF</div>
                        
                        <div class="summary-row">
                            <span>Sous-total</span>
                            <span><?php echo formatPrice($subtotal); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Livraison (Gabon)</span>
                            <span><?php echo formatPrice($frais_livraison); ?></span>
                        </div>
                        <div class="summary-row total">
                            <span>TOTAL</span>
                            <span><?php echo formatPrice($subtotal + $frais_livraison); ?></span>
                        </div>

                        <!-- Code Promo -->
                        <div class="promo-input-group">
                            <input type="text" placeholder="CODE PROMO">
                            <button type="button" class="btn-apply-promo">OK</button>
                        </div>

                        <!-- OPTION 1 : COMMANDER & PAYER SUR LE SITE -->
                        <?php if (isLoggedIn()): ?>
                            <form action="checkout.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                                <button type="submit" class="btn-site-checkout">
                                    <i class="fas fa-credit-card"></i> COMMANDER & PAYER SUR LE SITE
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="login.php?redirect=panier.php" class="btn-site-checkout" style="background:#475569;">
                                <i class="fas fa-lock"></i> SE CONNECTER POUR COMMANDER
                            </a>
                        <?php endif; ?>

                        <div class="checkout-separator">
                            <span>OU</span>
                        </div>

                        <!-- OPTION 2 : COMMANDER VIA WHATSAPP -->
                        <div class="whatsapp-box">
                            <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                                <i class="fab fa-whatsapp" style="color:#25d366; font-size:1.2rem;"></i>
                                <span style="font-weight:800; font-size:0.85rem; color:#0f172a;">Validation via WhatsApp</span>
                            </div>
                            <p style="font-size:0.78rem; color:#64748b; margin-bottom:14px; line-height:1.4;">
                                Votre récapitulatif sera transmis automatiquement. Notre équipe vous recontacte rapidement pour valider l'adresse et le paiement à la livraison.
                            </p>
                            
                            <a href="<?php echo $whatsapp_url; ?>" target="_blank" class="btn-whatsapp-valider">
                                <i class="fab fa-whatsapp fa-lg"></i> COMMANDER VIA WHATSAPP
                            </a>
                            
                            <a href="tel:+<?php echo $whatsapp_number; ?>" style="display:flex; align-items:center; justify-content:center; gap:6px; margin-top:12px; color:#25d366; text-decoration:none; font-size:0.78rem; font-weight:700;">
                                <i class="fas fa-phone"></i> ou appelez le +241 07 45 88 99
                            </a>
                        </div>

                        <a href="catalogue.php" style="display:block; text-align:center; margin-top:16px; font-size:0.8rem; font-weight:700; color:#64748b; text-decoration:none;">
                            ← CONTINUER MES ACHATS
                        </a>
                    </div>
                </div>

            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>