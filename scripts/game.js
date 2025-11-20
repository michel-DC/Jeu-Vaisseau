document.addEventListener("DOMContentLoaded", () => {
    const btnForward = document.getElementById('btn-forward');
    const btnBackward = document.getElementById('btn-backward');
    const quitterGameButton = document.getElementById("quitter-game-button");

    let currentGameState = { ...initialGameState };

    function updateShipsDisplay() {
        document.querySelectorAll('.player-ship').forEach(img => img.remove());

        const {
            joueurRole,
            joueur1Vaisseau,
            joueur2Vaisseau,
            joueur1Position,
            joueur2Position
        } = currentGameState;

        let myVaisseauSrc, opponentVaisseauSrc, myPosition, opponentPosition;

        if (joueurRole === 'joueur1') {
            myVaisseauSrc = joueur1Vaisseau;
            myPosition = joueur1Position;
            opponentVaisseauSrc = joueur2Vaisseau;
            opponentPosition = joueur2Position;
        } else {
            myVaisseauSrc = joueur2Vaisseau;
            myPosition = joueur2Position;
            opponentVaisseauSrc = joueur1Vaisseau;
            opponentPosition = joueur1Position;
        }

        const myZoneId = `zone-${myPosition}`;
        const myZone = document.getElementById(myZoneId);
        if (myVaisseauSrc && myZone) {
            const shipImg = document.createElement('img');
            shipImg.src = myVaisseauSrc;
            shipImg.alt = "Mon vaisseau";
            shipImg.classList.add("player-ship");
            shipImg.id = "my-ship";
            myZone.appendChild(shipImg);
        }

        const opponentZoneId = `zone-${7 - opponentPosition}`;
        const opponentZone = document.getElementById(opponentZoneId);
        if (opponentVaisseauSrc && opponentZone) {
            const shipImg = document.createElement('img');
            shipImg.src = opponentVaisseauSrc;
            shipImg.alt = "Vaisseau de l'adversaire";
            shipImg.classList.add("player-ship");
            shipImg.id = "opponent-ship";
            opponentZone.appendChild(shipImg);
        }
    }

    async function movePlayer(direction) {
        try {
            const response = await fetch('api/deplacer-vaisseau.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    direction: direction
                })
            });

            const result = await response.json();

            if (result.success) {
                if (currentGameState.joueurRole === 'joueur1') {
                    currentGameState.joueur1Position = result.new_position;
                } else {
                    currentGameState.joueur2Position = result.new_position;
                }
                updateShipsDisplay();
            }
        } catch (error) {
            console.error('Erreur lors de la tentative de déplacement:', error);
        }
    }

    async function pollGameState() {
        try {
            const response = await fetch(`api/statut-partie.php?partie_id=${currentGameState.partieId}`);
            if (!response.ok) {
                return;
            }
            const serverState = await response.json();

            const hasPositionChanged = serverState.joueur1_position !== currentGameState.joueur1Position ||
                serverState.joueur2_position !== currentGameState.joueur2Position;

            if (hasPositionChanged) {
                currentGameState.joueur1Position = parseInt(serverState.joueur1_position, 10);
                currentGameState.joueur2Position = parseInt(serverState.joueur2_position, 10);
                updateShipsDisplay();
            }
        } catch (error) {
            console.error("Erreur lors du polling de l'état du jeu:", error);
        }
    }

    btnForward.addEventListener('click', () => movePlayer('forward'));
    btnBackward.addEventListener('click', () => movePlayer('backward'));

    if (quitterGameButton) {
        quitterGameButton.addEventListener("click", async () => {
            if (confirm("Êtes-vous sûr de vouloir quitter la partie ?")) {
                try {
                    const response = await fetch("api/quitter-partie.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                    });

                    const data = await response.json();
                    if (response.ok) {
                        alert(data.succes);
                        window.location.href = "choix-joueur.php";
                    } else {
                        alert(`Erreur: ${data.erreur}`);
                    }
                } catch (error) {
                    console.error("Erreur réseau (quitter game):", error);
                    alert("Erreur réseau lors de la tentative de quitter la partie.");
                }
            }
        });
    }

    updateShipsDisplay();
    setInterval(pollGameState, 3000);
});