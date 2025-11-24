<?php
require_once '../database/db.php';
require_once '../class/vaisseau.php';
require_once '../class/drone.php';
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
$joueur_id = $data['joueur_id'] ?? null;
$drone_type = $data['drone_type'] ?? null;

if (!$partie_id_from_client || !$joueur_id || !$drone_type) {
    http_response_code(400);
    echo json_encode(['erreur' => 'Données manquantes (partie_id, joueur_id, drone_type).']);
    exit();
}

$partie_id_session = $_SESSION['partie_id'] ?? null;
if (!$partie_id_session || $partie_id_from_client !== $partie_id_session) {
    http_response_code(403);
    echo json_encode(['erreur' => 'Session invalide ou ID de partie incorrect.']);
    exit();
}

$link = connexionDB();


$sql_game_state = "SELECT p.joueur1_id, p.joueur2_id, gs.* FROM parties p JOIN game_state gs ON p.partie_id = gs.partie_id WHERE p.partie_id = ?";
$stmt = mysqli_prepare($link, $sql_game_state);
mysqli_stmt_bind_param($stmt, "s", $partie_id_session);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$game_state = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$game_state) {
    http_response_code(404);
    echo json_encode(['erreur' => 'Partie introuvable.']);
    mysqli_close($link);
    exit();
}

$joueur_role = '';
if ($game_state['joueur1_id'] === $joueur_id) {
    $joueur_role = 'joueur1';
} elseif ($game_state['joueur2_id'] === $joueur_id) {
    $joueur_role = 'joueur2';
} else {
    http_response_code(403);
    echo json_encode(['erreur' => 'Joueur non reconnu.']);
    mysqli_close($link);
    exit();
}

if ($game_state['joueur_actuel'] !== $joueur_id) {
    http_response_code(403);
    echo json_encode(['erreur' => "Ce n'est pas votre tour."]);
    mysqli_close($link);
    exit();
}

if ($game_state[$joueur_role . '_action_faite'] == 1) {
    http_response_code(403);
    echo json_encode(['erreur' => 'Vous avez déjà effectué une action ce tour.']);
    mysqli_close($link);
    exit();
}

$vaisseau = new Vaisseau($joueur_role);
$vaisseau->setPointDeVie($game_state[$joueur_role . '_hp']);
$vaisseau->setPuissanceDeTir($game_state[$joueur_role . '_puissance_tir']);
$vaisseau->setMaxPuissanceDeTir($game_state[$joueur_role . '_max_puissance_tir']);
$vaisseau->setModificateurDegatsInfliges(1.0);

// Initialiser le magicien actuel depuis la BDD pour ne pas le perdre si le drone ne le change pas
$magicien_actuel = new Magicien(
    $game_state[$joueur_role . '_magicien_nom'] ?? 'Merlin',
    (int)($game_state[$joueur_role . '_magicien_mana'] ?? 1),
    (int)($game_state[$joueur_role . '_magicien_puissance'] ?? 1)
);
$vaisseau->setMagicienActuel($magicien_actuel);

$drones_json = $game_state[$joueur_role . '_drones'];
$drones_data = $drones_json ? json_decode($drones_json, true) : null;

$drones_objets = [];
if ($drones_data === null) {
    $drones_objets = $vaisseau->getDrones();
} else {
    foreach ($drones_data as $d) {
        $drones_objets[] = new Drone($d['type'], $vaisseau->getPosition());
    }
}

$drone_index = -1;
foreach ($drones_objets as $index => $drone) {
    $type_courant = '';
    if ($drones_data !== null) {
        $type_courant = $drones_data[$index]['type'];
    } else {
        if ($index < 2) $type_courant = 'reconnaissance';
        else $type_courant = 'attaque';
    }

    if ($type_courant === $drone_type) {
        $drone_index = $index;
        break;
    }
}

if ($drone_index === -1) {
    http_response_code(400);
    echo json_encode(['erreur' => "Vous n'avez plus de drone de type $drone_type."]);
    mysqli_close($link);
    exit();
}

$drone_utilise = $drones_objets[$drone_index];
array_splice($drones_objets, $drone_index, 1);

$message_resultat = $drone_utilise->agir($vaisseau);

$message_resultat = "DRONE:{$joueur_role}:" . $message_resultat;

$nouveaux_drones_data = [];
foreach ($drones_objets as $d) {
    $nouveaux_drones_data[] = ['type' => $d->getType()];
}
$nouveaux_drones_json = json_encode($nouveaux_drones_data);

// Récupération des stats finales (potentiellement modifiées par le drone)
$nouv_hp = $vaisseau->getPointDeVie();
$nouv_puissance = $vaisseau->getPuissanceDeTir();
$nouv_modif = $vaisseau->getModificateurDegatsInfliges();
$magicien_final = $vaisseau->getMagicienActuel();
$nouv_magicien_puissance = $magicien_final->getPuissance();
$nouv_magicien_mana = $magicien_final->getMana();
$nouv_magicien_nom = $magicien_final->getNom();

$joueur_suivant_id = ($joueur_role === 'joueur1') ? $game_state['joueur2_id'] : $game_state['joueur1_id'];

$stmt_update = mysqli_prepare($link, "UPDATE game_state SET 
    {$joueur_role}_drones = ?, 
    {$joueur_role}_hp = ?, 
    {$joueur_role}_puissance_tir = ?, 
    {$joueur_role}_damage_multiplier = ?,
    {$joueur_role}_magicien_puissance = ?,
    {$joueur_role}_magicien_mana = ?,
    {$joueur_role}_magicien_nom = ?,
    joueur_actuel = ?,
    joueur1_action_faite = 0,
    joueur2_action_faite = 0,
    joueur1_a_bouge = 0,
    joueur2_a_bouge = 0
    WHERE partie_id = ?");

mysqli_stmt_bind_param(
    $stmt_update,
    "siidiisss",
    $nouveaux_drones_json,
    $nouv_hp,
    $nouv_puissance,
    $nouv_modif,
    $nouv_magicien_puissance,
    $nouv_magicien_mana,
    $nouv_magicien_nom,
    $joueur_suivant_id,
    $partie_id_session
);

if (mysqli_stmt_execute($stmt_update)) {
    // Gérer les effets de début de tour pour le joueur suivant
    gerer_debut_tour($link, $partie_id_session, $joueur_suivant_id);

    echo json_encode([
        'success' => true,
        'message' => $message_resultat,
        'drone_type' => $drone_type
    ]);
} else {
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur BDD: ' . mysqli_error($link)]);
}

mysqli_stmt_close($stmt_update);
mysqli_close($link);
