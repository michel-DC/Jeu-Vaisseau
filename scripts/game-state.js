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

  // Create player HP display elements
  const playerHpDivYou = document.createElement("div");
  playerHpDivYou.className = "player-hp";
  const playerHpSpanYou = document.createElement("span");
  playerHpSpanYou.id = "player-hp-you";
  playerHpDivYou.innerHTML = `Vous: `;
  playerHpDivYou.appendChild(playerHpSpanYou);

  const playerHpDivOther = document.createElement("div");
  playerHpDivOther.className = "player-hp";
  const playerHpSpanOther = document.createElement("span");
  playerHpSpanOther.id = "player-hp-other";
  playerHpDivOther.innerHTML = `L'autre: `;
  playerHpDivOther.appendChild(playerHpSpanOther);

  // Clear existing player HP elements from game-state-bar
  const existingPlayerHpElements = gameStateBar.querySelectorAll(".player-hp");
  existingPlayerHpElements.forEach((el) => el.remove());

  // Insert elements in correct order
  if (initialGameState.joueurRole === "joueur1") {
    gameStateBar.insertBefore(playerHpDivYou, gameStateBar.children[0]);
    gameStateBar.insertBefore(playerHpDivOther, gameStateBar.children[1]);
  } else {
    gameStateBar.insertBefore(playerHpDivOther, gameStateBar.children[0]);
    gameStateBar.insertBefore(playerHpDivYou, gameStateBar.children[1]);
  }

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
    if (initialGameState.joueurRole === "joueur1") {
      playerHpSpanYou.textContent = `${initialGameState.joueur1Hp} HP`;
      playerHpSpanOther.textContent = `${initialGameState.joueur2Hp} HP`;
    } else {
      playerHpSpanYou.textContent = `${initialGameState.joueur2Hp} HP`;
      playerHpSpanOther.textContent = `${initialGameState.joueur1Hp} HP`;
    }
  }

  updateTimerDisplay();
  updateHpDisplay();

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
        initialGameState.joueur1Hp = result.joueur1_hp;
        initialGameState.joueur2Hp = result.joueur2_hp;
        initialGameState.dureePartie = result.duree_partie;
        seconds = result.duree_partie;
        updateHpDisplay();
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
