<?php
/* ============================================================
   TISSUS PASSION — Gestion des produits (admin simplifié)
   Stockage dans un fichier JSON côté serveur.
   Seul un utilisateur connecté peut accéder à ces actions.
   ============================================================ */
include "config.php";

/* Seules les requêtes POST sont acceptées */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php#catalogue');
    exit;
}

/* Protection CSRF */
verifyCsrf();

/* L'utilisateur doit être connecté */
if (empty($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

/* ── Fichier de données JSON ─────────────────────────────────
   Si le fichier n'existe pas, on initialise depuis products_data.php
*/
define('PRODUCTS_JSON', __DIR__ . '/data/products.json');

if (!is_dir(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0755, true);
}

/**
 * Charge les produits : depuis le JSON si présent, sinon depuis products_data.php
 */
function loadProducts(): array {
    if (file_exists(PRODUCTS_JSON)) {
        $json = file_get_contents(PRODUCTS_JSON);
        return json_decode($json, true) ?? [];
    }
    include __DIR__ . '/products_data.php';
    return $PRODUCTS ?? [];
}

/**
 * Sauvegarde les produits dans le JSON
 */
function saveProducts(array $products): void {
    file_put_contents(PRODUCTS_JSON, json_encode(array_values($products), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * Génère un nouvel ID unique (max existant + 1)
 */
function nextId(array $products): int {
    if (empty($products)) return 1;
    return max(array_column($products, 'id')) + 1;
}

/**
 * Sanitize une chaîne de caractères
 */
function sanitizeStr(string $str, int $max = 200): string {
    return mb_substr(trim(strip_tags($str)), 0, $max);
}

$action   = $_POST['action'] ?? '';
$products = loadProducts();

/* ============================================================
   ACTION : Ajouter un nouveau tissu
   POST : nom, description, prix, categorie, couleur
   ============================================================ */
if ($action === 'product_add') {

    $nom         = sanitizeStr($_POST['nom'] ?? '');
    $description = sanitizeStr($_POST['description'] ?? '', 500);
    $prix        = (int) ($_POST['prix'] ?? 0);
    $categorie   = sanitizeStr($_POST['categorie'] ?? '');
    $couleur     = sanitizeStr($_POST['couleur'] ?? 'linear-gradient(135deg,#C9B89A,#8A7048)');
    $emoji       = sanitizeStr($_POST['emoji'] ?? '🧵', 10);

    $errors = [];
    if (empty($nom))         $errors[] = 'Le nom du tissu est obligatoire.';
    if (empty($description)) $errors[] = 'La description est obligatoire.';
    if ($prix < 100)         $errors[] = 'Le prix doit être supérieur à 100 XPF.';
    if ($prix > 50000)       $errors[] = 'Le prix semble trop élevé.';
    if (empty($categorie))   $errors[] = 'La catégorie est obligatoire.';

    if (!empty($errors)) {
        $_SESSION['flash_error'] = implode(' ', $errors);
        header('Location: index.php#admin-products');
        exit;
    }

    $products[] = [
        'id'          => nextId($products),
        'nom'         => $nom,
        'description' => $description,
        'prix'        => $prix,
        'categorie'   => $categorie,
        'couleur'     => $couleur,
        'emoji'       => $emoji,
    ];

    saveProducts($products);
    $_SESSION['flash_success'] = 'Tissu « ' . htmlspecialchars($nom) . ' » ajouté au catalogue.';
    header('Location: index.php#admin-products');
    exit;
}

/* ============================================================
   ACTION : Modifier un tissu existant
   POST : product_id, nom, description, prix, categorie, couleur
   ============================================================ */
if ($action === 'product_edit') {

    $productId   = (int) ($_POST['product_id'] ?? 0);
    $nom         = sanitizeStr($_POST['nom'] ?? '');
    $description = sanitizeStr($_POST['description'] ?? '', 500);
    $prix        = (int) ($_POST['prix'] ?? 0);
    $categorie   = sanitizeStr($_POST['categorie'] ?? '');
    $couleur     = sanitizeStr($_POST['couleur'] ?? '');
    $emoji       = sanitizeStr($_POST['emoji'] ?? '🧵', 10);

    $errors = [];
    if (empty($nom))         $errors[] = 'Le nom est obligatoire.';
    if ($prix < 100)         $errors[] = 'Prix invalide.';

    if (!empty($errors)) {
        $_SESSION['flash_error'] = implode(' ', $errors);
        header('Location: index.php#admin-products');
        exit;
    }

    $updated = false;
    foreach ($products as &$p) {
        if ($p['id'] === $productId) {
            $p['nom']         = $nom;
            $p['description'] = $description;
            $p['prix']        = $prix;
            $p['categorie']   = $categorie;
            if (!empty($couleur)) $p['couleur'] = $couleur;
            if (!empty($emoji))   $p['emoji']   = $emoji;
            $updated = true;
            break;
        }
    }
    unset($p);

    if ($updated) {
        saveProducts($products);
        $_SESSION['flash_success'] = 'Tissu modifié avec succès.';
    } else {
        $_SESSION['flash_error'] = 'Tissu introuvable.';
    }

    header('Location: index.php#admin-products');
    exit;
}

/* ============================================================
   ACTION : Supprimer un tissu
   POST : product_id
   ============================================================ */
if ($action === 'product_delete') {

    $productId = (int) ($_POST['product_id'] ?? 0);
    $before    = count($products);
    $products  = array_filter($products, fn($p) => $p['id'] !== $productId);

    if (count($products) < $before) {
        saveProducts($products);
        /* Retire aussi le produit du panier si présent */
        if (isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
        }
        $_SESSION['flash_success'] = 'Tissu supprimé du catalogue.';
    } else {
        $_SESSION['flash_error'] = 'Tissu introuvable.';
    }

    header('Location: index.php#admin-products');
    exit;
}

/* ============================================================
   ACTION : Réinitialiser le catalogue (recharge products_data.php)
   ============================================================ */
if ($action === 'product_reset') {
    if (file_exists(PRODUCTS_JSON)) {
        unlink(PRODUCTS_JSON);
    }
    $_SESSION['flash_success'] = 'Catalogue réinitialisé avec les produits par défaut.';
    header('Location: index.php#admin-products');
    exit;
}

/* Fallback */
header('Location: index.php#catalogue');
exit;
