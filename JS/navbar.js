// Gérer l'affichage du dropdown
const dropdownToggle = document.getElementById('userEmail');
const dropdownMenu = document.querySelector('.dropdown-menu');

// Ouvrir ou fermer le menu déroulant
dropdownToggle.addEventListener('click', function (e) {
    e.preventDefault(); // Empêche l'action par défaut
    dropdownMenu.classList.toggle('show'); // Affiche/masque le menu
});

// Fermer le menu si on clique ailleurs
document.addEventListener('click', function (e) {
    if (!dropdownToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
        dropdownMenu.classList.remove('show');
    }
});

// Gérer la déconnexion
const logoutButton = document.getElementById('logout-btn');
const modalOverlay = document.getElementById('logout-modal');
const confirmLogoutButton = document.getElementById('confirm-logout');
const cancelLogoutButton = document.getElementById('cancel-logout');

// Ouvrir le pop-up
logoutButton.addEventListener('click', (event) => {
    event.preventDefault(); // Empêche toute action par défaut (comme une redirection immédiate)
    modalOverlay.style.display = 'flex';
});

// Fermer le pop-up
cancelLogoutButton.addEventListener('click', () => {
    modalOverlay.style.display = 'none';
});

// Confirmer la déconnexion
confirmLogoutButton.addEventListener('click', () => {
    window.location.href = './accueil.php'; // Redirection vers la page de déconnexion
});