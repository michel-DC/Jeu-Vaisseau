document.addEventListener("DOMContentLoaded", () => {
    const quitterGameButton = document.getElementById("quitter-game-button");
    const gameContainer = document.getElementById("game-container");

    // Afficher les vaisseaux choisis en fonction du rôle du joueur
    const myVaisseauSrc = initialGameState.joueurRole === 'joueur1' ? initialGameState.joueur1Vaisseau : initialGameState.joueur2Vaisseau;
    const opponentVaisseauSrc = initialGameState.joueurRole === 'joueur1' ? initialGameState.joueur2Vaisseau : initialGameState.joueur1Vaisseau;

    if (myVaisseauSrc && gameContainer) {
        const myShipImg = document.createElement('img');
        myShipImg.src = myVaisseauSrc;
        myShipImg.alt = "Mon vaisseau";
        myShipImg.id = "my-ship";
        myShipImg.classList.add("player-ship");
        gameContainer.appendChild(myShipImg);
    }

    if (opponentVaisseauSrc && gameContainer) {
        const opponentShipImg = document.createElement('img');
        opponentShipImg.src = opponentVaisseauSrc;
        opponentShipImg.alt = "Vaisseau de l'adversaire";
        opponentShipImg.id = "opponent-ship";
        opponentShipImg.classList.add("player-ship");
        gameContainer.appendChild(opponentShipImg);
    }


    if (quitterGameButton) {
        quitterGameButton.addEventListener("click", async () => {
            if (confirm("Êtes-vous sûr de vouloir quitter la partie ?")) {
                try {
                    const response = await fetch("api/quitter-partie.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    });

                    const data = await response.json();
                    if (response.ok) {
                        alert(data.succes);
                        window.location.href = "choix-joueur.php"; // Rediriger vers la page de sélection
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
});
