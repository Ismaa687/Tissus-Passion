<?php
/* ============================================================
   TISSUS PASSION — Configuration : base de données, session, CSRF
   ------------------------------------------------------------
   Les identifiants de la base ne sont PAS écrits ici :
   - sur le serveur du lycée, ils sont dans config.local.php
     (fichier non versionné, voir .gitignore) ;
   - en local avec XAMPP, on garde les valeurs par défaut
     (root sans mot de passe).
   Modèle à copier : config.local.example.php
   ============================================================ */

/* ── Identifiants de la base ─────────────────────────────── */
if (file_exists(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}
defined('DB_HOST') || define('DB_HOST', 'localhost');
defined('DB_NAME') || define('DB_NAME', 'tissus_passion');
defined('DB_USER') || define('DB_USER', 'etud2-05');
defined('DB_PASS') || define('DB_PASS', 'btssioslam988');
define('DB_CHARSET', 'utf8mb4');

/* ── Connexion PDO sécurisée ─────────────────────────────── */
try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    /* Heures de Nouméa, côté PHP et côté base (dates des commandes) */
    date_default_timezone_set('Pacific/Noumea');
    $pdo->exec("SET time_zone = '+11:00'");
} catch (PDOException $e) {
    /* Le détail technique va dans le journal du serveur, jamais à l'écran */
    error_log('Erreur BDD : ' . $e->getMessage());
    http_response_code(500);
    die('Le site est momentanément indisponible. Merci de réessayer plus tard.');
}

/* ── Démarrage sécurisé de la session ───────────────────── */
if (session_status() === PHP_SESSION_NONE) {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_start([
        'cookie_httponly' => true,          // cookie illisible en JavaScript
        'cookie_secure'   => $https,        // envoyé seulement en HTTPS quand le site l'est
        'cookie_samesite' => 'Strict',      // pas envoyé depuis un autre site (anti-CSRF)
        'use_strict_mode' => true,          // refuse un identifiant de session inventé
    ]);
}

/* ── Génération du token CSRF ────────────────────────────── */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Vérifie le token CSRF envoyé via POST.
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

require_once __DIR__ . '/fonctions.php';
