// scripts/narration.js

/**
 * Ajoute un événement de narration qui est stocké en base de données et visible par les deux joueurs.
 * @param {string} message - Le message objectif à enregistrer (ex: "MOVE:joueur1:avance").
 */
async function addNarrationEvent(message) {
    try {
        await fetch('api/add-narration.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ message: message })
        });
        // On ne rafraîchit pas immédiatement, on attend le prochain poll pour garder la synchro.
    } catch (error) {
        console.error('Erreur lors de l\'ajout de l\'événement de narration:', error);
    }
}

/**
 * Ajoute un événement de narration local, visible uniquement par le joueur actuel.
 * @param {string} localMessage - Le message à afficher localement.
 */
function addLocalNarrationEvent(localMessage) {
    const eventsContainer = document.getElementById('narration-events');
    if (!eventsContainer) return;

    const now = new Date();
    const time = [now.getHours(), now.getMinutes(), now.getSeconds()].map(n => n.toString().padStart(2, '0')).join(':');

    const eventDiv = document.createElement('div');
    eventDiv.className = 'narration-event local-feedback';
    eventDiv.innerHTML = `<span class="timestamp">[${time}]</span> <span class="message" style="color: #ffcc00;">${localMessage}</span>`;
    eventsContainer.appendChild(eventDiv);
    eventsContainer.scrollTop = eventsContainer.scrollHeight;
}


/**
 * Traduit un message de narration objectif en un message compréhensible pour le joueur.
 * @param {string} rawMessage - Le message brut de la base de données.
 * @returns {string} Le message traduit.
 */
function parseAndTranslateNarration(rawMessage) {
    const myRole = initialGameState.joueurRole;

    const parts = rawMessage.split(':');
    if (parts.length > 1 && parts[0] === 'MOVE') {
        const [, player, direction] = parts;
        const isMyAction = player === myRole;
        const who = isMyAction ? 'Vous' : 'L\'adversaire';
        const verb = isMyAction ? (direction === 'avance' ? 'avancez' : 'reculez') : (direction === 'avance' ? 'avance' : 'recule');
        return `${who} ${verb}.`;
    }

    // Par défaut, retourne le message brut (ex: messages de bienvenue)
    return rawMessage;
}

/**
 * Affiche une liste d'événements dans la boîte de narration.
 * @param {Array} events - La liste des événements à afficher.
 */
function renderNarrationEvents(events) {
    const eventsContainer = document.getElementById('narration-events');
    if (!eventsContainer) return;

    eventsContainer.innerHTML = ''; // Toujours tout effacer pour resynchroniser

    events.forEach(event => {
        const message = parseAndTranslateNarration(event.message);
        if (message) { // Ne pas afficher les messages qui retournent une chaîne vide
            const eventDiv = document.createElement('div');
            eventDiv.className = 'narration-event';
            eventDiv.innerHTML = `<span class="timestamp">[${event.time}]</span> <span class="message">${message}</span>`;
            eventsContainer.appendChild(eventDiv);
        }
    });

    eventsContainer.scrollTop = eventsContainer.scrollHeight;
}

/**
 * Récupère les derniers événements de narration depuis le serveur.
 */
async function fetchNarrationEvents() {
    try {
        const response = await fetch(`api/get-narration.php`);
        const events = await response.json();

        if (events.erreur) {
            console.error(events.erreur);
            return;
        }

        renderNarrationEvents(events);

    } catch (error) {
        // Silencieux pour ne pas spammer la console en cas de problème réseau temporaire
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // On garde un intervalle raisonnable pour ne pas surcharger le serveur
    setInterval(fetchNarrationEvents, 2500); 
    fetchNarrationEvents(); // Premier appel
});