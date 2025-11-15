document.addEventListener('DOMContentLoaded', () => {
    const coinFlipPopup = document.getElementById('coin-flip-popup');
    const turnResultPopup = document.getElementById('turn-result-popup');
    const closeTurnPopupButton = document.getElementById('close-turn-popup');
    const mySideSpan = document.getElementById('my-side');
    const opponentSideSpan = document.getElementById('opponent-side');
    const coin = document.getElementById('coin');
    const flipResultP = document.getElementById('flip-result');
    const turnResultTitle = document.getElementById('turn-result-title');

    function startCoinFlipProcess() {
        // 1. Assigner Pile ou Face
        const sides = ['Pile', 'Face'];
        const mySide = sides[Math.floor(Math.random() * 2)];
        const opponentSide = mySide === 'Pile' ? 'Face' : 'Pile';

        mySideSpan.textContent = mySide;
        opponentSideSpan.textContent = opponentSide;

        // 2. Afficher le popup et lancer l'animation
        coinFlipPopup.style.display = 'flex';
        coin.classList.add('flipping');

        // 3. Déterminer le résultat après l'animation
        setTimeout(() => {
            coin.classList.remove('flipping');
            const winningSide = sides[Math.floor(Math.random() * 2)];

            // Arrêter l'animation sur la bonne face
            if (winningSide === 'Pile') {
                coin.style.transform = 'rotateY(0deg)';
            } else {
                coin.style.transform = 'rotateY(180deg)';
            }

            flipResultP.textContent = `Résultat: ${winningSide}!`;

            // 4. Afficher le popup de résultat du tour
            setTimeout(() => {
                coinFlipPopup.style.display = 'none';

                const iWon = mySide === winningSide;
                if (iWon) {
                    turnResultTitle.textContent = "Vous commencez la partie !";
                } else {
                    turnResultTitle.textContent = "L'adversaire commence la partie.";
                }

                turnResultPopup.style.display = 'flex';

            }, 2000); // Attendre 2s avant de montrer le 2ème popup

        }, 3000); // Durée de l'animation de 3s
    }

    // Gérer la fermeture du popup de résultat
    closeTurnPopupButton.addEventListener('click', () => {
        turnResultPopup.style.display = 'none';
        // Ici, on pourrait débloquer le jeu ou commencer le premier tour
    });

    // Démarrer le processus dès que la page est chargée
    startCoinFlipProcess();
});
