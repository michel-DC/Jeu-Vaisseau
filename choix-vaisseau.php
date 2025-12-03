<?php
session_start();

if (!isset($_SESSION['partie_id'])) {
    header('Location: choix-joueur.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Choix du Vaisseau - Nova Protocol</title>
    <link rel="stylesheet" href="styles/choix-vaisseau.css">
    <link rel="shortcut icon" href="assets/logo/image.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>

<body>
    <div class="scanline"></div>
    <div class="container">
        <h1 class="title">
            <span class="glitch" data-text="SÉLECTION VAISSEAU">SÉLECTION VAISSEAU</span>
        </h1>

        <div id="selection-vaisseau" class="selection-layout">
            <div class="carousel-section">
                <div class="carousel-container">
                    <button id="prev-vaisseau" class="nav-button">&#10094;</button>
                    <img src="" alt="Vaisseau" id="vaisseau-image" class="vaisseau-image">
                    <button id="next-vaisseau" class="nav-button">&#10095;</button>
                </div>
            </div>
            
            <div class="details-section">
                <h2 id="vaisseau-nom" class="vaisseau-nom"></h2>
                <div class="description-container">
                    <p id="vaisseau-description" class="vaisseau-description"></p>
                </div>
                <button id="valider-choix" class="btn-primary">
                    <span class="btn-text">CONFIRMER SÉLECTION</span>
                    <span class="btn-glare"></span>
                </button>
            </div>
        </div>


        <div id="salle-attente-vaisseau" style="display: none;">
            <h2>En attente de l'autre joueur...</h2>
            <p>Votre adversaire est en train de choisir son vaisseau.</p>
            <div class="loader"></div>
        </div>

    </div>

    <script src="scripts/choix-vaisseau.js" defer></script>
</body>

</html>