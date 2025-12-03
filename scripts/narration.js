function addNarrationEvent(message) {
  $.ajax({
    url: "api/add-narration.php",
    method: "POST",
    contentType: "application/json",
    data: JSON.stringify({ message: message }),
    success: function () {
      // On ne rafraîchit pas immédiatement, on attend le prochain poll pour garder la synchro.
    },
    error: function (jqXHR, textStatus, errorThrown) {
      console.error(
        "Erreur lors de l'ajout de l'événement de narration:",
        errorThrown
      );
    },
  });
}

function addLocalNarrationEvent(localMessage) {
  const eventsContainer = document.getElementById("narration-events");
  if (!eventsContainer) return;

  const now = new Date();
  const time = [now.getHours(), now.getMinutes(), now.getSeconds()]
    .map((n) => n.toString().padStart(2, "0"))
    .join(":");

  const eventDiv = document.createElement("div");
  eventDiv.className = "narration-event local-feedback";
  eventDiv.innerHTML = `<span class="timestamp">[${time}]</span> <span class="message" style="color: #ffcc00;">${localMessage}</span>`;
  eventsContainer.prepend(eventDiv);
}

function parseAndTranslateNarration(rawMessage) {
  const myRole = initialGameState.joueurRole;
  const parts = rawMessage.split(":");

  // Helper to return structured result
  const asResult = (text, owner = "system") => ({ text, owner });

  // Format MAGIC:joueur_role:message_lanceur:::message_cible
  if (parts.length > 2 && parts[0] === "MAGIC") {
    const [, ownerRole, ...rest] = parts;
    const restStr = rest.join(":");
    // split the two messages by a delimiter we choose ':::'. If not present, fallback to whole
    const split = restStr.split(":::");
    const messageLanceur = split[0] || "";
    const messageCible = split[1] || "";
    const isMine = ownerRole === myRole;
    if (isMine) {
      return asResult(messageLanceur, "you");
    } else {
      // adapt wording for opponent view: replace leading 'Votre' with 'Le' / 'Le magicien adverse'
      let adapted = messageLanceur
        .replace(/^Votre magicien/g, "Le magicien adverse")
        .replace(/Votre magicien/g, "Le magicien adverse")
        .replace(/Votre/g, "Le")
        .replace(/votre/g, "le");
      // if server provided a specific message_cible, prefer adapted message_cible for opponent
      if (messageCible) {
        adapted = messageCible.replace(/^Vous/g, "Le magicien adverse a");
      }
      return asResult(adapted, "opponent");
    }
  }

  // Format DRONE:joueur_role:message_complet
  if (parts.length > 2 && parts[0] === "DRONE") {
    const [, droneOwnerRole, ...messageParts] = parts;
    const message = messageParts.join(":");
    const isMyDrone = droneOwnerRole === myRole;
    if (!isMyDrone) {
      const adapted = message
        .replace(/Votre drone/g, "Le drone de l'adversaire")
        .replace(/Votre/g, "Sa")
        .replace(/votre/g, "son");
      return asResult(adapted, "opponent");
    }
    return asResult(message, "you");
  }

  // Format ATTACK:joueur_role:message_complet
  if (parts.length > 2 && parts[0] === "ATTACK") {
    const [, attackerRole, ...messageParts] = parts;
    const message = messageParts.join(":");
    const isMyAttack = attackerRole === myRole;
    if (!isMyAttack) {
      const adapted = message
        .replace(/Vous avez/g, "L'adversaire a")
        .replace(/l'adversaire/g, "vous");
      return asResult(adapted, "opponent");
    }
    return asResult(message, "you");
  }

  // Format DRONE_ATTACK:joueur_role:multiplicateur
  if (parts.length > 2 && parts[0] === "DRONE_ATTACK") {
    const [, droneOwnerRole, multiplicateur] = parts;
    const isMyDrone = droneOwnerRole === myRole;
    const droneOwner = isMyDrone ? "Votre" : "Le drone de l'adversaire";
    if (multiplicateur === "1.5") {
      return asResult(`${droneOwner} drone d'attaque a trouvé une faille ! ${isMyDrone ? "Votre" : "Sa"} prochaine attaque infligera 1.5x dégâts.`, isMyDrone ? "you" : "opponent");
    } else if (multiplicateur === "1.0") {
      return asResult(`${droneOwner} drone d'attaque n'a rien trouvé d'exceptionnel. Attaque normale.`, isMyDrone ? "you" : "opponent");
    } else if (multiplicateur === "2.0") {
      return asResult(`${droneOwner} drone d'attaque a trouvé une énorme faille ! ${isMyDrone ? "Votre" : "Sa"} prochaine attaque infligera 2x dégâts.`, isMyDrone ? "you" : "opponent");
    }
  }

  // Format DRONE_RECON:joueur_role:type:valeur
  if (parts.length > 3 && parts[0] === "DRONE_RECON") {
    const [, droneOwnerRole, type, valeur] = parts;
    const isMyDrone = droneOwnerRole === myRole;
    const droneOwner = isMyDrone ? "Votre" : "Le";
    const possessif = isMyDrone ? "votre" : "son";
    if (type === "magicien") {
      return asResult(`${droneOwner} drone de reconnaissance a trouvé un magicien plus puissant (Puissance: ${valeur}) ! Il remplace ${possessif} ancien magicien.`, isMyDrone ? "you" : "opponent");
    } else if (type === "canon") {
      return asResult(`${droneOwner} drone a trouvé un meilleur canon ! ${isMyDrone ? "Votre" : "Sa"} puissance de tir est maintenant de ${valeur}.`, isMyDrone ? "you" : "opponent");
    } else if (type === "soin") {
      return asResult(`${droneOwner} drone a trouvé une étoile de soin ! ${isMyDrone ? "Votre" : "Son"} vaisseau a récupéré ${valeur} points de vie.`, isMyDrone ? "you" : "opponent");
    }
  }

  // Format MOVE:joueur_role:message_complet
  if (parts.length > 2 && parts[0] === "MOVE") {
    const [, playerRole, ...messageParts] = parts;
    const message = messageParts.join(":");
    const isMyMove = playerRole === myRole;
    if (!isMyMove) {
      return asResult(message.replace(/Vous/g, "L'adversaire"), "opponent");
    }
    return asResult(message, "you");
  }

  // Format EFFECT:message (for status effects like paralysis)
  if (parts.length > 1 && parts[0] === "EFFECT") {
    const message = parts.slice(1).join(":");
    // Check if this is a paralysis message that prevents a turn
    if (message.includes("est paralysé et ne peut pas jouer ce tour")) {
      // Determine which player is paralyzed
      let targetShip = null;
      if (message.includes("Joueur 1")) {
        targetShip = myRole === 'joueur1' ? document.getElementById('my-ship') : document.getElementById('opponent-ship');
      } else if (message.includes("Joueur 2")) {
        targetShip = myRole === 'joueur2' ? document.getElementById('my-ship') : document.getElementById('opponent-ship');
      }

      // Trigger the paralysis effect
      if (targetShip && typeof triggerParalysisEffect === 'function') {
        triggerParalysisEffect(targetShip);
      }
    }
    return asResult(message, "system");
  }

  return asResult(rawMessage, "system");
}

let lastRenderedEventId = 0; // Garde la trace du dernier ID d'événement rendu

function renderNarrationEvents(events) {
  const eventsContainer = document.getElementById("narration-events");
  if (!eventsContainer) return;

  const newEvents = events.filter(
    (event) => event.event_id > lastRenderedEventId
  );

  newEvents.reverse().forEach((event) => {
    const res = parseAndTranslateNarration(event.message);
    const message = res && res.text ? res.text : event.message;
    const owner = res && res.owner ? res.owner : "system";
    if (message) {
      const eventDiv = document.createElement("div");
      // assign class based on owner: you / opponent / system
      let cls = "narration-event";
      if (owner === "you") cls += " you";
      else if (owner === "opponent") cls += " opponent";
      else cls += " system";
      eventDiv.className = cls;
      eventDiv.innerHTML = `<span class="timestamp">[${event.time}]</span> <span class="message">${message}</span>`;
      eventsContainer.prepend(eventDiv);
      lastRenderedEventId = Math.max(lastRenderedEventId, event.event_id);
    }
  });
}

function fetchNarrationEvents() {
  $.ajax({
    url: `api/get-narration.php`,
    method: "GET",
    dataType: "json",
    success: function (events) {
      if (events.erreur) {
        console.error(events.erreur);
        return;
      }

      renderNarrationEvents(events);
    },
    error: function (jqXHR, textStatus, errorThrown) { },
  });
}

document.addEventListener("DOMContentLoaded", () => {
  setInterval(fetchNarrationEvents, 2500);
  fetchNarrationEvents();
});
