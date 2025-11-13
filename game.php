<?php
require_once 'database/db.php';

$link = connexionDB();
$sql = "SELECT joueur1_pret, joueur2_pret FROM partie_actuelle WHERE id = 1";
$result = mysqli_query($link, $sql);
$partie = mysqli_fetch_assoc($result);
mysqli_close($link);

if (!$partie || $partie['joueur1_pret'] != 1 || $partie['joueur2_pret'] != 1) {
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
    <style>

    </style>
</head>

<body>
    <a href="choix-joueur.php" class="launch-button"></a>
    <script src="scripts/taille-ecran.js" defer></script>
</body>

</html>