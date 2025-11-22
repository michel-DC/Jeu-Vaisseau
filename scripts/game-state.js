document.addEventListener("DOMContentLoaded", () => {
  const gameStateBar = document.getElementById("game-state-bar");
  const timerElement = document.getElementById("game-timer-value");
  const quitButton = document.getElementById("quitter-game-button");

  let seconds = initialGameState.dureePartie;
  let timerInterval = null;
  let saveInterval = null;
  let fetchStateInterval = null;
  let coinFlipHasRun = false;

  const turnStatusDiv = document.createElement("div");
  turnStatusDiv.id = "turn-status";
  turnStatusDiv.className = "turn-status";
  gameStateBar.insertBefore(turnStatusDiv, gameStateBar.children[0]);

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

  updateTimerDisplay();

  function incrementTimerAndDisplay() {
    seconds++;
    updateTimerDisplay();
  }

  function saveGameState() {
    $.ajax({
      url: "api/update-game-state.php",
      method: "POST",
      data: { duree_partie: seconds },
      dataType: "json",
      success: function (result) {
        if (!result.success) {
          console.error(
            "Erreur lors de la sauvegarde de l'état du jeu:",
            result.message
          );
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error(
          "Erreur de connexion lors de la sauvegarde:",
          errorThrown
        );
      },
    });
  }

  function fetchGameState() {
    $.ajax({
      url: `api/statut-partie.php?partie_id=${initialGameState.partieId}`,
      method: "GET",
      dataType: "json",
      success: function (result) {
        if (result.erreur) {
          console.error(
            "Erreur lors de la récupération de l'état du jeu:",
            result.erreur
          );
          return;
        }
        // HP updates are handled by game.js's pollGameState
        initialGameState.dureePartie = result.duree_partie;
        seconds = result.duree_partie;
        updateTimerDisplay();

        const localPlayerId = initialGameState.joueurId;
        const isMyTurn = result.joueur_actuel === localPlayerId;

        const actionButtons = document.querySelectorAll(".action-button");
        const turnStatusElement = document.getElementById("turn-status");

        actionButtons.forEach((button) => {
          button.disabled = !isMyTurn;
        });

        if (isMyTurn) {
          turnStatusElement.textContent = "C'est votre tour";
          document.body.classList.add("active-turn");
          document.body.classList.remove("waiting-turn");
        } else {
          turnStatusElement.textContent = "Tour de l'adversaire";
          document.body.classList.add("waiting-turn");
          document.body.classList.remove("active-turn");
        }

        if (result.premier_joueur && !coinFlipHasRun) {
          coinFlipHasRun = true;
          const iStart = result.premier_joueur === localPlayerId;
          runCoinFlipAnimation(iStart);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error(
          "Erreur de connexion lors de la récupération de l'état du jeu:",
          errorThrown
        );
      },
    });
  }

  if (timerElement) {
    timerInterval = setInterval(incrementTimerAndDisplay, 1000);
    saveInterval = setInterval(saveGameState, 5000);
    fetchStateInterval = setInterval(fetchGameState, 2000); // Fetch game state every 2 seconds
  }

  if (quitButton) {
    quitButton.addEventListener("click", () => {
      if (timerInterval) {
        clearInterval(timerInterval);
      }
      if (saveInterval) {
        clearInterval(saveInterval);
      }
      if (fetchStateInterval) {
        clearInterval(fetchStateInterval);
      }
      saveGameState();

      $.ajax({
        url: "api/quitter-partie.php",
        method: "POST",
        dataType: "json",
        success: function (result) {
          if (result.success) {
            window.location.href = "index.php";
          } else {
            console.error(result.message || "Une erreur est survenue.");
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.error("Erreur de connexion:", errorThrown);
        },
      });
    });
  }
});
