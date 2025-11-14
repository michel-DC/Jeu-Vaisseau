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

// Insérer la nouvelle partie
$sql = "INSERT INTO parties (partie_id, joueur1_id, statut) VALUES (?, ?, 'en_attente')";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "ss", $partie_id, $joueur_id);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['partie_id'] = $partie_id;
    $_SESSION['joueur_role'] = 'joueur1';
    http_response_code(201);
    echo json_encode(['succes' => 'Partie créée avec succès.', 'partie_id' => $partie_id]);
} else {
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur lors de la création de la partie: ' . mysqli_error($link)]);
}

mysqli_close($link);
