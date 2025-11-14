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
$partie = mysqli_fetch_assoc($result);
mysqli_close($link);

// Vérifier si la partie est bien "complète"
if (!$partie || $partie['statut'] !== 'complete') {
    header('Location: choix-joueur.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Nova Protocol</title>
    <link rel="stylesheet" href="styles/style.css">
    <link rel="shortcut icon" href="assets/logo/image.png" type="image/x-icon">
</head>

<body>
    <h1>Partie en cours...</h1>
    <p>ID de la partie : <?php echo htmlspecialchars($partie_id_session); ?></p>
    
    <button id="quitter-game-button">Quitter la partie</button>

    <script src="scripts/taille-ecran.js" defer></script>
    <script src="scripts/game.js" defer></script>
</body>

</html>
