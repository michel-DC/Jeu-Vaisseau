<?php
session_start();
require_once __DIR__ . '/../database/db.php';

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

$sql = "INSERT INTO parties (partie_id, joueur1_id, statut) VALUES (?, ?, 'en_attente')";
$stmt = mysqli_prepare($link, $sql);

if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur de préparation de la requête: ' . mysqli_error($link)]);
    exit();
}

mysqli_stmt_bind_param($stmt, "ss", $partie_id, $joueur_id);

if (mysqli_stmt_execute($stmt)) {
    $sql_game_state = "INSERT INTO game_state (partie_id, joueur1_hp, joueur2_hp, duree_partie) VALUES (?, 1000, 1000, 0)";
    $stmt_game_state = mysqli_prepare($link, $sql_game_state);
    mysqli_stmt_bind_param($stmt_game_state, "s", $partie_id);

    if (mysqli_stmt_execute($stmt_game_state)) {
        $_SESSION['partie_id'] = $partie_id;
        $_SESSION['joueur_role'] = 'joueur1';
        http_response_code(201);
        echo json_encode(['succes' => 'Partie créée avec succès.', 'partie_id' => $partie_id]);
    } else {
        http_response_code(500);
        echo json_encode(['erreur' => 'Erreur lors de l\'initialisation de l\'état du jeu: ' . mysqli_error($link)]);
    }
    mysqli_stmt_close($stmt_game_state);
} else {
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur lors de la création de la partie: ' . mysqli_error($link)]);
}

mysqli_close($link);
