<?php
session_start();

if (!isset($_SESSION['partie_id'])) {
    header('Location: choix-joueur.php');
    exit();
}

$vaisseaux_dir = 'assets/vaisseaux/';
$vaisseaux_files = array_values(array_diff(scandir($vaisseaux_dir), array('..', '.')));
$vaisseaux_paths = array_map(function ($file) use ($vaisseaux_dir) {
    return $vaisseaux_dir . $file;
}, $vaisseaux_files);

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Choix du Vaisseau - Nova Protocol</title>
    <link rel="stylesheet" href="styles/choix-vaisseau.css">
    <link rel="shortcut icon" href="assets/logo/image.png" type="image/x-icon">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>

<body>
    <div class="stars"></div>
    <div class="stars2"></div>
    <div class="stars3"></div>
    <div class="container">
        <h1 class="title">Besoin d'un vaisseau ?</h1>

        <div id="selection-vaisseau">
            <div class="carousel-container">
                <button id="prev-vaisseau" class="nav-button">&#10094;</button>
                <img src="" alt="Vaisseau" id="vaisseau-image" class="vaisseau-image">
                <button id="next-vaisseau" class="nav-button">&#10095;</button>
            </div>
            <button id="valider-choix" class="valider-button">Valider le choix</button>
        </div>


        <div id="salle-attente-vaisseau" style="display: none;">
            <h2>En attente de l'autre joueur...</h2>
            <p>Votre adversaire est en train de choisir son vaisseau.</p>
            <div class="loader"></div>
        </div>

    </div>

    <script>
        const vaisseaux = <?php echo json_encode($vaisseaux_paths); ?>;
    </script>
    <script src="scripts/choix-vaisseau.js" defer></script>
</body>

</html>