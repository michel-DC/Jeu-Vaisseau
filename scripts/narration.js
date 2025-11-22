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
  eventsContainer.appendChild(eventDiv);
  eventsContainer.scrollTop = eventsContainer.scrollHeight;
}

function parseAndTranslateNarration(rawMessage) {
  const myRole = initialGameState.joueurRole;

  const parts = rawMessage.split(":");
  if (parts.length > 1 && parts[0] === "MOVE") {
    const [, player, direction] = parts;
    const isMyAction = player === myRole;
    const who = isMyAction ? "Vous" : "L'adversaire";
    const verb = isMyAction
      ? direction === "avance"
        ? "avancez"
        : "reculez"
      : direction === "avance"
      ? "avance"
      : "recule";
    return `${who} ${verb}.`;
  }

  return rawMessage;
}

function renderNarrationEvents(events) {
  const eventsContainer = document.getElementById("narration-events");
  if (!eventsContainer) return;

  eventsContainer.innerHTML = "";

  events.forEach((event) => {
    const message = parseAndTranslateNarration(event.message);
    if (message) {
      const eventDiv = document.createElement("div");
      eventDiv.className = "narration-event";
      eventDiv.innerHTML = `<span class="timestamp">[${event.time}]</span> <span class="message">${message}</span>`;
      eventsContainer.appendChild(eventDiv);
    }
  });

  eventsContainer.scrollTop = eventsContainer.scrollHeight;
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
