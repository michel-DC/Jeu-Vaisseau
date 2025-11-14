document.addEventListener("DOMContentLoaded", () => {
    const quitterGameButton = document.getElementById("quitter-game-button");

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
