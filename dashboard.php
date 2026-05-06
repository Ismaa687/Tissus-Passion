<?php
/* ============================================================
   TISSUS PASSION — Tableau de bord utilisateur
   Inclut : modification du mot de passe + suppression du compte
   ============================================================ */
include "config.php";

/* ── Protection : accès réservé aux utilisateurs connectés ── */
if (empty($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

$userId    = (int) $_SESSION['user_id'];
$userEmail = htmlspecialchars($_SESSION['user_email'] ?? 'Utilisateur');
$initiale  = strtoupper(mb_substr($userEmail, 0, 1));

/* ── Messages flash ──────────────────────────────────────── */
$flashSuccess = '';
$flashError   = '';

/* ============================================================
   TRAITEMENT — Modifier le mot de passe (POST action=pwd)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'pwd') {

    verifyCsrf();

    $currentPwd = $_POST['current_password'] ?? '';
    $newPwd     = $_POST['new_password']     ?? '';
    $confirmPwd = $_POST['confirm_password'] ?? '';

    /* Validation des champs */
    if (empty($currentPwd) || empty($newPwd) || empty($confirmPwd)) {
        $flashError = "Tous les champs sont obligatoires.";

    } elseif ($newPwd !== $confirmPwd) {
        $flashError = "Le nouveau mot de passe et sa confirmation ne correspondent pas.";

    } elseif (strlen($newPwd) < 8) {
        $flashError = "Le nouveau mot de passe doit contenir au moins 8 caractères.";

    } elseif (!preg_match('/[A-Z]/', $newPwd)) {
        $flashError = "Le nouveau mot de passe doit contenir au moins une majuscule.";

    } elseif (!preg_match('/[0-9]/', $newPwd)) {
        $flashError = "Le nouveau mot de passe doit contenir au moins un chiffre.";

    } else {
        /* Récupération du hash actuel depuis la base */
        try {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $row = $stmt->fetch();

            if (!$row || !password_verify($currentPwd, $row['password'])) {
                $flashError = "Le mot de passe actuel est incorrect.";
            } else {
                /* Hash du nouveau mot de passe et mise à jour en base */
                $newHash = password_hash($newPwd, PASSWORD_BCRYPT, ['cost' => 12]);
                $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upd->execute([$newHash, $userId]);
                $flashSuccess = "Votre mot de passe a été modifié avec succès.";
            }

        } catch (PDOException $e) {
            error_log('Erreur changement MDP : ' . $e->getMessage());
            $flashError = "Une erreur est survenue. Veuillez réessayer.";
        }
    }
}

/* ============================================================
   TRAITEMENT — Supprimer le compte (POST action=delete)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {

    verifyCsrf();

    try {
        /* Suppression de l'utilisateur en base */
        $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $del->execute([$userId]);

        /* Destruction complète de la session */
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        /* Redirection vers l'accueil */
        header('Location: index.php');
        exit;

    } catch (PDOException $e) {
        error_log('Erreur suppression compte : ' . $e->getMessage());
        $flashError = "Une erreur est survenue lors de la suppression. Veuillez réessayer.";
    }
}

/* ── Données supplémentaires du profil ───────────────────── */
$stmt = $pdo->prepare("SELECT created_at FROM users WHERE id = ?");
$stmt->execute([$userId]);
$row = $stmt->fetch();
$memberSince = $row ? date('d/m/Y', strtotime($row['created_at'])) : '—';

/* ── Booléen : afficher la zone de confirmation de suppression */
$showDeleteConfirm = isset($_POST['confirm_delete_step']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon espace — Tissus Passion</title>

    <!-- Polices Google (identiques à index.php) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Cormorant+Garamond:wght@300;400;500&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Feuille de style principale du thème -->
    <link rel="stylesheet" href="style.css">
    <!-- Feuille de style auth + dashboard -->
    <link rel="stylesheet" href="css/auth.css">
    <!-- Styles supplémentaires pour les nouvelles sections -->
    <link rel="stylesheet" href="css/style_additions.css">
</head>
<body>

<div class="dashboard-layout">

    <!-- ══════════════════════════════════════════════════════
         NAVBAR — Identique à index.php / auth.php
    ══════════════════════════════════════════════════════ -->
    <nav class="navbar">
        <a href="index.php" class="nav-logo">
            Tissus <span>Passion</span>
        </a>

        <ul class="nav-links" id="navLinks">
            <li><a href="index.php#accueil">Accueil</a></li>
            <li><a href="index.php#catalogue">Catalogue</a></li>
            <li><a href="index.php#horaires">Horaires</a></li>
            <li><a href="index.php#lieu">Lieu</a></li>
            <li><a href="dashboard.php" style="color:var(--terra);">Mon compte</a></li>
        </ul>

        <div style="display:flex; align-items:center; gap:1rem;">
            <!-- Icône panier (utilisateur connecté) -->
            <a href="index.php#catalogue" class="nav-cart" title="Mon panier" style="text-decoration:none;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <path d="M16 10a4 4 0 0 1-8 0"/>
                </svg>
            </a>

            <!-- Avatar + email -->
            <div class="dash-avatar" title="<?= $userEmail ?>"><?= $initiale ?></div>
            <span class="dash-email" style="font-size:0.82rem; color:var(--text-muted);"><?= $userEmail ?></span>

            <!-- Déconnexion -->
            <a href="logout.php" class="dash-logout">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Déconnexion
            </a>

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

    <!-- ══════════════════════════════════════════════════════
         CONTENU PRINCIPAL
    ══════════════════════════════════════════════════════ -->
    <main class="dash-main">

        <!-- Message de bienvenue ─────────────────────────── -->
        <div class="dash-welcome">
            <div class="dash-welcome-eyebrow">Espace client</div>
            <h2>Bonjour, <?= $initiale ?>.</h2>
            <p>Gérez votre compte et vos préférences depuis cet espace personnel.</p>
        </div>

        <!-- ── Messages flash globaux ─────────────────────── -->
        <?php if ($flashSuccess): ?>
            <div class="dash-alert alert-success">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <span><?= htmlspecialchars($flashSuccess) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($flashError): ?>
            <div class="dash-alert alert-error">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <span><?= htmlspecialchars($flashError) ?></span>
            </div>
        <?php endif; ?>

        <!-- ── Statistiques du compte ─────────────────────── -->
        <div class="dash-stats">
            <div class="dash-stat">
                <div class="dash-stat-label">Statut du compte</div>
                <div class="dash-stat-value">Actif</div>
                <span class="dash-stat-badge badge-active">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Vérifié
                </span>
            </div>

            <div class="dash-stat">
                <div class="dash-stat-label">Membre depuis</div>
                <div class="dash-stat-value" style="font-size:1.2rem; padding-top:0.2rem"><?= $memberSince ?></div>
                <div style="font-size:0.78rem; color:var(--text-muted); margin-top:0.4rem;">Date d'inscription</div>
            </div>

            <div class="dash-stat">
                <div class="dash-stat-label">Identifiant</div>
                <div class="dash-stat-value">#<?= str_pad($userId, 4, '0', STR_PAD_LEFT) ?></div>
                <span class="dash-stat-badge badge-info">Compte standard</span>
            </div>
        </div>

        <!-- ── Panneaux profil + activité ─────────────────── -->
        <div class="dash-panels">

            <!-- Profil -->
            <div class="dash-panel">
                <div class="dash-panel-title">Informations du profil</div>

                <div class="profile-row">
                    <span class="profile-key">Email</span>
                    <span class="profile-val"><?= $userEmail ?></span>
                </div>
                <div class="profile-row">
                    <span class="profile-key">Mot de passe</span>
                    <span class="profile-val">••••••••••••</span>
                </div>
                <div class="profile-row">
                    <span class="profile-key">Inscription</span>
                    <span class="profile-val"><?= $memberSince ?></span>
                </div>
                <div class="profile-row">
                    <span class="profile-key">Session</span>
                    <span class="profile-val active">Active</span>
                </div>
            </div>

            <!-- Activité récente -->
            <div class="dash-panel">
                <div class="dash-panel-title">Activité récente</div>

                <div class="activity-item">
                    <div class="activity-dot dot-sage"></div>
                    <div>
                        <div class="activity-text">Connexion réussie</div>
                        <div class="activity-time">À l'instant</div>
                    </div>
                </div>

                <div class="activity-item">
                    <div class="activity-dot dot-terra"></div>
                    <div>
                        <div class="activity-text">Session initialisée</div>
                        <div class="activity-time">À l'instant</div>
                    </div>
                </div>

                <div class="activity-item">
                    <div class="activity-dot dot-muted"></div>
                    <div>
                        <div class="activity-text">Compte créé</div>
                        <div class="activity-time"><?= $memberSince ?></div>
                    </div>
                </div>
            </div>

        </div><!-- /.dash-panels -->


        <!-- ══════════════════════════════════════════════════
             SECTION A — Modifier le mot de passe
        ══════════════════════════════════════════════════ -->
        <section class="dash-section" id="modifier-mdp">

            <!-- En-tête de section -->
            <div class="dash-section-header">
                <div class="dash-section-eyebrow">Sécurité</div>
                <h2 class="dash-section-title">Modifier <em>mon mot de passe</em></h2>
                <p class="dash-section-sub">Choisissez un nouveau mot de passe sécurisé (8 caractères minimum, une majuscule et un chiffre).</p>
                <div class="dash-section-divider"></div>
            </div>

            <!-- Carte formulaire -->
            <div class="dash-form-card">
                <form action="dashboard.php" method="POST" autocomplete="off">
                    <!-- Token CSRF -->
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <!-- Identifiant d'action -->
                    <input type="hidden" name="action" value="pwd">

                    <div class="dash-form-grid">

                        <!-- Mot de passe actuel -->
                        <div class="form-group">
                            <label class="form-label" for="current_password">Mot de passe actuel</label>
                            <input
                                type="password"
                                id="current_password"
                                name="current_password"
                                class="form-input"
                                placeholder="••••••••"
                                required
                                autocomplete="current-password">
                        </div>

                        <!-- Nouveau mot de passe -->
                        <div class="form-group">
                            <label class="form-label" for="new_password">Nouveau mot de passe</label>
                            <input
                                type="password"
                                id="new_password"
                                name="new_password"
                                class="form-input"
                                placeholder="••••••••"
                                required
                                minlength="8"
                                autocomplete="new-password">
                            <p class="form-hint">Min. 8 caractères, une majuscule, un chiffre.</p>
                        </div>

                        <!-- Confirmer le nouveau mot de passe -->
                        <div class="form-group">
                            <label class="form-label" for="confirm_password">Confirmer le nouveau mot de passe</label>
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                class="form-input"
                                placeholder="••••••••"
                                required
                                autocomplete="new-password">
                        </div>

                    </div><!-- /.dash-form-grid -->

                    <!-- Bouton de validation -->
                    <div class="dash-form-actions">
                        <button type="submit" class="dash-btn-primary">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                            <span>Mettre à jour le mot de passe</span>
                        </button>
                    </div>

                </form>
            </div><!-- /.dash-form-card -->

        </section><!-- /#modifier-mdp -->


        <!-- ══════════════════════════════════════════════════
             SECTION B — Supprimer le compte
        ══════════════════════════════════════════════════ -->
        <section class="dash-section dash-section-danger" id="supprimer-compte">

            <!-- En-tête de section -->
            <div class="dash-section-header">
                <div class="dash-section-eyebrow dash-eyebrow-danger">Zone de danger</div>
                <h2 class="dash-section-title">Supprimer <em>mon compte</em></h2>
                <p class="dash-section-sub">
                    Cette action est <strong>définitive et irréversible</strong>. Toutes vos données seront immédiatement supprimées
                    et il vous sera impossible de les récupérer.
                </p>
                <div class="dash-section-divider dash-divider-danger"></div>
            </div>

            <!-- Carte avertissement -->
            <div class="dash-form-card dash-card-danger">

                <!-- Avertissement visuel -->
                <div class="danger-warning">
                    <div class="danger-warning-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                            <line x1="12" y1="9" x2="12" y2="13"/>
                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                    </div>
                    <div class="danger-warning-text">
                        <strong>Attention — action irréversible</strong>
                        <p>En supprimant votre compte, vous perdrez l'accès à votre espace personnel ainsi que toutes vos données associées. Cette opération ne peut pas être annulée.</p>
                    </div>
                </div>

                <?php if (!$showDeleteConfirm): ?>
                    <!-- Étape 1 : bouton "Supprimer mon compte" -->
                    <!-- Ce formulaire affiche la zone de confirmation (sans JS) -->
                    <form action="dashboard.php#supprimer-compte" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="confirm_delete_step" value="1">
                        <div class="dash-form-actions">
                            <button type="submit" class="dash-btn-danger">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                    <path d="M10 11v6"/><path d="M14 11v6"/>
                                    <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                                </svg>
                                <span>Supprimer mon compte</span>
                            </button>
                        </div>
                    </form>

                <?php else: ?>
                    <!-- Étape 2 : zone de confirmation après clic -->
                    <div class="delete-confirm-box">
                        <p class="delete-confirm-text">
                            Êtes-vous <strong>absolument certain</strong> de vouloir supprimer votre compte&nbsp;?
                            Cette action <strong>ne peut pas être annulée</strong>.
                        </p>
                        <div class="delete-confirm-actions">
                            <!-- Bouton de confirmation définitive -->
                            <form action="dashboard.php" method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="dash-btn-danger-solid">
                                    Oui, supprimer définitivement
                                </button>
                            </form>
                            <!-- Bouton d'annulation (simple lien de redirection) -->
                            <a href="dashboard.php" class="dash-btn-cancel">Annuler</a>
                        </div>
                    </div>

                <?php endif; ?>

            </div><!-- /.dash-form-card.dash-card-danger -->

        </section><!-- /#supprimer-compte -->

    </main><!-- /.dash-main -->

    <!-- ══════════════════════════════════════════════════════
         FOOTER — harmonisé avec index.php
    ══════════════════════════════════════════════════════ -->
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
                    <li><a href="dashboard.php">Mon compte</a></li>
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

</div><!-- /.dashboard-layout -->

<script src="js/main.js"></script>
</body>
</html>
