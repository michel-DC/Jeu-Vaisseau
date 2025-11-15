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
if (!$result) {
    error_log("Erreur: " . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
    mysqli_close($link);
    header('Location: choix-joueur.php');
    exit();
}
$partie = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

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
if (!$result_game_state) {
    error_log("Erreur: " . mysqli_stmt_error($stmt_game_state));
    mysqli_stmt_close($stmt_game_state);
    mysqli_close($link);
    header('Location: choix-joueur.php');
    exit();
}
$gameState = mysqli_fetch_assoc($result_game_state);
mysqli_stmt_close($stmt_game_state);
mysqli_close($link);

if (!$gameState) {
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
        <div class="game-timer">
            Durée: <span id="game-timer-value">00:00</span>
        </div>
        <button id="quitter-game-button">Quitter</button>
    </div>

    <div id="game-container">
        <div class="column zone-starry">
            <div class="stars"></div>
            <div class="stars2"></div>
            <div class="stars3"></div>
        </div>
        <div class="column zone-starry">
            <div class="stars"></div>
            <div class="stars2"></div>
            <div class="stars3"></div>
        </div>
        <div class="column zone-starry">
            <div class="stars"></div>
            <div class="stars2"></div>
            <div class="stars3"></div>
        </div>
        <div class="column zone-starry">
            <div class="stars"></div>
            <div class="stars2"></div>
            <div class="stars3"></div>
        </div>
        <div class="column zone-starry">
            <div class="stars"></div>
            <div class="stars2"></div>
            <div class="stars3"></div>
        </div>
        <div class="column zone-starry">
            <div class="stars"></div>
            <div class="stars2"></div>
            <div class="stars3"></div>
            yannco
        </div>
    </div>

    <script>
        const initialGameState = {
            joueur1Hp: <?php echo $initial_joueur1_hp; ?>,
            joueur2Hp: <?php echo $initial_joueur2_hp; ?>,
            dureePartie: <?php echo $initial_duree_partie; ?>,
            joueurRole: '<?php echo $_SESSION['joueur_role']; ?>',
            partieId: '<?php echo $partie_id_session; ?>'
        };
    </script>
    <script src="scripts/taille-ecran.js" defer></script>
    <script src="scripts/game.js" defer></script>
    <script src="scripts/game-state.js" defer></script>
</body>

</html>