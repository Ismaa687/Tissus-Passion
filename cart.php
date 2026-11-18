<?php
/* ============================================================
   TISSUS PASSION — Panier et commande (actions POST)
   Le panier est en session (id du tissu → mètres).
   La commande est enregistrée en base (tables commandes et
   lignes_commande), dans une transaction.
   ============================================================ */
include "config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rediriger('accueil.php#catalogue');
}

verifyCsrf();

if (!estConnecte()) {
    rediriger('auth.php');
}

$_SESSION['cart'] = $_SESSION['cart'] ?? [];
$action    = $_POST['action'] ?? '';
$productId = (int) ($_POST['product_id'] ?? 0);

/** Quantité en mètres, au demi-mètre près, entre 0,5 et 100. */
function lireQuantite(string $defaut): float {
    $qty = (float) str_replace(',', '.', $_POST['quantity'] ?? $defaut);
    return round($qty * 2) / 2;
}

switch ($action) {

    /* ── Ajouter un tissu ──────────────────────────────────── */
    case 'add':
        $produit = trouverProduit($pdo, $productId);
        if (!$produit) {
            flash('error', 'Tissu introuvable.');
            break;
        }
        $qty = max(0.5, min(100, lireQuantite('1')));
        $total = ($_SESSION['cart'][$productId] ?? 0) + $qty;
        $_SESSION['cart'][$productId] = min(100, $total);
        flash('success', $produit['nom'] . ' ajouté au panier (' . formatQty($qty) . ').');
        rediriger('accueil.php#catalogue');

    /* ── Modifier une quantité ─────────────────────────────── */
    case 'update':
        if (isset($_SESSION['cart'][$productId])) {
            $qty = lireQuantite('0');
            if ($qty < 0.5) {
                unset($_SESSION['cart'][$productId]);
            } else {
                $_SESSION['cart'][$productId] = min(100, $qty);
            }
        }
        break;

    /* ── Retirer un tissu ──────────────────────────────────── */
    case 'remove':
        unset($_SESSION['cart'][$productId]);
        break;

    /* ── Vider le panier ───────────────────────────────────── */
    case 'clear':
        $_SESSION['cart'] = [];
        break;

    /* ── Valider la commande ───────────────────────────────── */
    case 'checkout':
        /* Prix relus en base : le client ne peut pas modifier un prix */
        $lignes = lignesPanier($pdo);
        if (empty($lignes)) {
            flash('error', 'Votre panier est vide.');
            break;
        }
        $total = array_sum(array_column($lignes, 'sous_total'));

        try {
            /* Tout ou rien : la commande et toutes ses lignes, ou rien du tout */
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('INSERT INTO commandes (user_id, total) VALUES (?, ?)');
            $stmt->execute([(int) $_SESSION['user_id'], $total]);
            $commandeId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO lignes_commande (commande_id, produit_id, nom_produit, prix_unitaire, quantite, sous_total)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            foreach ($lignes as $l) {
                $stmt->execute([$commandeId, $l['id'], $l['nom'], $l['prix'], $l['qty'], $l['sous_total']]);
            }

            $pdo->commit();
            $_SESSION['cart'] = [];
            flash('success', 'Commande n°' . $commandeId . ' enregistrée (' . xpf($total) . '). '
                . 'Nous vous contactons pour la confirmer ; suivez-la dans « Mon compte ».');

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Erreur commande : ' . $e->getMessage());
            flash('error', 'La commande n\'a pas pu être enregistrée. Votre panier est conservé.');
        }
        break;
}

rediriger('accueil.php#panier');
