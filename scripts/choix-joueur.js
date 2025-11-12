document.addEventListener("DOMContentLoaded", () => {
  const selectionForm = document.querySelector("#selection-joueur form");
  const selectionContainer = document.querySelector("#selection-joueur");
  const statutJ1 = document.getElementById("statut-j1");
  const statutJ2 = document.getElementById("statut-j2");
  const gestionPartieContainer = document.getElementById("gestion-partie");
  const quitterPartieBouton = document.getElementById("quitter-partie");

  let pollingInterval;

  const majStatut = (statuts) => {
    statutJ1.textContent = statuts.joueur1_pret ? "Prêt" : "En attente...";
    statutJ2.textContent = statuts.joueur2_pret ? "Prêt" : "En attente...";

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

  const recupererStatut = async () => {
    try {
      const response = await fetch("api/statut-partie.php");
      if (!response.ok) {
        console.error("Erreur lors de la récupération du statut.");
        return;
      }
      const statuts = await response.json();
      majStatut(statuts);
    } catch (error) {
      console.error("Erreur réseau (statut):", error);
    }
  };

  if (selectionForm) {
    selectionForm.addEventListener("submit", async (event) => {
      event.preventDefault();
      const clickedButton = event.submitter;
      if (!clickedButton) return;
      const choixJoueur = clickedButton.value;

      try {
        const response = await fetch("api/choix-joueur.php", {
          method: "POST",
          body: new URLSearchParams({ choix_joueur: choixJoueur }),
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
        });

        if (response.ok) {
          sessionStorage.setItem("mon_choix", choixJoueur);
          recupererStatut();
        } else {
          console.error(
            "Erreur lors de la sélection du joueur:",
            await response.text()
          );
        }
      } catch (error) {
        console.error("Erreur réseau (choix):", error);
      }
    });
  }

  if (quitterPartieBouton) {
    quitterPartieBouton.addEventListener("click", async () => {
      const monChoix = sessionStorage.getItem("mon_choix");
      if (!monChoix) return;

      try {
        const response = await fetch("api/quitter-partie.php", {
          method: "POST",
          body: new URLSearchParams({ choix_joueur: monChoix }),
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
        });

        if (response.ok) {
          sessionStorage.removeItem("mon_choix");
          recupererStatut();
        } else {
          console.error(
            "Erreur pour quitter la partie:",
            await response.text()
          );
        }
      } catch (error) {
        console.error("Erreur réseau (quitter):", error);
      }
    });
  }

  // Deco automatique quand un joueur quitte la page
  window.addEventListener("beforeunload", () => {
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
