<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'erreur' => 'Méthode non autorisée.']);
    exit();
}

$partie_id = $_SESSION['partie_id'] ?? null;
$joueur_id = $_SESSION['joueur_id'] ?? null;
$joueur_role = $_SESSION['joueur_role'] ?? null; // joueur1/ joueur2

if (!$partie_id || !$joueur_id || !$joueur_role) {
    http_response_code(400);
    echo json_encode(['success' => false, 'erreur' => 'Session invalide ou manquante.']);
    exit();
}

$link = connexionDB();

// Set this player's HP to 0 (forfeit) and add a narration event.
$hp_col = ($joueur_role === 'joueur1') ? 'joueur1_hp' : 'joueur2_hp';
$sql = "UPDATE game_state SET {$hp_col} = 0 WHERE partie_id = ?";
$stmt = mysqli_prepare($link, $sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'erreur' => 'Erreur prepare SQL']);
    mysqli_close($link);
    exit();
}

mysqli_stmt_bind_param($stmt, 's', $partie_id);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (!$ok) {
    http_response_code(500);
    echo json_encode(['success' => false, 'erreur' => 'Erreur BDD: ' . mysqli_error($link)]);
    mysqli_close($link);
    exit();
}

// Narration
$msg = "ABANDON:" . $joueur_role . ":Le joueur a abandonné la partie. Victoire pour l'adversaire.";
$stmt2 = mysqli_prepare($link, "INSERT INTO narration_events (partie_id, message) VALUES (?, ?)");
if ($stmt2) {
    mysqli_stmt_bind_param($stmt2, 'ss', $partie_id, $msg);
    mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);
}

mysqli_close($link);

// Also clear session and mark success
unset($_SESSION['partie_id']);
unset($_SESSION['joueur_role']);

echo json_encode(['success' => true, 'message' => 'Vous avez abandonné la partie.']);

?>
