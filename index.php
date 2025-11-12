<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Le jeu de michou</title>
    <link rel="stylesheet" href="styles/style.css">
    <link rel="shortcut icon" href="images/logo/image.png" type="image/x-icon">
    <style>
        body {
            margin: 0;
            padding: 0;
            background-image: url('assets/images/landing-screen.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
            position: relative;
        }

        .launch-button {
            position: absolute;
            top: 66%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 220px;
            height: 65px;
            display: block;
            border-radius: 35px;
            background: transparent;
            cursor: pointer;
            z-index: 10;
        }
    </style>
</head>

<body>
    <a href="choix-joueur.php" class="launch-button"></a>
    <script src="scripts/choix-joueur.js" defer></script>
    <script src="scripts/taille-ecran.js" defer></script>
</body>

</html>