<?php
/* ============================================================
   TISSUS PASSION — Page Connexion / Inscription
   Les formulaires sont traités par login.php et register.php,
   qui reviennent ici avec un message en session.
   ============================================================ */
include "config.php";

/* Déjà connecté : pas besoin de se reconnecter */
if (estConnecte()) {
    rediriger('dashboard.php');
}

$erreur   = $_SESSION['error']   ?? '';
$succes   = $_SESSION['success'] ?? '';
$oldEmail = $_SESSION['old_email'] ?? '';
unset($_SESSION['error'], $_SESSION['success'], $_SESSION['old_email']);

/* Onglet ouvert : inscription si demandé dans l'adresse */
$onglet = (($_GET['tab'] ?? '') === 'register') ? 'register' : 'login';
$csrf   = e($_SESSION['csrf_token']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Tissus Passion</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Cormorant+Garamond:wght@300;400;500&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="css/style_additions.css">
</head>
<body>

<!-- ══════════════════════════════════════════════════════════
     NAVBAR
══════════════════════════════════════════════════════════ -->
<nav class="navbar">
    <a href="accueil.php" class="nav-logo">Tissus <span>Passion</span></a>

    <ul class="nav-links" id="navLinks">
        <li><a href="accueil.php#accueil">Accueil</a></li>
        <li><a href="accueil.php#catalogue">Catalogue</a></li>
        <li><a href="accueil.php#horaires">Horaires</a></li>
        <li><a href="accueil.php#lieu">Lieu</a></li>
        <li><a href="auth.php" style="color:var(--terra);">Connexion</a></li>
    </ul>

    <button class="nav-toggle" id="navToggle" aria-label="Menu" aria-expanded="false">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
    </button>
</nav>

<div class="auth-wrapper">

    <!-- Panneau décoratif -->
    <div class="auth-deco">
        <div class="auth-deco-bg"></div>
        <div class="auth-deco-pattern"></div>
        <div class="auth-deco-swatches">
            <div class="deco-swatch"></div>
            <div class="deco-swatch"></div>
            <div class="deco-swatch"></div>
        </div>
        <div class="auth-deco-content">
            <p class="auth-deco-quote">« Chaque intérieur commence par une belle matière. »</p>
            <p class="auth-deco-sub">TISSUS PASSION — NOUMÉA</p>
        </div>
    </div>

    <!-- Formulaires -->
    <div class="auth-panel">
        <div class="auth-form-box">
            <div class="auth-form-eyebrow">Espace client</div>
            <h1 class="auth-form-title">Bienvenue</h1>
            <p class="auth-form-sub">Connectez-vous pour remplir votre panier, passer commande et suivre vos achats.</p>
            <div class="auth-divider"></div>

            <?php if ($erreur): ?>
            <div class="auth-alert alert-error" role="alert"><span><?= nl2br(e($erreur)) ?></span></div>
            <?php endif; ?>
            <?php if ($succes): ?>
            <div class="auth-alert alert-success" role="status"><span><?= e($succes) ?></span></div>
            <?php endif; ?>

            <div class="auth-tabs" role="tablist">
                <button type="button" class="auth-tab-btn <?= $onglet === 'login' ? 'active' : '' ?>" data-onglet="login" role="tab">Connexion</button>
                <button type="button" class="auth-tab-btn <?= $onglet === 'register' ? 'active' : '' ?>" data-onglet="register" role="tab">Inscription</button>
            </div>

            <!-- Connexion -->
            <form action="login.php" method="POST" class="auth-panel-form <?= $onglet === 'login' ? 'active' : '' ?>" id="panneau-login">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="form-group">
                    <label class="form-label" for="login-email">Adresse email</label>
                    <input type="email" id="login-email" name="email" class="form-input" required maxlength="150"
                           autocomplete="email" placeholder="vous@exemple.nc" value="<?= e($oldEmail) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="login-password">Mot de passe</label>
                    <input type="password" id="login-password" name="password" class="form-input" required
                           autocomplete="current-password" placeholder="••••••••">
                </div>
                <button type="submit" class="auth-btn">Se connecter</button>
            </form>

            <!-- Inscription -->
            <form action="register.php" method="POST" class="auth-panel-form <?= $onglet === 'register' ? 'active' : '' ?>" id="panneau-register">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="form-group">
                    <label class="form-label" for="reg-email">Adresse email</label>
                    <input type="email" id="reg-email" name="email" class="form-input" required maxlength="150"
                           autocomplete="email" placeholder="vous@exemple.nc" value="<?= e($oldEmail) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="reg-password">Mot de passe</label>
                    <input type="password" id="reg-password" name="password" class="form-input" required minlength="8"
                           autocomplete="new-password" placeholder="••••••••">
                    <p class="form-hint">8 caractères minimum, dont une majuscule et un chiffre.</p>
                </div>
                <div class="form-group">
                    <label class="form-label" for="reg-password2">Confirmer le mot de passe</label>
                    <input type="password" id="reg-password2" name="password_confirm" class="form-input" required minlength="8"
                           autocomplete="new-password" placeholder="••••••••">
                </div>
                <button type="submit" class="auth-btn">Créer mon compte</button>
            </form>

            <p class="auth-footer-note" style="margin-top:1.5rem;font-size:.8rem;color:var(--text-muted,#7A7068);">
                Vos données servent uniquement à gérer votre compte et vos commandes.
            </p>
        </div>
    </div>
</div>

<script src="js/main.js"></script>
<script>
/* Onglets Connexion / Inscription */
document.querySelectorAll('.auth-tab-btn').forEach(bouton => {
    bouton.addEventListener('click', () => {
        const cible = bouton.dataset.onglet;
        document.querySelectorAll('.auth-tab-btn').forEach(b => b.classList.toggle('active', b === bouton));
        document.querySelectorAll('.auth-panel-form').forEach(f => f.classList.toggle('active', f.id === 'panneau-' + cible));
    });
});
</script>
</body>
</html>
