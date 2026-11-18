<?php
/* ============================================================
   TISSUS PASSION — Gestion du catalogue (administrateur)
   Ajout, modification, suppression de tissus dans la table
   `produits`. Réservé au rôle « admin » (vérifié en base).
   ============================================================ */
include "config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rediriger('accueil.php#catalogue');
}

verifyCsrf();
exigerAdmin($pdo);

/** Nettoie un texte saisi : sans balises, sans espaces autour, longueur limitée. */
function texte(string $cle, int $max = 200): string {
    return mb_substr(trim(strip_tags($_POST[$cle] ?? '')), 0, $max);
}

/**
 * Lit et vérifie le formulaire tissu.
 * Renvoie [données, erreurs].
 */
function lireFormulaireTissu(): array {
    $d = [
        'nom'         => texte('nom', 150),
        'description' => texte('description', 500),
        'prix'        => (int) ($_POST['prix'] ?? 0),
        'categorie'   => texte('categorie', 60),
        'couleur'     => texte('couleur', 200) ?: 'linear-gradient(135deg, #C9B89A, #8A7048)',
        'emoji'       => texte('emoji', 10) ?: '🧵',
        'image'       => texte('image', 150) ?: null,
    ];

    $erreurs = [];
    if ($d['nom'] === '')         $erreurs[] = 'Le nom du tissu est obligatoire.';
    if ($d['description'] === '') $erreurs[] = 'La description est obligatoire.';
    if ($d['categorie'] === '')   $erreurs[] = 'La catégorie est obligatoire.';
    if ($d['prix'] < 100)         $erreurs[] = 'Le prix doit être d\'au moins 100 XPF.';
    if ($d['prix'] > 50000)       $erreurs[] = 'Le prix semble trop élevé (50 000 XPF maximum).';
    if (!couleurValide($d['couleur'])) {
        $erreurs[] = 'Couleur invalide : utilise un code (#C9826B) ou un dégradé linear-gradient(…).';
    }
    /* La photo doit être un fichier présent dans img/ : rien d'autre n'est accepté */
    if ($d['image'] !== null && !in_array($d['image'], photosDisponibles(), true)) {
        $erreurs[] = 'Photo introuvable dans le dossier img/.';
    }
    return [$d, $erreurs];
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {

        /* ── Ajouter un tissu ──────────────────────────────── */
        case 'product_add':
            [$d, $erreurs] = lireFormulaireTissu();
            if ($erreurs) {
                flash('error', implode(' ', $erreurs));
                break;
            }
            $stmt = $pdo->prepare(
                'INSERT INTO produits (nom, description, prix, categorie, couleur, emoji, image)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$d['nom'], $d['description'], $d['prix'], $d['categorie'], $d['couleur'], $d['emoji'], $d['image']]);
            flash('success', 'Tissu « ' . $d['nom'] . ' » ajouté au catalogue.');
            break;

        /* ── Modifier un tissu ─────────────────────────────── */
        case 'product_edit':
            $id = (int) ($_POST['product_id'] ?? 0);
            [$d, $erreurs] = lireFormulaireTissu();
            if ($erreurs) {
                flash('error', implode(' ', $erreurs));
                break;
            }
            $stmt = $pdo->prepare(
                'UPDATE produits SET nom = ?, description = ?, prix = ?, categorie = ?, couleur = ?, emoji = ?, image = ?
                 WHERE id = ?'
            );
            $stmt->execute([$d['nom'], $d['description'], $d['prix'], $d['categorie'], $d['couleur'], $d['emoji'], $d['image'], $id]);
            /* rowCount() vaut 0 aussi si rien n'a changé : on vérifie l'existence à part */
            if (trouverProduit($pdo, $id)) {
                flash('success', 'Tissu « ' . $d['nom'] . ' » modifié.');
            } else {
                flash('error', 'Tissu introuvable.');
            }
            break;

        /* ── Supprimer un tissu ────────────────────────────── */
        case 'product_delete':
            $id = (int) ($_POST['product_id'] ?? 0);
            $stmt = $pdo->prepare('DELETE FROM produits WHERE id = ?');
            $stmt->execute([$id]);
            if ($stmt->rowCount() > 0) {
                /* Les commandes passées gardent le nom et le prix du tissu (lignes_commande) */
                flash('success', 'Tissu supprimé du catalogue.');
            } else {
                flash('error', 'Tissu introuvable.');
            }
            break;

        /* ── Réinitialiser le catalogue de démonstration ───── */
        case 'product_reset':
            include __DIR__ . '/products_data.php';
            $pdo->beginTransaction();
            $pdo->exec('DELETE FROM produits');
            $stmt = $pdo->prepare(
                'INSERT INTO produits (nom, description, prix, categorie, couleur, emoji, image)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($PRODUCTS as $p) {
                $stmt->execute([$p['nom'], $p['description'], $p['prix'], $p['categorie'], $p['couleur'], $p['emoji'], $p['image']]);
            }
            $pdo->commit();
            $_SESSION['cart'] = [];
            flash('success', 'Catalogue réinitialisé avec les 9 tissus par défaut.');
            break;

        default:
            rediriger('accueil.php#catalogue');
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Erreur catalogue : ' . $e->getMessage());
    flash('error', 'Une erreur est survenue. Le catalogue n\'a pas été modifié.');
}

rediriger('accueil.php#admin-products');
