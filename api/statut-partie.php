<?php
require_once '../database/db.php';
session_start();

header('Content-Type: application/json');

$partie_id_session = $_SESSION['partie_id'] ?? null;
if (!$partie_id_session) {
    http_response_code(401);
    echo json_encode(['erreur' => 'Session de partie expirée ou non définie.']);
    exit();
}

$partie_id_from_get = $_GET['partie_id'] ?? null;
if ($partie_id_from_get && $partie_id_from_get !== $partie_id_session) {
    http_response_code(403);
    echo json_encode(['erreur' => 'ID de partie fourni via GET incohérent avec la session.']);
    exit();
}

$partie_id = $partie_id_session;

$link = connexionDB();

$sql = "SELECT p.joueur1_id, p.joueur2_id, p.statut, gs.joueur1_hp, gs.joueur2_hp, gs.duree_partie, gs.joueur1_choix_vaisseau, gs.joueur2_choix_vaisseau, gs.premier_joueur, gs.joueur1_position, gs.joueur2_position, gs.joueur_actuel, gs.joueur1_action_faite, gs.joueur2_action_faite, gs.joueur1_a_bouge, gs.joueur2_a_bouge, gs.joueur1_magicien_mana, gs.joueur2_magicien_mana, gs.joueur1_puissance_tir, gs.joueur2_puissance_tir, gs.joueur1_damage_multiplier, gs.joueur2_damage_multiplier, gs.joueur1_magicien_puissance, gs.joueur2_magicien_puissance FROM parties p JOIN game_state gs ON p.partie_id = gs.partie_id WHERE p.partie_id = ?";
$stmt = mysqli_prepare($link, $sql);

if ($stmt === false) {
    http_response_code(500);
    error_log('Erreur de préparation SQL pour l\'état du jeu : ' . mysqli_error($link));
    echo json_encode(['erreur' => 'Erreur de préparation SQL pour l\'état du jeu : ' . mysqli_error($link)]);
    mysqli_close($link);
    exit();
}

mysqli_stmt_bind_param($stmt, "s", $partie_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && $partie = mysqli_fetch_assoc($result)) {
    if (empty($partie['premier_joueur']) && $partie['statut'] === 'complete') {
        $players = [$partie['joueur1_id'], $partie['joueur2_id']];
        $premier_joueur = $players[array_rand($players)];

        $stmt_update = mysqli_prepare($link, "UPDATE game_state SET premier_joueur = ?, joueur_actuel = ? WHERE partie_id = ?");
        if ($stmt_update === false) {
            error_log('Erreur de préparation SQL pour le set premier joueur : ' . mysqli_error($link));
        } else {
            mysqli_stmt_bind_param($stmt_update, "sss", $premier_joueur, $premier_joueur, $partie_id);
            mysqli_stmt_execute($stmt_update);
            mysqli_stmt_close($stmt_update);
        }

        $partie['premier_joueur'] = $premier_joueur;
        $partie['joueur_actuel'] = $premier_joueur;
    }

    $partie['joueur1_pret'] = !empty($partie['joueur1_id']);
    $partie['joueur2_pret'] = !empty($partie['joueur2_id']);
    $partie['vaisseaux_choisis'] = !empty($partie['joueur1_choix_vaisseau']) && !empty($partie['joueur2_choix_vaisseau']);
    echo json_encode($partie);
} else {
    http_response_code(404);
    echo json_encode(['erreur' => 'Aucune partie trouvée pour cet ID: ' . $partie_id]);
}

mysqli_stmt_close($stmt);
mysqli_close($link);