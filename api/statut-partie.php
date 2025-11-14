<?php
require_once '../database/db.php';

header('Content-Type: application/json');

$partie_id = $_GET['partie_id'] ?? null;

if (!$partie_id) {
    http_response_code(400);
    echo json_encode(['erreur' => 'ID de partie manquant.']);
    exit();
}

$link = connexionDB();

$sql = "SELECT p.joueur1_id, p.joueur2_id, p.statut, gs.joueur1_hp, gs.joueur2_hp, gs.duree_partie FROM parties p JOIN game_state gs ON p.partie_id = gs.partie_id WHERE p.partie_id = ?";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "s", $partie_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && $partie = mysqli_fetch_assoc($result)) {
    $partie['joueur1_pret'] = !empty($partie['joueur1_id']);
    $partie['joueur2_pret'] = !empty($partie['joueur2_id']);
    echo json_encode($partie);
} else {
    http_response_code(404);
    echo json_encode(['erreur' => 'Aucune partie trouvée pour cet ID.']);
}

mysqli_close($link);
