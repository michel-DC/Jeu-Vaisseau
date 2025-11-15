document.addEventListener("DOMContentLoaded", () => {
    const sectionChoix = document.getElementById("choix-initial");
    const sectionAttente = document.getElementById("salle-attente");

    const formCreer = document.getElementById("form-creer-partie");
    const formRejoindre = document.getElementById("form-rejoindre-partie");
    
    const inputIdPartie = document.getElementById("id-partie-input");
    const idPartieAffiche = document.getElementById("id-partie-affiche");
    const btnCopier = document.getElementById("copier-id");
    
    const statutJ1 = document.getElementById("statut-j1");
    const statutJ2 = document.getElementById("statut-j2");
    const compteAReboursMessage = document.getElementById("compte-a-rebours-message");

    let pollingInterval;

    const genererPartieId = (longueur = 6) => {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let resultat = '';
        for (let i = 0; i < longueur; i++) {
            resultat += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return resultat;
    };

    const afficherSalleAttente = (idPartie) => {
        idPartieAffiche.textContent = idPartie;
        sectionChoix.style.display = "none";
        sectionAttente.style.display = "block";
        demarrerPolling(idPartie);
    };

    const demarrerPolling = (idPartie) => {
        if (pollingInterval) clearInterval(pollingInterval);

        pollingInterval = setInterval(async () => {
            try {
                const response = await fetch(`api/statut-partie.php?partie_id=${idPartie}`);
                if (!response.ok) {
                    console.error("Erreur lors de la récupération du statut.");
                    clearInterval(pollingInterval);
                    return;
                }
                const statut = await response.json();
                majStatut(statut);
            } catch (error) {
                console.error("Erreur réseau (statut):", error);
            }
        }, 2000);
    };

    const majStatut = (statut) => {
        statutJ1.textContent = statut.joueur1_pret ? "Connecté" : "En attente...";
        statutJ2.textContent = statut.joueur2_pret ? "Connecté" : "En attente...";

        if (statut.statut === 'complete') {
            clearInterval(pollingInterval);
            lancerCompteARebours();
        }
    };

    const lancerCompteARebours = () => {
        compteAReboursMessage.style.display = "block";
        let secondes = 3;
        compteAReboursMessage.textContent = `Les deux joueurs sont connectés ! La partie commence dans ${secondes}s...`;
        
        const interval = setInterval(() => {
            secondes--;
            if (secondes > 0) {
                compteAReboursMessage.textContent = `Les deux joueurs sont connectés ! La partie commence dans ${secondes}s...`;
            } else {
                compteAReboursMessage.textContent = "Lancement de la partie...";
                clearInterval(interval);
                window.location.href = "choix-vaisseau.php";
            }
        }, 1000);
    };

    formCreer.addEventListener("submit", async (e) => {
        e.preventDefault();
        const idPartie = genererPartieId();
        
        try {
            const response = await fetch("api/creer-partie.php", {
                method: "POST",
                body: new URLSearchParams({ partie_id: idPartie }),
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
            });

            const data = await response.json();
            if (response.ok) {
                afficherSalleAttente(idPartie);
            } else {
                alert(`Erreur: ${data.erreur}`);
            }
        } catch (error) {
            console.error("Erreur réseau (création):", error);
            alert("Erreur réseau lors de la création de la partie.");
        }
    });

    formRejoindre.addEventListener("submit", async (e) => {
        e.preventDefault();
        const idPartie = inputIdPartie.value.trim().toUpperCase();
        if (!idPartie) {
            alert("Veuillez entrer un ID de partie.");
            return;
        }

        try {
            const response = await fetch("api/rejoindre-partie.php", {
                method: "POST",
                body: new URLSearchParams({ partie_id: idPartie }),
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
            });

            const data = await response.json();
            if (response.ok) {
                afficherSalleAttente(idPartie);
            } else {
                alert(`Erreur: ${data.erreur}`);
            }
        } catch (error) {
            console.error("Erreur réseau (rejoindre):", error);
            alert("Erreur réseau pour rejoindre la partie.");
        }
    });

    btnCopier.addEventListener("click", () => {
        navigator.clipboard.writeText(idPartieAffiche.textContent).then(() => {
            btnCopier.textContent = "Copié !";
            setTimeout(() => {
                btnCopier.textContent = "Copier l'ID";
            }, 2000);
        }).catch(err => {
            console.error('Erreur de copie:', err);
            alert("La copie a échoué.");
        });
    });
});