document.addEventListener("DOMContentLoaded", () => {
  const selectionForm = document.querySelector("#selection-joueur form");
  const selectionContainer = document.querySelector("#selection-joueur");
  const statutJ1 = document.getElementById("statut-j1");
  const statutJ2 = document.getElementById("statut-j2");
  const gestionPartieContainer = document.getElementById("gestion-partie");
  const quitterPartieBouton = document.getElementById("quitter-partie");
  const compteAReboursMessage = document.getElementById(
    "compte-a-rebours-message"
  );

  let pollingInterval;
  let countdownStarted = false;
  let isRedirectingToGame = false;

  const majStatut = (statuts) => {
    statutJ1.textContent = statuts.joueur1_pret ? "Prêt" : "En attente...";
    statutJ2.textContent = statuts.joueur2_pret ? "Prêt" : "En attente...";

    if (statuts.joueur1_pret && statuts.joueur2_pret && !countdownStarted) {
      countdownStarted = true;
      if (compteAReboursMessage) {
        compteAReboursMessage.style.display = "block";
        let secondes = 3;
        compteAReboursMessage.textContent = `La partie commence dans ${secondes} secondes...`;
        const interval = setInterval(() => {
          secondes--;
          if (secondes > 0) {
            compteAReboursMessage.textContent = `La partie commence dans ${secondes} secondes...`;
          } else {
            compteAReboursMessage.textContent = "Lancement de la partie...";
            clearInterval(interval);
            isRedirectingToGame = true;
            window.location.href = "game/index.php";
          }
        }, 1000);
      }
      return;
    }

    const boutonJoueur1 = selectionForm.querySelector(
      'button[value="joueur1"]'
    );
    const boutonJoueur2 = selectionForm.querySelector(
      'button[value="joueur2"]'
    );

    if (boutonJoueur1) boutonJoueur1.disabled = statuts.joueur1_pret;
    if (boutonJoueur2) boutonJoueur2.disabled = statuts.joueur2_pret;

    const monChoix = sessionStorage.getItem("mon_choix");
    if (
      monChoix &&
      ((monChoix === "joueur1" && statuts.joueur1_pret) ||
        (monChoix === "joueur2" && statuts.joueur2_pret))
    ) {
      if (selectionContainer) selectionContainer.style.display = "none";
      if (gestionPartieContainer)
        gestionPartieContainer.style.display = "block";
    } else {
      if (selectionContainer) selectionContainer.style.display = "block";
      if (gestionPartieContainer) gestionPartieContainer.style.display = "none";
    }
  };

  const recupererStatut = () => {
    $.ajax({
      url: "api/statut-partie.php",
      method: "GET",
      dataType: 'json',
      success: function(statuts) {
        majStatut(statuts);
      },
      error: function(jqXHR, textStatus, errorThrown) {
        console.error("Erreur réseau (statut):", errorThrown);
      }
    });
  };

  if (selectionForm) {
    selectionForm.addEventListener("submit", (event) => {
      event.preventDefault();
      const clickedButton = event.submitter;
      if (!clickedButton) return;
      const choixJoueur = clickedButton.value;

      $.ajax({
        url: "api/choix-joueur.php",
        method: "POST",
        data: { choix_joueur: choixJoueur },
        success: function() {
          sessionStorage.setItem("mon_choix", choixJoueur);
          recupererStatut();
        },
        error: function(jqXHR, textStatus, errorThrown) {
          console.error("Erreur lors de la sélection du joueur:", errorThrown);
        }
      });
    });
  }

  if (quitterPartieBouton) {
    quitterPartieBouton.addEventListener("click", () => {
      const monChoix = sessionStorage.getItem("mon_choix");
      if (!monChoix) return;

      $.ajax({
        url: "api/quitter-partie.php",
        method: "POST",
        data: { choix_joueur: monChoix },
        success: function() {
          sessionStorage.removeItem("mon_choix");
          recupererStatut();
        },
        error: function(jqXHR, textStatus, errorThrown) {
          console.error("Erreur pour quitter la partie:", errorThrown);
        }
      });
    });
  }

  window.addEventListener("beforeunload", () => {
    if (isRedirectingToGame) {
      return;
    }
    const monChoix = sessionStorage.getItem("mon_choix");
    if (monChoix) {
      const formData = new URLSearchParams();
      formData.append("choix_joueur", monChoix);
      navigator.sendBeacon("api/quitter-partie.php", formData);
      sessionStorage.removeItem("mon_choix");
    }
  });

  recupererStatut();
  pollingInterval = setInterval(recupererStatut, 1000);
});
