<?php
/* ============================================================
   TISSUS PASSION — Traitement de la connexion
   Script POST uniquement ; la page visible est auth.php.
   ============================================================ */
include "config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rediriger('auth.php');
}

verifyCsrf();

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    $_SESSION['error'] = "Veuillez remplir tous les champs correctement.";
    rediriger('auth.php');
}

/* ── Protection brute-force, enregistrée en base ──────────────
   Au-delà de 5 échecs en 15 minutes pour la même adresse IP ou
   pour le même email, on bloque. Contrairement à un compteur en
   session, vider ses cookies ne remet pas le compteur à zéro.  */
const MAX_TENTATIVES = 5;
const FENETRE_MINUTES = 15;

$ip = $_SERVER['REMOTE_ADDR'] ?? 'inconnue';

try {
    $pdo->prepare('DELETE FROM tentatives_connexion WHERE created_at < NOW() - INTERVAL 1 DAY')->execute();

    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM tentatives_connexion
         WHERE (ip = ? OR email = ?) AND created_at > NOW() - INTERVAL ' . FENETRE_MINUTES . ' MINUTE'
    );
    $stmt->execute([$ip, $email]);

    if ((int) $stmt->fetchColumn() >= MAX_TENTATIVES) {
        $_SESSION['error'] = "Trop de tentatives. Réessayez dans " . FENETRE_MINUTES . " minutes.";
        rediriger('auth.php');
    }

    /* ── Vérification des identifiants ─────────────────────── */
    $stmt = $pdo->prepare('SELECT id, password, role FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    if ($row && password_verify($password, $row['password'])) {
        /* Succès : nouvel identifiant de session (anti-fixation) */
        session_regenerate_id(true);

        $_SESSION['user_id']    = (int) $row['id'];
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role']  = $row['role'];

        /* Les échecs précédents de ce compte sont oubliés */
        $pdo->prepare('DELETE FROM tentatives_connexion WHERE email = ?')->execute([$email]);

        rediriger($row['role'] === 'admin' ? 'accueil.php#admin-products' : 'dashboard.php');
    }

    /* Même temps de réponse si l'email n'existe pas (anti-timing) */
    if (!$row) {
        password_verify($password, '$2y$12$invalidhashinvalidhashinvalidhashinvalidhashinvalidha');
    }
    $pdo->prepare('INSERT INTO tentatives_connexion (ip, email) VALUES (?, ?)')->execute([$ip, $email]);

    /* Message identique dans tous les cas : on ne révèle pas si l'email existe */
    $_SESSION['error'] = "Email ou mot de passe incorrect.";

} catch (PDOException $e) {
    error_log('Erreur login : ' . $e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue. Veuillez réessayer.";
}

$_SESSION['old_email'] = $email;
rediriger('auth.php');
