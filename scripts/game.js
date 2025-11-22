document.addEventListener("DOMContentLoaded", () => {
  const btnForward = document.getElementById("btn-forward");
  const btnBackward = document.getElementById("btn-backward");
  const btnShoot = document.getElementById("btn-shoot"); // Get the shoot button
  const quitterGameButton = document.getElementById("quitter-game-button");
  const gameOverPopup = document.getElementById("game-over-popup");
  const gameOverMessage = document.getElementById("game-over-message");
  const returnMenuButton = document.getElementById("return-menu-button");

  let currentGameState = { ...initialGameState };

  // Helper function to get opponent ID
  function getOpponentId() {
    if (currentGameState.joueurRole === "joueur1") {
      return currentGameState.joueur2Id; // Assuming joueur2Id is available in initialGameState
    } else if (currentGameState.joueurRole === "joueur2") {
      return currentGameState.joueur1Id; // Assuming joueur1Id is available in initialGameState
    }
    return null;
  }

  // Fonction pour déclencher l'animation du faisceau
  function triggerBeamAnimation(attackerShipElement, defenderShipElement) {
    if (!attackerShipElement || !defenderShipElement) {
      console.warn("Impossible de déclencher l'animation du faisceau: éléments de vaisseau manquants.");
      return;
    }

    const gameContainer = document.getElementById("game-container");
    if (!gameContainer) return;

    const attackerRect = attackerShipElement.getBoundingClientRect();
    const defenderRect = defenderShipElement.getBoundingClientRect();
    const gameContainerRect = gameContainer.getBoundingClientRect();

    // Calcul des points de départ et d'arrivée du faisceau (centre des vaisseaux)
    const startX = attackerRect.left + attackerRect.width / 2 - gameContainerRect.left;
    const startY = attackerRect.top + attackerRect.height / 2 - gameContainerRect.top;
    const endX = defenderRect.left + defenderRect.width / 2 - gameContainerRect.left;
    const endY = defenderRect.top + defenderRect.height / 2 - gameContainerRect.top;

    // Calcul de la distance et de l'angle
    const distance = Math.sqrt(Math.pow(endX - startX, 2) + Math.pow(endY - startY, 2));
    const angle = Math.atan2(endY - startY, endX - startX) * 180 / Math.PI;

    const beam = document.createElement("div");
    beam.classList.add("beam");
    beam.style.width = `${distance}px`;
    beam.style.left = `${startX}px`;
    beam.style.top = `${startY}px`;
    beam.style.transform = `rotate(${angle}deg)`;

    gameContainer.appendChild(beam);

    // Supprimer le faisceau après la fin de l'animation
    setTimeout(() => {
      beam.remove();
    }, 500); // Doit correspondre à la durée de l'animation CSS (0.5s)
  }

  function updateShipsDisplay() {
    document.querySelectorAll(".player-ship").forEach((img) => img.remove());

    const {
      joueurRole,
      joueur1Vaisseau,
      joueur2Vaisseau,
      joueur1Position,
      joueur2Position,
      joueur1Id, // Added to pass to getOpponentId correctly
      joueur2Id, // Added to pass to getOpponentId correctly
    } = currentGameState;

    let myVaisseauSrc, opponentVaisseauSrc, myPosition, opponentPosition;

    if (joueurRole === "joueur1") {
      myVaisseauSrc = joueur1Vaisseau;
      myPosition = joueur1Position;
      opponentVaisseauSrc = joueur2Vaisseau;
      opponentPosition = joueur2Position;
      currentGameState.joueur1Id = joueur1Id; // Ensure these are set in currentGameState
      currentGameState.joueur2Id = joueur2Id; // Ensure these are set in currentGameState
    } else {
      myVaisseauSrc = joueur2Vaisseau;
      myPosition = joueur2Position;
      opponentVaisseauSrc = joueur1Vaisseau;
      opponentPosition = joueur1Position;
      currentGameState.joueur1Id = joueur1Id; // Ensure these are set in currentGameState
      currentGameState.joueur2Id = joueur2Id; // Ensure these are set in currentGameState
    }

    const myZoneId = `zone-${myPosition}`;
    const myZone = document.getElementById(myZoneId);
    if (myVaisseauSrc && myZone) {
      const shipImg = document.createElement("img");
      shipImg.src = myVaisseauSrc;
      shipImg.alt = "Mon vaisseau";
      shipImg.classList.add("player-ship");
      shipImg.id = "my-ship";
      myZone.appendChild(shipImg);
    }

    const opponentZoneId = `zone-${7 - opponentPosition}`;
    const opponentZone = document.getElementById(opponentZoneId);
    if (opponentVaisseauSrc && opponentZone) {
      const shipImg = document.createElement("img");
      shipImg.src = opponentVaisseauSrc;
      shipImg.alt = "Vaisseau de l'adversaire";
      shipImg.classList.add("player-ship");
      shipImg.id = "opponent-ship";
      opponentZone.appendChild(shipImg);
    }
  }

  // Fonction pour mettre à jour l'affichage des HP
  function updateHpDisplay() {
    const playerHpYouElement = document.getElementById("player-hp-you");
    const playerHpOtherElement = document.getElementById("player-hp-other");

    if (currentGameState.joueurRole === "joueur1") {
      playerHpYouElement.textContent = `${currentGameState.joueur1Hp} HP`;
      playerHpOtherElement.textContent = `${currentGameState.joueur2Hp} HP`;
    } else {
      playerHpYouElement.textContent = `${currentGameState.joueur2Hp} HP`;
      playerHpOtherElement.textContent = `${currentGameState.joueur1Hp} HP`;
    }
  }

  // Fonction pour mettre à jour l'état des boutons d'action
  function updateActionButtonsState() {
    const isMyTurn = currentGameState.joueurId === currentGameState.joueurActuel;
    const myRole = currentGameState.joueurRole;
    const hasMoved = currentGameState[`${myRole}ABouge`];
    const hasTakenOffensiveAction = currentGameState[`${myRole}ActionFaite`];

    // Disable all buttons by default
    btnForward.disabled = true;
    btnBackward.disabled = true;
    btnShoot.disabled = true;
    // TODO: Add other action buttons here (drone, magic, heal)

    if (isMyTurn) {
      // Movement buttons
      if (!hasMoved) {
        btnForward.disabled = false;
        btnBackward.disabled = false;
      }
      // Offensive action buttons
      if (!hasTakenOffensiveAction) {
        btnShoot.disabled = false;
        // TODO: enable other offensive action buttons here (drone, magic)
      }
      // Non-offensive actions (heal, recharge) might always be available on your turn
      // TODO: Enable heal/recharge buttons here
    }
  }


  function movePlayer(direction) {
    $.ajax({
      url: "api/deplacer-vaisseau.php",
      method: "POST",
      contentType: "application/json",
      data: JSON.stringify({
        direction: direction,
      }),
      dataType: "json",
      success: function (result) {
        if (!result.success) {
          addLocalNarrationEvent(result.error || "Mouvement impossible.");
        }
        // pollGameState will pick up the position update and update button states
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error(
          "Erreur lors de la tentative de déplacement:",
          errorThrown
        );
        addLocalNarrationEvent("Erreur réseau lors du déplacement.");
      },
    });
  }

  function shoot() {
    const attaquantId = currentGameState.joueurId;
    const defenseurId = getOpponentId();
    const partieId = currentGameState.partieId;

    if (!attaquantId || !defenseurId || !partieId) {
      console.error("Impossible de tirer: IDs manquants.");
      addLocalNarrationEvent("Erreur: IDs de joueur ou de partie manquants.");
      return;
    }

    // Temporarily disable the shoot button to prevent multiple clicks
    btnShoot.disabled = true;

    $.ajax({
      url: "api/attaquer-vaisseau.php",
      method: "POST",
      contentType: "application/json",
      data: JSON.stringify({
        partie_id: partieId,
        attaquant_id: attaquantId,
        defenseur_id: defenseurId,
      }),
      dataType: "json",
      success: function (response) {
        console.log("Résultat de l'attaque:", response);
        if (!response.erreur) { // Check for server-side errors
            if (response.message) {
                addLocalNarrationEvent(response.message);
                addNarrationEvent(response.message); // Send to server for persistence
            }

            const attackerShip = document.getElementById("my-ship");
            const defenderShip = document.getElementById("opponent-ship");
            triggerBeamAnimation(attackerShip, defenderShip);
        } else {
            addLocalNarrationEvent(response.erreur);
        }
        // pollGameState will handle button states
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("Erreur lors de l'attaque:", errorThrown);
        addLocalNarrationEvent("Erreur réseau lors de l'attaque.");
        // We can't re-enable shoot button here, as pollGameState will do it
        // Or handle disabling/enabling of buttons on error if turn logic fails
      },
    });
  }

  function pollGameState() {
    $.ajax({
      url: `api/statut-partie.php?partie_id=${currentGameState.partieId}`,
      method: "GET",
      dataType: "json",
      success: function (serverState) {
        if (
          !serverState ||
          serverState.joueur1_position === undefined ||
          serverState.joueur2_position === undefined ||
          serverState.joueur1_hp === undefined || // Check for HP updates
          serverState.joueur2_hp === undefined ||
          serverState.joueur_actuel === undefined || // New: Check for current player
          serverState.joueur1_action_faite === undefined || // New: Check for action flags
          serverState.joueur2_action_faite === undefined ||
          serverState.joueur1_a_bouge === undefined || // New: Check for move flags
          serverState.joueur2_a_bouge === undefined
        ) {
          return; // Données incomplètes
        }

        const newJ1Pos = parseInt(serverState.joueur1_position, 10);
        const newJ2Pos = parseInt(serverState.joueur2_position, 10);
        const newJ1Hp = parseInt(serverState.joueur1_hp, 10);
        const newJ2Hp = parseInt(serverState.joueur2_hp, 10);

        let needsShipDisplayUpdate = false;
        let needsHpDisplayUpdate = false;
        let needsButtonStateUpdate = false;

        if (
          newJ1Pos !== currentGameState.joueur1Position ||
          newJ2Pos !== currentGameState.joueur2Position
        ) {
          currentGameState.joueur1Position = newJ1Pos;
          currentGameState.joueur2Position = newJ2Pos;
          needsShipDisplayUpdate = true;
        }

        if (
          newJ1Hp !== currentGameState.joueur1Hp ||
          newJ2Hp !== currentGameState.joueur2Hp
        ) {
          currentGameState.joueur1Hp = newJ1Hp;
          currentGameState.joueur2Hp = newJ2Hp;
          needsHpDisplayUpdate = true;
        }


        // Update currentGameState with player IDs from serverState if not already present
        if (serverState.joueur1_id && !currentGameState.joueur1Id) {
            currentGameState.joueur1Id = serverState.joueur1_id;
        }
        if (serverState.joueur2_id && !currentGameState.joueur2Id) {
            currentGameState.joueur2Id = serverState.joueur2_id;
        }

        // Update turn-related flags and check if button state needs update
        if (serverState.joueur_actuel !== currentGameState.joueurActuel ||
            serverState.joueur1_action_faite !== currentGameState.joueur1ActionFaite ||
            serverState.joueur2_action_faite !== currentGameState.joueur2ActionFaite ||
            serverState.joueur1_a_bouge !== currentGameState.joueur1ABouge ||
            serverState.joueur2_a_bouge !== currentGameState.joueur2ABouge) {
            
            currentGameState.joueurActuel = serverState.joueur_actuel;
            currentGameState.joueur1ActionFaite = serverState.joueur1_action_faite;
            currentGameState.joueur2ActionFaite = serverState.joueur2_action_faite;
            currentGameState.joueur1ABouge = serverState.joueur1_a_bouge;
            currentGameState.joueur2ABouge = serverState.joueur2_a_bouge;
            needsButtonStateUpdate = true;
        }


        if (needsShipDisplayUpdate) {
          updateShipsDisplay();
        }
        if (needsHpDisplayUpdate) {
            updateHpDisplay(); // <--- CALL IT HERE
            
            // Check for Game Over
            if (currentGameState.joueur1Hp <= 0 || currentGameState.joueur2Hp <= 0) {
                let message = "";
                const myRole = currentGameState.joueurRole;
                
                if (currentGameState.joueur1Hp <= 0 && currentGameState.joueur2Hp <= 0) {
                     message = "Match nul ! Un combat acharné qui finit sans vainqueur.";
                } else if (currentGameState.joueur1Hp <= 0) {
                    if (myRole === 'joueur1') {
                        message = "Dommage... Votre vaisseau a été détruit. Meilleure chance la prochaine fois !";
                    } else {
                        message = "Félicitations ! Vous avez triomphé de votre adversaire.";
                    }
                } else if (currentGameState.joueur2Hp <= 0) {
                    if (myRole === 'joueur2') {
                        message = "Dommage... Votre vaisseau a été détruit. Meilleure chance la prochaine fois !";
                    } else {
                        message = "Félicitations ! Vous avez triomphé de votre adversaire.";
                    }
                }
                
                gameOverMessage.textContent = message;
                gameOverPopup.style.display = "flex";
            }
        }
        if (needsButtonStateUpdate) {
            updateActionButtonsState();
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {},
    });
  }

  btnForward.addEventListener("click", () => movePlayer("forward"));
  btnBackward.addEventListener("click", () => movePlayer("backward"));
  btnShoot.addEventListener("click", shoot); // Add event listener for the shoot button

  if (quitterGameButton) {
    quitterGameButton.addEventListener("click", () => {
      if (confirm("Êtes-vous sûr de vouloir quitter la partie ?")) {
        quitGame();
      }
    });
  }

  if (returnMenuButton) {
      returnMenuButton.addEventListener("click", () => {
          quitGame();
      });
  }

  function quitGame() {
      $.ajax({
          url: "api/quitter-partie.php",
          method: "POST",
          dataType: "json",
          success: function (data) {
            window.location.href = "choix-joueur.php";
          },
          error: function (jqXHR, textStatus, errorThrown) {
            console.error("Erreur réseau (quitter game):", errorThrown);
            // Even if error, try to redirect
            window.location.href = "choix-joueur.php";
          },
        });
  }

  updateShipsDisplay();
  updateHpDisplay(); // Initial call
  // Call pollGameState immediately to get initial player IDs if not in initialGameState
  pollGameState();
  setInterval(pollGameState, 3000);
});