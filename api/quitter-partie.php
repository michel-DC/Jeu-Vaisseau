<?php
session_start();
require_once '../database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erreur' => 'Méthode non autorisée.']);
    exit();
}

$partie_id = $_SESSION['partie_id'] ?? null;
$joueur_role = $_SESSION['joueur_role'] ?? null;

if (!$partie_id || !$joueur_role) {
    http_response_code(400);
    echo json_encode(['erreur' => 'Aucune partie active ou rôle de joueur non défini en session.']);
    exit();
}

$link = connexionDB();
$colonne_a_reinitialiser = ($joueur_role === 'joueur1') ? 'joueur1_id' : 'joueur2_id';

// Mettre à jour le statut du joueur à NULL et le statut de la partie à 'en_attente'
$sql = "UPDATE parties SET $colonne_a_reinitialiser = NULL, statut = 'en_attente' WHERE partie_id = ?";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "s", $partie_id);

if (mysqli_stmt_execute($stmt)) {
    // Nettoyer les variables de session
    unset($_SESSION['partie_id']);
    unset($_SESSION['joueur_role']);
    http_response_code(200);
    echo json_encode(['succes' => 'Le joueur a quitté la partie.']);
} else {
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur de base de données: ' . mysqli_error($link)]);
}

mysqli_close($link);
