document.addEventListener("DOMContentLoaded", () => {
    const quitterGameButton = document.getElementById("quitter-game-button");
    const conteneurVaisseau1 = document.getElementById("vaisseau-joueur1");
    const conteneurVaisseau2 = document.getElementById("vaisseau-joueur2");

    // Afficher les vaisseaux choisis
    if (initialGameState.joueur1Vaisseau && conteneurVaisseau1) {
        const img1 = document.createElement('img');
        img1.src = initialGameState.joueur1Vaisseau;
        img1.alt = "Vaisseau du joueur 1";
        conteneurVaisseau1.appendChild(img1);
    }

    if (initialGameState.joueur2Vaisseau && conteneurVaisseau2) {
        const img2 = document.createElement('img');
        img2.src = initialGameState.joueur2Vaisseau;
        img2.alt = "Vaisseau du joueur 2";
        conteneurVaisseau2.appendChild(img2);
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
