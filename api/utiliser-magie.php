<?php
// Defensive: always return JSON and capture errors; convert warnings/notices to exceptions
// IMPORTANT: set buffering/handlers BEFORE any includes so warnings or accidental output
// from included files are captured and won't leak HTML to the client.
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // convert to exceptions so we can always return JSON
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});
set_exception_handler(function($e) {
    // Write a concise debug trace to a local file.
    $logFile = __DIR__ . '/magie-debug.log';
    $debugMsg = date('c') . " - Unhandled exception: " . $e->getMessage() . " in " . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString() . "\n";
    @file_put_contents($logFile, $debugMsg, FILE_APPEND);

    // Clear output and send a safe JSON error with a short debug hint
    while (ob_get_level() > 0) { @ob_end_clean(); }
    http_response_code(500);
    header('Content-Type: application/json');
    $short = substr($e->getMessage(), 0, 120);
    echo json_encode(['erreur' => 'Erreur interne serveur lors de l\'utilisation de la magie. (debug: ' . $short . ')']);
    exit();
});
ob_start();

require_once '../database/db.php';
require_once '../class/vaisseau.php';
require_once '../class/magicien.php';
require_once '../class/magie.php';
require_once 'gestion-tour.php';
// Wrap the main processing in a try/catch to capture and log unexpected exceptions
// (keeps existing global handlers but provides a local debug log file for easier diagnosis)
try {
function send_json($data, $status = 200) {
    // clear any accidental output and return a clean JSON response
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        // Clean any buffered output (likely HTML) and return JSON
        @ob_end_clean();
        $logFile = __DIR__ . '/magie-debug.log';
        @file_put_contents($logFile, date('c') . " - Fatal shutdown error: " . var_export($err, true) . "\n", FILE_APPEND);
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['erreur' => 'Erreur interne serveur lors de l\'utilisation de la magie.']);
    }
});

session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['erreur' => 'Méthode non autorisée. Seul POST est accepté.'], 405);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$partie_id_from_client = $data['partie_id'] ?? null;
$lanceur_id = $data['lanceur_id'] ?? null;
$cible_id = $data['cible_id'] ?? null;

if (!$partie_id_from_client || !$lanceur_id || !$cible_id) {
    send_json(['erreur' => 'ID de partie, lanceur ou cible manquant.'], 400);
    exit();
}

$partie_id_session = $_SESSION['partie_id'] ?? null;
if (!$partie_id_session) {
    send_json(['erreur' => 'Session de partie expirée ou non définie.'], 401);
    exit();
}

if ($partie_id_from_client !== $partie_id_session) {
    send_json(['erreur' => 'ID de partie incohérent avec la session.'], 403);
    exit();
}

$partie_id = $partie_id_session;

$link = connexionDB();

$sql_game_state = "SELECT p.joueur1_id, p.joueur2_id, gs.joueur1_hp, gs.joueur2_hp, gs.joueur1_position, gs.joueur2_position, gs.joueur1_magicien_nom, gs.joueur1_magicien_mana, gs.joueur1_magicien_puissance, gs.joueur2_magicien_nom, gs.joueur2_magicien_mana, gs.joueur2_magicien_puissance, gs.joueur1_effets, gs.joueur2_effets, gs.joueur_actuel, gs.joueur1_action_faite, gs.joueur2_action_faite FROM parties p JOIN game_state gs ON p.partie_id = gs.partie_id WHERE p.partie_id = ?";
$stmt_game_state = mysqli_prepare($link, $sql_game_state);

if ($stmt_game_state === false) {
    error_log('Erreur de préparation SQL pour l\'état du jeu : ' . mysqli_error($link));
    send_json(['erreur' => 'Erreur de préparation SQL pour l\'état du jeu.'], 500);
    mysqli_close($link);
    exit();
}

mysqli_stmt_bind_param($stmt_game_state, "s", $partie_id);
mysqli_stmt_execute($stmt_game_state);
$result_game_state = mysqli_stmt_get_result($stmt_game_state);

if (!$result_game_state || mysqli_num_rows($result_game_state) === 0) {
    send_json(['erreur' => 'Partie non trouvée ou état de jeu introuvable pour ID: ' . $partie_id], 404);
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
    send_json(['erreur' => 'Le lanceur spécifié ne participe pas à cette partie.'], 403);
    mysqli_close($link);
    exit();
}

if ($game_state['joueur_actuel'] !== $lanceur_id) {
    send_json(['erreur' => 'Ce n\'est pas votre tour de jouer.'], 403);
    mysqli_close($link);
    exit();
}

$action_faite_column = "{$lanceur_role}_action_faite";
if ($game_state[$action_faite_column] == 1) {
    send_json(['erreur' => 'Vous avez déjà effectué une action offensive ce tour.'], 403);
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

// Defensive: ensure we got a valid array result and expected keys
if (!is_array($resultatMagie) || !array_key_exists('success', $resultatMagie)) {
    error_log('utiliser-magie.php: Magie::lancerSort returned unexpected value: ' . var_export($resultatMagie, true));
    send_json(['erreur' => 'Erreur interne lors du lancement du sort.'], 500);
    mysqli_close($link);
    exit();
}

// Vérifier si le sort a réussi
if (!$resultatMagie['success']) {
    send_json(['erreur' => $resultatMagie['message_lanceur']], 400);
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
    error_log('Erreur de préparation SQL pour l\'UPDATE : ' . mysqli_error($link) . ' (Requête générée: ' . $sql_update_game_state . ')');
    send_json(['erreur' => 'Erreur de préparation SQL pour l\'UPDATE.'], 500);
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

        send_json([
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
        send_json(['erreur' => 'Erreur lors de la mise à jour de l\'état de la partie : Aucune ligne affectée. La partie_id était-elle correcte ?'], 500);
    }
} else {
    error_log('Erreur lors de l\'exécution de l\'UPDATE : ' . mysqli_stmt_error($stmt_update_game_state) . ' (Partie ID: ' . $partie_id . ')');
    send_json(['erreur' => 'Erreur lors de la mise à jour de l\'état de la partie après l\'utilisation de la magie.'], 500);
}

mysqli_stmt_close($stmt_update_game_state);
mysqli_close($link);
} catch (Throwable $e) {
    // Write a concise debug trace to a local log file (helps the developer inspect on the server)
    $logFile = __DIR__ . '/magie-debug.log';
    $debugMsg = date('c') . " - Exception: " . $e->getMessage() . " in " . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString() . "\n";
    @file_put_contents($logFile, $debugMsg, FILE_APPEND);

    // Capture any accidental output that may have been buffered
    $buf = '';
    if (ob_get_level() > 0) {
        $buf = ob_get_contents();
    }
    if ($buf) @file_put_contents($logFile, "BUFFER:" . substr($buf, 0, 2000) . "\n", FILE_APPEND);

    // Send a JSON error with a short debug hint (truncated message) so the client gets something useful
    $short = substr($e->getMessage(), 0, 180);
    send_json(['erreur' => "Erreur interne serveur lors de l'utilisation de la magie. (debug: $short)"], 500);
}
