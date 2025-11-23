function checkScreenSize() {
  const mobileMaxWidth = 1024;

  if (window.innerWidth <= mobileMaxWidth) {
    document.body.innerHTML = `
            <div style="display: flex; flex-direction: column; justify-content: center; align-items: center; height: 100vh; text-align: center; font-family: sans-serif; background-color: #f8f9fa;">
                <h1 style="color: #dc3545;">Accès non autorisé</h1>
                <p style="font-size: 1.2em;">Ce site est optimisé pour une expérience sur ordinateur. Veuillez vous connecter depuis un ordinateur pour continuer.</p>
            </div>
        `;
    document.body.style.margin = "0";
    document.body.style.overflow = "hidden";
  }
}

window.onload = checkScreenSize;
window.onresize = checkScreenSize;
