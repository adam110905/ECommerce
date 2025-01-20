<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">    
    <link href="../CSS/StyleAcceuil.css" rel="stylesheet">
    <link href="../CSS/navbar.css" rel="stylesheet">
    <link rel="icon" href="../Images/iconSite.png">
    <title>SiteDeAdam - Accueil</title>
</head>
<body>    
    <!-- Navbar -->
    <header>
        <div class="logo-container">
            <a href="#hero" class="logo">
                <img src="../Images/iconSite.png" alt="Logo Image" class="logo-image">
                SiteDeAdam
            </a>
        </div>
        <input type="checkbox" id="nav_check" hidden>
        <nav>
            <ul>
                <li><a href="./magasin.php" id="Accueil">Mon Panier</a></li>
                <li><a href="./compte.php" id="Accueil">Compte</a></li>
            </ul>
        </nav>
        <label for="nav_check" class="hamburger">
            <div></div>
            <div></div>
            <div></div>
        </label>
    </header>

    <section id="hero">
        <div class="hero-slider">
            <img src="../Images/IA_1.jpg" alt="Image 1">
            <img src="../Images/IA_2.jpg" alt="Image 2">
            <img src="../Images/IA_3.jpg" alt="Image 3">
            <img src="../Images/IA_4.jpg" alt="Image 4">
            <img src="../Images/IA_5.jpg" alt="Image 5">
        </div>
        <div class="hero-content">
            <div class="text-container1">
                <h1 class="animated-text">Bienvenue Sur SiteDeAdam !</h1>
                <p class="animated-text">Vous pouvez ici acheter des images générées par l'IA à prix abordables !</p>
            </div>
            <a href="#sep" class="btn-hero">Voir les articles</a>
        </div>
    </section>
    <div class="separator" id="sep"></div>

    <section id="suite">
        <h1 class="titre">Nos articles</h1>
        <div class="content">
            <?php
            // Connexion à la base de données
            $conn = new mysqli('localhost', 'root', '', 'sitemartin');

            // Vérification de la connexion
            if ($conn->connect_error) {
                die("Erreur de connexion : " . $conn->connect_error);
            }

            // Requête pour récupérer les produits
            $sql = "SELECT * FROM products"; 
            $result = $conn->query($sql);

            // Vérification s'il y a des produits
            if ($result->num_rows > 0) {
                // Affichage des produits
                while ($row = $result->fetch_assoc()) {
                    echo '
                    <div class="box">
                        <img src="' . $row['image_url'] . '" alt="' . $row['description'] . '">
                        <div class="content">
                            <div>
                                <h4>' . $row['name'] . '</h4>
                                <p>' . $row['description'] . '</p>
                                <p>Prix Unitaire : ' . $row['price'] . '€</p>
                                <a href="#" class="buy-btn" data-product-id="' . $row['id'] . '" data-price="' . $row['price'] . '">Acheter</a>
                            </div>
                        </div>
                    </div>';
                }
            } else {
                echo "<p>Aucun produit trouvé.</p>";
            }

            // Fermeture de la connexion
            $conn->close();
            ?>
        </div>
    </section>

    <!-- Modal pour demander la connexion -->
    <div class="modal-overlay" id="login-modal" style="display: none;">
        <div class="modal-content">
            <h2>Vous devez être connecté</h2>
            <p>Pour effectuer un achat, veuillez vous connecter ou créer un compte.</p>
            <div class="modal-buttons">
                <a href="./compte.php" class="modal-btn login-btn">S'inscrire ou se connecter</a>
            </div>
        </div>
    </div>

    <section class="footer">
        <div class="footer-content">
            <p class="footerTxt">SiteDeAdam, Un exemple magnifique pour acheter des œuvres faites par l'IA!</p>
            <br>
            <p class="footerTxt">Nos réseaux : </p>
            <br>
            <div class="footerImg">
                <a class="insta" target="blank" href="https://www.instagram.com/"> 
                    <img src="../Images/Instagram.jpg" alt="Instagram" title="Instagram" />
                </a>
                <a target="blank" href="https://www.google.com/maps"> 
                    <img src="../Images/Maps.png" alt="Maps" title="Maps" />
                </a>
            </div>
            <br>
            <br>
            <p class="footerTxt">© 2024 SiteDeAdam. Tous droits réservés.</p>
        </div>
    </section>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sélection des éléments
        const buyButtons = document.querySelectorAll(".buy-btn");
        const loginModal = document.getElementById("login-modal");
        const slider = document.querySelector('.hero-slider');
        const images = document.querySelectorAll('.hero-slider img');
        let currentIndex = 0;

        // Fonction pour ouvrir le modal de connexion
        function openLoginModal() {
            loginModal.style.display = "flex";
        }

        // Fonction pour fermer le modal
        function closeLoginModal() {
            loginModal.style.display = "none";
        }

        // Gérer les clics sur les boutons d'achat
        buyButtons.forEach((button) => {
            button.addEventListener("click", (e) => {
                e.preventDefault();
                openLoginModal();
            });
        });

        // Fermer le modal en cliquant en dehors
        loginModal.addEventListener("click", (e) => {
            if (e.target === loginModal) {
                closeLoginModal();
            }
        });

        // Fonctions pour le slider
        function changeSlide() {
            if (currentIndex < images.length - 1) {
                currentIndex++;
            } else {
                setTimeout(reverseSlide, 1000);
                currentIndex = images.length - 1;
            }
            updateSlider();
        }

        function reverseSlide() {
            if (currentIndex > 0) {
                currentIndex--;
            } else {
                currentIndex = 0;
            }
            updateSlider();
        }

        function updateSlider() {
            const offset = -100 * currentIndex;
            slider.style.transform = `translateX(${offset}vw)`;
        }

        // Démarrer le slider
        setInterval(changeSlide, 5000);
    });
    </script>
</body>
</html>