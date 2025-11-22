<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

$partie_id = $_SESSION['partie_id'] ?? null;
$joueur_role = $_SESSION['joueur_role'] ?? null;

if (!$partie_id || !$joueur_role) {
    echo json_encode(['success' => false, 'error' => 'Session invalide.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$direction = $data['direction'] ?? null;

if ($direction !== 'forward' && $direction !== 'backward') {
    echo json_encode(['success' => false, 'error' => 'Direction invalide.']);
    exit();
}

$link = connexionDB();

// --- Vérification du tour du joueur ---
$joueur_id = $_SESSION['joueur_id'];
$sql_check_turn = "SELECT joueur_actuel FROM game_state WHERE partie_id = ?";
$stmt_check_turn = mysqli_prepare($link, $sql_check_turn);
mysqli_stmt_bind_param($stmt_check_turn, "s", $partie_id);
mysqli_stmt_execute($stmt_check_turn);
$result_turn = mysqli_stmt_get_result($stmt_check_turn);
$game_state_turn = mysqli_fetch_assoc($result_turn);
mysqli_stmt_close($stmt_check_turn);

if (!$game_state_turn || $game_state_turn['joueur_actuel'] !== $joueur_id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Ce n\'est pas votre tour de jouer.']);
    mysqli_close($link);
    exit();
}

// 1. Récupérer la position actuelle
$sql_get_pos = "SELECT joueur1_position, joueur2_position FROM game_state WHERE partie_id = ?";
$stmt_get_pos = mysqli_prepare($link, $sql_get_pos);
mysqli_stmt_bind_param($stmt_get_pos, "s", $partie_id);
mysqli_stmt_execute($stmt_get_pos);
$result = mysqli_stmt_get_result($stmt_get_pos);
$gameState = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt_get_pos);

if (!$gameState) {
    echo json_encode(['success' => false, 'error' => 'État de la partie non trouvé.']);
    mysqli_close($link);
    exit();
}

$pos_column = ($joueur_role === 'joueur1') ? 'joueur1_position' : 'joueur2_position';
$current_pos = $gameState[$pos_column];
$new_pos = $current_pos;

// 2. Calculer la nouvelle position en respectant les règles
if ($direction === 'forward') {
    if ($current_pos < 3) {
        $new_pos = $current_pos + 1;
    }
} elseif ($direction === 'backward') {
    if ($current_pos > 1) {
        $new_pos = $current_pos - 1;
    }
}

// Si la position a changé, on met à jour la BDD
if ($new_pos !== $current_pos) {
    $sql_update_pos = "UPDATE game_state SET $pos_column = ? WHERE partie_id = ?";
    $stmt_update_pos = mysqli_prepare($link, $sql_update_pos);
    mysqli_stmt_bind_param($stmt_update_pos, "is", $new_pos, $partie_id);
    $success = mysqli_stmt_execute($stmt_update_pos);
    mysqli_stmt_close($stmt_update_pos);

    if ($success) {
        // --- Le tour se termine, on passe au joueur suivant ---
        $sql_get_players = "SELECT j.joueur1_id, j.joueur2_id FROM parties j WHERE j.partie_id = ?";
        $stmt_get_players = mysqli_prepare($link, $sql_get_players);
        mysqli_stmt_bind_param($stmt_get_players, "s", $partie_id);
        mysqli_stmt_execute($stmt_get_players);
        $result_players = mysqli_stmt_get_result($stmt_get_players);
        $players = mysqli_fetch_assoc($result_players);
        mysqli_stmt_close($stmt_get_players);

        if ($players) {
            $joueur_suivant = ($joueur_id === $players['joueur1_id']) ? $players['joueur2_id'] : $players['joueur1_id'];
            $sql_update_turn = "UPDATE game_state SET joueur_actuel = ? WHERE partie_id = ?";
            $stmt_update_turn = mysqli_prepare($link, $sql_update_turn);
            mysqli_stmt_bind_param($stmt_update_turn, "ss", $joueur_suivant, $partie_id);
            mysqli_stmt_execute($stmt_update_turn);
            mysqli_stmt_close($stmt_update_turn);
        }
        // --- Fin du changement de tour ---

        // Générer la narration pour le mouvement
        $dir = $direction === 'forward' ? 'avance' : 'recule';
        $narration_msg = "MOVE:{$joueur_role}:{$dir}";
        $sql_narrate = "INSERT INTO narration_events (partie_id, message) VALUES (?, ?)";
        $stmt_narrate = mysqli_prepare($link, $sql_narrate);
        mysqli_stmt_bind_param($stmt_narrate, "ss", $partie_id, $narration_msg);
        mysqli_stmt_execute($stmt_narrate);
        mysqli_stmt_close($stmt_narrate);

        echo json_encode(['success' => true, 'new_position' => $new_pos]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la mise à jour de la position.']);
    }
} else {
    // Mouvement non autorisé (déjà au max ou au min)
    echo json_encode(['success' => false, 'error' => 'Mouvement impossible.', 'current_position' => $current_pos]);
}

mysqli_close($link);
