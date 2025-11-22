document.addEventListener("DOMContentLoaded", () => {
  const btnForward = document.getElementById("btn-forward");
  const btnBackward = document.getElementById("btn-backward");
  const quitterGameButton = document.getElementById("quitter-game-button");

  let currentGameState = { ...initialGameState };

  function updateShipsDisplay() {
    document.querySelectorAll(".player-ship").forEach((img) => img.remove());

    const {
      joueurRole,
      joueur1Vaisseau,
      joueur2Vaisseau,
      joueur1Position,
      joueur2Position,
    } = currentGameState;

    let myVaisseauSrc, opponentVaisseauSrc, myPosition, opponentPosition;

    if (joueurRole === "joueur1") {
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
        if (!result.success && result.error.includes("Mouvement impossible")) {
          addLocalNarrationEvent("Mouvement impossible.");
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error(
          "Erreur lors de la tentative de déplacement:",
          errorThrown
        );
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
          serverState.joueur2_position === undefined
        ) {
          return; // Données incomplètes
        }

        const newJ1Pos = parseInt(serverState.joueur1_position, 10);
        const newJ2Pos = parseInt(serverState.joueur2_position, 10);

        if (
          newJ1Pos !== currentGameState.joueur1Position ||
          newJ2Pos !== currentGameState.joueur2Position
        ) {
          currentGameState.joueur1Position = newJ1Pos;
          currentGameState.joueur2Position = newJ2Pos;
          updateShipsDisplay();
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {},
    });
  }

  btnForward.addEventListener("click", () => movePlayer("forward"));
  btnBackward.addEventListener("click", () => movePlayer("backward"));

  if (quitterGameButton) {
    quitterGameButton.addEventListener("click", () => {
      if (confirm("Êtes-vous sûr de vouloir quitter la partie ?")) {
        $.ajax({
          url: "api/quitter-partie.php",
          method: "POST",
          dataType: "json",
          success: function (data) {
            alert(data.succes);
            window.location.href = "choix-joueur.php";
          },
          error: function (jqXHR, textStatus, errorThrown) {
            console.error("Erreur réseau (quitter game):", errorThrown);
            alert("Erreur réseau lors de la tentative de quitter la partie.");
          },
        });
      }
    });
  }

  updateShipsDisplay();
  setInterval(pollGameState, 3000);
});
