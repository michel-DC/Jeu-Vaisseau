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
$sql_game_state = "SELECT joueur1_hp, joueur2_hp, duree_partie, joueur1_choix_vaisseau, joueur2_choix_vaisseau FROM game_state WHERE partie_id = ?";
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
    // Si l'état du jeu n'existe pas, c'est une erreur critique.
    header('Location: choix-joueur.php');
    exit();
}

// Vérification que les deux joueurs ont choisi leur vaisseau
if (empty($gameState['joueur1_choix_vaisseau']) || empty($gameState['joueur2_choix_vaisseau'])) {
    // Rediriger vers la page de choix si un des choix est manquant
    header('Location: choix-vaisseau.php');
    exit();
}


$initial_joueur1_hp = $gameState['joueur1_hp'];
$initial_joueur2_hp = $gameState['joueur2_hp'];
$initial_duree_partie = $gameState['duree_partie'];
$joueur1_vaisseau = $gameState['joueur1_choix_vaisseau'];
$joueur2_vaisseau = $gameState['joueur2_choix_vaisseau'];

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Nova Protocol</title>
    <link rel="stylesheet" href="styles/game.css">
    <link rel="stylesheet" href="styles/game-state.css">
    <link rel="stylesheet" href="styles/coin-flip-popup.css">
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

    <!-- Popup Pile ou Face -->
    <div id="coin-flip-popup" class="popup-overlay" style="display: none;">
        <div class="popup-content">
            <h2>Pile ou Face</h2>
            <div id="player-assignments">
                <p>Vous: <span id="my-side"></span></p>
                <p>Adversaire: <span id="opponent-side"></span></p>
            </div>
            <div id="coin-container">
                <div id="coin">
                    <div class="side-a"></div> <!-- Pile -->
                    <div class="side-b"></div> <!-- Face -->
                </div>
            </div>
            <p id="flip-result"></p>
        </div>
    </div>

    <!-- Popup Résultat du Tour -->
    <div id="turn-result-popup" class="popup-overlay" style="display: none;">
        <div class="popup-content">
            <h2 id="turn-result-title"></h2>
            <button id="close-turn-popup">Compris</button>
        </div>
    </div>

    <script>
        const initialGameState = {
            joueur1Hp: <?php echo $initial_joueur1_hp; ?>,
            joueur2Hp: <?php echo $initial_joueur2_hp; ?>,
            dureePartie: <?php echo $initial_duree_partie; ?>,
            joueur1Vaisseau: '<?php echo $joueur1_vaisseau; ?>',
            joueur2Vaisseau: '<?php echo $joueur2_vaisseau; ?>',
            joueurRole: '<?php echo $_SESSION['joueur_role']; ?>',
            partieId: '<?php echo $partie_id_session; ?>'
        };
    </script>
    <script src="scripts/taille-ecran.js" defer></script>
    <script src="scripts/game.js" defer></script>
    <script src="scripts/game-state.js" defer></script>
    <script src="scripts/coin-flip.js" defer></script>
</body>

</html>