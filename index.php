<?php
/* ============================================================
   TISSUS PASSION — Page d'accueil principale
   Catalogue dynamique, panier PHP en session, gestion admin
   ============================================================ */
include "config.php";
include "products_data.php"; /* Catalogue par défaut */

/* ── Chargement des produits : JSON si présent, sinon défaut ─ */
define('PRODUCTS_JSON', __DIR__ . '/data/products.json');
if (file_exists(PRODUCTS_JSON)) {
    $json      = file_get_contents(PRODUCTS_JSON);
    $PRODUCTS  = json_decode($json, true) ?? $PRODUCTS;
}

/* ── Initialisation du panier ────────────────────────────────  */
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/* ── Calculs panier ──────────────────────────────────────────  */
$cartItems = $_SESSION['cart'];
$cartTotal = 0;
$cartCount = 0;
foreach ($cartItems as $item) {
    $cartTotal += $item['prix'] * $item['qty'];
    $cartCount++;
}

/* ── Messages flash ──────────────────────────────────────────  */
$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError   = $_SESSION['flash_error']   ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

/* ── Helpers ─────────────────────────────────────────────────  */
/**
 * Formate un montant en XPF avec séparateur de milliers
 * Ex : 4500 → "4 500 XPF"
 */
function xpf(float $amount): string {
    return number_format($amount, 0, ',', ' ') . ' XPF';
}

/**
 * Formate une quantité en mètres (supprime les zéros inutiles)
 * Ex : 1.50 → "1,5 m" | 2.00 → "2 m"
 */
function formatQty(float $qty): string {
    $str = rtrim(number_format($qty, 2, ',', ''), '0');
    $str = rtrim($str, ',');
    return $str . ' m';
}

$isLoggedIn = !empty($_SESSION['user_id']);
$userEmail  = htmlspecialchars($_SESSION['user_email'] ?? '');
$initiale   = strtoupper(mb_substr($userEmail, 0, 1));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Tissus Passion — Votre spécialiste en tissus d'ameublement depuis 1998. Découvrez notre collection de velours, lin, soie et bien plus.">
    <title>Tissus Passion — L'art de l'ameublement textile</title>

    <!-- Polices Google -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Cormorant+Garamond:wght@300;400;500&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Feuille de style principale (inchangée) -->
    <link rel="stylesheet" href="style.css">

    <!-- ═══════════════════════════════════════════════════════
         STYLES ADDITIONNELS — Catalogue, Panier dynamique, Admin
         Ces styles ne modifient pas les classes existantes.
    ═══════════════════════════════════════════════════════ -->
    <style>
    /* ── Catalogue produits ──────────────────────────────── */
    .catalogue-section {
        background: var(--bg, #f7f5f2);
    }
    .catalogue-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.8rem;
        margin-top: 2.5rem;
    }
    .product-card {
        background: #fff;
        border: 1px solid var(--border, #e8e0d5);
        border-radius: 14px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: transform .2s, box-shadow .2s;
    }
    .product-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(0,0,0,.08);
    }
    .product-swatch {
        height: 130px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.8rem;
        position: relative;
    }
    .product-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(255,255,255,.85);
        color: var(--terra, #c0784a);
        font-size: .68rem;
        font-weight: 600;
        letter-spacing: .06em;
        text-transform: uppercase;
        padding: .25rem .6rem;
        border-radius: 20px;
        backdrop-filter: blur(4px);
    }
    .product-body {
        padding: 1.2rem 1.4rem 1.4rem;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: .6rem;
    }
    .product-name {
        font-family: 'Playfair Display', serif;
        font-size: 1.05rem;
        font-weight: 600;
        color: var(--text, #2a2015);
        line-height: 1.3;
    }
    .product-desc {
        font-size: .82rem;
        color: var(--text-muted, #888);
        line-height: 1.55;
        flex: 1;
    }
    .product-price {
        font-size: 1rem;
        font-weight: 700;
        color: var(--terra, #c0784a);
        letter-spacing: .01em;
    }
    .product-price span {
        font-weight: 400;
        font-size: .8rem;
        color: var(--text-muted, #aaa);
        margin-left: .3rem;
    }
    .product-add-form {
        display: flex;
        gap: .6rem;
        align-items: center;
        margin-top: .4rem;
    }
    .product-qty-input {
        width: 70px;
        padding: .5rem .6rem;
        border: 1px solid var(--border, #e8e0d5);
        border-radius: 8px;
        font-size: .88rem;
        font-family: 'Jost', sans-serif;
        background: var(--surface, #faf9f7);
        color: var(--text, #2a2015);
        text-align: center;
        transition: border-color .2s;
    }
    .product-qty-input:focus {
        outline: none;
        border-color: var(--terra, #c0784a);
    }
    .product-qty-label {
        font-size: .78rem;
        color: var(--text-muted, #aaa);
        white-space: nowrap;
    }
    .btn-add-cart {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        padding: .55rem 1rem;
        background: var(--terra, #c0784a);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-family: 'Jost', sans-serif;
        font-size: .84rem;
        font-weight: 600;
        cursor: pointer;
        transition: background .2s, transform .1s;
    }
    .btn-add-cart:hover {
        background: #a5622f;
    }
    .btn-add-cart:active {
        transform: scale(.97);
    }
    .btn-add-cart:disabled {
        background: #ccc;
        cursor: not-allowed;
    }

    /* ── Filtre catégories ───────────────────────────────── */
    .catalogue-filters {
        display: flex;
        flex-wrap: wrap;
        gap: .6rem;
        margin-top: 1.5rem;
    }
    .filter-btn {
        padding: .4rem 1rem;
        border: 1px solid var(--border, #e8e0d5);
        background: #fff;
        border-radius: 20px;
        font-size: .82rem;
        font-family: 'Jost', sans-serif;
        color: var(--text-muted, #888);
        cursor: pointer;
        transition: all .2s;
    }
    .filter-btn:hover,
    .filter-btn.active {
        background: var(--terra, #c0784a);
        border-color: var(--terra, #c0784a);
        color: #fff;
    }

    /* ── Panier dynamique ────────────────────────────────── */
    .cart-section-dynamic {
        background: var(--surface, #faf9f7);
    }
    .cart-dynamic-grid {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 2rem;
        margin-top: 2rem;
    }
    @media (max-width: 860px) {
        .cart-dynamic-grid { grid-template-columns: 1fr; }
    }

    /* Liste des articles */
    .cart-items-list {
        display: flex;
        flex-direction: column;
        gap: .8rem;
    }
    .cart-item-row {
        display: grid;
        grid-template-columns: 48px 1fr auto auto;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.2rem;
        background: #fff;
        border: 1px solid var(--border, #e8e0d5);
        border-radius: 10px;
        transition: box-shadow .2s;
    }
    .cart-item-row:hover {
        box-shadow: 0 4px 14px rgba(0,0,0,.06);
    }
    .cart-swatch-sm {
        width: 48px;
        height: 48px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
    }
    .cart-item-details {
        min-width: 0;
    }
    .cart-item-title {
        font-family: 'Playfair Display', serif;
        font-size: .95rem;
        font-weight: 600;
        color: var(--text, #2a2015);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .cart-item-meta {
        font-size: .78rem;
        color: var(--text-muted, #aaa);
        margin-top: .15rem;
    }
    .cart-item-subtotal {
        font-weight: 700;
        color: var(--terra, #c0784a);
        font-size: .92rem;
        white-space: nowrap;
        text-align: right;
        min-width: 90px;
    }
    .cart-qty-form {
        display: flex;
        align-items: center;
        gap: .35rem;
    }
    .cart-qty-input {
        width: 60px;
        padding: .35rem .4rem;
        border: 1px solid var(--border, #e8e0d5);
        border-radius: 6px;
        font-size: .84rem;
        font-family: 'Jost', sans-serif;
        text-align: center;
        background: var(--bg, #f7f5f2);
    }
    .cart-qty-input:focus {
        outline: none;
        border-color: var(--terra, #c0784a);
    }
    .btn-cart-action {
        padding: .3rem .55rem;
        border: 1px solid var(--border, #e8e0d5);
        border-radius: 6px;
        background: transparent;
        cursor: pointer;
        font-size: .75rem;
        color: var(--text-muted, #888);
        transition: all .15s;
        font-family: 'Jost', sans-serif;
    }
    .btn-cart-action:hover {
        background: var(--terra, #c0784a);
        border-color: var(--terra, #c0784a);
        color: #fff;
    }
    .btn-cart-remove {
        border-color: #fce8e8;
        background: #fff5f5;
        color: #c0392b;
    }
    .btn-cart-remove:hover {
        background: #c0392b;
        border-color: #c0392b;
        color: #fff;
    }

    /* Récapitulatif panier */
    .cart-summary-box {
        background: #fff;
        border: 1px solid var(--border, #e8e0d5);
        border-radius: 12px;
        padding: 1.6rem;
        position: sticky;
        top: 80px;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .cart-summary-title {
        font-family: 'Playfair Display', serif;
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text, #2a2015);
        border-bottom: 1px solid var(--border, #e8e0d5);
        padding-bottom: .8rem;
    }
    .cart-summary-lines {
        display: flex;
        flex-direction: column;
        gap: .5rem;
    }
    .cart-summary-line {
        display: flex;
        justify-content: space-between;
        font-size: .87rem;
        color: var(--text-muted, #888);
    }
    .cart-summary-line strong {
        color: var(--text, #2a2015);
    }
    .cart-summary-total {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        padding-top: .8rem;
        border-top: 2px solid var(--terra, #c0784a);
        font-family: 'Playfair Display', serif;
    }
    .cart-summary-total-label {
        font-size: 1rem;
        font-weight: 600;
    }
    .cart-summary-total-amount {
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--terra, #c0784a);
    }
    .btn-checkout {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        width: 100%;
        padding: .85rem;
        background: var(--terra, #c0784a);
        color: #fff;
        border: none;
        border-radius: 10px;
        font-family: 'Jost', sans-serif;
        font-size: .95rem;
        font-weight: 600;
        cursor: pointer;
        transition: background .2s;
        letter-spacing: .02em;
    }
    .btn-checkout:hover {
        background: #a5622f;
    }
    .btn-clear-cart {
        text-align: center;
        font-size: .8rem;
        color: var(--text-muted, #aaa);
        cursor: pointer;
        background: none;
        border: none;
        font-family: 'Jost', sans-serif;
        text-decoration: underline;
        transition: color .15s;
    }
    .btn-clear-cart:hover {
        color: #c0392b;
    }

    /* Panier vide */
    .cart-empty {
        text-align: center;
        padding: 3.5rem 2rem;
        background: #fff;
        border: 1px dashed var(--border, #e8e0d5);
        border-radius: 12px;
        color: var(--text-muted, #aaa);
        grid-column: 1 / -1;
    }
    .cart-empty svg {
        opacity: .35;
        margin-bottom: 1rem;
    }
    .cart-empty p {
        font-size: .95rem;
    }
    .cart-empty a {
        color: var(--terra, #c0784a);
        font-weight: 600;
    }

    /* Badge panier dans la navbar */
    .cart-badge {
        position: absolute;
        top: -6px;
        right: -6px;
        background: var(--terra, #c0784a);
        color: #fff;
        font-size: .65rem;
        font-weight: 700;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }
    .nav-cart {
        position: relative;
        cursor: pointer;
        color: var(--text, #2a2015);
        transition: color .2s;
        display: flex;
        align-items: center;
    }
    .nav-cart:hover {
        color: var(--terra, #c0784a);
    }

    /* Alertes flash */
    .flash-alert {
        padding: .9rem 1.2rem;
        border-radius: 8px;
        font-size: .88rem;
        display: flex;
        align-items: flex-start;
        gap: .6rem;
        margin-top: 1.5rem;
    }
    .flash-success {
        background: #f0faf4;
        border: 1px solid #a3d9b1;
        color: #276744;
    }
    .flash-error {
        background: #fff5f5;
        border: 1px solid #fca5a5;
        color: #991b1b;
    }

    /* ── Section Admin ───────────────────────────────────── */
    .admin-section {
        background: var(--bg, #f7f5f2);
        border-top: 2px solid var(--terra, #c0784a);
    }
    .admin-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .admin-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        background: rgba(192,120,74,.1);
        color: var(--terra, #c0784a);
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        padding: .3rem .8rem;
        border-radius: 20px;
        margin-bottom: .6rem;
    }
    .admin-table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,.05);
        font-size: .87rem;
    }
    .admin-table th {
        background: var(--surface, #faf9f7);
        padding: .75rem 1rem;
        text-align: left;
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--text-muted, #888);
        border-bottom: 1px solid var(--border, #e8e0d5);
    }
    .admin-table td {
        padding: .85rem 1rem;
        border-bottom: 1px solid var(--border, #e8e0d5);
        vertical-align: middle;
        color: var(--text, #2a2015);
    }
    .admin-table tr:last-child td {
        border-bottom: none;
    }
    .admin-table tr:hover td {
        background: var(--surface, #faf9f7);
    }
    .admin-swatch-cell {
        display: flex;
        align-items: center;
        gap: .7rem;
    }
    .admin-swatch-mini {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }
    .admin-actions {
        display: flex;
        gap: .5rem;
    }
    .btn-admin-edit {
        padding: .3rem .7rem;
        background: var(--surface, #faf9f7);
        border: 1px solid var(--border, #e8e0d5);
        border-radius: 6px;
        font-size: .78rem;
        font-family: 'Jost', sans-serif;
        color: var(--text, #2a2015);
        cursor: pointer;
        transition: all .15s;
    }
    .btn-admin-edit:hover {
        background: var(--terra, #c0784a);
        border-color: var(--terra, #c0784a);
        color: #fff;
    }
    .btn-admin-del {
        padding: .3rem .7rem;
        background: #fff5f5;
        border: 1px solid #fca5a5;
        border-radius: 6px;
        font-size: .78rem;
        font-family: 'Jost', sans-serif;
        color: #991b1b;
        cursor: pointer;
        transition: all .15s;
    }
    .btn-admin-del:hover {
        background: #c0392b;
        border-color: #c0392b;
        color: #fff;
    }

    /* Formulaire d'ajout/édition produit */
    .admin-form-card {
        background: #fff;
        border: 1px solid var(--border, #e8e0d5);
        border-radius: 12px;
        padding: 1.8rem 2rem;
        margin-top: 1.5rem;
    }
    .admin-form-title {
        font-family: 'Playfair Display', serif;
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text, #2a2015);
        margin-bottom: 1.2rem;
        padding-bottom: .8rem;
        border-bottom: 1px solid var(--border, #e8e0d5);
    }
    .admin-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    @media (max-width: 600px) {
        .admin-form-grid { grid-template-columns: 1fr; }
        .cart-item-row { grid-template-columns: 40px 1fr; }
        .cart-item-subtotal, .cart-qty-form { display: none; }
    }
    .admin-form-grid .full-col {
        grid-column: 1 / -1;
    }
    .admin-form-actions {
        margin-top: 1.2rem;
        display: flex;
        gap: .8rem;
        align-items: center;
    }
    .btn-admin-submit {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .65rem 1.5rem;
        background: var(--terra, #c0784a);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-family: 'Jost', sans-serif;
        font-size: .88rem;
        font-weight: 600;
        cursor: pointer;
        transition: background .2s;
    }
    .btn-admin-submit:hover {
        background: #a5622f;
    }
    .btn-admin-cancel {
        font-size: .85rem;
        color: var(--text-muted, #888);
        cursor: pointer;
        background: none;
        border: none;
        font-family: 'Jost', sans-serif;
        text-decoration: underline;
    }
    .btn-admin-cancel:hover {
        color: var(--text, #2a2015);
    }

    /* Modal d'édition */
    .edit-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.45);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(3px);
    }
    .edit-modal-overlay.open {
        display: flex;
    }
    .edit-modal {
        background: #fff;
        border-radius: 14px;
        padding: 2rem 2.2rem;
        width: min(500px, 95vw);
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
    }
    .edit-modal-close {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: none;
        border: none;
        cursor: pointer;
        color: var(--text-muted, #aaa);
        font-size: 1.3rem;
        line-height: 1;
        transition: color .15s;
    }
    .edit-modal-close:hover { color: var(--text, #2a2015); }

    /* Responsive table → cards sur mobile */
    @media (max-width: 700px) {
        .admin-table thead { display: none; }
        .admin-table td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: none;
            padding: .5rem 1rem;
        }
        .admin-table td::before {
            content: attr(data-label);
            font-weight: 600;
            font-size: .75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .admin-table tr {
            display: block;
            border: 1px solid var(--border, #e8e0d5);
            border-radius: 10px;
            margin-bottom: .8rem;
            overflow: hidden;
        }
    }
    </style>
</head>
<body>

<!-- ══════════════════════════════════════════════════════════
     NAVBAR
══════════════════════════════════════════════════════════ -->
<nav class="navbar">
    <a href="index.php" class="nav-logo">
        Tissus <span>Passion</span>
    </a>

    <ul class="nav-links" id="navLinks">
        <li><a href="#accueil">Accueil</a></li>
        <li><a href="#catalogue">Catalogue</a></li>
        <li><a href="#panier">Panier</a></li>
        <li><a href="#horaires">Horaires</a></li>
        <li><a href="#lieu">Lieu</a></li>
        <?php if ($isLoggedIn): ?>
            <li><a href="dashboard.php">Mon compte</a></li>
        <?php else: ?>
            <li><a href="auth.php">Connexion</a></li>
        <?php endif; ?>
    </ul>

    <div style="display:flex; align-items:center; gap:1rem;">
        <?php if ($isLoggedIn): ?>
        <!-- Icône panier avec badge -->
        <a href="#panier" class="nav-cart" title="Mon panier" style="text-decoration:none; color:var(--text);">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <path d="M16 10a4 4 0 0 1-8 0"/>
            </svg>
            <?php if ($cartCount > 0): ?>
            <span class="cart-badge"><?= $cartCount ?></span>
            <?php endif; ?>
        </a>
        <!-- Avatar utilisateur -->
        <div class="dash-avatar" title="<?= $userEmail ?>" style="cursor:default;"><?= $initiale ?></div>
        <!-- Déconnexion -->
        <a href="logout.php" class="dash-logout" style="font-size:.8rem;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Déco.
        </a>
        <?php endif; ?>

        <!-- Menu mobile -->
        <button class="nav-toggle" id="navToggle" aria-label="Menu">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="12" x2="21" y2="12"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>
    </div>
</nav>

<!-- ══════════════════════════════════════════════════════════
     HERO
══════════════════════════════════════════════════════════ -->
<section class="hero" id="accueil">
    <div class="hero-bg"></div>
    <div class="hero-pattern"></div>
    <div class="hero-container">
        <div class="hero-text">
            <p class="hero-eyebrow anim-fade-up">Artisans du tissu depuis 1998</p>
            <h1 class="anim-fade-up anim-delay-1">
                L'art de donner <em>vie</em><br>
                à vos intérieurs
            </h1>
            <p class="hero-desc anim-fade-up anim-delay-2">
                Velours, lin, soie, jacquard… Explorez notre collection
                de tissus d'ameublement sélectionnés avec soin pour
                sublimer chaque pièce de votre maison.
            </p>
            <div class="hero-actions anim-fade-up anim-delay-3">
                <a href="#catalogue" class="btn-primary">
                    <span>Découvrir nos tissus</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="12 5 19 12 12 19"/>
                    </svg>
                </a>
                <a href="#lieu" class="btn-secondary">Nous trouver</a>
            </div>
        </div>
        <div class="hero-visual anim-fade-up anim-delay-4">
            <div class="hero-card-stack">
                <div class="fabric-card"></div>
                <div class="fabric-card"></div>
                <div class="fabric-card"></div>
                <div class="hero-stats">
                    <p>Références disponibles</p>
                    <strong>+100</strong>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════
     CATALOGUE PRODUITS
══════════════════════════════════════════════════════════ -->
<section class="section catalogue-section" id="catalogue">
    <div class="container">
        <div class="section-label">Notre sélection</div>
        <h2 class="section-title">Nos <em>tissus</em> au mètre</h2>
        <p class="section-subtitle">
            Qualité artisanale, prix en Francs Pacifique (XPF). Tous nos tissus sont vendus au mètre,
            minimum 0,5 m par commande.
        </p>

        <?php if (!empty($flashSuccess)): ?>
        <div class="flash-alert flash-success">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <?= $flashSuccess ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($flashError)): ?>
        <div class="flash-alert flash-error">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?= $flashError ?>
        </div>
        <?php endif; ?>

        <!-- Filtres par catégorie -->
        <?php
        $categories = array_unique(array_column($PRODUCTS, 'categorie'));
        sort($categories);
        ?>
        <div class="catalogue-filters" id="catalogueFilters">
            <button class="filter-btn active" onclick="filterCat('*', this)">Tout</button>
            <?php foreach ($categories as $cat): ?>
            <button class="filter-btn" onclick="filterCat(<?= htmlspecialchars(json_encode($cat)) ?>, this)">
                <?= htmlspecialchars($cat) ?>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Grille produits -->
        <div class="catalogue-grid" id="catalogueGrid">
            <?php foreach ($PRODUCTS as $p):
                $pid = (int) $p['id'];
            ?>
            <div class="product-card" data-cat="<?= htmlspecialchars($p['categorie']) ?>">
                <!-- Swatch coloré -->
                <div class="product-swatch" style="background:<?= htmlspecialchars($p['couleur']) ?>;">
                    <?= htmlspecialchars($p['emoji']) ?>
                    <span class="product-badge"><?= htmlspecialchars($p['categorie']) ?></span>
                </div>

                <!-- Infos -->
                <div class="product-body">
                    <div class="product-name"><?= htmlspecialchars($p['nom']) ?></div>
                    <div class="product-desc"><?= htmlspecialchars($p['description']) ?></div>
                    <div class="product-price">
                        <?= xpf((float)$p['prix']) ?><span>/ mètre</span>
                    </div>

                    <!-- Ajout au panier -->
                    <?php if ($isLoggedIn): ?>
                    <form action="cart.php" method="POST" class="product-add-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?= $pid ?>">
                        <input
                            type="number"
                            name="quantity"
                            class="product-qty-input"
                            value="1"
                            min="0.5"
                            max="100"
                            step="0.5"
                            aria-label="Quantité en mètres">
                        <span class="product-qty-label">m</span>
                        <button type="submit" class="btn-add-cart" title="Ajouter au panier">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <line x1="12" y1="5" x2="12" y2="19"/>
                                <line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                            Ajouter
                        </button>
                    </form>
                    <?php else: ?>
                    <a href="auth.php" class="btn-add-cart" style="text-decoration:none; margin-top:.4rem;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        Connexion requise
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════
     PANIER DYNAMIQUE
══════════════════════════════════════════════════════════ -->
<section class="section cart-section-dynamic" id="panier">
    <div class="container">
        <div class="section-label">Votre sélection</div>
        <h2 class="section-title">Mon <em>panier</em></h2>
        <p class="section-subtitle">Retrouvez les articles que vous avez ajoutés et finalisez votre commande.</p>

        <?php if (!$isLoggedIn): ?>
        <!-- Non connecté -->
        <div style="text-align:center; padding:3.5rem 2rem; background:#fff; border:1px solid var(--border,#e8e0d5); border-radius:12px; margin-top:2rem;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--terra,#c0784a)" stroke-width="1.4" style="opacity:.6; margin-bottom:1.2rem; display:block; margin-left:auto; margin-right:auto;">
                <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <path d="M16 10a4 4 0 0 1-8 0"/>
            </svg>
            <h3 style="font-family:'Playfair Display',serif; font-size:1.4rem; margin-bottom:.6rem;">Connectez-vous pour accéder à votre panier</h3>
            <p style="color:var(--text-muted,#888); font-size:.95rem; margin-bottom:1.8rem; max-width:420px; margin-left:auto; margin-right:auto;">
                Créez un compte ou connectez-vous pour ajouter des articles à votre panier.
            </p>
            <a href="auth.php" class="btn-primary" style="display:inline-flex; gap:.5rem; align-items:center;">
                <span>Se connecter / S'inscrire</span>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </a>
        </div>

        <?php else: ?>

        <!-- Connecté : affichage panier -->
        <div class="cart-dynamic-grid">

            <!-- Colonne gauche : liste des articles -->
            <div>
                <?php if (empty($cartItems)): ?>
                <div class="cart-empty">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" style="display:block; margin:0 auto 1rem;">
                        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <path d="M16 10a4 4 0 0 1-8 0"/>
                    </svg>
                    <p>Votre panier est vide. <a href="#catalogue">Parcourir le catalogue →</a></p>
                </div>

                <?php else: ?>
                <div class="cart-items-list">
                    <?php foreach ($cartItems as $item):
                        $subtotal = $item['prix'] * $item['qty'];
                    ?>
                    <div class="cart-item-row">
                        <!-- Swatch -->
                        <div class="cart-swatch-sm" style="background:<?= htmlspecialchars($item['couleur']) ?>;">
                            <?= htmlspecialchars($item['emoji'] ?? '🧵') ?>
                        </div>

                        <!-- Infos article -->
                        <div class="cart-item-details">
                            <div class="cart-item-title"><?= htmlspecialchars($item['nom']) ?></div>
                            <div class="cart-item-meta">
                                <?= htmlspecialchars($item['categorie']) ?> · <?= xpf((float)$item['prix']) ?> / m
                            </div>
                        </div>

                        <!-- Quantité modifiable -->
                        <form action="cart.php" method="POST" class="cart-qty-form">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="product_id" value="<?= (int)$item['id'] ?>">
                            <input
                                type="number"
                                name="quantity"
                                class="cart-qty-input"
                                value="<?= htmlspecialchars($item['qty']) ?>"
                                min="0.5"
                                max="100"
                                step="0.5"
                                aria-label="Quantité"
                                onchange="this.form.submit()">
                            <span style="font-size:.78rem; color:var(--text-muted,#aaa);">m</span>
                        </form>

                        <!-- Sous-total + suppression -->
                        <div style="display:flex; flex-direction:column; align-items:flex-end; gap:.4rem;">
                            <div class="cart-item-subtotal"><?= xpf($subtotal) ?></div>
                            <form action="cart.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?= (int)$item['id'] ?>">
                                <button type="submit" class="btn-cart-action btn-cart-remove" title="Supprimer">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                        <path d="M10 11v6"/><path d="M14 11v6"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Colonne droite : récapitulatif -->
            <div>
                <div class="cart-summary-box">
                    <div class="cart-summary-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle; margin-right:.4rem;">
                            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                            <line x1="3" y1="6" x2="21" y2="6"/>
                            <path d="M16 10a4 4 0 0 1-8 0"/>
                        </svg>
                        Récapitulatif
                    </div>

                    <?php if (!empty($cartItems)): ?>
                    <div class="cart-summary-lines">
                        <?php foreach ($cartItems as $item): ?>
                        <div class="cart-summary-line">
                            <span><?= htmlspecialchars($item['nom']) ?> × <?= formatQty((float)$item['qty']) ?></span>
                            <strong><?= xpf($item['prix'] * $item['qty']) ?></strong>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="cart-summary-total">
                        <span class="cart-summary-total-label">Total</span>
                        <span class="cart-summary-total-amount"><?= xpf($cartTotal) ?></span>
                    </div>

                    <!-- Bouton valider -->
                    <form action="cart.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="checkout">
                        <button type="submit" class="btn-checkout" onclick="return confirm('Confirmer votre commande de <?= xpf($cartTotal) ?> ?')">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            Valider ma commande
                        </button>
                    </form>

                    <!-- Vider le panier -->
                    <form action="cart.php" method="POST" style="text-align:center;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="btn-clear-cart" onclick="return confirm('Vider le panier ?')">
                            Vider le panier
                        </button>
                    </form>

                    <?php else: ?>
                    <p style="font-size:.88rem; color:var(--text-muted,#aaa); text-align:center; padding:1rem 0;">
                        Aucun article dans le panier.
                    </p>
                    <?php endif; ?>

                    <!-- Avantages -->
                    <div style="border-top:1px solid var(--border,#e8e0d5); padding-top:1rem; display:flex; flex-direction:column; gap:.8rem;">
                        <div class="cart-feature" style="font-size:.8rem;">
                            <div class="cart-feature-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg></div>
                            <div class="cart-feature-text"><h4>Livraison offerte dès 5 000 XPF</h4></div>
                        </div>
                        <div class="cart-feature" style="font-size:.8rem;">
                            <div class="cart-feature-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
                            <div class="cart-feature-text"><h4>Paiement sécurisé (CB, PayPal)</h4></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php endif; ?>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════
     SECTION ADMIN — Gestion du catalogue (connectés)
══════════════════════════════════════════════════════════ -->
<?php if ($isLoggedIn): ?>
<section class="section admin-section" id="admin-products">
    <div class="container">
        <div class="admin-eyebrow">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>
            Espace administration
        </div>
        <div class="admin-header-row">
            <div>
                <h2 class="section-title" style="margin:0;">Gestion du <em>catalogue</em></h2>
                <p style="font-size:.88rem; color:var(--text-muted,#888); margin-top:.4rem;">Ajoutez, modifiez ou supprimez des tissus du catalogue.</p>
            </div>
            <button class="btn-admin-submit" onclick="document.getElementById('formAddProduct').classList.toggle('hidden')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Ajouter un tissu
            </button>
        </div>

        <!-- Formulaire d'ajout (masqué par défaut) -->
        <div id="formAddProduct" class="admin-form-card hidden">
            <div class="admin-form-title">Nouveau tissu</div>
            <form action="products.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="product_add">
                <div class="admin-form-grid">
                    <div class="form-group">
                        <label class="form-label">Nom du tissu *</label>
                        <input type="text" name="nom" class="form-input" placeholder="Ex : Velours Terracotta" required maxlength="150">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Catégorie *</label>
                        <input type="text" name="categorie" class="form-input" placeholder="Ex : Velours, Lin, Coton…" required maxlength="60">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Prix au mètre (XPF) *</label>
                        <input type="number" name="prix" class="form-input" placeholder="Ex : 2500" min="100" max="50000" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Emoji représentatif</label>
                        <input type="text" name="emoji" class="form-input" placeholder="Ex : 🌺" maxlength="10" value="🧵">
                    </div>
                    <div class="form-group full-col">
                        <label class="form-label">Description *</label>
                        <input type="text" name="description" class="form-input" placeholder="Description courte et attrayante…" required maxlength="400">
                    </div>
                    <div class="form-group full-col">
                        <label class="form-label">Couleur CSS (dégradé)</label>
                        <input type="text" name="couleur" class="form-input" placeholder="Ex : linear-gradient(135deg, #D4956A, #8B5030)" maxlength="200" value="linear-gradient(135deg, #C9B89A, #8A7048)">
                    </div>
                </div>
                <div class="admin-form-actions">
                    <button type="submit" class="btn-admin-submit">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Enregistrer le tissu
                    </button>
                    <button type="button" class="btn-admin-cancel" onclick="document.getElementById('formAddProduct').classList.add('hidden')">Annuler</button>
                </div>
            </form>
        </div>

        <!-- Tableau produits -->
        <div style="margin-top:1.5rem; overflow-x:auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tissu</th>
                        <th>Catégorie</th>
                        <th>Prix / m</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($PRODUCTS as $p): ?>
                <tr>
                    <td data-label="ID"><?= (int)$p['id'] ?></td>
                    <td data-label="Tissu">
                        <div class="admin-swatch-cell">
                            <div class="admin-swatch-mini" style="background:<?= htmlspecialchars($p['couleur']) ?>;">
                                <?= htmlspecialchars($p['emoji'] ?? '🧵') ?>
                            </div>
                            <div>
                                <div style="font-weight:600;"><?= htmlspecialchars($p['nom']) ?></div>
                                <div style="font-size:.76rem; color:var(--text-muted,#aaa); max-width:220px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?= htmlspecialchars($p['description']) ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td data-label="Catégorie"><?= htmlspecialchars($p['categorie']) ?></td>
                    <td data-label="Prix"><?= xpf((float)$p['prix']) ?></td>
                    <td data-label="Actions">
                        <div class="admin-actions">
                            <!-- Modifier -->
                            <button
                                type="button"
                                class="btn-admin-edit"
                                onclick="openEditModal(<?= htmlspecialchars(json_encode($p)) ?>)">
                                ✏️ Modifier
                            </button>
                            <!-- Supprimer -->
                            <form action="products.php" method="POST" style="display:inline;" onsubmit="return confirm('Supprimer « <?= htmlspecialchars(addslashes($p['nom'])) ?> » ?')">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="product_delete">
                                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                                <button type="submit" class="btn-admin-del">🗑️ Suppr.</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Réinitialiser le catalogue -->
        <div style="margin-top:1.2rem; text-align:right;">
            <form action="products.php" method="POST" style="display:inline;" onsubmit="return confirm('Réinitialiser le catalogue avec les produits par défaut ?')">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="product_reset">
                <button type="submit" style="font-size:.78rem; color:var(--text-muted,#aaa); background:none; border:none; cursor:pointer; text-decoration:underline; font-family:'Jost',sans-serif;">
                    Réinitialiser le catalogue par défaut
                </button>
            </form>
        </div>

    </div>
</section>

<!-- Modal d'édition produit -->
<div class="edit-modal-overlay" id="editModalOverlay" onclick="if(event.target===this)closeEditModal()">
    <div class="edit-modal">
        <button class="edit-modal-close" onclick="closeEditModal()">✕</button>
        <div class="admin-form-title">Modifier le tissu</div>
        <form action="products.php" method="POST" id="formEditProduct">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="product_edit">
            <input type="hidden" name="product_id" id="edit_product_id">
            <div class="admin-form-grid">
                <div class="form-group">
                    <label class="form-label">Nom du tissu *</label>
                    <input type="text" name="nom" id="edit_nom" class="form-input" required maxlength="150">
                </div>
                <div class="form-group">
                    <label class="form-label">Catégorie *</label>
                    <input type="text" name="categorie" id="edit_categorie" class="form-input" required maxlength="60">
                </div>
                <div class="form-group">
                    <label class="form-label">Prix au mètre (XPF) *</label>
                    <input type="number" name="prix" id="edit_prix" class="form-input" min="100" max="50000" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Emoji</label>
                    <input type="text" name="emoji" id="edit_emoji" class="form-input" maxlength="10">
                </div>
                <div class="form-group full-col">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" id="edit_description" class="form-input" maxlength="400">
                </div>
                <div class="form-group full-col">
                    <label class="form-label">Couleur CSS</label>
                    <input type="text" name="couleur" id="edit_couleur" class="form-input" maxlength="200">
                </div>
            </div>
            <div class="admin-form-actions">
                <button type="submit" class="btn-admin-submit">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    Enregistrer
                </button>
                <button type="button" class="btn-admin-cancel" onclick="closeEditModal()">Annuler</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════
     HORAIRES
══════════════════════════════════════════════════════════ -->
<section class="section section-alt" id="horaires">
    <div class="container">
        <div class="section-label">Organisation</div>
        <h2 class="section-title">Nos <em>horaires</em> d'ouverture</h2>
        <p class="section-subtitle">La boutique vous accueille toute la semaine pour vous guider dans vos choix de tissus.</p>

        <div class="horaires-grid">
            <div class="horaires-table">
                <?php
                $jourSemaine = (int) date('N');
                $horaires = [
                    1 => ['Lundi',    '09h00 – 18h00', true],
                    2 => ['Mardi',    '09h00 – 18h00', true],
                    3 => ['Mercredi', '09h00 – 18h00', true],
                    4 => ['Jeudi',    '09h00 – 18h00', true],
                    5 => ['Vendredi', '09h00 – 18h00', true],
                    6 => ['Samedi',   '09h00 – 17h00', true],
                    7 => ['Dimanche', 'Fermé',          false],
                ];
                foreach ($horaires as $num => [$jour, $heure, $ouvert]) :
                    $isToday = ($num === $jourSemaine);
                ?>
                <div class="horaire-row <?= $isToday ? 'today' : '' ?>">
                    <span class="horaire-day"><?= $jour ?><?= $isToday ? ' <small style="font-size:.7rem;color:var(--terra);">— Aujourd\'hui</small>' : '' ?></span>
                    <span class="horaire-time"><?= $heure ?></span>
                    <span class="horaire-badge <?= $ouvert ? 'badge-open' : 'badge-closed' ?>"><?= $ouvert ? 'Ouvert' : 'Fermé' ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="horaires-info">
                <div class="info-card">
                    <div class="info-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.18a2 2 0 0 1 1.99-2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.96a16 16 0 0 0 6 6l.92-.92a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                    </div>
                    <div>
                        <h4>Réservation & Conseils</h4>
                        <p>Prenez rendez-vous pour un accompagnement personnalisé avec nos experts tissu.</p>
                    </div>
                </div>
                <div class="info-card">
                    <div class="info-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <div>
                        <h4>Fermetures exceptionnelles</h4>
                        <p>Consultez notre page ou appelez-nous avant de vous déplacer lors des jours fériés.</p>
                    </div>
                </div>
                <div class="info-card">
                    <div class="info-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <div>
                        <h4>Accueil professionnel</h4>
                        <p>Décorateurs, architectes, hôteliers : espace dédié sur rendez-vous le matin.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════
     LIEU / CARTE
══════════════════════════════════════════════════════════ -->
<section class="section" id="lieu">
    <div class="container">
        <div class="section-label">Nous rendre visite</div>
        <h2 class="section-title">Où nous <em>trouver</em></h2>
        <p class="section-subtitle">Notre boutique est installée au cœur de Nouméa, facilement accessible.</p>

        <div class="lieu-grid">
            <div class="lieu-info">
                <div class="lieu-address">
                    <h3>Tissus Passion</h3>
                    <div class="lieu-detail">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <div><strong>Adresse</strong>71 Rue De Charleroi,<br>Nouméa 98800, Nouvelle-Calédonie</div>
                    </div>
                    <div class="lieu-detail">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.18 2 2 0 0 1 3.6 1.18h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 8.96a16 16 0 0 0 6 6l.92-.92a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        <div><strong>Téléphone</strong>+687 76.85.84</div>
                    </div>
                    <div class="lieu-detail">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <div><strong>Email</strong>tissuspassions@hotmail.com</div>
                    </div>
                    <div class="lieu-detail">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        <div><strong>Paiement</strong>CB, Visa, Mastercard, PayPal</div>
                    </div>
                </div>
                <a href="https://www.google.com/maps/place/Tissus+Passions/@-22.2764408,166.4593076,17.89z" target="_blank" rel="noopener" class="btn-primary" style="justify-content:center;">
                    <span>Ouvrir dans Google Maps</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                </a>
            </div>
            <div class="map-container">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3279.5!2d166.4593!3d-22.2765!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x6c28098a6d831e5d:0xe81dbf1597dd63e0!2sTissus+Passions!5e0!3m2!1sfr!2snc!4v1620000000000!5m2!1sfr!2snc"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    title="Localisation Tissus Passion Nouméa">
                </iframe>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════
     FOOTER
══════════════════════════════════════════════════════════ -->
<footer class="footer">
    <div class="footer-grid">
        <div>
            <div class="footer-brand">Tissus <span>Passion</span></div>
            <p class="footer-desc">Votre spécialiste en tissus d'ameublement depuis 1998. Qualité artisanale, conseil personnalisé et large choix de matières pour sublimer vos intérieurs.</p>
        </div>
        <div class="footer-col">
            <h4>Navigation</h4>
            <ul>
                <li><a href="#accueil">Accueil</a></li>
                <li><a href="#catalogue">Catalogue</a></li>
                <li><a href="#panier">Panier</a></li>
                <li><a href="#horaires">Horaires</a></li>
                <li><a href="#lieu">Lieu</a></li>
                <li><a href="<?= $isLoggedIn ? 'dashboard.php' : 'auth.php' ?>">Mon compte</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Matières</h4>
            <ul>
                <li><a href="#catalogue">Velours</a></li>
                <li><a href="#catalogue">Lin naturel</a></li>
                <li><a href="#catalogue">Jacquard</a></li>
                <li><a href="#catalogue">Soie</a></li>
                <li><a href="#catalogue">Coton</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Contact</h4>
            <div class="footer-contact">
                <span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    71 Rue De Charleroi, Nouméa
                </span>
                <span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.18 2 2 0 0 1 3.6 1.18h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 8.96a16 16 0 0 0 6 6l.92-.92a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    +687 76.85.84
                </span>
                <span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    tissuspassions@hotmail.com
                </span>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <span>© Tissus Passion <?= date('Y') ?> — Tous droits réservés</span>
        <div style="display:flex; gap:1.5rem;">
            <a href="#">Mentions légales</a>
            <a href="#">Politique de confidentialité</a>
            <a href="#">CGV</a>
        </div>
    </div>
</footer>

<!-- ══════════════════════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════════════════════ -->
<script src="js/main.js"></script>
<script>
/* ── Filtre catalogue par catégorie ──────────────────────── */
function filterCat(cat, btn) {
    /* Met à jour le bouton actif */
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    /* Affiche / masque les cartes */
    document.querySelectorAll('#catalogueGrid .product-card').forEach(card => {
        if (cat === '*' || card.dataset.cat === cat) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

/* ── Modal édition produit ───────────────────────────────── */
function openEditModal(product) {
    document.getElementById('edit_product_id').value   = product.id;
    document.getElementById('edit_nom').value          = product.nom;
    document.getElementById('edit_categorie').value    = product.categorie;
    document.getElementById('edit_prix').value         = product.prix;
    document.getElementById('edit_emoji').value        = product.emoji || '';
    document.getElementById('edit_description').value  = product.description;
    document.getElementById('edit_couleur').value      = product.couleur;
    document.getElementById('editModalOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    document.getElementById('editModalOverlay').classList.remove('open');
    document.body.style.overflow = '';
}

/* Fermer la modal avec Échap */
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeEditModal();
});

/* ── Scroll vers le panier depuis l'icône navbar ─────────── */
function scrollToCart() {
    document.getElementById('panier')?.scrollIntoView({ behavior: 'smooth' });
}

/* ── Utilitaire : toggle classe hidden ───────────────────── */
document.querySelectorAll('[data-toggle]').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById(btn.dataset.toggle)?.classList.toggle('hidden');
    });
});
</script>

<style>
/* Utilitaire : .hidden masque un élément */
.hidden { display: none !important; }
</style>

</body>
</html>
