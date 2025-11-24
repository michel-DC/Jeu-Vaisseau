<?php
session_start();

if (isset($_SESSION['partie_id'])) {
    header('Location: game.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Veuillez choisir votre joueur...</title>
    <link rel="stylesheet" href="styles/choix-joueur.css">
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
            <span class="glitch" data-text="NOVA PROTOCOL">NOVA PROTOCOL</span>
        </h1>

        <div id="choix-initial">
            <h2><span class="bracket">[</span> CRÉER NOUVELLE MISSION <span class="bracket">]</span></h2>
            <form id="form-creer-partie">
                <button type="submit" class="btn-primary">
                    <span class="btn-text">INITIALISER PARTIE</span>
                    <span class="btn-glare"></span>
                </button>
            </form>

            <div class="divider">
                <span class="divider-text">OU</span>
            </div>

            <h2><span class="bracket">[</span> REJOINDRE MISSION <span class="bracket">]</span></h2>
            <form id="form-rejoindre-partie">
                <div class="input-wrapper">
                    <input type="text" id="id-partie-input" placeholder="CODE MISSION" maxlength="10" required>
                    <div class="input-border"></div>
                </div>
                <button type="submit" class="btn-secondary">
                    <span class="btn-text">CONNEXION</span>
                    <span class="btn-glare"></span>
                </button>
            </form>
        </div>

        <div id="salle-attente" style="display: none;">
            <h2><span class="bracket">[</span> MISSION ACTIVE <span class="bracket">]</span></h2>
            <p class="share-text">CODE DE MISSION:</p>
            <div class="id-container">
                <span id="id-partie-affiche" class="mission-code"></span>
                <button id="copier-id" class="btn-copy">
                    <span class="btn-text">COPIER</span>
                </button>
            </div>

            <div id="statut-joueurs">
                <div class="player-status">
                    <span class="player-label">PILOTE 1:</span>
                    <span id="statut-j1" class="status-indicator pending">STANDBY</span>
                </div>
                <div class="player-status">
                    <span class="player-label">PILOTE 2:</span>
                    <span id="statut-j2" class="status-indicator pending">STANDBY</span>
                </div>
            </div>

            <div id="compte-a-rebours-message" style="display: none;"></div>
        </div>

    </div>
    <script src="scripts/gestion-partie.js" defer></script>
    <script src="scripts/taille-ecran.js" defer></script>
</body>

</html>