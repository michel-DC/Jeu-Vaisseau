<?php
require_once '../database/db.php';
require_once '../class/vaisseau.php';
require_once '../class/attaque.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erreur' => 'Méthode non autorisée. Seul POST est accepté.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$partie_id_from_client = $data['partie_id'] ?? null;
$attaquant_id = $data['attaquant_id'] ?? null;
$defenseur_id = $data['defenseur_id'] ?? null;

if (!$partie_id_from_client || !$attaquant_id || !$defenseur_id) {
    http_response_code(400);
    echo json_encode(['erreur' => 'ID de partie, attaquant ou défenseur manquant.']);
    exit();
}

$partie_id_session = $_SESSION['partie_id'] ?? null;
if (!$partie_id_session) {
    http_response_code(401);
    echo json_encode(['erreur' => 'Session de partie expirée ou non définie.']);
    exit();
}

if ($partie_id_from_client !== $partie_id_session) {
    http_response_code(403);
    echo json_encode(['erreur' => 'ID de partie incohérent avec la session.']);
    exit();
}

$partie_id = $partie_id_session;

$link = connexionDB();

$sql_game_state = "SELECT p.joueur1_id, p.joueur2_id, gs.joueur1_hp, gs.joueur2_hp, gs.joueur1_position, gs.joueur2_position, gs.joueur1_puissance_tir, gs.joueur2_puissance_tir, gs.joueur1_max_puissance_tir, gs.joueur2_max_puissance_tir, gs.joueur1_attaques_sans_tirer, gs.joueur2_attaques_sans_tirer, gs.joueur1_cooldown_attaque_speciale, gs.joueur2_cooldown_attaque_speciale, gs.joueur1_damage_multiplier, gs.joueur2_damage_multiplier, gs.joueur_actuel, gs.joueur1_action_faite, gs.joueur2_action_faite, gs.joueur1_a_bouge, gs.joueur2_a_bouge FROM parties p JOIN game_state gs ON p.partie_id = gs.partie_id WHERE p.partie_id = ?";
$stmt_game_state = mysqli_prepare($link, $sql_game_state);

if ($stmt_game_state === false) {
    http_response_code(500);
    error_log('Erreur de préparation SQL pour l\'état du jeu : ' . mysqli_error($link));
    echo json_encode(['erreur' => 'Erreur de préparation SQL pour l\'état du jeu : ' . mysqli_error($link)]);
    mysqli_close($link);
    exit();
}

mysqli_stmt_bind_param($stmt_game_state, "s", $partie_id);
mysqli_stmt_execute($stmt_game_state);
$result_game_state = mysqli_stmt_get_result($stmt_game_state);

if (!$result_game_state || mysqli_num_rows($result_game_state) === 0) {
    http_response_code(404);
    echo json_encode(['erreur' => 'Partie non trouvée ou état de jeu introuvable pour ID: ' . $partie_id]);
    mysqli_close($link);
    exit();
}

$game_state = mysqli_fetch_assoc($result_game_state);
mysqli_stmt_close($stmt_game_state);

$attaquant_role = '';
if ($game_state['joueur1_id'] === $attaquant_id) {
    $attaquant_role = 'joueur1';
} elseif ($game_state['joueur2_id'] === $attaquant_id) {
    $attaquant_role = 'joueur2';
} else {
    http_response_code(403);
    echo json_encode(['erreur' => 'L\'attaquant spécifié ne participe pas à cette partie.']);
    mysqli_close($link);
    exit();
}

if ($game_state['joueur_actuel'] !== $attaquant_id) {
    http_response_code(403);
    echo json_encode(['erreur' => 'Ce n\'est pas votre tour de jouer.']);
    mysqli_close($link);
    exit();
}

$action_faite_column = "{$attaquant_role}_action_faite";
if ($game_state[$action_faite_column] == 1) {
    http_response_code(403);
    echo json_encode(['erreur' => 'Vous avez déjà effectué une action offensive ce tour.']);
    mysqli_close($link);
    exit();
}

$defenseur_role = ($attaquant_role === 'joueur1') ? 'joueur2' : 'joueur1';

$attaquantData = [];
$defenseurData = [];

$attaquantData['id'] = $game_state["{$attaquant_role}_id"];
$attaquantData['hp'] = (int)$game_state["{$attaquant_role}_hp"];
$attaquantData['position'] = (int)$game_state["{$attaquant_role}_position"];
$attaquantData['puissance_tir'] = (int)$game_state["{$attaquant_role}_puissance_tir"];
$attaquantData['max_puissance_tir'] = (int)$game_state["{$attaquant_role}_max_puissance_tir"];
$attaquantData['attaques_sans_tirer'] = (int)$game_state["{$attaquant_role}_attaques_sans_tirer"];
$attaquantData['cooldown_attaque_speciale'] = (int)$game_state["{$attaquant_role}_cooldown_attaque_speciale"];
$attaquantData['damage_multiplier'] = (float)($game_state["{$attaquant_role}_damage_multiplier"] ?? 1.0);

$defenseurData['id'] = $game_state["{$defenseur_role}_id"];
$defenseurData['hp'] = (int)$game_state["{$defenseur_role}_hp"];
$defenseurData['position'] = (int)$game_state["{$defenseur_role}_position"];
$defenseurData['puissance_tir'] = (int)$game_state["{$defenseur_role}_puissance_tir"];
$defenseurData['max_puissance_tir'] = (int)$game_state["{$defenseur_role}_max_puissance_tir"];
$defenseurData['attaques_sans_tirer'] = (int)$game_state["{$defenseur_role}_attaques_sans_tirer"];
$defenseurData['cooldown_attaque_speciale'] = (int)$game_state["{$defenseur_role}_cooldown_attaque_speciale"];


$attaquantVaisseau = new Vaisseau($attaquantData['id']);
$attaquantVaisseau->setPointDeVie($attaquantData['hp']);
$attaquantVaisseau->setPosition($attaquantData['position']);
$attaquantVaisseau->setPuissanceDeTir($attaquantData['puissance_tir']);
$attaquantVaisseau->setMaxPuissanceDeTir($attaquantData['max_puissance_tir']);
$attaquantVaisseau->setAttaquesConsecutivesSansTirer($attaquantData['attaques_sans_tirer']);
$attaquantVaisseau->setCooldownAttaqueSpeciale($attaquantData['cooldown_attaque_speciale']);
$attaquantVaisseau->setModificateurDegatsInfliges($attaquantData['damage_multiplier']);

$defenseurVaisseau = new Vaisseau($defenseurData['id']);
$defenseurVaisseau->setPointDeVie($defenseurData['hp']);
$defenseurVaisseau->setPosition($defenseurData['position']);
$defenseurVaisseau->setPuissanceDeTir($defenseurData['puissance_tir']);
$defenseurVaisseau->setMaxPuissanceDeTir($defenseurData['max_puissance_tir']);
$defenseurVaisseau->setAttaquesConsecutivesSansTirer($defenseurData['attaques_sans_tirer']);
$defenseurVaisseau->setCooldownAttaqueSpeciale($defenseurData['cooldown_attaque_speciale']);

$attaque = new Attaque();
$resultatAttaque = $attaque->tirer($attaquantVaisseau, $defenseurVaisseau);

// Ajouter le rôle du joueur au début du message pour personnalisation côté client
$resultatAttaque['message'] = "ATTACK:{$attaquant_role}:" . $resultatAttaque['message'];

$joueur_suivant_id = ($game_state['joueur1_id'] === $attaquant_id) ? $game_state['joueur2_id'] : $game_state['joueur1_id'];


$params_update = [];
$types_update = "";

$sql_update_game_state = "UPDATE game_state SET ";

if ($attaquant_role === 'joueur1') {
    $sql_update_game_state .= "joueur1_hp = ?, joueur1_puissance_tir = ?, joueur1_attaques_sans_tirer = ?, joueur1_cooldown_attaque_speciale = ?, joueur1_damage_multiplier = ?, joueur1_action_faite = 1, ";
    $types_update .= "iiiid";

    $params_update[] = $attaquantVaisseau->getPointDeVie();
    $params_update[] = $attaquantVaisseau->getPuissanceDeTir();
    $params_update[] = $attaquantVaisseau->getAttaquesConsecutivesSansTirer();
    $params_update[] = $attaquantVaisseau->getCooldownAttaqueSpeciale();
    $params_update[] = 1.0; // Reset damage multiplier after attack

    $sql_update_game_state .= "joueur2_hp = ?, joueur2_puissance_tir = ?, joueur2_attaques_sans_tirer = ?, joueur2_cooldown_attaque_speciale = ?, ";
    $types_update .= "iiii";

    $params_update[] = $defenseurVaisseau->getPointDeVie();
    $params_update[] = $defenseurVaisseau->getPuissanceDeTir();
    $params_update[] = $defenseurVaisseau->getAttaquesConsecutivesSansTirer();
    $params_update[] = $defenseurVaisseau->getCooldownAttaqueSpeciale();
} else {
    $sql_update_game_state .= "joueur2_hp = ?, joueur2_puissance_tir = ?, joueur2_attaques_sans_tirer = ?, joueur2_cooldown_attaque_speciale = ?, joueur2_damage_multiplier = ?, joueur2_action_faite = 1, ";
    $types_update .= "iiiid";

    $params_update[] = $attaquantVaisseau->getPointDeVie();
    $params_update[] = $attaquantVaisseau->getPuissanceDeTir();
    $params_update[] = $attaquantVaisseau->getAttaquesConsecutivesSansTirer();
    $params_update[] = $attaquantVaisseau->getCooldownAttaqueSpeciale();
    $params_update[] = 1.0; // Reset damage multiplier after attack

    $sql_update_game_state .= "joueur1_hp = ?, joueur1_puissance_tir = ?, joueur1_attaques_sans_tirer = ?, joueur1_cooldown_attaque_speciale = ?, ";
    $types_update .= "iiii";

    $params_update[] = $defenseurVaisseau->getPointDeVie();
    $params_update[] = $defenseurVaisseau->getPuissanceDeTir();
    $params_update[] = $defenseurVaisseau->getAttaquesConsecutivesSansTirer();
    $params_update[] = $defenseurVaisseau->getCooldownAttaqueSpeciale();
}

$sql_update_game_state .= "joueur_actuel = ?, joueur1_action_faite = 0, joueur2_action_faite = 0, joueur1_a_bouge = 0, joueur2_a_bouge = 0 WHERE partie_id = ?";

$types_update .= "ss";

$params_update[] = $joueur_suivant_id;
$params_update[] = $partie_id;


$stmt_update_game_state = mysqli_prepare($link, $sql_update_game_state);

if ($stmt_update_game_state === false) {
    http_response_code(500);
    error_log('Erreur de préparation SQL pour l\'UPDATE : ' . mysqli_error($link) . ' (Requête générée: ' . $sql_update_game_state . ')');
    echo json_encode(['erreur' => 'Erreur de préparation SQL pour l\'UPDATE : ' . mysqli_error($link) . ' (Requête générée: ' . $sql_update_game_state . ')']);
    mysqli_close($link);
    exit();
}

$args_for_bind_param = [$types_update];

foreach ($params_update as $key => $value) {
    $args_for_bind_param[] = & $params_update[$key];
}

call_user_func_array([$stmt_update_game_state, 'bind_param'], $args_for_bind_param);


$execute_success = mysqli_stmt_execute($stmt_update_game_state);

if ($execute_success) {
    $affected_rows = mysqli_stmt_affected_rows($stmt_update_game_state);
    if ($affected_rows > 0) {
        echo json_encode(array_merge($resultatAttaque, [
            'attaquant_nouveaux_hp' => $attaquantVaisseau->getPointDeVie(),
            'defenseur_nouveaux_hp' => $defenseurVaisseau->getPointDeVie(),
            'joueur_suivant_id' => $joueur_suivant_id
        ]));
    } else {
        error_log('Erreur lors de la mise à jour de l\'état de la partie : Aucune ligne affectée. La partie_id était-elle correcte ? Client ID: ' . $partie_id_from_client . ', Session ID: ' . ($_SESSION['partie_id'] ?? 'NOT SET'));
        http_response_code(500);
        echo json_encode(['erreur' => 'Erreur lors de la mise à jour de l\'état de la partie : Aucune ligne affectée. La partie_id était-elle correcte ?']);
    }
} else {
    error_log('Erreur lors de l\'exécution de l\'UPDATE : ' . mysqli_stmt_error($stmt_update_game_state) . ' (Partie ID: ' . $partie_id . ')');
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur lors de la mise à jour de l\'état de la partie après l\'attaque. Détails : ' . mysqli_stmt_error($stmt_update_game_state)]);
}

mysqli_stmt_close($stmt_update_game_state);
mysqli_close($link);