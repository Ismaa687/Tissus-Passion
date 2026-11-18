-- ============================================================
--  TISSUS PASSION — Création de la base (MariaDB / MySQL)
--  À exécuter dans phpMyAdmin (onglet SQL).
--  Sur le serveur du lycée : sélectionne d'abord TA base
--  et ne lance pas les deux premières lignes.
-- ============================================================

-- Les accents et les emojis sont envoyés en UTF-8
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS tissus_passion
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tissus_passion;

-- ── Comptes ─────────────────────────────────────────────────
-- role : 'client' par défaut ; 'admin' gère le catalogue et les commandes.
CREATE TABLE IF NOT EXISTS users (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  email      VARCHAR(150) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,
  role       ENUM('client','admin') NOT NULL DEFAULT 'client',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Si ta table users existe déjà (ancienne version), ajoute la colonne role :
-- ALTER TABLE users ADD COLUMN role ENUM('client','admin') NOT NULL DEFAULT 'client' AFTER password;

-- ── Catalogue ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS produits (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nom         VARCHAR(150) NOT NULL,
  description VARCHAR(500) NOT NULL,
  prix        INT UNSIGNED NOT NULL,                 -- prix au mètre, en XPF
  categorie   VARCHAR(60)  NOT NULL,
  couleur     VARCHAR(200) NOT NULL DEFAULT 'linear-gradient(135deg, #C9B89A, #8A7048)',
  emoji       VARCHAR(10)  NOT NULL DEFAULT '🧵',
  image       VARCHAR(150) NULL,                     -- ex : img/velours-terracotta.jpg
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Commandes ───────────────────────────────────────────────
-- user_id passe à NULL si le client supprime son compte :
-- la boutique garde la trace de la vente.
CREATE TABLE IF NOT EXISTS commandes (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NULL,
  total      INT UNSIGNED NOT NULL,
  statut     ENUM('en_attente','confirmee','prete','annulee') NOT NULL DEFAULT 'en_attente',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_commandes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Une ligne par tissu commandé. Le nom et le prix sont copiés au moment
-- de la commande : modifier ou supprimer un tissu ne change pas l'historique.
CREATE TABLE IF NOT EXISTS lignes_commande (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  commande_id   INT NOT NULL,
  produit_id    INT NULL,
  nom_produit   VARCHAR(150) NOT NULL,
  prix_unitaire INT UNSIGNED NOT NULL,
  quantite      DECIMAL(6,2) NOT NULL,               -- en mètres
  sous_total    INT UNSIGNED NOT NULL,
  CONSTRAINT fk_lignes_commande FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
  CONSTRAINT fk_lignes_produit  FOREIGN KEY (produit_id)  REFERENCES produits(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Anti-brute-force ────────────────────────────────────────
-- Une ligne par échec de connexion (par adresse IP et par email).
CREATE TABLE IF NOT EXISTS tentatives_connexion (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  ip         VARCHAR(45)  NOT NULL,
  email      VARCHAR(150) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_tentatives_ip (ip, created_at),
  INDEX idx_tentatives_email (email, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Catalogue de départ (9 tissus) ──────────────────────────
INSERT INTO produits (nom, description, prix, categorie, couleur, emoji, image)
SELECT * FROM (
  SELECT 'Coton Imprimé Floral' AS nom, 'Motifs floraux tropicaux aux couleurs vives. Idéal pour coussins, rideaux et nappes. Doux au toucher, facile d''entretien.' AS description, 1200 AS prix, 'Coton' AS categorie, 'linear-gradient(135deg, #E8A87C, #C0784A)' AS couleur, '🌺' AS emoji, 'img/coton-imprime-floral.jpg' AS image
  UNION ALL SELECT 'Velours Terracotta', 'Velours ras aux reflets chauds. Parfait pour tapisserie de canapé ou têtes de lit. Toucher soyeux et grande durabilité.', 3800, 'Velours', 'linear-gradient(135deg, #D4956A, #8B5030)', '🛋️', 'img/velours-terracotta.jpg'
  UNION ALL SELECT 'Lin Naturel Écru', 'Lin brut d''aspect noble, respirant et naturel. Idéal pour rideaux, voilages et coussins style bohème ou scandinave.', 1800, 'Lin', 'linear-gradient(135deg, #D9CBAD, #A89268)', '🌾', NULL
  UNION ALL SELECT 'Tissu Paréo Tahitien', 'Tissu léger inspiré des tifaifai polynésiens. Motifs géométriques et floraux traditionnels. Parfait pour décoration estivale.', 950, 'Traditionnel', 'linear-gradient(135deg, #6EC6CA, #2A8A8E)', '🌴', 'img/tissu-pareo-tahitien.jpg'
  UNION ALL SELECT 'Jacquard Prestige Doré', 'Tissage jacquard à motifs damassés, reflets dorés. Haut de gamme pour rideaux de salon, galons et voilures d''apparat.', 4500, 'Jacquard', 'linear-gradient(135deg, #C9B89A, #8A7048)', '✨', 'img/jacquard-prestige-dore.jpg'
  UNION ALL SELECT 'Microfibre Anthracite', 'Microfibre ultra-douce, imperméable et facile à nettoyer. Parfaite pour revêtement de chaises, banquettes et mobilier enfant.', 2200, 'Synthétique', 'linear-gradient(135deg, #6B7280, #374151)', '🪑', 'img/microfibre-anthracite.webp'
  UNION ALL SELECT 'Soie Sauvage Ivoire', 'Soie sauvage naturelle, légèrement texturée. Luminosité incomparable pour rideaux de prestige et tentures murales.', 4200, 'Soie', 'linear-gradient(135deg, #F5ECD7, #D4C5A0)', '🕊️', 'img/soie-sauvage-ivoire.jpg'
  UNION ALL SELECT 'Coton Popeline Vert Sauge', 'Popeline de coton fin, ton vert sauge apaisant. Parfait pour nappes, coussins de jardin et déco intérieure naturelle.', 1400, 'Coton', 'linear-gradient(135deg, #8FAE88, #4A6B42)', '🌿', 'img/coton-popeline-vert-sauge.jpg'
  UNION ALL SELECT 'Broderie Anglaise Blanche', 'Tissu coton brodé de motifs ajourés délicats. Idéal pour voilages, napperons, robes d''intérieur et décoration romantique.', 2600, 'Coton', 'linear-gradient(135deg, #FFFFFF, #E8DDD0)', '🤍', 'img/broderie-anglaise-blanche.jpg'
) AS depart
WHERE NOT EXISTS (SELECT 1 FROM produits);

-- Les numéros des prochains tissus suivent directement (10, 11…)
ALTER TABLE produits AUTO_INCREMENT = 1;

-- ── Donner le rôle administrateur à TON compte ──────────────
-- 1. Inscris-toi sur le site avec ton email.
-- 2. Lance cette requête en mettant ton email :
-- UPDATE users SET role = 'admin' WHERE email = 'ton.email@exemple.nc';
