<?php
/* ============================================================
   TISSUS PASSION — Suivi des commandes (administrateur)
   Change le statut d'une commande : en attente → confirmée →
   prête à retirer, ou annulée.
   ============================================================ */
include "config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rediriger('accueil.php#admin-commandes');
}

verifyCsrf();
exigerAdmin($pdo);

$commandeId = (int) ($_POST['commande_id'] ?? 0);
$statut     = $_POST['statut'] ?? '';

if (!in_array($statut, STATUTS_COMMANDE, true)) {
    flash('error', 'Statut inconnu.');
    rediriger('accueil.php#admin-commandes');
}

try {
    $stmt = $pdo->prepare('UPDATE commandes SET statut = ? WHERE id = ?');
    $stmt->execute([$statut, $commandeId]);
    flash('success', 'Commande n°' . $commandeId . ' : ' . libelleStatut($statut) . '.');
} catch (PDOException $e) {
    error_log('Erreur statut commande : ' . $e->getMessage());
    flash('error', 'Le statut n\'a pas pu être modifié.');
}

rediriger('accueil.php#admin-commandes');
