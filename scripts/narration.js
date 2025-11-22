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

  // Format DRONE:joueur_role:message_complet
  if (parts.length > 2 && parts[0] === "DRONE") {
    const [, droneOwnerRole, ...messageParts] = parts;
    const message = messageParts.join(":");
    const isMyDrone = droneOwnerRole === myRole;

    // Remplacer "Votre drone" par "Le drone de l'adversaire" si ce n'est pas le nôtre
    if (!isMyDrone) {
      return message
        .replace(/Votre drone/g, "Le drone de l'adversaire")
        .replace(/Votre/g, "Sa")
        .replace(/votre/g, "son");
    }
    return message;
  }

  // Format ATTACK:joueur_role:message_complet
  if (parts.length > 2 && parts[0] === "ATTACK") {
    const [, attackerRole, ...messageParts] = parts;
    const message = messageParts.join(":");
    const isMyAttack = attackerRole === myRole;

    // Remplacer "Vous" et "l'adversaire" selon qui attaque
    if (!isMyAttack) {
      return message
        .replace(/Vous avez/g, "L'adversaire a")
        .replace(/l'adversaire/g, "vous")
        .replace(/L'adversaire a/g, "L'adversaire a");
    }
    return message;
  }

  // Format DRONE_ATTACK:joueur_role:multiplicateur
  if (parts.length > 2 && parts[0] === "DRONE_ATTACK") {
    const [, droneOwnerRole, multiplicateur] = parts;
    const isMyDrone = droneOwnerRole === myRole;
    const droneOwner = isMyDrone ? "Votre" : "Le drone de l'adversaire";

    if (multiplicateur === "1.5") {
      return `${droneOwner} drone d'attaque a trouvé une faille ! ${
        isMyDrone ? "Votre" : "Sa"
      } prochaine attaque infligera 1.5x dégâts.`;
    } else if (multiplicateur === "1.0") {
      return `${droneOwner} drone d'attaque n'a rien trouvé d'exceptionnel. Attaque normale.`;
    } else if (multiplicateur === "2.0") {
      return `${droneOwner} drone d'attaque a trouvé une énorme faille ! ${
        isMyDrone ? "Votre" : "Sa"
      } prochaine attaque infligera 2x dégâts.`;
    }
  }

  // Format DRONE_RECON:joueur_role:type:valeur
  if (parts.length > 3 && parts[0] === "DRONE_RECON") {
    const [, droneOwnerRole, type, valeur] = parts;
    const isMyDrone = droneOwnerRole === myRole;
    const droneOwner = isMyDrone ? "Votre" : "Le";
    const possessif = isMyDrone ? "votre" : "son";

    if (type === "magicien") {
      return `${droneOwner} drone de reconnaissance a trouvé un magicien plus puissant (Puissance: ${valeur}) ! Il remplace ${possessif} ancien magicien.`;
    } else if (type === "canon") {
      return `${droneOwner} drone a trouvé un meilleur canon ! ${
        isMyDrone ? "Votre" : "Sa"
      } puissance de tir est maintenant de ${valeur}.`;
    } else if (type === "soin") {
      return `${droneOwner} drone a trouvé une étoile de soin ! ${
        isMyDrone ? "Votre" : "Son"
      } vaisseau a récupéré ${valeur} points de vie.`;
    }
  }

  // Format MOVE:joueur_role:message_complet
  if (parts.length > 2 && parts[0] === "MOVE") {
    const [, playerRole, ...messageParts] = parts;
    const message = messageParts.join(":");
    const isMyMove = playerRole === myRole;

    // Remplacer "Vous" par "L'adversaire" si ce n'est pas notre mouvement
    if (!isMyMove) {
      return message.replace(/Vous/g, "L'adversaire");
    }
    return message;
  }

  return rawMessage;
}

let lastRenderedEventId = 0; // Garde la trace du dernier ID d'événement rendu

function renderNarrationEvents(events) {
  const eventsContainer = document.getElementById("narration-events");
  if (!eventsContainer) return;

  const newEvents = events.filter(
    (event) => event.event_id > lastRenderedEventId
  );

  newEvents.reverse().forEach((event) => {
    const message = parseAndTranslateNarration(event.message);
    if (message) {
      const eventDiv = document.createElement("div");
      eventDiv.className = "narration-event";
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
    error: function (jqXHR, textStatus, errorThrown) {},
  });
}

document.addEventListener("DOMContentLoaded", () => {
  setInterval(fetchNarrationEvents, 2500);
  fetchNarrationEvents();
});
