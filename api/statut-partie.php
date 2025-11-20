<?php
require_once '../database/db.php';
session_start();

header('Content-Type: application/json');

$partie_id = $_SESSION['partie_id'] ?? $_GET['partie_id'] ?? null;

if (!$partie_id) {
    http_response_code(400);
    echo json_encode(['erreur' => 'ID de partie manquant.']);
    exit();
}

$link = connexionDB();

$sql = "SELECT p.joueur1_id, p.joueur2_id, p.statut, gs.joueur1_hp, gs.joueur2_hp, gs.duree_partie, gs.joueur1_choix_vaisseau, gs.joueur2_choix_vaisseau, gs.premier_joueur, gs.joueur1_position, gs.joueur2_position FROM parties p JOIN game_state gs ON p.partie_id = gs.partie_id WHERE p.partie_id = ?";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "s", $partie_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && $partie = mysqli_fetch_assoc($result)) {
    // Si le premier joueur n'est pas encore défini et que la partie est complète
    if (empty($partie['premier_joueur']) && $partie['statut'] === 'complete') {
        // Tirage au sort
        $players = [$partie['joueur1_id'], $partie['joueur2_id']];
        $premier_joueur = $players[array_rand($players)];

        // Enregistrer le résultat en base de données
        $stmt_update = mysqli_prepare($link, "UPDATE game_state SET premier_joueur = ? WHERE partie_id = ?");
        mysqli_stmt_bind_param($stmt_update, "ss", $premier_joueur, $partie_id);
        mysqli_stmt_execute($stmt_update);
        
        // Mettre à jour l'objet partie pour l'inclure dans la réponse immédiate
        $partie['premier_joueur'] = $premier_joueur;
    }

    $partie['joueur1_pret'] = !empty($partie['joueur1_id']);
    $partie['joueur2_pret'] = !empty($partie['joueur2_id']);
    $partie['vaisseaux_choisis'] = !empty($partie['joueur1_choix_vaisseau']) && !empty($partie['joueur2_choix_vaisseau']);
    echo json_encode($partie);
} else {
    http_response_code(404);
    echo json_encode(['erreur' => 'Aucune partie trouvée pour cet ID.']);
}

mysqli_close($link);
