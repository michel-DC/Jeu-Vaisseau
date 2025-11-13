<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Bienvenue sur Nova Protocol</title>
    <link rel="stylesheet" href="styles/style.css">
    <link rel="shortcut icon" href="assets/logo/image.png" type="image/x-icon">
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
            overflow: hidden;
            background: black;
        }

        #myVideo {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            object-fit: contain;
            background: black;
        }

        .start-title {
            font-size: 8rem;
            color: white;
        }

        .launch-button {
            background-color: #ffffff;
            color: #000000;
            border: none;
            padding: 15px 30px;
            font-size: 1.5rem;
            border-radius: 50px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 20px
        }

        .launch-button:hover {
            background-color: #b9b9b9ff;
            color: #000000ff;
        }
    </style>
</head>

<body>
    <video autoplay muted loop id="myVideo">
        <source src="assets/video/space.mp4" type="video/mp4">
    </video>

    <audio id="background-audio" src="assets/audio/space-sound.mp3" autoplay loop muted></audio>

    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10; text-align: center; width: 100%;">
        <p style="color: white; font-size: 1.8rem;">Bienvenue sur:</p>
        <h1 class="start-title">Nova Protocol</h1>
        <button id="launchGameButton" class="launch-button">Lancer le jeu</button>
    </div>

    <script src="scripts/choix-joueur.js" defer></script>
    <script src="scripts/taille-ecran.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var audio = document.getElementById('background-audio');
            var launchButton = document.getElementById('launchGameButton');

            function playAndUnmuteAudio() {
                audio.muted = false;
                var playPromise = audio.play();
                if (playPromise !== undefined) {
                    playPromise.catch(function(error) {
                        console.error("Échec de la lecture audio sur interaction utilisateur:", error);
                    });
                }
            }

            var initialPlayPromise = audio.play();
            if (initialPlayPromise !== undefined) {
                initialPlayPromise.then(function() {
                    console.log("Lecture audio automatique démarrée (muette).");
                }).catch(function(error) {
                    console.error("Échec de la lecture audio automatique:", error);
                });
            }

            launchButton.addEventListener('click', function() {
                playAndUnmuteAudio();
                window.location.href = 'choix-joueur.php';
            });
        });
    </script>
</body>

</html>