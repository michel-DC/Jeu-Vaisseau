function runCoinFlipAnimation(iStart) {
    const coinFlipPopup = document.getElementById('coin-flip-popup');
    const turnResultPopup = document.getElementById('turn-result-popup');
    const closeTurnPopupButton = document.getElementById('close-turn-popup');
    const mySideSpan = document.getElementById('my-side');
    const opponentSideSpan = document.getElementById('opponent-side');
    const coin = document.getElementById('coin');
    const flipResultP = document.getElementById('flip-result');
    const turnResultTitle = document.getElementById('turn-result-title');

    function showTurnResult(start) {
        if (start) {
            turnResultTitle.textContent = "Vous commencez la partie !";
        } else {
            turnResultTitle.textContent = "L'adversaire commence la partie.";
        }
        turnResultPopup.style.display = 'flex';
    }

    const mySide = 'Pile';
    const opponentSide = 'Face';
    mySideSpan.textContent = mySide;
    opponentSideSpan.textContent = opponentSide;

    coinFlipPopup.style.display = 'flex';
    coin.classList.add('flipping');

    setTimeout(() => {
        coin.classList.remove('flipping');
        const winningSide = iStart ? mySide : opponentSide;

        if (winningSide === 'Pile') {
            coin.style.transform = 'rotateY(0deg)';
        } else {
            coin.style.transform = 'rotateY(180deg)';
        }

        flipResultP.textContent = `Résultat: ${winningSide}!`;

        setTimeout(() => {
            coinFlipPopup.style.display = 'none';
            showTurnResult(iStart);
        }, 1000); // Also reducing this timeout for quicker dismissal

    }, 2000);

    closeTurnPopupButton.addEventListener('click', () => {
        turnResultPopup.style.display = 'none';
    });
}
