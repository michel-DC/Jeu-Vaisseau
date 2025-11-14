<?php
session_start();
require_once 'database/db.php';

$partie_id_session = $_SESSION['partie_id'] ?? null;

if (!$partie_id_session) {
    header('Location: choix-joueur.php');
    exit();
}

$link = connexionDB();
$sql = "SELECT statut FROM parties WHERE partie_id = ?";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "s", $partie_id_session);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$partie = mysqli_fetch_assoc($result_partie_status);
mysqli_stmt_close($stmt_partie_status);

if (!$partie || $partie['statut'] !== 'complete') {
    mysqli_close($link);
    header('Location: choix-joueur.php');
    exit();
}

// Fetch game state
$sql_game_state = "SELECT joueur1_hp, joueur2_hp, duree_partie FROM game_state WHERE partie_id = ?";
$stmt_game_state = mysqli_prepare($link, $sql_game_state);
mysqli_stmt_bind_param($stmt_game_state, "s", $partie_id_session);
mysqli_stmt_execute($stmt_game_state);
$result_game_state = mysqli_stmt_get_result($stmt_game_state);
$gameState = mysqli_fetch_assoc($result_game_state);
mysqli_stmt_close($stmt_game_state);
mysqli_close($link);

if (!$gameState) {
    // Handle case where game state is not found (shouldn't happen if creer-partie.php works)
    header('Location: choix-joueur.php');
    exit();
}

$initial_joueur1_hp = $gameState['joueur1_hp'];
$initial_joueur2_hp = $gameState['joueur2_hp'];
$initial_duree_partie = $gameState['duree_partie'];

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Nova Protocol</title>
    <link rel="stylesheet" href="styles/game.css">
    <link rel="stylesheet" href="styles/game-state.css">
    <link rel="shortcut icon" href="assets/logo/image.png" type="image/x-icon">
</head>

<body>

    <div id="game-state-bar">
        <div class="player-hp">
            Joueur 1: <span id="player1-hp"><?php echo $initial_joueur1_hp; ?> HP</span>
        </div>
        <div class="game-timer">
            Temps: <span id="game-timer-value">00:00</span>
        </div>
        <div class="player-hp">
            Joueur 2: <span id="player2-hp"><?php echo $initial_joueur2_hp; ?> HP</span>
        </div>
        <button id="quitter-game-button">Quitter</button>
    </div>

    <div id="game-container">
        <div class="column"></div>
        <div class="column"></div>
        <div class="column"></div>
        <div class="column"></div>
        <div class="column"></div>
        <div class="column"></div>
    </div>

    <script>
        const initialGameState = {
            joueur1Hp: <?php echo $initial_joueur1_hp; ?>,
            joueur2Hp: <?php echo $initial_joueur2_hp; ?>,
            dureePartie: <?php echo $initial_duree_partie; ?>
        };
    </script>
    <script src="scripts/taille-ecran.js" defer></script>
    <script src="scripts/game.js" defer></script>
    <script src="scripts/game-state.js" defer></script>
</body>

</html>
