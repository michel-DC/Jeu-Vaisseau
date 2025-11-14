document.addEventListener("DOMContentLoaded", () => {
    const timerElement = document.getElementById("game-timer-value");
    const player1HpElement = document.getElementById("player1-hp");
    const player2HpElement = document.getElementById("player2-hp");
    const quitButton = document.getElementById("quitter-game-button");

    let seconds = initialGameState.dureePartie;
    let timerInterval = null;
    let saveInterval = null;

    function formatTime(totalSeconds) {
        const minutes = Math.floor(totalSeconds / 60)
            .toString()
            .padStart(2, "0");
        const secs = (totalSeconds % 60).toString().padStart(2, "0");
        return `${minutes}:${secs}`;
    }

    function updateTimerDisplay() {
        timerElement.textContent = formatTime(seconds);
    }

    function updateHpDisplay() {
        player1HpElement.textContent = `${initialGameState.joueur1Hp} HP`;
        player2HpElement.textContent = `${initialGameState.joueur2Hp} HP`;
    }

    updateTimerDisplay();
    updateHpDisplay();

    function incrementTimerAndDisplay() {
        seconds++;
        updateTimerDisplay();
    }

    async function saveGameState() {
        try {
            const response = await fetch("api/update-game-state.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: `duree_partie=${seconds}`,
            });
            const result = await response.json();
            if (!result.success) {
                console.error("Erreur lors de la sauvegarde de l'état du jeu:", result.message);
            }
        } catch (error) {
            console.error("Erreur de connexion lors de la sauvegarde:", error);
        }
    }

    if (timerElement) {
        timerInterval = setInterval(incrementTimerAndDisplay, 1000);
        saveInterval = setInterval(saveGameState, 5000);
    }

    if (quitButton) {
        quitButton.addEventListener("click", async () => {
            if (timerInterval) {
                clearInterval(timerInterval);
            }
            if (saveInterval) {
                clearInterval(saveInterval);
            }
            await saveGameState();

            try {
                const response = await fetch("api/quitter-partie.php", {
                    method: "POST",
                });
                const result = await response.json();
                if (result.success) {
                    window.location.href = "index.php";
                } else {
                    console.error(result.message || "Une erreur est survenue.");
                }
            } catch (error) {
                console.error("Erreur de connexion:", error);
            }
        });
    }
});
