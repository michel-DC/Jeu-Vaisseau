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
  const compteAReboursMessage = document.getElementById(
    "compte-a-rebours-message"
  );

  let pollingInterval;

  const genererPartieId = (longueur = 6) => {
    const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    let resultat = "";
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
      $.ajax({
        url: `api/statut-partie.php?partie_id=${idPartie}`,
        method: "GET",
        dataType: "json",
        success: function (statut) {
          majStatut(statut);
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.error(
            "Erreur lors de la récupération du statut.",
            textStatus,
            errorThrown
          );
          clearInterval(pollingInterval);
        },
      });
    }, 2000);
  };

  const majStatut = (statut) => {
    statutJ1.textContent = statut.joueur1_pret ? "Connecté" : "En attente...";
    statutJ2.textContent = statut.joueur2_pret ? "Connecté" : "En attente...";

    if (statut.statut === "complete") {
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

  formCreer.addEventListener("submit", (e) => {
    e.preventDefault();
    const idPartie = genererPartieId();

    $.ajax({
      url: "api/creer-partie.php",
      method: "POST",
      data: { partie_id: idPartie },
      dataType: "json",
      success: function (data) {
        afficherSalleAttente(idPartie);
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("Erreur réseau (création):", textStatus, errorThrown);
        alert("Erreur réseau lors de la création de la partie.");
      },
    });
  });

  formRejoindre.addEventListener("submit", (e) => {
    e.preventDefault();
    const idPartie = inputIdPartie.value.trim().toUpperCase();
    if (!idPartie) {
      alert("Veuillez entrer un ID de partie.");
      return;
    }

    $.ajax({
      url: "api/rejoindre-partie.php",
      method: "POST",
      data: { partie_id: idPartie },
      dataType: "json",
      success: function (data) {
        if (data.succes) {
          afficherSalleAttente(idPartie);
        } else {
          alert(`Erreur: ${data.erreur}`);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("Erreur réseau (rejoindre):", textStatus, errorThrown);
        alert("Erreur réseau pour rejoindre la partie.");
      },
    });
  });

  btnCopier.addEventListener("click", () => {
    navigator.clipboard
      .writeText(idPartieAffiche.textContent)
      .then(() => {
        btnCopier.textContent = "Copié !";
        setTimeout(() => {
          btnCopier.textContent = "Copier l'ID";
        }, 2000);
      })
      .catch((err) => {
        console.error("Erreur de copie:", err);
        alert("La copie a échoué.");
      });
  });
});
