<?php
require_once '../database/db.php';

header('Content-Type: application/json');

$link = connexionDB();

$sql = "SELECT joueur1_pret, joueur2_pret FROM partie_actuelle WHERE id = 1";

$result = mysqli_query($link, $sql);

if ($result) {
    $partie = mysqli_fetch_assoc($result);
    if ($partie) {
        $partie['joueur1_pret'] = (bool)$partie['joueur1_pret'];
        $partie['joueur2_pret'] = (bool)$partie['joueur2_pret'];
        echo json_encode($partie);
    } else {
        http_response_code(404);
        echo json_encode(['erreur' => 'Aucune partie trouvée.']);
    }
} else {
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur de base de données: ' . mysqli_error($link)]);
}

mysqli_close($link);
