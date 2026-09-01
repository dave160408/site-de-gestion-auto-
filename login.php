<?php
require_once 'config/database.php';

// Redirection si déjà connecté
if (isLoggedIn()) {
    header('Location: ' . ($_SESSION['user_role'] === 'admin' ? 'admin/' : 'index.php'));
    exit;
}

$error = '';
$email_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $email_val = htmlspecialchars($email);

    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } else {
        $stmt = $pdo->prepare(
            "SELECT * FROM CLIENTS WHERE email = ? LIMIT 1"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['mot_de_passe'])) {
            // Régénération de session (anti-fixation)
            session_regenerate_id(true);
            $_SESSION['user_id']    = $user['id_clients'];
            $_SESSION['user_name']  = $user['prenom_client'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role']  = $user['role'];

            $redirect = $user['role'] === 'admin'
                ? 'admin/'
                : 'index.php';
            header("Location: $redirect");
            exit;
        } else {
            // Message volontairement vague (sécurité)
            $error = 'Email ou mot de passe incorrect.';
            // Délai anti brute-force
            sleep(1);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — DUTCH COMPANY DIESEL GABON</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* ── Auth Layout ── */
        .auth-page {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        /* Panneau gauche visuel */
        .auth-visual {
            background:
                linear-gradient(135deg, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.6) 100%),
                url('assets/images/diesel-engine-bg.jpg') center/cover no-repeat;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 50px;
            position: relative;
            overflow: hidden;
        }
        .auth-visual::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 30% 70%, rgba(255,199,0,0.12) 0%, transparent 60%);
        }
        .auth-visual-logo { position: relative; z-index: 1; }
        .auth-visual-logo .brand-main { font-size: 1.4rem; color: #fff; font-weight: 900; letter-spacing: 3px; display: block; }
        .auth-visual-logo .brand-sub  { font-size: 0.65rem; color: var(--gold); letter-spacing: 6px; display: block; margin-top: 4px; }

        .auth-visual-quote { position: relative; z-index: 1; }
        .auth-visual-quote blockquote {
            font-size: clamp(1.8rem, 3vw, 2.8rem);
            font-weight: 900;
            line-height: 1.1;
            text-transform: uppercase;
            letter-spacing: -1px;
        }
        .auth-visual-quote blockquote span { color: var(--gold); }
        .auth-visual-quote p { color: #666; font-size: 0.85rem; margin-top: 16px; }

        .auth-visual-features { position: relative; z-index: 1; display: flex; flex-direction: column; gap: 14px; }
        .auth-feature { display: flex; align-items: center; gap: 14px; }
        .auth-feature-icon {
            width: 40px; height: 40px;
            background: rgba(255,199,0,0.1);
            border: 1px solid rgba(255,199,0,0.2);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: var(--gold); font-size: 0.9rem;
            flex-shrink: 0;
        }
        .auth-feature-text { font-size: 0.82rem; color: #aaa; line-height: 1.4; }
        .auth-feature-text strong { color: #ddd; display: block; font-size: 0.85rem; }

        /* Panneau droit formulaire */
        .auth-form-panel {
            background: var(--bg-root);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 50px;
            position: relative;
        }
        .auth-form-panel::before {
            content: '';
            position: absolute;
            top: 0; bottom: 0; left: 0;
            width: 1px;
            background: linear-gradient(to bottom, transparent, rgba(255,199,0,0.3), transparent);
        }

        .auth-form-inner { width: 100%; max-width: 400px; }

        .auth-form-inner h1 {
            font-size: 2rem; font-weight: 900;
            text-transform: uppercase; letter-spacing: -1px;
            margin-bottom: 8px;
        }
        .auth-form-inner .auth-subtitle {
            color: #555; font-size: 0.85rem; margin-bottom: 40px;
        }

        /* Champs */
        .field-group { margin-bottom: 20px; }
        .field-label {
            display: block;
            font-size: 0.7rem; font-weight: 700;
            color: #555; letter-spacing: 2px;
            text-transform: uppercase; margin-bottom: 8px;
        }
        .field-input-wrapper { position: relative; }
        .field-input-wrapper i {
            position: absolute; left: 16px; top: 50%;
            transform: translateY(-50%);
            color: #444; font-size: 0.85rem;
            pointer-events: none;
        }
        .field-input {
            width: 100%;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 14px;
            color: white;
            padding: 15px 16px 15px 44px;
            font-size: 0.9rem;
            outline: none;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }
        .field-input:focus {
            border-color: var(--gold);
            background: rgba(255,199,0,0.03);
            box-shadow: 0 0 0 4px rgba(255,199,0,0.08);
        }
        .field-input::placeholder { color: #333; }

        .field-toggle-pass {
            position: absolute; right: 16px; top: 50%;
            transform: translateY(-50%);
            background: transparent; border: none;
            color: #444; cursor: pointer; font-size: 0.85rem;
            transition: color 0.3s;
        }
        .field-toggle-pass:hover { color: var(--gold); }

        /* Alerte */
        .auth-alert {
            padding: 14px 18px;
            border-radius: 12px;
            font-size: 0.82rem;
            font-weight: 600;
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 24px;
        }
        .auth-alert-error {
            background: rgba(239,68,68,0.08);
            border: 1px solid rgba(239,68,68,0.3);
            color: #ef4444;
        }
        .auth-alert-success {
            background: rgba(34,197,94,0.08);
            border: 1px solid rgba(34,197,94,0.3);
            color: #22c55e;
        }

        /* Options */
        .auth-options {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 28px; font-size: 0.82rem;
        }
        .auth-options label { display: flex; align-items: center; gap: 8px; color: #666; cursor: pointer; }
        .auth-options input[type="checkbox"] { accent-color: var(--gold); }
        .auth-options a { color: var(--gold); text-decoration: none; }
        .auth-options a:hover { opacity: 0.8; }

        /* Bouton submit */
        .btn-auth-submit {
            width: 100%;
            background: var(--gold);
            color: #000;
            border: none;
            border-radius: 14px;
            padding: 16px;
            font-size: 0.88rem;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .btn-auth-submit:hover {
            background: #fff;
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(255,199,0,0.2);
        }

        /* Divider */
        .auth-divider {
            display: flex; align-items: center; gap: 14px;
            margin: 24px 0; color: #333; font-size: 0.75rem;
        }
        .auth-divider::before,
        .auth-divider::after {
            content: ''; flex: 1;
            height: 1px; background: rgba(255,255,255,0.06);
        }

        /* Footer lien */
        .auth-switch {
            text-align: center; color: #444;
            font-size: 0.82rem; margin-top: 28px;
        }
        .auth-switch a {
            color: var(--gold); text-decoration: none;
            font-weight: 700; border-bottom: 1px solid rgba(255,199,0,0.4);
        }

        /* Responsive */
        @media (max-width: 900px) {
            .auth-page { grid-template-columns: 1fr; }
            .auth-visual { display: none; }
            .auth-form-panel { padding: 40px 24px; }
        }
    </style>
</head>
<body>

<div class="auth-page">
    <!-- PANNEAU VISUEL GAUCHE -->
    <div class="auth-visual">
        <div class="auth-visual-logo">
            <span class="brand-main">DUTCH COMPANY</span>
            <span class="brand-sub">DIESEL GABON</span>
        </div>

        <div class="auth-visual-quote">
            <blockquote>
                ENGINEERED<br>
                FOR <span>POWER.</span>
            </blockquote>
            <p>Le leader gabonais de la pièce détachée diesel haute performance.</p>
        </div>

        <div class="auth-visual-features">
            <div class="auth-feature">
                <div class="auth-feature-icon"><i class="fas fa-shield-alt"></i></div>
                <div class="auth-feature-text">
                    <strong>Commandes sécurisées</strong>
                    Paiement SSL 256-bit chiffré
                </div>
            </div>
            <div class="auth-feature">
                <div class="auth-feature-icon"><i class="fas fa-truck-fast"></i></div>
                <div class="auth-feature-text">
                    <strong>Livraison Express</strong>
                    Libreville, Port-Gentil, Franceville
                </div>
            </div>
            <div class="auth-feature">
                <div class="auth-feature-icon"><i class="fas fa-headset"></i></div>
                <div class="auth-feature-text">
                    <strong>Support WhatsApp 7j/7</strong>
                    +241 07 45 88 99
                </div>
            </div>
        </div>
    </div>

    <!-- PANNEAU FORMULAIRE DROIT -->
    <div class="auth-form-panel">
        <div class="auth-form-inner">
            <h1>Connexion</h1>
            <p class="auth-subtitle">
                Pas encore client ?
                <a href="register.php" style="color:var(--gold);text-decoration:none;font-weight:700;">Créer un compte</a>
            </p>

            <?php if ($error): ?>
            <div class="auth-alert auth-alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="login.php" autocomplete="on">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                <div class="field-group">
                    <label class="field-label">Adresse Email</label>
                    <div class="field-input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" class="field-input"
                               placeholder="votre@email.com"
                               value="<?php echo $email_val; ?>"
                               required autocomplete="email">
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label">Mot de Passe</label>
                    <div class="field-input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="passwordField"
                               class="field-input" placeholder="••••••••"
                               required autocomplete="current-password">
                        <button type="button" class="field-toggle-pass"
                                onclick="togglePassword('passwordField', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="auth-options">
                    <label>
                        <input type="checkbox" name="remember"> Se souvenir de moi
                    </label>
                    <a href="mailto:contact@dcd-gabon.com?subject=Réinitialisation%20du%20mot%20de%20passe">Mot de passe oublié ?</a>
                </div>

                <button type="submit" class="btn-auth-submit">
                    <i class="fas fa-arrow-right-to-bracket"></i>
                    SE CONNECTER
                </button>
            </form>

            <div class="auth-divider">OU CONTACTEZ-NOUS DIRECTEMENT</div>

            <a href="https://wa.me/24107458899" target="_blank"
               style="display:flex;align-items:center;justify-content:center;gap:10px;
                      background:rgba(37,211,102,0.08);border:1px solid rgba(37,211,102,0.2);
                      color:#25d366;border-radius:14px;padding:14px;
                      text-decoration:none;font-weight:700;font-size:0.85rem;
                      transition:all 0.3s;">
                <i class="fab fa-whatsapp fa-lg"></i> COMMANDER VIA WHATSAPP
            </a>

            <div class="auth-switch">
                Nouveau sur DUTCH COMPANY ?
                <a href="register.php">Créer mon compte gratuitement</a>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId, btn) {
    const field = document.getElementById(fieldId);
    const icon  = btn.querySelector('i');
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>
</body>
</html>