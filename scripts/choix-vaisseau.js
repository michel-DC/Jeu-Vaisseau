document.addEventListener('DOMContentLoaded', () => {
    const selectionVaisseau = document.getElementById('selection-vaisseau');
    const salleAttente = document.getElementById('salle-attente-vaisseau');
    
    const prevButton = document.getElementById('prev-vaisseau');
    const nextButton = document.getElementById('next-vaisseau');
    const validerButton = document.getElementById('valider-choix');
    const vaisseauImage = document.getElementById('vaisseau-image');

    let currentIndex = 0;
    let pollingInterval;

    function showVaisseau(index) {
        vaisseauImage.style.opacity = 0;
        setTimeout(() => {
            vaisseauImage.src = vaisseaux[index];
            vaisseauImage.style.opacity = 1;
        }, 300);
    }

    // Initialiser le carousel
    if (vaisseaux && vaisseaux.length > 0) {
        showVaisseau(currentIndex);
    } else {
        selectionVaisseau.innerHTML = "<p>Aucun vaisseau n'est disponible.</p>";
        return;
    }

    prevButton.addEventListener('click', () => {
        currentIndex = (currentIndex > 0) ? currentIndex - 1 : vaisseaux.length - 1;
        showVaisseau(currentIndex);
    });

    nextButton.addEventListener('click', () => {
        currentIndex = (currentIndex < vaisseaux.length - 1) ? currentIndex + 1 : 0;
        showVaisseau(currentIndex);
    });

    validerButton.addEventListener('click', () => {
        const vaisseauChoisi = vaisseaux[currentIndex];

        fetch('api/enregistrer-vaisseau.php', {
            method: 'POST',
            body: JSON.stringify({ vaisseau: vaisseauChoisi }),
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                selectionVaisseau.style.display = 'none';
                salleAttente.style.display = 'block';
                startPolling();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur lors de la sauvegarde du vaisseau:', error);
            alert('Une erreur est survenue. Veuillez réessayer.');
        });
    });

    function startPolling() {
        pollingInterval = setInterval(checkGameStatus, 2000); // Vérifie toutes les 2 secondes
    }

    function checkGameStatus() {
        fetch('api/statut-partie.php')
            .then(response => response.json())
            .then(data => {
                if (data.vaisseaux_choisis) {
                    clearInterval(pollingInterval);
                    window.location.href = 'game.php';
                }
            })
            .catch(error => {
                console.error('Erreur lors de la vérification du statut:', error);
            });
    }
});
