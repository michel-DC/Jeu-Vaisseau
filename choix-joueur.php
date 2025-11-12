<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Veillez choisir votre joueur...</title>
    <link rel="stylesheet" href="styles/style.css">
    <link rel="shortcut icon" href="images/logo/image.png" type="image/x-icon">
</head>

<body>
    <div class="container">
        <h1>Bienvenue dans le jeu de vaisseaux !</h1>

        <div id="statut-joueurs">
            <p>Statut Joueur 1: <span id="statut-j1">En attente...</span></p>
            <p>Statut Joueur 2: <span id="statut-j2">En attente...</span></p>
        </div>

        <div id="gestion-partie" style="display: none;">
            <button id="quitter-partie">Quitter la partie</button>
        </div>

        <div id="selection-joueur">
            <p>Êtes-vous le Joueur 1 ou le Joueur 2 ?</p>
            <form method="post">
                <button type="submit" name="choix_joueur" value="joueur1">Joueur 1</button>
                <button type="submit" name="choix_joueur" value="joueur2">Joueur 2</button>
            </form>
        </div>

    </div>
    <script src="scripts/choix-joueur.js" defer></script>
    <script src="scripts/taille-ecran.js" defer></script>
</body>

</html>