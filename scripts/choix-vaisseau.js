// Configuration des vaisseaux avec leurs spécificités
const VAISSEAUX_CONFIG = {
    'vaisseau-1': {
        id: 'vaisseau-1',
        nom: 'Le Tout-Terrain',
        image: 'assets/vaisseaux/vaisseau-1.webp',
        description: 'Peut se déplacer 2 fois par tour avant d\'attaquer',
        bonus: {
            mouvements_max: 2,
            hp_initial: 1000,
            bonus_degats: 0,
            magicien_puissance: 1,
            drones_attaque: 1,
            drones_reconnaissance: 2
        }
    },
    'vaisseau-2': {
        id: 'vaisseau-2',
        nom: 'Le Falcon',
        image: 'assets/vaisseaux/vaisseau-2.webp',
        description: 'Commence avec 2 drones d\'attaque et 3 drones de reconnaissance',
        bonus: {
            mouvements_max: 1,
            hp_initial: 1000,
            bonus_degats: 0,
            magicien_puissance: 1,
            drones_attaque: 2,
            drones_reconnaissance: 3
        }
    },
    'vaisseau-3': {
        id: 'vaisseau-3',
        nom: 'Le Chaos',
        image: 'assets/vaisseaux/vaisseau-3.png',
        description: 'Ses attaques infligent +20 dégâts supplémentaires',
        bonus: {
            mouvements_max: 1,
            hp_initial: 1000,
            bonus_degats: 20,
            magicien_puissance: 1,
            drones_attaque: 1,
            drones_reconnaissance: 2
        }
    },
    'vaisseau-4': {
        id: 'vaisseau-4',
        nom: 'Le Lego',
        image: 'assets/vaisseaux/vaisseau-4.webp',
        description: 'Commence avec 1200 points de vie au lieu de 1000',
        bonus: {
            mouvements_max: 1,
            hp_initial: 1200,
            bonus_degats: 0,
            magicien_puissance: 1,
            drones_attaque: 1,
            drones_reconnaissance: 2
        }
    },
    'Vaisseau-5': {
        id: 'Vaisseau-5',
        nom: 'Le Magique',
        image: 'assets/vaisseaux/Vaisseau-5.png',
        description: 'Commence avec un magicien de puissance 3',
        bonus: {
            mouvements_max: 1,
            hp_initial: 1000,
            bonus_degats: 0,
            magicien_puissance: 3,
            drones_attaque: 1,
            drones_reconnaissance: 2
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const selectionVaisseau = document.getElementById('selection-vaisseau');
    const salleAttente = document.getElementById('salle-attente-vaisseau');

    const prevButton = document.getElementById('prev-vaisseau');
    const nextButton = document.getElementById('next-vaisseau');
    const validerButton = document.getElementById('valider-choix');
    const vaisseauImage = document.getElementById('vaisseau-image');
    const vaisseauNom = document.getElementById('vaisseau-nom');
    const vaisseauDescription = document.getElementById('vaisseau-description');

    // Convertir la config en tableau
    const vaisseauxArray = Object.values(VAISSEAUX_CONFIG);
    let currentIndex = 0;
    let pollingInterval;

    function showVaisseau(index) {
        const vaisseau = vaisseauxArray[index];

        vaisseauImage.style.opacity = 0;
        vaisseauNom.style.opacity = 0;
        vaisseauDescription.style.opacity = 0;

        setTimeout(() => {
            vaisseauImage.src = vaisseau.image;
            vaisseauNom.textContent = vaisseau.nom;
            vaisseauDescription.textContent = vaisseau.description;

            vaisseauImage.style.opacity = 1;
            vaisseauNom.style.opacity = 1;
            vaisseauDescription.style.opacity = 1;
        }, 300);
    }

    // Initialiser le carousel
    if (vaisseauxArray && vaisseauxArray.length > 0) {
        showVaisseau(currentIndex);
    } else {
        selectionVaisseau.innerHTML = "<p>Aucun vaisseau n'est disponible.</p>";
        return;
    }

    prevButton.addEventListener('click', () => {
        currentIndex = (currentIndex > 0) ? currentIndex - 1 : vaisseauxArray.length - 1;
        showVaisseau(currentIndex);
    });

    nextButton.addEventListener('click', () => {
        currentIndex = (currentIndex < vaisseauxArray.length - 1) ? currentIndex + 1 : 0;
        showVaisseau(currentIndex);
    });

    validerButton.addEventListener('click', () => {
        const vaisseauChoisi = vaisseauxArray[currentIndex];

        $.ajax({
            url: 'api/enregistrer-vaisseau.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                vaisseau_id: vaisseauChoisi.id,
                vaisseau_image: vaisseauChoisi.image,
                bonus: vaisseauChoisi.bonus
            }),
            dataType: 'json',
            success: function (data) {
                if (data.success) {
                    selectionVaisseau.style.display = 'none';
                    salleAttente.style.display = 'block';
                    startPolling();
                } else {
                    alert('Erreur: ' + data.message);
                }
            },
            error: function () {
                alert('Une erreur est survenue. Veuillez réessayer.');
            }
        });
    });

    function startPolling() {
        pollingInterval = setInterval(checkGameStatus, 2000);
    }

    function checkGameStatus() {
        $.ajax({
            url: 'api/statut-partie.php',
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                if (data.vaisseaux_choisis) {
                    clearInterval(pollingInterval);
                    window.location.href = 'game.php';
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('Erreur lors de la vérification du statut:', errorThrown);
            }
        });
    }
});
