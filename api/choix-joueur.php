<?php
require_once '../database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['choix_joueur'])) {
    $choix = $_POST['choix_joueur'];

    $colonne_a_updater = '';
    if ($choix === 'joueur1') {
        $colonne_a_updater = 'joueur1_pret';
    } elseif ($choix === 'joueur2') {
        $colonne_a_updater = 'joueur2_pret';
    } else {
        http_response_code(400);
        echo json_encode(['erreur' => 'Choix de joueur invalide.']);
        exit();
    }

    $link = connexionDB();

    $sql = "UPDATE partie_actuelle SET $colonne_a_updater = TRUE WHERE id = 1";

    if (mysqli_query($link, $sql)) {
        http_response_code(200);
        echo json_encode(['succes' => 'Le statut du joueur a été mis à jour.']);
    } else {
        http_response_code(500);
        echo json_encode(['erreur' => 'Erreur de base de données: ' . mysqli_error($link)]);
    }

    mysqli_close($link);
} else {
    http_response_code(405);
    echo json_encode(['erreur' => 'Méthode non autorisée.']);
}
