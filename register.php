<?php
/* ============================================================
   TISSUS PASSION — Traitement de l'inscription
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

/* ── Validation des champs ─────────────────────────────────── */
$errors = [];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Adresse email invalide.";
}

if (strlen($password) < 8) {
    $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
}

if (!preg_match('/[A-Z]/', $password)) {
    $errors[] = "Le mot de passe doit contenir au moins une majuscule.";
}

if (!preg_match('/[0-9]/', $password)) {
    $errors[] = "Le mot de passe doit contenir au moins un chiffre.";
}

if (!empty($errors)) {
    $_SESSION['error'] = implode("\n", $errors);
    header('Location: auth.php?tab=register');
    exit;
}

/* ── Vérification doublon + insertion via PDO ─────────────── */
try {
    /* Vérifie si l'email existe déjà */
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        $_SESSION['error'] = "Cette adresse email est déjà utilisée.";
        header('Location: auth.php?tab=register');
        exit;
    }

    /* Hashage sécurisé du mot de passe */
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    /* Insertion du nouvel utilisateur */
    $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
    $stmt->execute([$email, $hashedPassword]);

    $_SESSION['success'] = "Compte créé avec succès ! Vous pouvez maintenant vous connecter.";

} catch (PDOException $e) {
    error_log('Erreur inscription : ' . $e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue lors de la création du compte. Veuillez réessayer.";
    header('Location: auth.php?tab=register');
    exit;
}

header('Location: auth.php');
exit;
