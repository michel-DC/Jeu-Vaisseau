<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Une erreur est survenue.'];

if (!isset($_SESSION['partie_id']) || !isset($_SESSION['joueur_role'])) {
    $response['message'] = 'Utilisateur non authentifié ou partie non trouvée.';
    echo json_encode($response);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$vaisseau_id = $data['vaisseau_id'] ?? null;
$vaisseau_image = $data['vaisseau_image'] ?? null;
$bonus = $data['bonus'] ?? null;

if (empty($vaisseau_id) || empty($vaisseau_image) || empty($bonus)) {
    $response['message'] = 'Données de vaisseau incomplètes.';
    echo json_encode($response);
    exit();
}

$partie_id = $_SESSION['partie_id'];
$joueur_role = $_SESSION['joueur_role'];

$link = connexionDB();
if (!$link) {
    $response['message'] = 'Erreur de connexion à la base de données.';
    echo json_encode($response);
    exit();
}

// Déterminer les colonnes selon le rôle du joueur
$colonne_choix = ($joueur_role === 'joueur1') ? 'joueur1_choix_vaisseau' : 'joueur2_choix_vaisseau';
$colonne_hp = ($joueur_role === 'joueur1') ? 'joueur1_hp' : 'joueur2_hp';
$colonne_magicien_puissance = ($joueur_role === 'joueur1') ? 'joueur1_magicien_puissance' : 'joueur2_magicien_puissance';
$colonne_drones = ($joueur_role === 'joueur1') ? 'joueur1_drones' : 'joueur2_drones';

// Préparer les drones selon le vaisseau
$drones = [];
for ($i = 0; $i < $bonus['drones_attaque']; $i++) {
    $drones[] = ['type' => 'attaque'];
}
for ($i = 0; $i < $bonus['drones_reconnaissance']; $i++) {
    $drones[] = ['type' => 'reconnaissance'];
}
$drones_json = json_encode($drones);

// Stocker les bonus dans la session pour les utiliser pendant le jeu
$_SESSION['vaisseau_bonus'] = [
    'mouvements_max' => $bonus['mouvements_max'],
    'bonus_degats' => $bonus['bonus_degats']
];

// Mettre à jour la base de données avec le choix du vaisseau et ses bonus initiaux
$sql = "UPDATE game_state SET 
    $colonne_choix = ?, 
    $colonne_hp = ?, 
    $colonne_magicien_puissance = ?,
    $colonne_drones = ?
    WHERE partie_id = ?";

$stmt = mysqli_prepare($link, $sql);

if ($stmt) {
    mysqli_stmt_bind_param(
        $stmt, 
        "siiss", 
        $vaisseau_image, 
        $bonus['hp_initial'], 
        $bonus['magicien_puissance'],
        $drones_json,
        $partie_id
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $response['success'] = true;
        $response['message'] = 'Vaisseau enregistré avec succès.';
    } else {
        $response['message'] = 'Erreur lors de la mise à jour de la base de données.';
        error_log("Erreur SQL: " . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);
} else {
    $response['message'] = 'Erreur de préparation de la requête.';
    error_log("Erreur prepare: " . mysqli_error($link));
}

mysqli_close($link);
echo json_encode($response);
