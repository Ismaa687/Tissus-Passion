<?php
/* ============================================================
   TISSUS PASSION — Traitement de la connexion
   Script de traitement POST uniquement — pas de HTML affiché.
   La page visible est auth.php.
   Utilise PDO avec requêtes préparées.
   ============================================================ */
include "config.php";

/* Seules les requêtes POST sont acceptées */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: auth.php');
    exit;
}

/* Vérification du token CSRF */
verifyCsrf();

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

/* Validation basique des champs */
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password)) {
    $_SESSION['error'] = "Veuillez remplir tous les champs correctement.";
    header('Location: auth.php');
    exit;
}

/* ── Protection brute-force (compteur en session) ─────────── */
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts']     = 0;
    $_SESSION['login_last_attempt'] = time();
}

/* Réinitialise le compteur après 15 minutes */
if (time() - $_SESSION['login_last_attempt'] > 900) {
    $_SESSION['login_attempts'] = 0;
}

if ($_SESSION['login_attempts'] >= 5) {
    $wait = 900 - (time() - $_SESSION['login_last_attempt']);
    $_SESSION['error'] = "Trop de tentatives. Réessayez dans " . ceil($wait / 60) . " minute(s).";
    header('Location: auth.php');
    exit;
}

/* ── Vérification des identifiants via PDO ────────────────── */
/* Message générique pour éviter l'énumération d'utilisateurs */
$genericError = "Email ou mot de passe incorrect.";

try {
    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    if ($row && password_verify($password, $row['password'])) {
        /* Succès — régénération de l'ID de session (anti-fixation) */
        session_regenerate_id(true);

        $_SESSION['user_id']        = $row['id'];
        $_SESSION['user_email']     = $email;
        $_SESSION['login_attempts'] = 0;

        header('Location: dashboard.php');
        exit;

    } else {
        /* Même délai en cas d'email introuvable (anti-timing attack) */
        if (!$row) {
            password_verify('dummy', '$2y$12$invalidhashinvalidhashinvalidhashx');
        }
        $_SESSION['login_attempts']++;
        $_SESSION['login_last_attempt'] = time();
        $_SESSION['error']              = $genericError;
    }

} catch (PDOException $e) {
    error_log('Erreur login : ' . $e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue. Veuillez réessayer.";
}

header('Location: auth.php');
exit;
