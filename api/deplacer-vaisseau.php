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

$joueur_id = $_SESSION['joueur_id'];
$joueur_role_db_field = ($joueur_role === 'joueur1') ? 'joueur1' : 'joueur2';

$sql_check_turn_and_move_status = "SELECT joueur_actuel, {$joueur_role_db_field}_a_bouge FROM game_state WHERE partie_id = ?";
$stmt_check = mysqli_prepare($link, $sql_check_turn_and_move_status);
mysqli_stmt_bind_param($stmt_check, "s", $partie_id);
mysqli_stmt_execute($stmt_check);
$result_status = mysqli_stmt_get_result($stmt_check);
$current_game_status = mysqli_fetch_assoc($result_status);
mysqli_stmt_close($stmt_check);

if (!$current_game_status || $current_game_status['joueur_actuel'] !== $joueur_id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Ce n\'est pas votre tour de jouer.']);
    mysqli_close($link);
    exit();
}

if ($current_game_status["{$joueur_role_db_field}_a_bouge"] == 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Vous avez déjà bougé ce tour.']);
    mysqli_close($link);
    exit();
}

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

if ($direction === 'forward') {
    if ($current_pos < 3) {
        $new_pos = $current_pos + 1;
    }
} elseif ($direction === 'backward') {
    if ($current_pos > 1) {
        $new_pos = $current_pos - 1;
    }
}

if ($new_pos !== $current_pos) {
    $sql_update_pos = "UPDATE game_state SET $pos_column = ?, {$joueur_role_db_field}_a_bouge = 1 WHERE partie_id = ?";
    $stmt_update_pos = mysqli_prepare($link, $sql_update_pos);
    mysqli_stmt_bind_param($stmt_update_pos, "is", $new_pos, $partie_id);
    $success = mysqli_stmt_execute($stmt_update_pos);
    mysqli_stmt_close($stmt_update_pos);

    if ($success) {
        $dir = $direction === 'forward' ? 'avance' : 'recule';
        $narration_msg = "MOVE:{$joueur_role}:Vous {$dir}z.";
        $sql_narrate = "INSERT INTO narration_events (partie_id, message) VALUES (?, ?)";
        $stmt_narrate = mysqli_prepare($link, $sql_narrate);
        mysqli_stmt_bind_param($stmt_narrate, "ss", $partie_id, $narration_msg);
        mysqli_stmt_execute($stmt_narrate);
        mysqli_stmt_close($stmt_narrate);

        $sql_get_ids = "SELECT joueur1_id, joueur2_id FROM parties WHERE partie_id = ?";
        $stmt_ids = mysqli_prepare($link, $sql_get_ids);
        mysqli_stmt_bind_param($stmt_ids, "s", $partie_id);
        mysqli_stmt_execute($stmt_ids);
        $result_ids = mysqli_stmt_get_result($stmt_ids);
        $ids_data = mysqli_fetch_assoc($result_ids);
        mysqli_stmt_close($stmt_ids);

        $joueur_suivant_id = ($ids_data['joueur1_id'] === $joueur_id) ? $ids_data['joueur2_id'] : $ids_data['joueur1_id'];

        $sql_update_turn = "UPDATE game_state SET joueur_actuel = ?, joueur1_action_faite = 0, joueur2_action_faite = 0, joueur1_a_bouge = 0, joueur2_a_bouge = 0 WHERE partie_id = ?";
        $stmt_update_turn = mysqli_prepare($link, $sql_update_turn);
        mysqli_stmt_bind_param($stmt_update_turn, "ss", $joueur_suivant_id, $partie_id);
        $turn_update_success = mysqli_stmt_execute($stmt_update_turn);
        mysqli_stmt_close($stmt_update_turn);

        if ($turn_update_success) {
             echo json_encode(['success' => true, 'new_position' => $new_pos, 'a_bouge' => 1, 'tour_change' => true]);
        } else {
             echo json_encode(['success' => true, 'new_position' => $new_pos, 'a_bouge' => 1, 'error' => 'Mouvement OK mais erreur changement de tour.']);
        }

    } else {
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la mise à jour de la position.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Mouvement impossible.', 'current_position' => $current_pos, 'a_bouge' => $current_game_status["{$joueur_role_db_field}_a_bouge"]]);
}

mysqli_close($link);
