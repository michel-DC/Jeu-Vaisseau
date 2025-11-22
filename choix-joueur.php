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
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>

<body>
    <div class="stars"></div>
    <div class="stars2"></div>
    <div class="stars3"></div>
    <div class="container">
        <h1 class="title">Nova Protocol </h1>

        <div id="choix-initial">
            <h2>Créer une nouvelle partie</h2>
            <form id="form-creer-partie">
                <button type="submit">Créer une partie</button>
            </form>

            <hr>

            <h2>Rejoindre une partie existante</h2>
            <form id="form-rejoindre-partie">
                <input type="text" id="id-partie-input" placeholder="Entrez l'ID de la partie" maxlength="10" required>
                <button type="submit">Rejoindre</button>
            </form>
        </div>

        <div id="salle-attente" style="display: none;">
            <h2>Votre partie est prête !</h2>
            <p>Partagez cet ID avec votre ami :</p>
            <div class="id-container">
                <span id="id-partie-affiche"></span>
                <button id="copier-id">Copier l'ID</button>
            </div>

            <div id="statut-joueurs">
                <p>Statut Joueur 1: <span id="statut-j1">En attente...</span></p>
                <p>Statut Joueur 2: <span id="statut-j2">En attente...</span></p>
            </div>

            <div id="compte-a-rebours-message" style="display: none;"></div>
        </div>

    </div>
    <script src="scripts/gestion-partie.js" defer></script>
    <script src="scripts/taille-ecran.js" defer></script>
</body>

</html>