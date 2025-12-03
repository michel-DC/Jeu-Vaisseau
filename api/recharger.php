<?php
session_start();
require_once '../database/db.php';
require_once 'gestion-tour.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'erreur' => 'Méthode non autorisée.']);
    exit();
}

$partie_id = $_SESSION['partie_id'] ?? null;
$joueur_id = $_SESSION['joueur_id'] ?? null;
$joueur_role = $_SESSION['joueur_role'] ?? null; // 'joueur1' or 'joueur2'

if (!$partie_id || !$joueur_id || !$joueur_role) {
    http_response_code(401);
    echo json_encode(['success' => false, 'erreur' => 'Session invalide ou manquante.']);
    exit();
}

$link = connexionDB();

$sql = "SELECT p.joueur1_id, p.joueur2_id, gs.joueur_actuel, gs.{$joueur_role}_magicien_mana AS my_mana, gs.{$joueur_role}_drones AS my_drones FROM parties p JOIN game_state gs ON p.partie_id = gs.partie_id WHERE p.partie_id = ?";
$stmt = mysqli_prepare($link, $sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'erreur' => 'Erreur de préparation SQL.']);
    mysqli_close($link);
    exit();
}

mysqli_stmt_bind_param($stmt, 's', $partie_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$game_state = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$game_state) {
    http_response_code(404);
    echo json_encode(['success' => false, 'erreur' => 'Partie introuvable.']);
    mysqli_close($link);
    exit();
}

// Check it's the player's turn
if ($game_state['joueur_actuel'] !== $joueur_id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'erreur' => "Ce n'est pas votre tour."]);
    mysqli_close($link);
    exit();
}

// Recharging is always allowed: it will add 1 recon + 1 attack drone and ensure the mana is set to 1.
// If mana is already full it will not be lowered; we still add drones.

// Parse existing drones JSON
$existing = $game_state['my_drones'];
$drones = [];
if ($existing) {
    $decoded = json_decode($existing, true);
    if (is_array($decoded)) $drones = $decoded;
}

// Add one recon and one attack drone
$drones[] = ['type' => 'reconnaissance'];
$drones[] = ['type' => 'attaque'];
$new_drones_json = json_encode($drones);

// Determine the player id for the next player (we end the turn)
$joueur_suivant_id = ($game_state['joueur1_id'] === $joueur_id) ? $game_state['joueur2_id'] : $game_state['joueur1_id'];

// Update: set mana to 1 (idempotent), write new drones, change current player and reset turn flags.
$sql_up = "UPDATE game_state SET {$joueur_role}_magicien_mana = 1, {$joueur_role}_drones = ?, joueur_actuel = ?, joueur1_action_faite = 0, joueur2_action_faite = 0, joueur1_a_bouge = 0, joueur2_a_bouge = 0 WHERE partie_id = ?";
$stmt_up = mysqli_prepare($link, $sql_up);
if (!$stmt_up) {
    http_response_code(500);
    echo json_encode(['success' => false, 'erreur' => 'Erreur de préparation SQL pour la mise à jour.']);
    mysqli_close($link);
    exit();
}

$stmt_bind_ok = mysqli_stmt_bind_param($stmt_up, 'sss', $new_drones_json, $joueur_suivant_id, $partie_id);
$ok = mysqli_stmt_execute($stmt_up);
mysqli_stmt_close($stmt_up);

if (!$ok) {
    http_response_code(500);
    echo json_encode(['success' => false, 'erreur' => 'Erreur BDD: ' . mysqli_error($link)]);
    mysqli_close($link);
    exit();
}

// Add narration entry
$msg = "RECHARGE:{$joueur_role}:Rechargement effectué — Mana restaurée et +1 drone reconnaissance +1 drone attaque.";
$stmt_n = mysqli_prepare($link, "INSERT INTO narration_events (partie_id, message) VALUES (?, ?)");
if ($stmt_n) {
    mysqli_stmt_bind_param($stmt_n, 'ss', $partie_id, $msg);
    mysqli_stmt_execute($stmt_n);
    mysqli_stmt_close($stmt_n);
}

if ($ok) {
    // Gérer les effets de début de tour du joueur suivant
    gerer_debut_tour($link, $partie_id, $joueur_suivant_id);

    mysqli_close($link);
    echo json_encode([
        'success' => true,
        'message' => 'Rechargement: mana restaurée (si pas déjà pleine) et deux drones ajoutés. Le tour est terminé.',
        'nouveaux_drones' => $drones,
        'joueur_suivant_id' => $joueur_suivant_id,
        'tour_change' => true
    ]);
} else {
    mysqli_close($link);
    http_response_code(500);
    echo json_encode(['success' => false, 'erreur' => 'Erreur BDD: ' . mysqli_error($link)]);
}

?>
