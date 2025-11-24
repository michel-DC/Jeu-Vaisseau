<?php
require_once '../database/db.php';
require_once '../class/vaisseau.php';
require_once '../class/magicien.php';
require_once '../class/magie.php';
require_once 'gestion-tour.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erreur' => 'Méthode non autorisée. Seul POST est accepté.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$partie_id_from_client = $data['partie_id'] ?? null;
$lanceur_id = $data['lanceur_id'] ?? null;
$cible_id = $data['cible_id'] ?? null;

if (!$partie_id_from_client || !$lanceur_id || !$cible_id) {
    http_response_code(400);
    echo json_encode(['erreur' => 'ID de partie, lanceur ou cible manquant.']);
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

$sql_game_state = "SELECT p.joueur1_id, p.joueur2_id, gs.joueur1_hp, gs.joueur2_hp, gs.joueur1_position, gs.joueur2_position, gs.joueur1_magicien_nom, gs.joueur1_magicien_mana, gs.joueur1_magicien_puissance, gs.joueur2_magicien_nom, gs.joueur2_magicien_mana, gs.joueur2_magicien_puissance, gs.joueur1_effets, gs.joueur2_effets, gs.joueur_actuel, gs.joueur1_action_faite, gs.joueur2_action_faite FROM parties p JOIN game_state gs ON p.partie_id = gs.partie_id WHERE p.partie_id = ?";
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

$lanceur_role = '';
if ($game_state['joueur1_id'] === $lanceur_id) {
    $lanceur_role = 'joueur1';
} elseif ($game_state['joueur2_id'] === $lanceur_id) {
    $lanceur_role = 'joueur2';
} else {
    http_response_code(403);
    echo json_encode(['erreur' => 'Le lanceur spécifié ne participe pas à cette partie.']);
    mysqli_close($link);
    exit();
}

if ($game_state['joueur_actuel'] !== $lanceur_id) {
    http_response_code(403);
    echo json_encode(['erreur' => 'Ce n\'est pas votre tour de jouer.']);
    mysqli_close($link);
    exit();
}

$action_faite_column = "{$lanceur_role}_action_faite";
if ($game_state[$action_faite_column] == 1) {
    http_response_code(403);
    echo json_encode(['erreur' => 'Vous avez déjà effectué une action offensive ce tour.']);
    mysqli_close($link);
    exit();
}

$cible_role = ($lanceur_role === 'joueur1') ? 'joueur2' : 'joueur1';

$lanceurData = [];
$cibleData = [];

$lanceurData['id'] = $game_state["{$lanceur_role}_id"];
$lanceurData['hp'] = (int)$game_state["{$lanceur_role}_hp"];
$lanceurData['position'] = (int)$game_state["{$lanceur_role}_position"];
$lanceurData['magicien_nom'] = $game_state["{$lanceur_role}_magicien_nom"] ?? 'Merlin';
$lanceurData['magicien_mana'] = (int)($game_state["{$lanceur_role}_magicien_mana"] ?? 1);
$lanceurData['magicien_puissance'] = (int)($game_state["{$lanceur_role}_magicien_puissance"] ?? 1);
$lanceurData['effets'] = $game_state["{$lanceur_role}_effets"] ? json_decode($game_state["{$lanceur_role}_effets"], true) : [];

$cibleData['id'] = $game_state["{$cible_role}_id"];
$cibleData['hp'] = (int)$game_state["{$cible_role}_hp"];
$cibleData['position'] = (int)$game_state["{$cible_role}_position"];
$cibleData['effets'] = $game_state["{$cible_role}_effets"] ? json_decode($game_state["{$cible_role}_effets"], true) : [];

$lanceurVaisseau = new Vaisseau($lanceurData['id']);
$lanceurVaisseau->setPointDeVie($lanceurData['hp']);
$lanceurVaisseau->setPosition($lanceurData['position']);
$lanceurVaisseau->setStatusEffects($lanceurData['effets']);

$magicien = new Magicien(
    $lanceurData['magicien_nom'],
    $lanceurData['magicien_mana'],
    $lanceurData['magicien_puissance']
);
$lanceurVaisseau->setMagicienActuel($magicien);

$cibleVaisseau = new Vaisseau($cibleData['id']);
$cibleVaisseau->setPointDeVie($cibleData['hp']);
$cibleVaisseau->setPosition($cibleData['position']);
$cibleVaisseau->setStatusEffects($cibleData['effets']);

$magie = new Magie();
$resultatMagie = $magie->lancerSort($magicien, $cibleVaisseau, $lanceurVaisseau);

// Vérifier si le sort a réussi
if (!$resultatMagie['success']) {
    http_response_code(400);
    echo json_encode(['erreur' => $resultatMagie['message_lanceur']]);
    mysqli_close($link);
    exit();
}

$joueur_suivant_id = ($game_state['joueur1_id'] === $lanceur_id) ? $game_state['joueur2_id'] : $game_state['joueur1_id'];

$nouveaux_effets_lanceur = json_encode($lanceurVaisseau->getStatusEffects());
$nouveaux_effets_cible = json_encode($cibleVaisseau->getStatusEffects());

$params_update = [];
$types_update = "";

$sql_update_game_state = "UPDATE game_state SET ";

if ($lanceur_role === 'joueur1') {
    $sql_update_game_state .= "joueur1_hp = ?, joueur1_magicien_mana = ?, joueur1_effets = ?, joueur1_action_faite = 1, ";
    $types_update .= "iis";

    $params_update[] = $lanceurVaisseau->getPointDeVie();
    $params_update[] = $magicien->getMana();
    $params_update[] = $nouveaux_effets_lanceur;

    $sql_update_game_state .= "joueur2_hp = ?, joueur2_effets = ?, ";
    $types_update .= "is";

    $params_update[] = $cibleVaisseau->getPointDeVie();
    $params_update[] = $nouveaux_effets_cible;
} else {
    $sql_update_game_state .= "joueur2_hp = ?, joueur2_magicien_mana = ?, joueur2_effets = ?, joueur2_action_faite = 1, ";
    $types_update .= "iis";

    $params_update[] = $lanceurVaisseau->getPointDeVie();
    $params_update[] = $magicien->getMana();
    $params_update[] = $nouveaux_effets_lanceur;

    $sql_update_game_state .= "joueur1_hp = ?, joueur1_effets = ?, ";
    $types_update .= "is";

    $params_update[] = $cibleVaisseau->getPointDeVie();
    $params_update[] = $nouveaux_effets_cible;
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
    $args_for_bind_param[] = &$params_update[$key];
}

call_user_func_array([$stmt_update_game_state, 'bind_param'], $args_for_bind_param);

$execute_success = mysqli_stmt_execute($stmt_update_game_state);

if ($execute_success) {
    $affected_rows = mysqli_stmt_affected_rows($stmt_update_game_state);
    if ($affected_rows > 0) {
        // Gérer les effets de début de tour pour le joueur suivant
        gerer_debut_tour($link, $partie_id, $joueur_suivant_id);

        echo json_encode([
            'success' => true,
            'message_lanceur' => $resultatMagie['message_lanceur'],
            'message_cible' => $resultatMagie['message_cible'],
            'lanceur_nouveaux_hp' => $lanceurVaisseau->getPointDeVie(),
            'cible_nouveaux_hp' => $cibleVaisseau->getPointDeVie(),
            'joueur_suivant_id' => $joueur_suivant_id
        ]);
    } else {
        error_log('Erreur lors de la mise à jour de l\'état de la partie : Aucune ligne affectée. La partie_id était-elle correcte ? Client ID: ' . $partie_id_from_client . ', Session ID: ' . ($_SESSION['partie_id'] ?? 'NOT SET'));
        http_response_code(500);
        echo json_encode(['erreur' => 'Erreur lors de la mise à jour de l\'état de la partie : Aucune ligne affectée. La partie_id était-elle correcte ?']);
    }
} else {
    error_log('Erreur lors de l\'exécution de l\'UPDATE : ' . mysqli_stmt_error($stmt_update_game_state) . ' (Partie ID: ' . $partie_id . ')');
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur lors de la mise à jour de l\'état de la partie après l\'utilisation de la magie. Détails : ' . mysqli_stmt_error($stmt_update_game_state)]);
}

mysqli_stmt_close($stmt_update_game_state);
mysqli_close($link);
