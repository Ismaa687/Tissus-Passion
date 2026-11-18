<?php
/* ============================================================
   TISSUS PASSION — Fonctions communes à toutes les pages
   (chargé automatiquement par config.php)
   ============================================================ */

/* ── Affichage ───────────────────────────────────────────── */

/** Échappe un texte pour l'afficher dans du HTML (anti-XSS). */
function e(?string $texte): string {
    return htmlspecialchars($texte ?? '', ENT_QUOTES, 'UTF-8');
}

/** 4500 → « 4 500 XPF » */
function xpf(float $montant): string {
    return number_format($montant, 0, ',', ' ') . ' XPF';
}

/** 1.50 → « 1,5 m » ; 2.00 → « 2 m » */
function formatQty(float $qty): string {
    $str = rtrim(number_format($qty, 2, ',', ''), '0');
    return rtrim($str, ',') . ' m';
}

/** Libellé lisible du statut d'une commande. */
function libelleStatut(string $statut): string {
    return [
        'en_attente' => 'En attente',
        'confirmee'  => 'Confirmée',
        'prete'      => 'Prête à retirer',
        'annulee'    => 'Annulée',
    ][$statut] ?? $statut;
}

const STATUTS_COMMANDE = ['en_attente', 'confirmee', 'prete', 'annulee'];

/* ── Messages flash (affichés une seule fois, après une redirection) ── */

function flash(string $type, string $message): void {
    $_SESSION['flash_' . $type] = $message;   // type : success ou error
}

function lireFlash(string $type): string {
    $message = $_SESSION['flash_' . $type] ?? '';
    unset($_SESSION['flash_' . $type]);
    return $message;
}

/** Redirige puis arrête le script. */
function rediriger(string $url): void {
    header('Location: ' . $url);
    exit;
}

/* ── Comptes et droits ───────────────────────────────────── */

function estConnecte(): bool {
    return !empty($_SESSION['user_id']);
}

/** Rôle affiché (mémorisé à la connexion). */
function estAdmin(): bool {
    return estConnecte() && ($_SESSION['user_role'] ?? '') === 'admin';
}

/**
 * Bloque l'accès si l'utilisateur n'est pas administrateur.
 * Le rôle est relu en base à chaque fois : un compte rétrogradé
 * perd ses droits tout de suite, même s'il est encore connecté.
 */
function exigerAdmin(PDO $pdo): void {
    if (!estConnecte()) {
        rediriger('auth.php');
    }
    $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $role = $stmt->fetchColumn();
    $_SESSION['user_role'] = $role ?: 'client';
    if ($role !== 'admin') {
        http_response_code(403);
        die('Accès réservé à l\'administrateur de la boutique.');
    }
}

/* ── Catalogue ───────────────────────────────────────────── */

/** Tous les tissus, triés par catégorie puis par nom. */
function chargerProduits(PDO $pdo): array {
    return $pdo->query('SELECT * FROM produits ORDER BY categorie, nom')->fetchAll();
}

function trouverProduit(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare('SELECT * FROM produits WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/** Photos disponibles dans img/ (pour le choix de l'admin). */
function photosDisponibles(): array {
    $fichiers = glob(__DIR__ . '/img/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [];
    $photos = array_map(fn($f) => 'img/' . basename($f), $fichiers);
    sort($photos);
    return $photos;
}

/** Chemin de la photo si le fichier existe vraiment, sinon null. */
function photoProduit(array $p): ?string {
    $image = $p['image'] ?? null;
    if (!$image || !in_array($image, photosDisponibles(), true)) {
        return null;
    }
    return $image;
}

/**
 * Fond de la vignette : la photo si elle existe, sinon le dégradé de couleur.
 * Résultat à placer dans style="background: …".
 */
function fondProduit(array $p): string {
    $couleur = couleurValide($p['couleur'] ?? '') ? $p['couleur'] : 'linear-gradient(135deg, #C9B89A, #8A7048)';
    $photo = photoProduit($p);
    return $photo ? "url('" . $photo . "') center / cover no-repeat, " . $couleur : $couleur;
}

/**
 * La couleur saisie par l'admin est insérée dans un style CSS :
 * on n'accepte que couleurs et dégradés simples (pas de url(), pas de « ; »).
 */
function couleurValide(string $couleur): bool {
    return (bool) preg_match('/^(linear-gradient\([#a-zA-Z0-9(),.%\s]+\)|#[0-9a-fA-F]{3,8}|[a-z]+)$/', trim($couleur))
        && stripos($couleur, 'url') === false;
}

/* ── Panier ──────────────────────────────────────────────── */

/**
 * Le panier en session ne garde que « id du tissu → quantité ».
 * Le nom et le prix sont toujours relus en base : un prix modifié
 * par l'admin s'applique tout de suite, et le client ne peut pas
 * le falsifier.
 */
function lignesPanier(PDO $pdo): array {
    $panier = $_SESSION['cart'] ?? [];
    if (empty($panier)) return [];

    $ids = array_map('intval', array_keys($panier));
    $marques = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE id IN ($marques)");
    $stmt->execute($ids);

    $lignes = [];
    foreach ($stmt->fetchAll() as $p) {
        $qty = (float) $panier[$p['id']];
        $lignes[] = $p + ['qty' => $qty, 'sous_total' => (int) round($p['prix'] * $qty)];
    }
    /* Un tissu supprimé du catalogue disparaît aussi du panier */
    $_SESSION['cart'] = array_intersect_key($panier, array_flip(array_column($lignes, 'id')));
    return $lignes;
}
