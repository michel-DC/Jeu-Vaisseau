<?php
session_start();
require_once '../database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit();
}

$partie_id = $_SESSION['partie_id'] ?? null;
$duree_partie = $_POST['duree_partie'] ?? null;

if (!$partie_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de partie manquant dans la session.']);
    exit();
}

if ($duree_partie === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Durée de partie manquante.']);
    exit();
}

$link = connexionDB();

$sql = "UPDATE game_state SET duree_partie = ? WHERE partie_id = ?";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "is", $duree_partie, $partie_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'État du jeu mis à jour.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour de l\'état du jeu: ' . mysqli_error($link)]);
}

mysqli_stmt_close($stmt);
mysqli_close($link);
