document.addEventListener("DOMContentLoaded", () => {
  const btnForward = document.getElementById("btn-forward");
  const btnBackward = document.getElementById("btn-backward");
  const btnShoot = document.getElementById("btn-shoot");
  const btnMagic = document.getElementById("btn-magic");
  const btnRecharge = document.getElementById("btn-recharge");
  const btnAbandon = document.getElementById("btn-abandon");
  const quitterGameButton = document.getElementById("quitter-game-button");
  const gameOverPopup = document.getElementById("game-over-popup");
  const gameOverMessage = document.getElementById("game-over-message");
  const returnMenuButton = document.getElementById("return-menu-button");

  let currentGameState = { ...initialGameState };
  console.log("Initial Game State:", currentGameState);

  // Audio SFX — keep file names in assets/audio. Files can be added later.
  const sfx = {
    laserWeak: new Audio('assets/audio/laser-weak.mp3'),
    laserStrong: new Audio('assets/audio/laser-strong.mp3'),
    droneAttack: new Audio('assets/audio/drone-attack.mp3'),
    droneRecon: new Audio('assets/audio/drone-recon.mp3'),
    move: new Audio('assets/audio/move.mp3'),
    recharge: new Audio('assets/audio/recharge.mp3'),
    magic: new Audio('assets/audio/magic.mp3'),
    paralysie: new Audio('assets/audio/paralysie.mp3'),
    error: new Audio('assets/audio/error.mp3')
  };

  // Preload SFX where possible
  Object.values(sfx).forEach(a => { a.preload = 'auto'; a.volume = 0.9; });

  // Helper function to get opponent ID
  function getOpponentId() {
    if (currentGameState.joueurRole === "joueur1") {
      return currentGameState.joueur2Id; // Assuming joueur2Id is available in initialGameState
    } else if (currentGameState.joueurRole === "joueur2") {
      return currentGameState.joueur1Id; // Assuming joueur1Id is available in initialGameState
    }
    return null;
  }

  // Global AJAX error logger (helps diagnose unexpected HTML/500 responses)
  $(document).ajaxError(function (event, jqXHR, ajaxSettings, thrownError) {
    try {
      console.error('AJAX error:', ajaxSettings.url, jqXHR.status, thrownError);
      console.error('Response text (truncated):', (jqXHR.responseText || '').slice(0, 1200));
    } catch (e) {
      console.error('Error while logging AJAX error', e);
    }
  });

  // Fonction pour déclencher l'animation du faisceau
  function triggerBeamAnimation(attackerShipElement, defenderShipElement, damage) {
    if (!attackerShipElement || !defenderShipElement) {
      console.warn(
        "Impossible de déclencher l'animation du faisceau: éléments de vaisseau manquants."
      );
      return;
    }

    const gameContainer = document.getElementById("game-container");
    if (!gameContainer) return;

    const attackerRect = attackerShipElement.getBoundingClientRect();
    const defenderRect = defenderShipElement.getBoundingClientRect();
    const gameContainerRect = gameContainer.getBoundingClientRect();

    // Calcul des points de départ et d'arrivée du faisceau (centre des vaisseaux)
    const startX =
      attackerRect.left + attackerRect.width / 2 - gameContainerRect.left;
    const startY =
      attackerRect.top + attackerRect.height / 2 - gameContainerRect.top;
    const endX =
      defenderRect.left + defenderRect.width / 2 - gameContainerRect.left;
    const endY =
      defenderRect.top + defenderRect.height / 2 - gameContainerRect.top;

    // Calcul de la distance et de l'angle
    const distance = Math.sqrt(
      Math.pow(endX - startX, 2) + Math.pow(endY - startY, 2)
    );
    const angle = (Math.atan2(endY - startY, endX - startX) * 180) / Math.PI;

    const beam = document.createElement("div");
    beam.classList.add("beam");
    // Apply strong laser class if damage > 100
    if (damage > 100) {
      beam.classList.add("strong");
    }
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

  // Create effect icon element for given effect type
  function createEffectIcon(effect) {
    const type = effect && effect.type ? effect.type : effect;
    const el = document.createElement('div');
    el.className = 'effect-icon';
    let title = '';
    let svg = '';
    switch (type) {
      case 'paralysie':
        title = 'Paralysie — chance de rater votre action ce tour.';
        svg = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2v6" stroke="#ffea00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 9l7 7 7-7" stroke="#ffea00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 20h16" stroke="#ffffff" stroke-opacity="0.15" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        break;
      case 'poison':
        title = 'Poison — inflige des dégâts en début de tour.';
        svg = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C8 6 6 9 6 11a6 6 0 0012 0c0-2-2-5-6-9z" stroke="#7CFF6B" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M8.5 13.5c.9 1.2 2.7 2.5 3.5 2.5s2.6-1.3 3.5-2.5" stroke="#7CFF6B" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        break;
      case 'soin':
      case 'soin':
        title = 'Soin — récupère des points de vie en début de tour.';
        svg = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 5v14" stroke="#4AD3FF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 12h14" stroke="#4AD3FF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        break;
      default:
        title = type;
        svg = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="8" stroke="#ddd" stroke-width="1.6"/></svg>';
    }
    el.innerHTML = svg;
    el.setAttribute('data-title', title);
    return el;
  }

  function renderEffectIconsForZone(zoneElement, role) {
    if (!zoneElement) return;
    // remove previous effect icons container
    const prev = zoneElement.querySelector('.effect-icons');
    if (prev) prev.remove();

    const container = document.createElement('div');
    container.className = 'effect-icons';

    // read effects from currentGameState
    const effetsRaw = currentGameState[`${role}Effets`];
    let parsed = [];
    if (!effetsRaw) {
      // nothing
    } else if (typeof effetsRaw === 'string') {
      try { parsed = JSON.parse(effetsRaw); } catch (e) { parsed = []; }
    } else if (Array.isArray(effetsRaw)) parsed = effetsRaw;

    if (!Array.isArray(parsed) || parsed.length === 0) return; // no icons

    // For each effect, create icon
    parsed.forEach(eff => {
      if (!eff || !eff.type) return;
      const icon = createEffectIcon(eff);
      container.appendChild(icon);
    });

    zoneElement.appendChild(container);
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
      // render effect icons under my ship
      renderEffectIconsForZone(myZone, joueurRole === 'joueur1' ? 'joueur1' : 'joueur2');
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
      // render effect icons under opponent ship
      renderEffectIconsForZone(opponentZone, joueurRole === 'joueur1' ? 'joueur2' : 'joueur1');
    }
  }

  // Fonction pour mettre à jour l'affichage des HP
  // Fonction pour mettre à jour l'affichage des HP
  function updateHpDisplay() {
    const playerHpYouElement = document.getElementById("player-hp-you");
    const playerHpOtherElement = document.getElementById("player-hp-other");

    console.log(
      "Updating HP Display:",
      currentGameState.joueur1Hp,
      currentGameState.joueur2Hp
    );

    if (currentGameState.joueurRole === "joueur1") {
      playerHpYouElement.textContent = `${currentGameState.joueur1Hp} HP`;
      playerHpOtherElement.textContent = `${currentGameState.joueur2Hp} HP`;
    } else {
      playerHpYouElement.textContent = `${currentGameState.joueur2Hp} HP`;
      playerHpOtherElement.textContent = `${currentGameState.joueur1Hp} HP`;
    }
  }

  // Fonction pour mettre à jour le panneau d'informations du joueur
  function updatePlayerStatsPanel() {
    const playerManaElement = document.getElementById("player-mana");
    const playerCannonElement = document.getElementById("player-cannon");
    const playerMagicPowerElement =
      document.getElementById("player-magic-power");
    const statsPanel = document.getElementById("player-stats-panel");

    if (
      !playerManaElement ||
      !playerCannonElement ||
      !playerMagicPowerElement ||
      !statsPanel
    ) {
      return; // Les éléments n'existent pas encore
    }

    const myRole = currentGameState.joueurRole;

    // Récupérer le mana du magicien (par défaut 1/1)
    const mana = currentGameState[`${myRole}MagicienMana`] ?? 1;
    const maxMana = 1; // Le mana max est toujours 1

    // Récupérer la puissance du canon avec le multiplicateur
    const basePower = currentGameState[`${myRole}PuissanceTir`] ?? 100;
    const multiplier = currentGameState[`${myRole}DamageMultiplier`] ?? 1.0;
    const cannonPower = Math.round(basePower * multiplier);

    // Récupérer la puissance du magicien
    const magicPower = currentGameState[`${myRole}MagicienPuissance`] ?? 1;

    // Mettre à jour l'affichage
    playerManaElement.textContent = `${mana}/${maxMana}`;
    playerCannonElement.textContent = cannonPower;
    if (playerMagicPowerElement) {
      playerMagicPowerElement.textContent = magicPower;
    }

    // Ajouter une classe visuelle si le mana est vide
    const manaStatItem = playerManaElement.closest(".stat-item");
    if (mana === 0) {
      manaStatItem.classList.add("mana-empty");
    } else {
      manaStatItem.classList.remove("mana-empty");
    }

    // Ajouter une classe visuelle si le canon est faible (< 80)
    const cannonStatItem = playerCannonElement.closest(".stat-item");
    if (cannonPower < 80) {
      cannonStatItem.classList.add("cannon-low");
    } else {
      cannonStatItem.classList.remove("cannon-low");
    }

    // --- Drones: afficher le nombre restant de chaque type ---
    const droneAttackEl = document.getElementById("player-drones-attack");
    const droneReconEl = document.getElementById("player-drones-recon");
    if (droneAttackEl && droneReconEl) {
      // currentGameState stores arrays at joueur1Drones/joueur2Drones
      const drones = currentGameState[`${myRole}Drones`] || [];
      // drones may be JSON string if not parsed; handle both
      let droneArray = drones;
      if (typeof drones === "string") {
        try {
          droneArray = JSON.parse(drones);
        } catch (e) {
          droneArray = [];
        }
      }

      let attackCount = 0;
      let reconCount = 0;
      if (Array.isArray(droneArray)) {
        droneArray.forEach((d) => {
          const t = d && d.type ? d.type : d;
          if (t === "attaque" || t === "attack") attackCount++;
          else if (t === "reconnaissance" || t === "recon") reconCount++;
        });
      }

      droneAttackEl.textContent = attackCount;
      droneReconEl.textContent = reconCount;
    }
  }

  // Fonction pour mettre à jour l'état des boutons d'action
  function updateActionButtonsState() {
    // If local paralysis flag is set, keep buttons disabled
    if (currentGameState.localParalyzed) {
      btnForward.disabled = true;
      btnBackward.disabled = true;
      btnShoot.disabled = true;
      if (btnDrone) btnDrone.disabled = true;
      if (btnMagic) btnMagic.disabled = true;
      if (btnRecharge) btnRecharge.disabled = true;
      return;
    }
    const isMyTurn =
      currentGameState.joueurId === currentGameState.joueurActuel;
    const myRole = currentGameState.joueurRole;
    const hasMoved =
      currentGameState[`${myRole}ABouge`] === "1" ||
      currentGameState[`${myRole}ABouge`] === 1;
    const hasTakenOffensiveAction =
      currentGameState[`${myRole}ActionFaite`] === "1" ||
      currentGameState[`${myRole}ActionFaite`] === 1;

    // Disable all buttons by default
    btnForward.disabled = true;
    btnBackward.disabled = true;
    btnShoot.disabled = true;
    // cooldown mechanic removed — ensure no badge is present
    try { btnShoot.removeAttribute('data-cooldown'); } catch (e) { }
    if (btnDrone) btnDrone.disabled = true;
    if (btnMagic) btnMagic.disabled = true;

    if (isMyTurn) {
      // Movement buttons
      if (!hasMoved) {
        btnForward.disabled = false;
        btnBackward.disabled = false;
      }
      // Offensive action buttons
      if (!hasTakenOffensiveAction) {
        // No longer a cooldown on shooting — allow shoot when no offensive action taken
        btnShoot.disabled = false;
        // Drone button should only be enabled if we still have a drone available
        if (btnDrone) {
          let droneAvailable = false;
          try {
            const drones = currentGameState[`${myRole}Drones`] || [];
            const parsed = typeof drones === 'string' ? JSON.parse(drones) : drones;
            if (Array.isArray(parsed) && parsed.length > 0) droneAvailable = true;
          } catch (e) {
            droneAvailable = false;
          }
          btnDrone.disabled = !droneAvailable;
        }
        // Only enable magic if the magicien has mana
        if (btnMagic) {
          const mana = parseInt(currentGameState[`${myRole}MagicienMana`] ?? 1, 10);
          btnMagic.disabled = !(mana > 0);
        }
      }
      // Non-offensive actions (heal, recharge) are available on your turn if applicable.
      if (btnRecharge) {
        // Recharge is always available on your turn (it only adds drones and may restore mana)
        btnRecharge.disabled = false;
      }
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
        } else {
          // Immediately update local state so the player can't move again
          // before the next poll. Keep the turn — movement doesn't end it.
          const myRole = currentGameState.joueurRole;
          if (myRole) {
            currentGameState[`${myRole}ABouge`] = 1;
          }
          // disable move buttons in the UI right away for better UX
          btnForward.disabled = true;
          btnBackward.disabled = true;

          // Play movement sound
          try { sfx.move.play().catch(() => { }); } catch (e) { }

          // Show immediate feedback to the player that move succeeded
          addLocalNarrationEvent("Déplacement effectué — vous pouvez encore attaquer.");

          // Also show a short temporary message in the turn-status area
          const turnStatusEl = document.getElementById("turn-status");
          if (turnStatusEl) {
            const prevText = turnStatusEl.textContent;
            turnStatusEl.textContent = "Déplacement effectué — vous pouvez encore agir";
            turnStatusEl.classList.add("move-done");
            setTimeout(() => {
              // Restore to the appropriate message (the poll will keep it accurate too)
              if (currentGameState.joueurActuel === currentGameState.joueurId) {
                turnStatusEl.textContent = "C'est votre tour";
              } else {
                turnStatusEl.textContent = "Tour de l'adversaire";
              }
              turnStatusEl.classList.remove("move-done");
            }, 2500);
          }
        }
        // pollGameState will still pick up the position update and update button states
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("Erreur lors de la tentative de déplacement:", errorThrown);

        // Try to parse server JSON errors (e.g. 403 when player already moved)
        let serverMsg = null;
        try {
          const parsed = JSON.parse(jqXHR.responseText || '{}');
          serverMsg = parsed.error || parsed.erreur || parsed.message || null;
        } catch (e) {
          serverMsg = null;
        }

        // If the server returned a known 'already moved' message, show a friendly 'Déplacement impossible'
        if (serverMsg && /déjà boug|déjà bougé|a déjà bougé|déjà bouge/i.test(serverMsg)) {
          addLocalNarrationEvent('Déplacement impossible.');
          // Mark locally that we've moved so the UI will gray out the buttons
          try {
            const myRole = currentGameState.joueurRole;
            if (myRole) currentGameState[`${myRole}ABouge`] = 1;
            btnForward.disabled = true;
            btnBackward.disabled = true;
          } catch (e) { }
          return;
        }

        // If server returned another clear error message, show it (no sound)
        if (serverMsg) {
          addLocalNarrationEvent(serverMsg);
          return;
        }

        // Otherwise it's a network / unexpected error — keep the error SFX and message
        try { sfx.error.play().catch(() => { }); } catch (e) { }
        addLocalNarrationEvent('Erreur réseau lors du déplacement. Détails: ' + (errorThrown || textStatus || jqXHR.status || 'unknown'));
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
        if (!response.erreur) {
          // Check for server-side errors
          // play laser sound according to damage
          try {
            const dmg = parseInt(response.degatsInfliges || response.degats || 0, 10);
            if (!isNaN(dmg)) {
              if (dmg <= 100) sfx.laserWeak.play().catch(() => { });
              else sfx.laserStrong.play().catch(() => { });
            } else {
              // default weak
              sfx.laserWeak.play().catch(() => { });
            }
          } catch (e) { }

          if (response.message) {
            addNarrationEvent(response.message); // Envoyer à la BDD via l'API
          }

          const attackerShip = document.getElementById("my-ship");
          const defenderShip = document.getElementById("opponent-ship");
          // Extract damage to determine laser strength
          const dmgValue = parseInt(response.degatsInfliges || response.degats || 0, 10);
          triggerBeamAnimation(attackerShip, defenderShip, dmgValue);
          // If server indicates the next player cannot play due to an effect (paralysis), show effect
          try {
            if (response.joueur_suivant_peut_jouer === false) {
              const targetId = response.joueur_suivant_id;
              const targetShip = (targetId === currentGameState.joueurId) ? document.getElementById('my-ship') : document.getElementById('opponent-ship');
              triggerParalysisEffect(targetShip);
              // If the paralyzed player is this client, ensure action buttons are disabled immediately
              if (targetId === currentGameState.joueurId) {
                btnForward.disabled = true; btnBackward.disabled = true; btnShoot.disabled = true; if (btnDrone) btnDrone.disabled = true; if (btnMagic) btnMagic.disabled = true; if (btnRecharge) btnRecharge.disabled = true;
              }
            }
          } catch (e) { }
        } else {
          addLocalNarrationEvent(response.erreur);
        }
        // pollGameState will handle button states
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("Erreur lors de l'attaque:", errorThrown);
        try { sfx.error.play().catch(() => { }); } catch (e) { }
        // Try to parse server JSON for a clearer message
        try {
          const parsed = JSON.parse(jqXHR.responseText || '{}');
          const msg = parsed.erreur || parsed.error || parsed.message;
          if (msg) {
            addLocalNarrationEvent(msg);
          } else {
            addLocalNarrationEvent("Erreur réseau lors de l'attaque. Détails: " + (errorThrown || textStatus || jqXHR.status || 'unknown'));
          }
        } catch (e) {
          addLocalNarrationEvent("Erreur réseau lors de l'attaque. Détails: " + (errorThrown || textStatus || jqXHR.status || 'unknown'));
        }
        // We can't re-enable shoot button here, as pollGameState will do it
        // Or handle disabling/enabling of buttons on error if turn logic fails
      },
    });
  }

  function useMagic() {
    const lanceurId = currentGameState.joueurId;
    const cibleId = getOpponentId();
    const partieId = currentGameState.partieId;

    if (!lanceurId || !cibleId || !partieId) {
      console.error("Impossible d'utiliser la magie: IDs manquants.");
      addLocalNarrationEvent("Erreur: IDs de joueur ou de partie manquants.");
      return;
    }

    // Temporarily disable the magic button to prevent multiple clicks
    btnMagic.disabled = true;

    $.ajax({
      url: "api/utiliser-magie.php",
      method: "POST",
      contentType: "application/json",
      data: JSON.stringify({
        partie_id: partieId,
        lanceur_id: lanceurId,
        cible_id: cibleId,
      }),
      dataType: "json",
      success: function (response) {
        console.log("Résultat de la magie:", response);
        if (response.success) {
          // Envoyer un message structuré en base pour que le journal affiche différemment
          // selon le lecteur (lanceur vs adversaire). Format: MAGIC:role:msg_lanceur:::msg_cible
          const ownerRole = currentGameState.joueurRole || 'joueur1';
          const msgL = (response.message_lanceur || '').replace(/:::/g, '::');
          const msgC = (response.message_cible || '').replace(/:::/g, '::');
          const magicMessage = `MAGIC:${ownerRole}:${msgL}:::${msgC}`;
          addNarrationEvent(magicMessage);

          // Play magic SFX
          try { sfx.magic.play().catch(() => { }); } catch (e) { }

          // Optionnel: Ajouter une animation visuelle pour la magie
          const myShip = document.getElementById("my-ship");
          const opponentShip = document.getElementById("opponent-ship");
          triggerMagicAnimation(myShip, opponentShip);
          // Apply an immediate optimistic update to local state so UI reacts quickly
          try {
            const myRole = currentGameState.joueurRole;
            const oppRole = myRole === 'joueur1' ? 'joueur2' : 'joueur1';

            // Magician loses all mana when casting
            currentGameState[`${myRole}MagicienMana`] = 0;

            // Mark that we've used our offensive action this turn
            currentGameState[`${myRole}ActionFaite`] = 1;

            // Update HPs locally if server returned them
            if (response.lanceur_nouveaux_hp !== undefined) {
              currentGameState[`${myRole}Hp`] = parseInt(response.lanceur_nouveaux_hp, 10);
            }
            if (response.cible_nouveaux_hp !== undefined) {
              currentGameState[`${oppRole}Hp`] = parseInt(response.cible_nouveaux_hp, 10);
            }

            // If server indicates the next player (after casting) cannot play, disable buttons
            // Visual/sound effects will be triggered by EFFECT: message in narration
            try {
              if (response.joueur_suivant_peut_jouer === false) {
                const targetId = response.joueur_suivant_id;
                if (targetId === currentGameState.joueurId) {
                  btnForward.disabled = true; btnBackward.disabled = true; btnShoot.disabled = true; if (btnDrone) btnDrone.disabled = true; if (btnMagic) btnMagic.disabled = true; if (btnRecharge) btnRecharge.disabled = true;
                }
              }
            } catch (e) { }

            // Update UI immediately
            updatePlayerStatsPanel();
            updateHpDisplay();
            updateActionButtonsState();

            // Also add a small local feedback message for instant UX
            if (response.message_lanceur) addLocalNarrationEvent(response.message_lanceur);
          } catch (e) {
            // If optimistic update fails, server poll will sync state shortly
            console.warn('Optimistic update after magic failed:', e);
          }
        } else if (response.erreur) {
          showErrorPopup(response.erreur);
          try { btnMagic.disabled = false; } catch (e) { }
        }
        // pollGameState will handle button states
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("Erreur lors de l'utilisation de la magie:", errorThrown);
        try { sfx.error.play().catch(() => { }); } catch (e) { }

        // Essayer de parser la réponse JSON pour afficher l'erreur
        try {
          const errorResponse = JSON.parse(jqXHR.responseText);
          const errorMsg = (errorResponse && (errorResponse.erreur || errorResponse.error || errorResponse.message)) || null;
          if (errorMsg) {
            showErrorPopup(errorMsg);
            try { btnMagic.disabled = false; } catch (e) { }
          } else {
            showErrorPopup("Erreur réseau lors de l'utilisation de la magie. Détails: " + (errorThrown || textStatus || jqXHR.status || 'unknown'));
            try { btnMagic.disabled = false; } catch (e) { }
          }
        } catch (e) {
          showErrorPopup("Erreur réseau lors de l'utilisation de la magie. Détails: " + (errorThrown || textStatus || jqXHR.status || 'unknown'));
          try { btnMagic.disabled = false; } catch (e) { }
        }
      },
    });
  }

  function useRecharge() {
    const joueurId = currentGameState.joueurId;
    const partieId = currentGameState.partieId;

    if (!joueurId || !partieId) {
      console.error("Impossible de recharger: IDs manquants.");
      addLocalNarrationEvent("Erreur: IDs de joueur ou de partie manquants.");
      return;
    }

    // Prevent double-click
    btnRecharge.disabled = true;

    $.ajax({
      url: "api/recharger.php",
      method: "POST",
      contentType: "application/json",
      data: JSON.stringify({ partie_id: partieId }),
      dataType: "json",
      success: function (response) {
        console.log("Résultat du rechargement:", response);
        if (response.success) {
          // play recharge sound
          try { sfx.recharge.play().catch(() => { }); } catch (e) { }
          if (response.message) {
            addNarrationEvent(response.message);
          }

          // Update local game state: restore mana and add drones (optimistic update)
          try {
            const myRole = currentGameState.joueurRole;
            currentGameState[`${myRole}MagicienMana`] = 1;

            const key = `${myRole}Drones`;
            let drones = currentGameState[key] || [];
            if (typeof drones === 'string') drones = JSON.parse(drones);
            if (!Array.isArray(drones)) drones = [];
            drones.push({ type: 'reconnaissance' });
            drones.push({ type: 'attaque' });
            currentGameState[key] = drones;
            // update UI immediately
            updatePlayerStatsPanel();
            // If the server ended the turn, reflect that immediately
            if (response.joueur_suivant_id) {
              currentGameState.joueurActuel = response.joueur_suivant_id;
              // Reset action / move flags locally until poll syncs
              currentGameState.joueur1ActionFaite = 0;
              currentGameState.joueur2ActionFaite = 0;
              currentGameState.joueur1ABouge = 0;
              currentGameState.joueur2ABouge = 0;
              updateActionButtonsState();
              // If the server indicates the next player is paralyzed on start of turn, disable buttons
              // Visual/sound effects will be triggered by EFFECT: message in narration
              try {
                if (response.joueur_suivant_peut_jouer === false) {
                  const targetId = response.joueur_suivant_id;
                  if (targetId === currentGameState.joueurId) {
                    btnForward.disabled = true; btnBackward.disabled = true; btnShoot.disabled = true; if (btnDrone) btnDrone.disabled = true; if (btnMagic) btnMagic.disabled = true; if (btnRecharge) btnRecharge.disabled = true;
                  }
                }
              } catch (e) { }
            }
            updateActionButtonsState();
          } catch (e) {
            // server poll will sync state
          }

        } else if (response.erreur) {
          showErrorPopup(response.erreur);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("Erreur lors du rechargement:", errorThrown);
        try { sfx.error.play().catch(() => { }); } catch (e) { }
        try {
          const err = JSON.parse(jqXHR.responseText);
          if (err.erreur) showErrorPopup(err.erreur);
          else addLocalNarrationEvent('Erreur réseau lors du rechargement.');
        } catch (e) {
          addLocalNarrationEvent('Erreur réseau lors du rechargement.');
        }
      }
    });
  }

  function triggerMagicAnimation(casterShip, targetShip) {
    if (!casterShip || !targetShip) {
      console.warn(
        "Impossible de déclencher l'animation de magie: éléments de vaisseau manquants."
      );
      return;
    }

    const gameContainer = document.getElementById("game-container");
    if (!gameContainer) return;

    const casterRect = casterShip.getBoundingClientRect();
    const targetRect = targetShip.getBoundingClientRect();
    const gameContainerRect = gameContainer.getBoundingClientRect();

    const startX =
      casterRect.left + casterRect.width / 2 - gameContainerRect.left;
    const startY =
      casterRect.top + casterRect.height / 2 - gameContainerRect.top;
    const endX =
      targetRect.left + targetRect.width / 2 - gameContainerRect.left;
    const endY = targetRect.top + targetRect.height / 2 - gameContainerRect.top;

    const distance = Math.sqrt(
      Math.pow(endX - startX, 2) + Math.pow(endY - startY, 2)
    );
    const angle = (Math.atan2(endY - startY, endX - startX) * 180) / Math.PI;

    const magicBeam = document.createElement("div");
    magicBeam.classList.add("magic-beam");
    magicBeam.style.width = `${distance}px`;
    magicBeam.style.left = `${startX}px`;
    magicBeam.style.top = `${startY}px`;
    magicBeam.style.transform = `rotate(${angle}deg)`;

    gameContainer.appendChild(magicBeam);

    setTimeout(() => {
      magicBeam.remove();
    }, 1000);
  }

  function pollGameState() {
    $.ajax({
      url: `api/statut-partie.php?partie_id=${currentGameState.partieId}`,
      method: "GET",
      dataType: "json",
      success: function (serverState) {
        if (!serverState) {
          console.error("Server state is empty or invalid");
          return;
        }

        // Log missing fields for debugging
        if (serverState.joueur_actuel === undefined)
          console.warn("Missing joueur_actuel");
        if (serverState.joueur1_hp === undefined)
          console.warn("Missing joueur1_hp");

        const newJ1Pos = parseInt(serverState.joueur1_position, 10) || 1;
        const newJ2Pos = parseInt(serverState.joueur2_position, 10) || 6;
        const newJ1Hp = parseInt(serverState.joueur1_hp, 10); // Can be 0
        const newJ2Hp = parseInt(serverState.joueur2_hp, 10); // Can be 0

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

        // Update mana and cannon power data
        if (serverState.joueur1_magicien_mana !== undefined) {
          currentGameState.joueur1MagicienMana = parseInt(
            serverState.joueur1_magicien_mana,
            10
          );
        }
        if (serverState.joueur2_magicien_mana !== undefined) {
          currentGameState.joueur2MagicienMana = parseInt(
            serverState.joueur2_magicien_mana,
            10
          );
        }
        if (serverState.joueur1_puissance_tir !== undefined) {
          currentGameState.joueur1PuissanceTir = parseInt(
            serverState.joueur1_puissance_tir,
            10
          );
        }
        if (serverState.joueur2_puissance_tir !== undefined) {
          currentGameState.joueur2PuissanceTir = parseInt(
            serverState.joueur2_puissance_tir,
            10
          );
        }
        if (serverState.joueur1_damage_multiplier !== undefined) {
          currentGameState.joueur1DamageMultiplier = parseFloat(
            serverState.joueur1_damage_multiplier
          );
        }
        if (serverState.joueur2_damage_multiplier !== undefined) {
          currentGameState.joueur2DamageMultiplier = parseFloat(
            serverState.joueur2_damage_multiplier
          );
        }
        if (serverState.joueur1_magicien_puissance !== undefined) {
          currentGameState.joueur1MagicienPuissance = parseInt(
            serverState.joueur1_magicien_puissance,
            10
          );
        }
        if (serverState.joueur2_magicien_puissance !== undefined) {
          currentGameState.joueur2MagicienPuissance = parseInt(
            serverState.joueur2_magicien_puissance,
            10
          );
        }

        // special-attack cooldown mechanic removed — ignore any cooldown fields

        // Update drone arrays (if the server sends them as JSON strings or arrays)
        if (serverState.joueur1_drones !== undefined) {
          currentGameState.joueur1Drones = serverState.joueur1_drones;
        }
        if (serverState.joueur2_drones !== undefined) {
          currentGameState.joueur2Drones = serverState.joueur2_drones;
        }

        // Capture previous effects to detect changes that should update ship display
        const prevJ1EffRaw = currentGameState.joueur1Effets;
        const prevJ2EffRaw = currentGameState.joueur2Effets;

        // Effects (used to detect paralysis visually and block when needed)
        if (serverState.joueur1_effets !== undefined) {
          currentGameState.joueur1Effets = serverState.joueur1_effets;
        }
        if (serverState.joueur2_effets !== undefined) {
          currentGameState.joueur2Effets = serverState.joueur2_effets;
        }

        // If effects changed, we should re-render ship display (icons)
        try {
          const newJ1 = JSON.stringify(currentGameState.joueur1Effets || []);
          const oldJ1 = JSON.stringify(prevJ1EffRaw || []);
          const newJ2 = JSON.stringify(currentGameState.joueur2Effets || []);
          const oldJ2 = JSON.stringify(prevJ2EffRaw || []);
          if (newJ1 !== oldJ1 || newJ2 !== oldJ2) {
            needsShipDisplayUpdate = true;
          }
        } catch (e) { }

        // Update turn-related flags and check if button state needs update
        // Update turn-related flags and check if button state needs update
        // Use loose comparison or default to current value if undefined to prevent breaking
        const newJoueurActuel = serverState.joueur_actuel;
        const newJ1Action = serverState.joueur1_action_faite;
        const newJ2Action = serverState.joueur2_action_faite;
        const newJ1Bouge = serverState.joueur1_a_bouge;
        const newJ2Bouge = serverState.joueur2_a_bouge;

        if (
          newJoueurActuel !== undefined &&
          newJoueurActuel !== currentGameState.joueurActuel
        ) {
          currentGameState.joueurActuel = newJoueurActuel;
          needsButtonStateUpdate = true;
        }
        if (
          newJ1Action !== undefined &&
          newJ1Action !== currentGameState.joueur1ActionFaite
        ) {
          currentGameState.joueur1ActionFaite = newJ1Action;
          needsButtonStateUpdate = true;
        }
        if (
          newJ2Action !== undefined &&
          newJ2Action !== currentGameState.joueur2ActionFaite
        ) {
          currentGameState.joueur2ActionFaite = newJ2Action;
          needsButtonStateUpdate = true;
        }
        if (
          newJ1Bouge !== undefined &&
          newJ1Bouge !== currentGameState.joueur1ABouge
        ) {
          currentGameState.joueur1ABouge = newJ1Bouge;
          needsButtonStateUpdate = true;
        }
        if (
          newJ2Bouge !== undefined &&
          newJ2Bouge !== currentGameState.joueur2ABouge
        ) {
          currentGameState.joueur2ABouge = newJ2Bouge;
          needsButtonStateUpdate = true;
        }

        if (needsShipDisplayUpdate) {
          updateShipsDisplay();
        }
        if (needsHpDisplayUpdate) {
          updateHpDisplay(); // <--- CALL IT HERE
        }

        // Check for Game Over (Run this check every poll, regardless of updates)
        if (
          currentGameState.joueur1Hp <= 0 ||
          currentGameState.joueur2Hp <= 0
        ) {
          let message = "";
          const myRole = currentGameState.joueurRole;

          if (
            currentGameState.joueur1Hp <= 0 &&
            currentGameState.joueur2Hp <= 0
          ) {
            message = "Match nul ! Un combat acharné qui finit sans vainqueur.";
          } else if (currentGameState.joueur1Hp <= 0) {
            if (myRole === "joueur1") {
              message =
                "Dommage... Votre vaisseau a été détruit. Meilleure chance la prochaine fois !";
            } else {
              message =
                "Félicitations ! Vous avez triomphé de votre adversaire.";
            }
          } else if (currentGameState.joueur2Hp <= 0) {
            if (myRole === "joueur2") {
              message =
                "Dommage... Votre vaisseau a été détruit. Meilleure chance la prochaine fois !";
            } else {
              message =
                "Félicitations ! Vous avez triomphé de votre adversaire.";
            }
          }

          // Only show if not already visible to avoid flickering/spamming if we add animations later
          if (gameOverPopup.style.display === "none") {
            gameOverMessage.textContent = message;
            gameOverPopup.style.display = "flex";
          }
        }
        if (needsButtonStateUpdate) {
          updateActionButtonsState();
        }

        // If it's now our turn, and server reports we have a paralysis effect, block buttons
        // Visual/sound effects are now triggered only when EFFECT: message appears in narration
        try {
          const myId = currentGameState.joueurId;
          const isMyTurnNow = currentGameState.joueurActuel === myId;
          if (isMyTurnNow) {
            const myRole = currentGameState.joueurRole;
            const effetsRaw = currentGameState[`${myRole}Effets`];
            if (effetsRaw) {
              let parsed = [];
              if (typeof effetsRaw === 'string') {
                try { parsed = JSON.parse(effetsRaw); } catch (e) { parsed = []; }
              } else if (Array.isArray(effetsRaw)) parsed = effetsRaw;

              const hasParalysis = Array.isArray(parsed) && parsed.some(e => e && e.type === 'paralysie');
              if (hasParalysis) {
                // mark locally paralyzed to keep buttons disabled until state changes
                currentGameState.localParalyzed = true;
                updateActionButtonsState();
              } else {
                // ensure flag cleared when no paralysis present
                if (currentGameState.localParalyzed) {
                  currentGameState.localParalyzed = false;
                  updateActionButtonsState();
                }
              }
            }
          }
        } catch (e) { }

        // Toujours mettre à jour le panneau des stats du joueur
        updatePlayerStatsPanel();
      },
      error: function (jqXHR, textStatus, errorThrown) { },
    });
  }

  btnForward.addEventListener("click", () => movePlayer("forward"));
  btnBackward.addEventListener("click", () => movePlayer("backward"));
  btnShoot.addEventListener("click", shoot);
  if (btnMagic) {
    btnMagic.addEventListener("click", useMagic);
  }
  if (btnRecharge) {
    btnRecharge.addEventListener("click", useRecharge);
  }
  if (btnAbandon) {
    btnAbandon.addEventListener("click", function () {
      if (!confirm('Êtes-vous sûr de vouloir abandonner la partie ? Cela donnera la victoire à votre adversaire.')) return;

      btnAbandon.disabled = true;

      $.ajax({
        url: 'api/abandonner.php',
        method: 'POST',
        dataType: 'json',
        success: function (res) {
          if (res.success) {
            try { sfx.error.play().catch(() => { }); } catch (e) { }
            addNarrationEvent(res.message || 'Vous avez abandonné la partie.');
            // redirect back to menu after short delay to let narration / poll update
            setTimeout(() => { window.location.href = 'choix-joueur.php'; }, 800);
          } else if (res.erreur) {
            showErrorPopup(res.erreur);
            btnAbandon.disabled = false;
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          try {
            const parsed = JSON.parse(jqXHR.responseText || '{}');
            const msg = parsed.erreur || parsed.error || parsed.message;
            if (msg) {
              showErrorPopup(msg);
              btnAbandon.disabled = false;
              return;
            }
          } catch (e) { }
          showErrorPopup('Erreur réseau lors de la tentative d\'abandon. Détails: ' + (errorThrown || textStatus || jqXHR.status || 'unknown'));
          btnAbandon.disabled = false;
        }
      });
    });
  }

  // Drone button functionality
  const btnDrone = document.getElementById("btn-drone");
  const droneSelectionPopup = document.getElementById("drone-selection-popup");
  const selectDroneAttaque = document.getElementById("select-drone-attaque");
  const selectDroneReconnaissance = document.getElementById(
    "select-drone-reconnaissance"
  );
  const cancelDroneSelection = document.getElementById(
    "cancel-drone-selection"
  );

  if (btnDrone) {
    btnDrone.addEventListener("click", () => {
      droneSelectionPopup.style.display = "flex";
    });
  }

  if (cancelDroneSelection) {
    cancelDroneSelection.addEventListener("click", () => {
      droneSelectionPopup.style.display = "none";
    });
  }

  if (selectDroneAttaque) {
    selectDroneAttaque.addEventListener("click", () => {
      launchDrone("attaque");
      droneSelectionPopup.style.display = "none";
    });
  }

  if (selectDroneReconnaissance) {
    selectDroneReconnaissance.addEventListener("click", () => {
      launchDrone("reconnaissance");
      droneSelectionPopup.style.display = "none";
    });
  }

  function launchDrone(droneType) {
    const joueurId = currentGameState.joueurId;
    const partieId = currentGameState.partieId;

    if (!joueurId || !partieId) {
      console.error("Impossible de lancer le drone: IDs manquants.");
      addLocalNarrationEvent("Erreur: IDs de joueur ou de partie manquants.");
      return;
    }

    btnDrone.disabled = true;

    $.ajax({
      url: "api/lancer-drone.php",
      method: "POST",
      contentType: "application/json",
      data: JSON.stringify({
        partie_id: partieId,
        joueur_id: joueurId,
        drone_type: droneType,
      }),
      dataType: "json",
      success: function (response) {
        console.log("Résultat du lancer de drone:", response);
        if (response.success) {
          if (response.message) {
            addNarrationEvent(response.message); // Envoyer à la BDD via l'API
          }

          const myShip = document.getElementById("my-ship");
          triggerDroneAnimation(myShip, droneType);

          // If server indicates the next player cannot play due to an effect (paralysis), disable buttons
          // Visual/sound effects will be triggered by EFFECT: message in narration
          try {
            if (response.joueur_suivant_peut_jouer === false) {
              const targetId = response.joueur_suivant_id;
              if (targetId === currentGameState.joueurId) {
                btnForward.disabled = true; btnBackward.disabled = true; btnShoot.disabled = true; if (btnDrone) btnDrone.disabled = true; if (btnMagic) btnMagic.disabled = true; if (btnRecharge) btnRecharge.disabled = true;
              }
            }
          } catch (e) { }

          // Play drone SFX
          try {
            if (droneType === 'attaque') sfx.droneAttack.play().catch(() => { });
            else sfx.droneRecon.play().catch(() => { });
          } catch (e) { }

          // Update local drone inventory immediately for better UX.
          try {
            const myRole = currentGameState.joueurRole;
            const key = `${myRole}Drones`;
            let drones = currentGameState[key] || [];
            if (typeof drones === 'string') drones = JSON.parse(drones);
            if (Array.isArray(drones)) {
              // remove one drone of requested type
              const idx = drones.findIndex(d => (d.type || d) === (droneType === 'attaque' ? 'attaque' : 'reconnaissance'));
              if (idx !== -1) drones.splice(idx, 1);
              currentGameState[key] = drones;
            }
            updatePlayerStatsPanel();
          } catch (e) {
            // silently ignore any parse errors; server poll will sync
          }
        } else if (response.erreur) {
          // Afficher un popup d'erreur
          showErrorPopup(response.erreur);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("Erreur lors du lancer de drone:", errorThrown);
        try { sfx.error.play().catch(() => { }); } catch (e) { }

        // Essayer de parser la réponse JSON pour afficher l'erreur
        try {
          const errorResponse = JSON.parse(jqXHR.responseText);
          if (errorResponse.erreur) {
            showErrorPopup(errorResponse.erreur);
          } else {
            showErrorPopup("Erreur réseau lors du lancer de drone. Détails: " + (errorThrown || textStatus || jqXHR.status || 'unknown'));
          }
        } catch (e) {
          showErrorPopup("Erreur réseau lors du lancer de drone. Détails: " + (errorThrown || textStatus || jqXHR.status || 'unknown'));
        }
      },
    });
  }

  function showErrorPopup(message) {
    const errorPopup = document.getElementById("error-popup");
    const errorMessage = document.getElementById("error-message");
    const closeButton = document.getElementById("close-error-popup");

    if (errorPopup && errorMessage) {
      errorMessage.textContent = message;
      errorPopup.style.display = "flex";

      if (closeButton) {
        closeButton.onclick = function () {
          errorPopup.style.display = "none";
        };
      }
    }
  }

  function triggerDroneAnimation(shipElement, droneType) {
    if (!shipElement) {
      console.warn(
        "Impossible de déclencher l'animation du drone: élément de vaisseau manquant."
      );
      return;
    }

    const gameContainer = document.getElementById("game-container");
    if (!gameContainer) return;

    const shipRect = shipElement.getBoundingClientRect();
    const gameContainerRect = gameContainer.getBoundingClientRect();

    const startX = shipRect.left + shipRect.width / 2 - gameContainerRect.left;
    const startY = shipRect.top + shipRect.height / 2 - gameContainerRect.top;

    const drone = document.createElement("div");
    drone.classList.add("drone-animation");
    drone.style.left = `${startX}px`;
    drone.style.top = `${startY}px`;

    const icon = document.createElement("i");
    icon.classList.add("fas");
    icon.classList.add(droneType === "attaque" ? "fa-crosshairs" : "fa-search");
    drone.appendChild(icon);

    gameContainer.appendChild(drone);

    setTimeout(() => {
      drone.remove();
    }, 2000);
  } // Add event listener for the shoot button

  if (quitterGameButton) {
    quitterGameButton.addEventListener("click", () => {
      if (confirm("Êtes-vous sûr de vouloir quitter la partie ?")) {
        quitGame();
      }
    });
  }

  // Small electric explosion for paralysis
  // Make this function globally accessible so narration.js can call it
  window.triggerParalysisEffect = function triggerParalysisEffect(shipElement) {
    if (!shipElement) return;

    const gameContainer = document.getElementById("game-container");
    if (!gameContainer) return;

    const rect = shipElement.getBoundingClientRect();
    const containerRect = gameContainer.getBoundingClientRect();

    const x = rect.left + rect.width / 2 - containerRect.left;
    const y = rect.top + rect.height / 2 - containerRect.top;

    const expl = document.createElement('div');
    expl.className = 'paralysis-explosion';
    expl.style.left = `${x}px`;
    expl.style.top = `${y}px`;
    gameContainer.appendChild(expl);

    // play sound
    try { sfx.paralysie.currentTime = 0; sfx.paralysie.play().catch(() => { }); } catch (e) { }

    setTimeout(() => { expl.remove(); }, 900);
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
  updatePlayerStatsPanel(); // Initial call for player stats panel
  // Call pollGameState immediately to get initial player IDs if not in initialGameState
  pollGameState();
  setInterval(pollGameState, 3000);
});
