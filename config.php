<?php
/* ============================================================
   TISSUS PASSION — Configuration base de données
   Utilise PDO avec requêtes préparées (sécurité renforcée)
   ============================================================ */

/* ── Paramètres de connexion ─────────────────────────────── */
define('DB_HOST', 'localhost');
define('DB_NAME', 'tissus_passion');  // Nom de la base de données
define('DB_USER', 'root');            // Utilisateur XAMPP par défaut
define('DB_PASS', '');                // Mot de passe XAMPP (vide par défaut)
define('DB_CHARSET', 'utf8mb4');

/* ── Connexion PDO sécurisée ─────────────────────────────── */
try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST, DB_NAME, DB_CHARSET
    );

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Lance des exceptions en cas d'erreur
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Résultats en tableaux associatifs
        PDO::ATTR_EMULATE_PREPARES   => false,                   // Désactive les requêtes préparées émulées
    ]);

    /* Compatibilité avec les fichiers qui utilisent encore $conn (mysqli) */
    /* Note : les nouveaux fichiers utilisent $pdo directement             */
    $conn = null; // Remplacé par $pdo

} catch (PDOException $e) {
    /* En production, ne jamais afficher le détail de l'erreur */
    error_log('Erreur de connexion DB : ' . $e->getMessage());
    http_response_code(503);
    die('Service temporairement indisponible. Veuillez réessayer plus tard.');
}

/* ── Démarrage sécurisé de la session ───────────────────── */
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,   // Inaccessible via JavaScript
        'cookie_secure'   => false,  // Passer à true en production HTTPS
        'cookie_samesite' => 'Strict',
    ]);
}

/* ── Génération du token CSRF ────────────────────────────── */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Vérifie le token CSRF envoyé via POST.
 * À appeler en début de chaque script qui traite un formulaire POST.
 */
function verifyCsrf(): void {
    if (
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])
    ) {
        http_response_code(403);
        die('Requête invalide (protection CSRF).');
    }
}

/*
 * ── REQUÊTE SQL DE CRÉATION DE TABLE ────────────────────────
 *
 * À exécuter une seule fois via phpMyAdmin ou la console MySQL :
 *
 * CREATE DATABASE IF NOT EXISTS tissus_passion
 *     CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
 *
 * USE tissus_passion;
 *
 * CREATE TABLE IF NOT EXISTS users (
 *     id         INT AUTO_INCREMENT PRIMARY KEY,
 *     email      VARCHAR(150) UNIQUE NOT NULL,
 *     password   VARCHAR(255) NOT NULL,
 *     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 */
