============================================================
  TISSUS PASSION — Boutique en ligne de tissus (Nouméa)
  PHP 8 · MariaDB/MySQL · PDO · BTS SIO SLAM
============================================================

FONCTIONNALITÉS
---------------
Visiteur
  • Catalogue des tissus avec photos, filtre par catégorie, prix au mètre (XPF)
  • Horaires d'ouverture (jour en cours mis en avant) et plan d'accès

Client (compte)
  • Inscription / connexion (auth.php)
  • Panier : ajout au demi-mètre près, modification, suppression
  • Validation de commande : enregistrée en base, numéro de commande
  • « Mon compte » : historique des commandes avec leur statut,
    activité récente, changement de mot de passe, suppression du compte

Administrateur (rôle « admin »)
  • Gestion du catalogue : ajouter, modifier, supprimer un tissu,
    choisir sa photo dans le dossier img/
  • Suivi des commandes : changer le statut
    (en attente → confirmée → prête à retirer, ou annulée)
  • Commandes en attente et chiffre d'affaires du mois


STRUCTURE
---------
tissus_passion/
├── index.html              Redirige vers accueil.php
├── accueil.php             Page principale : catalogue, panier, espace admin
├── auth.php                Page connexion / inscription
├── dashboard.php           Espace client (« Mon compte »)
├── login.php               Traitement de la connexion
├── register.php            Traitement de l'inscription
├── logout.php              Déconnexion
├── cart.php                Panier + validation de commande
├── products.php            Gestion du catalogue (admin)
├── commandes.php           Statut des commandes (admin)
├── config.php              Connexion PDO, session, protection CSRF
├── config.local.php        Identifiants du serveur du lycée (NON versionné)
├── config.local.example.php  Modèle de config.local.php
├── fonctions.php           Fonctions communes (droits, panier, affichage)
├── products_data.php       Catalogue de démonstration (bouton « réinitialiser »)
├── install.sql             Création des tables + 9 tissus de départ
├── style.css, css/, js/    Mise en forme et scripts
└── img/                    Photos des tissus


BASE DE DONNÉES (install.sql)
-----------------------------
users                 comptes (email, mot de passe haché, rôle client/admin)
produits              catalogue des tissus
commandes             une commande par validation de panier (total, statut)
lignes_commande       les tissus de chaque commande ; nom et prix COPIÉS
                      au moment de l'achat (l'historique ne change pas si
                      l'admin modifie ou supprime un tissu)
tentatives_connexion  échecs de connexion, pour l'anti-brute-force

Clés étrangères :
  commandes.user_id        → users.id     ON DELETE SET NULL (la vente reste)
  lignes_commande.commande_id → commandes.id ON DELETE CASCADE
  lignes_commande.produit_id  → produits.id  ON DELETE SET NULL


INSTALLATION EN LOCAL (XAMPP)
-----------------------------
1. Copier le dossier dans C:/xampp/htdocs/tissus_passion/
2. Démarrer Apache et MySQL dans le panneau XAMPP
3. phpMyAdmin (http://localhost/phpmyadmin) → onglet SQL →
   coller le contenu de install.sql → Exécuter
4. Ouvrir http://localhost/tissus_passion/
5. S'inscrire, puis donner le rôle admin à son compte :
   UPDATE users SET role = 'admin' WHERE email = 'ton.email@exemple.nc';
   (se déconnecter / reconnecter pour voir l'espace admin)

En local, config.php utilise root sans mot de passe (valeurs XAMPP).


MISE EN LIGNE (serveur du lycée)
--------------------------------
1. Envoyer les fichiers (FileZilla), y compris config.local.php
2. Dans phpMyAdmin du serveur, sélectionner SA base, puis exécuter
   install.sql SANS les lignes CREATE DATABASE et USE
3. Si la table users existait déjà, ajouter la colonne role :
   ALTER TABLE users ADD COLUMN role ENUM('client','admin') NOT NULL DEFAULT 'client' AFTER password;


SÉCURITÉ
--------
• Requêtes préparées PDO partout (anti-injection SQL)
• Mots de passe hachés avec bcrypt (password_hash / password_verify)
• Jeton CSRF sur tous les formulaires POST
• Échappement de tout l'affichage avec htmlspecialchars (anti-XSS)
• Rôle admin vérifié EN BASE à chaque action d'administration
• Prix toujours relus en base au moment de la commande
  (le client ne peut pas modifier un prix)
• Commande enregistrée dans une transaction (tout ou rien)
• Anti-brute-force en base : 5 échecs en 15 min par IP ou par email
• Session : cookie httpOnly, SameSite=Strict, Secure en HTTPS,
  nouvel identifiant à la connexion (anti-fixation)
• Identifiants de la base hors du code (config.local.php, .gitignore)
• Messages d'erreur techniques dans le journal du serveur, jamais à l'écran
• Couleur et photo d'un tissu contrôlées (pas d'injection CSS,
  pas de fichier en dehors du dossier img/)
============================================================
