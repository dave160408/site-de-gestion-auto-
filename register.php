<?php
require_once 'config/database.php';

if (isLoggedIn()) { header('Location: index.php'); exit; }

$error   = '';
$success = '';
$form    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $form = [
        'entreprise' => trim($_POST['entreprise'] ?? ''),
        'email'      => trim($_POST['email']  ?? ''),
        'tel'        => trim($_POST['tel']    ?? ''),
    ];
    $pass    = $_POST['password']         ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Validations
    if (empty($form['entreprise']) || empty($form['email']) || empty($pass)) {
        $error = 'Tous les champs obligatoires doivent être remplis.';
    } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } elseif (strlen($pass) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif ($pass !== $confirm) {
        $error = 'Les mots de passe ne correspondent pas.';
    } else {
        // Vérifier unicité email
        $check = $pdo->prepare("SELECT id_clients FROM CLIENTS WHERE email = ?");
        $check->execute([$form['email']]);
        if ($check->fetch()) {
            $error = 'Cette adresse email est déjà associée à un compte.';
        } else {
            $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
            // NOTE : le nom de l'entreprise est stocké dans la colonne nom_client.
            // prenom_client n'a plus lieu d'être ; on y met une chaîne vide.
            // -> Si la colonne prenom_client est en NOT NULL sans valeur par défaut,
            //    ça passera (chaîne vide ≠ NULL). Si tu préfères, on peut aussi
            //    rendre la colonne nullable ou la supprimer côté base de données.
            $stmt = $pdo->prepare(
                "INSERT INTO CLIENTS (nom_client, prenom_client, email, telephone, mot_de_passe, role, date_creation)
                 VALUES (?, '', ?, ?, ?, 'client', CURDATE())"
            );
            if ($stmt->execute([$form['entreprise'], $form['email'], $form['tel'] ?: null, $hash])) {
                $success = 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.';
                $form = [];
            } else {
                $error = 'Erreur lors de la création. Réessayez.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription — DUTCH COMPANY DIESEL GABON</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Réutilise les styles de login.php */
        .auth-page {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1.4fr;
        }
        .auth-visual {
            background:
                linear-gradient(135deg, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.7) 100%),
                url('assets/images/diesel-engine-bg.jpg') center/cover no-repeat;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px 50px;
            position: relative;
        }
        .auth-visual::before {
            content:'';
            position:absolute;inset:0;
            background: radial-gradient(ellipse at 50% 50%, rgba(255,199,0,0.1) 0%, transparent 70%);
        }
        .auth-steps { position: relative; z-index: 1; margin-top: 50px; }
        .auth-step {
            display: flex; align-items: flex-start; gap: 18px;
            margin-bottom: 30px; position: relative;
        }
        .auth-step::after {
            content: '';
            position: absolute;
            left: 19px; top: 44px;
            width: 2px; height: calc(100% + 10px);
            background: rgba(255,199,0,0.15);
        }
        .auth-step:last-child::after { display: none; }
        .step-num {
            width: 40px; height: 40px;
            background: rgba(255,199,0,0.1);
            border: 1px solid rgba(255,199,0,0.3);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: var(--gold); font-weight: 900; font-size: 0.85rem;
            flex-shrink: 0;
        }
        .step-info strong { display: block; font-size: 0.9rem; color: #ddd; margin-bottom: 4px; }
        .step-info span { font-size: 0.78rem; color: #555; }

        /* Form panel */
        .auth-form-panel {
            background: var(--bg-root);
            display: flex; align-items: center; justify-content: center;
            padding: 50px 60px;
            overflow-y: auto;
        }
        .auth-form-inner { width: 100%; max-width: 480px; }
        .auth-form-inner h1 { font-size: 1.8rem; font-weight: 900; text-transform: uppercase; letter-spacing: -1px; margin-bottom: 8px; }
        .auth-subtitle { color: #555; font-size: 0.85rem; margin-bottom: 36px; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

        .field-group { margin-bottom: 18px; }
        .field-label { display: block; font-size: 0.7rem; font-weight: 700; color: #555; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 8px; }
        .field-input-wrapper { position: relative; }
        .field-input-wrapper i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #444; font-size: 0.85rem; pointer-events: none; }
        .field-input {
            width: 100%; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
            border-radius: 14px; color: white; padding: 14px 16px 14px 44px;
            font-size: 0.88rem; outline: none; transition: all 0.3s; font-family: 'Inter', sans-serif;
        }
        .field-input:focus { border-color: var(--gold); background: rgba(255,199,0,0.03); box-shadow: 0 0 0 4px rgba(255,199,0,0.08); }
        .field-input::placeholder { color: #333; }

        /* Password strength */
        .password-strength { margin-top: 8px; }
        .strength-bar { height: 3px; background: #1e1e1e; border-radius: 2px; overflow: hidden; margin-bottom: 4px; }
        .strength-fill { height: 100%; width: 0%; border-radius: 2px; transition: all 0.4s ease; }
        .strength-text { font-size: 0.7rem; color: #555; }

        /* Checkbox CGV */
        .cgv-check { display: flex; align-items: flex-start; gap: 12px; margin: 20px 0; font-size: 0.8rem; color: #555; }
        .cgv-check input { accent-color: var(--gold); margin-top: 3px; flex-shrink: 0; }
        .cgv-check a { color: var(--gold); text-decoration: none; }

        .btn-auth-submit {
            width: 100%; background: var(--gold); color: #000;
            border: none; border-radius: 14px; padding: 16px;
            font-size: 0.88rem; font-weight: 800; letter-spacing: 1.5px;
            text-transform: uppercase; cursor: pointer;
            transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .btn-auth-submit:hover { background: #fff; transform: translateY(-2px); box-shadow: 0 15px 30px rgba(255,199,0,0.2); }
        .btn-auth-submit:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        .auth-alert { padding: 14px 18px; border-radius: 12px; font-size: 0.82rem; font-weight: 600; display: flex; align-items: center; gap: 10px; margin-bottom: 24px; }
        .auth-alert-error { background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.3); color: #ef4444; }
        .auth-alert-success { background: rgba(34,197,94,0.08); border: 1px solid rgba(34,197,94,0.3); color: #22c55e; }
        .auth-switch { text-align: center; color: #444; font-size: 0.82rem; margin-top: 24px; }
        .auth-switch a { color: var(--gold); text-decoration: none; font-weight: 700; }

        .field-toggle-pass { position: absolute; right: 16px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: #444; cursor: pointer; font-size: 0.85rem; transition: color 0.3s; }
        .field-toggle-pass:hover { color: var(--gold); }

        @media (max-width: 900px) {
            .auth-page { grid-template-columns: 1fr; }
            .auth-visual { display: none; }
            .auth-form-panel { padding: 40px 24px; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="auth-page">
    <!-- VISUEL GAUCHE -->
    <div class="auth-visual">
        <div style="position:relative;z-index:1;">
            <a href="index.php" style="text-decoration:none;">
                <span class="brand-main" style="font-size:1.3rem;color:#fff;font-weight:900;letter-spacing:3px;display:block;">DUTCH COMPANY</span>
                <span class="brand-sub" style="font-size:0.65rem;color:var(--gold);letter-spacing:6px;display:block;margin-top:4px;">DIESEL GABON</span>
            </a>
        </div>

        <div class="auth-steps">
            <h3 style="font-size:1.4rem;font-weight:900;text-transform:uppercase;margin-bottom:30px;position:relative;z-index:1;">
                Votre compte,<br><span style="color:var(--gold);">vos avantages.</span>
            </h3>
            <div class="auth-step">
                <div class="step-num">1</div>
                <div class="step-info">
                    <strong>Créez votre profil</strong>
                    <span>Rapide, gratuit, sans engagement</span>
                </div>
            </div>
            <div class="auth-step">
                <div class="step-num">2</div>
                <div class="step-info">
                    <strong>Accédez au catalogue complet</strong>
                    <span>+15 000 références OEM disponibles</span>
                </div>
            </div>
            <div class="auth-step">
                <div class="step-num">3</div>
                <div class="step-info">
                    <strong>Commandez & suivez</strong>
                    <span>Livraison express partout au Gabon</span>
                </div>
            </div>
        </div>
    </div>

    <!-- FORMULAIRE DROIT -->
    <div class="auth-form-panel">
        <div class="auth-form-inner">
            <h1>Créer mon compte</h1>
            <p class="auth-subtitle">
                Déjà inscrit ?
                <a href="login.php" style="color:var(--gold);text-decoration:none;font-weight:700;">Se connecter</a>
            </p>

            <?php if ($error): ?>
            <div class="auth-alert auth-alert-error">
                <i class="fas fa-exclamation-circle"></i><?php echo $error; ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="auth-alert auth-alert-success">
                <i class="fas fa-check-circle"></i><?php echo $success; ?>
                <a href="login.php" style="color:var(--gold);font-weight:700;margin-left:auto;">SE CONNECTER →</a>
            </div>
            <?php endif; ?>

            <form method="POST" action="register.php" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                <div class="field-group">
                    <label class="field-label">Raison sociale / Nom de l'entreprise *</label>
                    <div class="field-input-wrapper">
                        <i class="fas fa-building"></i>
                        <input type="text" name="entreprise" class="field-input"
                               placeholder="DUTCH COMPANY DIESEL GABON" required
                               value="<?php echo htmlspecialchars($form['entreprise'] ?? ''); ?>">
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label">Email *</label>
                    <div class="field-input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" class="field-input"
                               placeholder="votre@email.com" required
                               value="<?php echo htmlspecialchars($form['email'] ?? ''); ?>">
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label">Téléphone (Gabon)</label>
                    <div class="field-input-wrapper">
                        <i class="fas fa-phone"></i>
                        <input type="tel" name="tel" class="field-input"
                               placeholder="+241 07 00 00 00"
                               value="<?php echo htmlspecialchars($form['tel'] ?? ''); ?>">
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label">Mot de Passe * (min. 8 caractères)</label>
                    <div class="field-input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="passwordField"
                               class="field-input" placeholder="••••••••"
                               required minlength="8"
                               oninput="checkStrength(this.value)">
                        <button type="button" class="field-toggle-pass"
                                onclick="togglePassword('passwordField', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength">
                        <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                        <span class="strength-text" id="strengthText">Entrez un mot de passe</span>
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label">Confirmer le Mot de Passe *</label>
                    <div class="field-input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="confirm_password" id="confirmField"
                               class="field-input" placeholder="••••••••" required>
                        <button type="button" class="field-toggle-pass"
                                onclick="togglePassword('confirmField', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="cgv-check">
                    <input type="checkbox" id="cgv" required>
                    <label for="cgv">
                        J'accepte les Conditions Générales de Vente et la Politique de Confidentialité de DUTCH COMPANY DIESEL GABON.
                    </label>
                </div>

                <button type="submit" class="btn-auth-submit" id="submitBtn">
                    <i class="fas fa-user-plus"></i>
                    CRÉER MON COMPTE
                </button>
            </form>

            <div class="auth-switch">
                Déjà un compte ?
                <a href="login.php">Se connecter</a>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(id, btn) {
    const f = document.getElementById(id);
    const i = btn.querySelector('i');
    f.type = f.type === 'password' ? 'text' : 'password';
    i.classList.toggle('fa-eye'); i.classList.toggle('fa-eye-slash');
}

function checkStrength(val) {
    const fill = document.getElementById('strengthFill');
    const text = document.getElementById('strengthText');
    let score = 0;
    if (val.length >= 8)  score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const levels = [
        { w: '0%',   c: 'transparent', t: 'Entrez un mot de passe' },
        { w: '25%',  c: '#ef4444',     t: 'Très faible' },
        { w: '50%',  c: '#f97316',     t: 'Faible' },
        { w: '75%',  c: '#eab308',     t: 'Moyen' },
        { w: '100%', c: '#22c55e',     t: 'Fort ✓' },
    ];
    fill.style.width = levels[score].w;
    fill.style.background = levels[score].c;
    text.textContent = levels[score].t;
    text.style.color = levels[score].c;
}
</script>
</body>
</html>