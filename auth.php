<?php
/* ============================================================
   TISSUS PASSION — Page de connexion / inscription
   Navbar et footer harmonisés avec index.php
   ============================================================ */
include "config.php";

/* Si déjà connecté, redirige vers le dashboard */
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Connectez-vous à votre espace Tissus Passion pour gérer votre compte.">
    <title>Connexion — Tissus Passion</title>

    <!-- Polices Google (identiques à index.php) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Cormorant+Garamond:wght@300;400;500&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Feuille de style principale du thème -->
    <link rel="stylesheet" href="style.css">
    <!-- Feuille de style auth -->
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>

<!-- ══════════════════════════════════════════════════════════
     NAVBAR — Identique à index.php, adaptée selon connexion
══════════════════════════════════════════════════════════ -->
<nav class="navbar">
    <a href="index.php" class="nav-logo">
        Tissus <span>Passion</span>
    </a>

    <ul class="nav-links" id="navLinks">
        <li><a href="index.php#accueil">Accueil</a></li>
        <li><a href="index.php#catalogue">Catalogue</a></li>
        <li><a href="index.php#horaires">Horaires</a></li>
        <li><a href="index.php#lieu">Lieu</a></li>
        <!-- Lien actif mis en avant -->
        <li><a href="auth.php" style="color:var(--terra);">Connexion</a></li>
    </ul>

    <div style="display:flex; align-items:center; gap:1rem;">
        <!-- Bouton menu mobile -->
        <button class="nav-toggle" id="navToggle" aria-label="Menu" aria-expanded="false">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="12" x2="21" y2="12"/>
                <line x1="3" y1="6"  x2="21" y2="6"/>
                <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>
    </div>
</nav>

<!-- ══════════════════════════════════════════════════════════
     CONTENU AUTH — Split décoratif (inchangé)
══════════════════════════════════════════════════════════ -->
<div class="auth-wrapper">

    <!-- Panneau gauche décoratif -->
    <div class="auth-deco">
        <div class="auth-deco-bg"></div>
        <div class="auth-deco-pattern"></div>

        <!-- Échantillons de tissu flottants -->
        <div class="auth-deco-swatches">
            <div class="deco-swatch"></div>
            <div class="deco-swatch"></div>
            <div class="deco-swatch"></div>
        </div>

        <!-- Citation -->
        <div class="auth-deco-content">
            <blockquote class="auth-deco-quote">
                « Le tissu, c'est l'âme<br>
                d'un <em>intérieur</em> »
            </blockquote>
            <p class="auth-deco-sub">— Tissus Passion, depuis 1998</p>
        </div>
    </div>

    <!-- Panneau formulaire -->
    <div class="auth-panel">
        <div class="auth-form-box">

            <!-- En-tête -->
            <div class="auth-form-header">
                <div class="auth-form-eyebrow">Espace client</div>
                <h1 class="auth-form-title">Bienvenue</h1>
                <p class="auth-form-sub">Connectez-vous ou créez votre compte pour accéder à votre espace personnel.</p>
                <div class="auth-divider"></div>
            </div>

            <!-- Messages flash session -->
            <?php if (!empty($_SESSION['error'])): ?>
                <div class="auth-alert alert-error">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8"  x2="12"    y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <span><?= nl2br(htmlspecialchars($_SESSION['error'])) ?></span>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="auth-alert alert-success">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    <span><?= htmlspecialchars($_SESSION['success']) ?></span>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <!-- Onglets Connexion / Inscription -->
            <div class="auth-tabs">
                <button class="auth-tab-btn active" onclick="switchTab('login')">Connexion</button>
                <button class="auth-tab-btn"        onclick="switchTab('register')">Inscription</button>
            </div>

            <!-- ── Formulaire Connexion ──────────────────── -->
            <div id="panel-login" class="auth-panel-form active">
                <form action="login.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                    <div class="form-group">
                        <label class="form-label" for="login-email">Adresse email</label>
                        <input
                            type="email"
                            id="login-email"
                            name="email"
                            class="form-input"
                            placeholder="vous@exemple.com"
                            required
                            autocomplete="email">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="login-password">Mot de passe</label>
                        <input
                            type="password"
                            id="login-password"
                            name="password"
                            class="form-input"
                            placeholder="••••••••"
                            required
                            autocomplete="current-password">
                    </div>

                    <a href="#" class="forgot-link">Mot de passe oublié ?</a>

                    <button type="submit" class="auth-btn">
                        <span>Se connecter</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                            <polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </button>
                </form>
            </div>

            <!-- ── Formulaire Inscription ────────────────── -->
            <div id="panel-register" class="auth-panel-form">
                <form action="register.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                    <div class="form-group">
                        <label class="form-label" for="reg-email">Adresse email</label>
                        <input
                            type="email"
                            id="reg-email"
                            name="email"
                            class="form-input"
                            placeholder="vous@exemple.com"
                            required
                            autocomplete="email">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="reg-password">Mot de passe</label>
                        <input
                            type="password"
                            id="reg-password"
                            name="password"
                            class="form-input"
                            placeholder="••••••••"
                            required
                            autocomplete="new-password"
                            minlength="8">
                        <p class="form-hint">Minimum 8 caractères, une majuscule et un chiffre.</p>
                    </div>

                    <button type="submit" class="auth-btn">
                        <span>Créer mon compte</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                            <polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </button>
                </form>
            </div>

            <!-- Note de confiance -->
            <p class="auth-footer-note">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                Vos données sont protégées et ne sont jamais revendues.
            </p>

        </div>
    </div>
</div><!-- /.auth-wrapper -->

<!-- ══════════════════════════════════════════════════════════
     FOOTER — Identique à index.php
══════════════════════════════════════════════════════════ -->
<footer class="footer">
    <div class="footer-grid">

        <!-- Colonne marque -->
        <div>
            <div class="footer-brand">Tissus <span>Passion</span></div>
            <p class="footer-desc">
                Votre spécialiste en tissus d'ameublement depuis 1998. Qualité artisanale, conseil personnalisé et large choix de matières pour sublimer vos intérieurs.
            </p>
        </div>

        <!-- Navigation -->
        <div class="footer-col">
            <h4>Navigation</h4>
            <ul>
                <li><a href="index.php#accueil">Accueil</a></li>
                <li><a href="index.php#catalogue">Catalogue</a></li>
                <li><a href="index.php#horaires">Horaires</a></li>
                <li><a href="index.php#lieu">Lieu</a></li>
                <li><a href="auth.php">Mon compte</a></li>
            </ul>
        </div>

        <!-- Matières -->
        <div class="footer-col">
            <h4>Matières</h4>
            <ul>
                <li><a href="#">Velours</a></li>
                <li><a href="#">Lin naturel</a></li>
                <li><a href="#">Jacquard</a></li>
                <li><a href="#">Soie</a></li>
                <li><a href="#">Microfibre</a></li>
            </ul>
        </div>

        <!-- Contact -->
        <div class="footer-col">
            <h4>Contact</h4>
            <div class="footer-contact">
                <span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                    </svg>
                    71 Rue De Charleroi, Nouméa
                </span>
                <span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.18 2 2 0 0 1 3.6 1.18h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 8.96a16 16 0 0 0 6 6l.92-.92a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                    +687 76.85.84
                </span>
                <span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                    tissuspassions@hotmail.com
                </span>
            </div>
        </div>

    </div>

    <!-- Pied de footer -->
    <div class="footer-bottom">
        <span>© Tissus Passion <?= date('Y') ?> — Tous droits réservés</span>
        <div style="display:flex; gap:1.5rem;">
            <a href="#">Mentions légales</a>
            <a href="#">Politique de confidentialité</a>
            <a href="#">CGV</a>
        </div>
    </div>
</footer>

<!-- Scripts -->
<script src="js/main.js"></script>
<script>
    /* Basculement entre les onglets Connexion / Inscription */
    function switchTab(tab) {
        const btns   = document.querySelectorAll('.auth-tab-btn');
        const panels = document.querySelectorAll('.auth-panel-form');

        btns.forEach((b, i) => b.classList.toggle('active', (tab === 'login' ? i === 0 : i === 1)));
        document.getElementById('panel-login').classList.toggle('active', tab === 'login');
        document.getElementById('panel-register').classList.toggle('active', tab === 'register');
    }

    /* Ouvre automatiquement l'onglet inscription si redirigé depuis register.php */
    const url = new URL(window.location.href);
    if (url.searchParams.get('tab') === 'register') switchTab('register');
</script>
</body>
</html>
