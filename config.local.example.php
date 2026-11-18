<?php
/* ============================================================
   MODÈLE — copie ce fichier en « config.local.php » sur le serveur
   et remplace les valeurs par tes identifiants.
   ============================================================ */
if (!in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true)) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'nom_de_la_base');
    define('DB_USER', 'utilisateur');
    define('DB_PASS', 'mot_de_passe');
}
