<?php
/* ============================================================
   TISSUS PASSION — Gestion du panier (actions POST)
   Toutes les actions passent par ce fichier et redirigent
   vers index.php#catalogue après traitement.
   ============================================================ */
include "config.php";
include "products_data.php";

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

/* Initialisation du panier en session si absent */
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? '';

/* ── Helper : recherche un produit par son ID ─────────────── */
function findProduct(int $id, array $products): ?array {
    foreach ($products as $p) {
        if ($p['id'] === $id) return $p;
    }
    return null;
}

/* ── Helper : formate un prix XPF ────────────────────────────
   Ex : 4500 → "4 500 XPF"
*/
function formatXpf(float $amount): string {
    return number_format($amount, 0, ',', ' ') . ' XPF';
}

/* ============================================================
   ACTION : Ajouter un article
   POST : product_id (int), quantity (float, mètres)
   ============================================================ */
if ($action === 'add') {

    $productId = (int) ($_POST['product_id'] ?? 0);
    $qty       = round((float) str_replace(',', '.', $_POST['quantity'] ?? '1'), 2);

    /* Validation */
    if ($qty < 0.5) $qty = 0.5;
    if ($qty > 100) $qty = 100;

    $product = findProduct($productId, $PRODUCTS);

    if ($product) {
        /* Si le produit est déjà dans le panier, on additionne les quantités */
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId]['qty'] = round(
                $_SESSION['cart'][$productId]['qty'] + $qty, 2
            );
        } else {
            $_SESSION['cart'][$productId] = [
                'id'          => $product['id'],
                'nom'         => $product['nom'],
                'prix'        => $product['prix'],
                'couleur'     => $product['couleur'],
                'emoji'       => $product['emoji'],
                'categorie'   => $product['categorie'],
                'qty'         => $qty,
            ];
        }
        $_SESSION['flash_success'] = htmlspecialchars($product['nom']) . ' ajouté au panier.';
    } else {
        $_SESSION['flash_error'] = 'Produit introuvable.';
    }

    header('Location: index.php#catalogue');
    exit;
}

/* ============================================================
   ACTION : Modifier la quantité d'un article du panier
   POST : product_id (int), quantity (float)
   ============================================================ */
if ($action === 'update') {

    $productId = (int) ($_POST['product_id'] ?? 0);
    $qty       = round((float) str_replace(',', '.', $_POST['quantity'] ?? '0'), 2);

    if (isset($_SESSION['cart'][$productId])) {
        if ($qty < 0.5) {
            /* Quantité trop faible → suppression */
            unset($_SESSION['cart'][$productId]);
        } else {
            if ($qty > 100) $qty = 100;
            $_SESSION['cart'][$productId]['qty'] = $qty;
        }
    }

    header('Location: index.php#catalogue');
    exit;
}

/* ============================================================
   ACTION : Supprimer un article du panier
   POST : product_id (int)
   ============================================================ */
if ($action === 'remove') {

    $productId = (int) ($_POST['product_id'] ?? 0);
    unset($_SESSION['cart'][$productId]);

    header('Location: index.php#catalogue');
    exit;
}

/* ============================================================
   ACTION : Vider le panier
   ============================================================ */
if ($action === 'clear') {
    $_SESSION['cart'] = [];
    header('Location: index.php#catalogue');
    exit;
}

/* ============================================================
   ACTION : Valider la commande (cosmétique)
   ============================================================ */
if ($action === 'checkout') {
    if (!empty($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
        $_SESSION['flash_success'] = 'Votre commande a bien été enregistrée ! Nous vous contacterons très prochainement pour la confirmer.';
    }
    header('Location: index.php#catalogue');
    exit;
}

/* Fallback */
header('Location: index.php#catalogue');
exit;
