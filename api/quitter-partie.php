<?php
require_once '../database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['choix_joueur'])) {
    $choix = $_POST['choix_joueur'];

    $colonne_a_reinitialiser = '';
    if ($choix === 'joueur1') {
        $colonne_a_reinitialiser = 'joueur1_pret';
    } elseif ($choix === 'joueur2') {
        $colonne_a_reinitialiser = 'joueur2_pret';
    } else {
        http_response_code(400);
        echo json_encode(['erreur' => 'Choix de joueur invalide pour quitter.']);
        exit();
    }

    $link = connexionDB();

    // Réinitialiser le statut du joueur à FALSE
    $sql = "UPDATE partie_actuelle SET $colonne_a_reinitialiser = FALSE WHERE id = 1";

    if (mysqli_query($link, $sql)) {
        http_response_code(200);
        echo json_encode(['succes' => 'Le joueur a quitté la partie.']);
    } else {
        http_response_code(500);
        echo json_encode(['erreur' => 'Erreur de base de données: ' . mysqli_error($link)]);
    }

    mysqli_close($link);
} else {
    http_response_code(405);
    echo json_encode(['erreur' => 'Méthode non autorisée.']);
}
