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
$vaisseau_url = $data['vaisseau'] ?? null;

if (empty($vaisseau_url)) {
    $response['message'] = 'Aucun vaisseau sélectionné.';
    echo json_encode($response);
    exit();
}

// Simple validation pour s'assurer que le chemin est plausible
if (strpos($vaisseau_url, 'assets/vaisseaux/') !== 0) {
    $response['message'] = 'Chemin de vaisseau invalide.';
    echo json_encode($response);
    exit();
}


$partie_id = $_SESSION['partie_id'];
$joueur_role = $_SESSION['joueur_role'];
$colonne_choix = ($joueur_role === 'joueur1') ? 'joueur1_choix_vaisseau' : 'joueur2_choix_vaisseau';

$link = connexionDB();
if (!$link) {
    $response['message'] = 'Erreur de connexion à la base de données.';
    echo json_encode($response);
    exit();
}

$sql = "UPDATE game_state SET $colonne_choix = ? WHERE partie_id = ?";
$stmt = mysqli_prepare($link, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $vaisseau_url, $partie_id);
    if (mysqli_stmt_execute($stmt)) {
        $response['success'] = true;
        $response['message'] = 'Vaisseau enregistré avec succès.';
    } else {
        $response['message'] = 'Erreur lors de la mise à jour de la base de données.';
    }
    mysqli_stmt_close($stmt);
} else {
    $response['message'] = 'Erreur de préparation de la requête.';
}

mysqli_close($link);
echo json_encode($response);
