<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'sitemartin');
$conn->set_charset("utf8mb4");

// Vérification de la connexion
if ($conn->connect_error) {
    die("La connexion a échoué : " . $conn->connect_error);
}

// Vérification de la session utilisateur
if (!isset($_SESSION['email']) || !isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit();
}

$userEmail = $_SESSION['email'];
$userId = $_SESSION['user_id'];
$userNom = isset($_SESSION['nom_utilisateur']) ? $_SESSION['nom_utilisateur'] : "Invité";

// Récupération des produits
$sql = "SELECT * FROM products";
$result = $conn->query($sql);
$products = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Traitement de l'ajout au panier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = array('success' => false, 'message' => '');
    
    if (isset($_POST['produit_id']) && isset($_POST['quantite'])) {
        $produit_id = intval($_POST['produit_id']);
        $quantite = max(1, intval($_POST['quantite']));
        
        try {
            // Vérifier si le produit existe déjà dans le panier
            $check_sql = "SELECT quantite FROM panier WHERE user_id = ? AND produit_id = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param("ii", $userId, $produit_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Mise à jour de la quantité
                $row = $result->fetch_assoc();
                $nouvelle_quantite = $row['quantite'] + $quantite;
                $update_sql = "UPDATE panier SET quantite = ? WHERE user_id = ? AND produit_id = ?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("iii", $nouvelle_quantite, $userId, $produit_id);
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Quantité mise à jour dans le panier';
                } else {
                    $response['message'] = 'Erreur lors de la mise à jour : ' . $conn->error;
                }
            } else {
                // Ajout d'un nouveau produit
                $insert_sql = "INSERT INTO panier (user_id, produit_id, quantite, date_ajout) VALUES (?, ?, ?, NOW())";
                $stmt = $conn->prepare($insert_sql);
                $stmt->bind_param("iii", $userId, $produit_id, $quantite);
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Produit ajouté au panier';
                } else {
                    $response['message'] = 'Erreur lors de l\'insertion : ' . $conn->error;
                }
            }
        } catch (Exception $e) {
            $response['message'] = 'Erreur : ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Données manquantes';
    }
    
    echo json_encode($response);
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../CSS/StyleAccueil1.css" rel="stylesheet">
    <link href="../CSS/navbar.css" rel="stylesheet">
    <link rel="icon" href="../Images/iconSite.png">
    <title>Accueil - SiteDeAdam</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</head>
<body>

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
            <li><a href="./magasin1.php">Mon Panier</a></li>
            <li class="dropdown">
                <a href="#" id="userEmail" class="dropdown-toggle"><?php echo htmlspecialchars($userEmail); ?> ▼</a>
                <ul class="dropdown-menu">
                    <li><a href="./mdp.php">Changer de mot de passe</a></li>
                    <li><a href="./historique.php">Historique des Achats</a></li>
                    <li><a id="logout-btn" href="#">Déconnexion</a></li>
                </ul>
            </li>
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
        <h1>Bienvenue, <?php echo htmlspecialchars($userNom); ?> !</h1>
        <p>Découvrez des images générées par l'IA à des prix abordables.</p>
        <a href="#sep" class="btn-hero">Voir les articles</a>
    </div>
</section>

<div class="separator" id="sep"></div>

<section id="suite">
    <h1 class="titre">Nos articles</h1>
    <div class="content">
        <?php foreach ($products as $product): ?>
        <div class="box">
            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['description']); ?>">
            <div class="content">
                <div>
                    <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                    <p><?php echo htmlspecialchars($product['description']); ?></p>
                    <p>Prix Unitaire : <?php echo number_format($product['price'], 2); ?>€</p>
                    <a href="#" class="buy-btn" data-product-id="<?php echo $product['id']; ?>" 
                       data-price="<?php echo $product['price']; ?>">Acheter</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<div class="modal-overlay" id="quantity-modal" style="display: none;">
    <div class="modal-content">
        <h2>Choisir la quantité</h2>
        <div class="quantity-selector">
            <button type="button" id="decrease">-</button>
            <span id="quantity-display">1</span>
            <button type="button" id="increase">+</button>
        </div>
        <p id="total-price">Prix total : 0€</p>
        <button type="button" id="confirm" class="modal-btn">Confirmer</button>
    </div>
</div>

<div class="modal-overlay" id="logout-modal">
    <div class="modal-content">
        <h2>Voulez-vous vraiment vous déconnecter ?</h2>
        <div class="modal-buttons">
            <button class="confirm-btn" id="confirm-logout">Oui</button>
            <button class="cancel-btn" id="cancel-logout">Non</button>
        </div>
    </div>
</div>


<section class="footer">
    <div class="footer-content">
        <p class="footerTxt">PHP-PDO, Un exemple magnifique pour acheter des œuvres faites par l'IA!</p>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    let currentProductId = null;
    let currentPrice = 0;
    let quantity = 1;

    // Gestionnaire pour les boutons d'achat dans la grille de produits
    $('.box .buy-btn').click(function(e) { // Modifié pour cibler spécifiquement les boutons dans .box
        e.preventDefault();
        currentProductId = $(this).data('product-id');
        currentPrice = parseFloat($(this).data('price'));
        
        console.log('ID:', currentProductId, 'Price:', currentPrice); // Debug
        
        updateTotalPrice();
        $('#quantity-modal').show().css('display', 'flex');
    });

    // Gestion de la quantité
    $('#increase').click(function() {
        quantity = Math.min(quantity + 1, 999);
        updateQuantityDisplay();
    });

    $('#decrease').click(function() {
        quantity = Math.max(quantity - 1, 1);
        updateQuantityDisplay();
    });

    function updateQuantityDisplay() {
        $('#quantity-display').text(quantity);
        updateTotalPrice();
    }

    function updateTotalPrice() {
        const total = (currentPrice * quantity).toFixed(2);
        $('#total-price').text(`Prix total : ${total}€`);
    }

    function resetQuantity() {
        quantity = 1;
        $('#quantity-display').text(quantity);
        currentProductId = null;
        updateTotalPrice();
    }

    // Confirmation de l'achat
    $('#confirm').click(function() {
        if (currentProductId) {
            $.ajax({
                url: window.location.href,
                method: 'POST',
                data: {
                    produit_id: currentProductId,
                    quantite: quantity
                },
                success: function(response) {
                    try {
                        if (typeof response === 'string') {
                            response = JSON.parse(response);
                        }
                        if (response.success) {
                            alert(response.message || 'Produit ajouté au panier');
                        } else {
                            alert(response.message || 'Erreur lors de l\'ajout au panier');
                        }
                    } catch (e) {
                        console.error('Erreur de parsing:', e);
                        alert('Erreur lors de l\'ajout au panier');
                    }
                    $('#quantity-modal').hide();
                    resetQuantity();
                },
                error: function(xhr, status, error) {
                    console.error('Erreur AJAX:', status, error);
                    console.log('Réponse serveur:', xhr.responseText);
                    alert('Erreur lors de l\'ajout au panier');
                }
            });
        }
    });

    // Gestion des modals
    $('.modal-overlay').click(function(e) {
        if (e.target === this) {
            $(this).hide();
            if (this.id === 'quantity-modal') {
                resetQuantity();
            }
        }
    });

    // Gestion de la déconnexion
    $('#logout-btn').click(function(e) {
        e.preventDefault();
        $('#logout-modal').addClass('show');
    });

    $('#confirm-logout').click(function() {
        window.location.href = './accueil.php';
    });

    $('#cancel-logout').click(function() {
        $('#logout-modal').removeClass('show');
    });

    // Fermer le modal de déconnexion si on clique en dehors
    $('#logout-modal').click(function(e) {
        if (e.target === this) {
            $(this).removeClass('show');
        }
    });
});

document.querySelector('.btn-checkout').addEventListener('click', function(e) {
    e.preventDefault();
    document.querySelector('form').submit();
});

</script>

</body>
<script src="../JS/navbar.js"></script>
</html>