============================================================
  TISSUS PASSION — Guide d'installation
============================================================

STRUCTURE DU PROJET
-------------------
tissus_passion/
├── index.php            ← Page principale (catalogue + panier + admin)
├── auth.php             ← Page connexion / inscription
├── dashboard.php        ← Espace client connecté
├── login.php            ← Traitement formulaire connexion
├── logout.php           ← Déconnexion
├── register.php         ← Traitement inscription
├── config.php           ← Configuration BDD + session + CSRF
├── cart.php             ← Gestion panier (actions POST)
├── products.php         ← Gestion catalogue admin (actions POST)
├── products_data.php    ← Catalogue par défaut (9 tissus)
├── style.css            ← Feuille de style principale
├── css/
│   └── auth.css         ← Styles auth + dashboard
├── js/
│   └── main.js          ← Script navbar mobile + animations
├── img/                 ← ⚠️  PLACEZ VOS PHOTOS ICI
│   ├── coton-imprime-floral.jpg
│   ├── velours-terracotta.jpg
│   ├── lin-naturel-ecru.jpg
│   ├── tissu-pareo-tahitien.jpg
│   ├── jacquard-prestige-dore.jpg
│   ├── microfibre-anthracite.jpg
│   ├── soie-sauvage-ivoire.jpg
│   ├── coton-popeline-vert-sauge.jpg
│   └── broderie-anglaise-blanche.jpg
└── data/                ← Généré automatiquement (ne pas toucher)
    └── products.json    ← Catalogue modifié par l'admin


PHOTOS REQUISES (dossier img/)
-------------------------------
Format recommandé : JPG ou WebP
Ratio conseillé   : 4:3 (ex : 800×600 px)
Taille max        : 300 Ko par image (optimisez avec TinyPNG)

Noms de fichiers exacts (respecter la casse) :
  1. coton-imprime-floral.jpg
  2. velours-terracotta.jpg
  3. lin-naturel-ecru.jpg
  4. tissu-pareo-tahitien.jpg
  5. jacquard-prestige-dore.jpg
  6. microfibre-anthracite.jpg
  7. soie-sauvage-ivoire.jpg
  8. coton-popeline-vert-sauge.jpg
  9. broderie-anglaise-blanche.jpg

Tant que les photos ne sont pas présentes, une couleur de
fond CSS est affichée à la place (aucune erreur).


INSTALLATION (XAMPP)
--------------------
1. Copiez ce dossier dans : C:/xampp/htdocs/tissus_passion/
2. Lancez Apache + MySQL depuis le panneau XAMPP
3. Ouvrez phpMyAdmin → http://localhost/phpmyadmin
4. Exécutez ces requêtes SQL :

   CREATE DATABASE IF NOT EXISTS tissus_passion
     CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

   USE tissus_passion;

   CREATE TABLE IF NOT EXISTS users (
     id         INT AUTO_INCREMENT PRIMARY KEY,
     email      VARCHAR(150) UNIQUE NOT NULL,
     password   VARCHAR(255) NOT NULL,
     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

5. Accédez au site : http://localhost/tissus_passion/


CONFIGURATION BDD (config.php)
-------------------------------
Les valeurs par défaut XAMPP sont déjà configurées :
  DB_HOST : localhost
  DB_NAME : tissus_passion
  DB_USER : root
  DB_PASS : (vide)

Modifiez ces valeurs si votre configuration est différente.


FONCTIONNALITÉS
---------------
• Catalogue de 9 tissus avec photos au mètre (XPF)
• Filtre par catégorie (Coton, Lin, Velours, etc.)
• Panier dynamique en session PHP (ajout, modif, suppression)
• Connexion / inscription sécurisée (CSRF, bcrypt, brute-force)
• Espace admin : ajouter, modifier, supprimer des tissus
• Dashboard utilisateur : modification mot de passe, suppression compte


SÉCURITÉ
--------
• Protection CSRF sur tous les formulaires POST
• Mots de passe hashés avec bcrypt (coût 12)
• Requêtes PDO préparées (anti-injection SQL)
• Anti-brute-force sur la connexion (5 tentatives / 15 min)
• Sessions httpOnly + SameSite=Strict
• Régénération d'ID de session à la connexion

============================================================
