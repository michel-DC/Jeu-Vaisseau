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

        $.ajax({
            url: 'api/enregistrer-vaisseau.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ vaisseau: vaisseauChoisi }),
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    selectionVaisseau.style.display = 'none';
                    salleAttente.style.display = 'block';
                    startPolling();
                } else {
                    alert('Erreur: ' + data.message);
                }
            },
            error: function() {
                alert('Une erreur est survenue. Veuillez réessayer.');
            }
        });
    });

    function startPolling() {
        pollingInterval = setInterval(checkGameStatus, 2000); // Vérifie toutes les 2 secondes
    }

    function checkGameStatus() {
        $.ajax({
            url: 'api/statut-partie.php',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.vaisseaux_choisis) {
                    clearInterval(pollingInterval);
                    window.location.href = 'game.php';
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Erreur lors de la vérification du statut:', errorThrown);
            }
        });
    }
});
