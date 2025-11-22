<?php
session_start();
require_once '../database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erreur' => 'Méthode non autorisée.']);
    exit();
}

$partie_id = $_POST['partie_id'] ?? null;
if (!$partie_id) {
    http_response_code(400);
    echo json_encode(['erreur' => 'ID de partie manquant.']);
    exit();
}

$link = connexionDB();
$joueur_id = session_id();

$sql_check = "SELECT * FROM parties WHERE partie_id = ?";
$stmt_check = mysqli_prepare($link, $sql_check);
mysqli_stmt_bind_param($stmt_check, "s", $partie_id);
mysqli_stmt_execute($stmt_check);
$result = mysqli_stmt_get_result($stmt_check);
$partie = mysqli_fetch_assoc($result);

if (!$partie) {
    http_response_code(404);
    echo json_encode(['erreur' => 'Partie non trouvée.']);
    exit();
}

if ($partie['statut'] !== 'en_attente') {
    http_response_code(403);
    echo json_encode(['erreur' => 'Cette partie n\'est plus en attente de joueurs.']);
    exit();
}

if ($partie['joueur1_id'] === $joueur_id) {
    http_response_code(403);
    echo json_encode(['erreur' => 'Vous ne pouvez pas rejoindre votre propre partie.']);
    exit();
}

$sql_update = "UPDATE parties SET joueur2_id = ?, statut = 'complete' WHERE partie_id = ?";
$stmt_update = mysqli_prepare($link, $sql_update);
mysqli_stmt_bind_param($stmt_update, "ss", $joueur_id, $partie_id);

if (mysqli_stmt_execute($stmt_update)) {
    $_SESSION['partie_id'] = $partie_id;
    $_SESSION['joueur_id'] = $joueur_id;
    $_SESSION['joueur_role'] = 'joueur2';

    // Ajouter un message de narration pour le début de la partie
    $sql_narrate = "INSERT INTO narration_events (partie_id, message) VALUES (?, ?)";
    $stmt_narrate = mysqli_prepare($link, $sql_narrate);
    $message_debut = "La bataille commence !";
    mysqli_stmt_bind_param($stmt_narrate, "ss", $partie_id, $message_debut);
    mysqli_stmt_execute($stmt_narrate);
    mysqli_stmt_close($stmt_narrate);

    http_response_code(200);
    echo json_encode(['succes' => 'Vous avez rejoint la partie !']);
} else {
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur pour rejoindre la partie: ' . mysqli_error($link)]);
}

mysqli_close($link);
