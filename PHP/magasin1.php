<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli('localhost', 'root', '', 'sitemartin');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Vérification utilisateur
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit();
}

$userId = $_SESSION['user_id'];
$userEmail = isset($_SESSION['email']) ? $_SESSION['email'] : '';
$userNom = isset($_SESSION['nom_utilisateur']) ? $_SESSION['nom_utilisateur'] : "Invité";

// Récupération du panier
$sql = "SELECT p.id as product_id, p.name, p.description, p.price, p.image_url, c.quantite, 
        (p.price * c.quantite) as total 
        FROM panier c 
        JOIN products p ON c.produit_id = p.id 
        WHERE c.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$cartItems = [];
$totalGeneral = 0;

while ($row = $result->fetch_assoc()) {
    $cartItems[] = $row;
    $totalGeneral += $row['total'];
}

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($_POST['action']) {
        case 'checkout':
            // 1. Vérifier le panier
            $check_cart = "SELECT COUNT(*) as count FROM panier WHERE user_id = ?";
            $stmt = $conn->prepare($check_cart);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $cart_count = $stmt->get_result()->fetch_assoc()['count'];

            if ($cart_count > 0) {
                // 2. Créer la commande
                $insert_command = "INSERT INTO commandes (user_id, total_commande, statut, date_commande) 
                                 VALUES (?, ?, 'confirmée', NOW())";
                $stmt = $conn->prepare($insert_command);
                
                if ($stmt) {
                    $stmt->bind_param("id", $userId, $totalGeneral);
                    if ($stmt->execute()) {
                        $commande_id = $conn->insert_id;

                        // 3. Insérer les détails
                        $success = true;
                        foreach ($cartItems as $item) {
                            $sql = "INSERT INTO commande_details 
                                   (commande_id, produit_id, quantite, prix_unitaire, sous_total) 
                                   VALUES (?, ?, ?, ?, ?)";
                            $stmt = $conn->prepare($sql);
                            
                            if ($stmt) {
                                $sous_total = $item['price'] * $item['quantite'];
                                $stmt->bind_param("iiidi", 
                                    $commande_id,
                                    $item['product_id'],
                                    $item['quantite'],
                                    $item['price'],
                                    $sous_total
                                );
                                
                                if (!$stmt->execute()) {
                                    $success = false;
                                    break;
                                }
                            }
                        }

                        if ($success) {
                            // 4. Vider le panier
                            $sql = "DELETE FROM panier WHERE user_id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $userId);
                            
                            if ($stmt->execute()) {
                                header("Location: historique.php");
                                exit();
                            }
                        }
                    }
                }
            }
            break;

        case 'remove':
            $productId = intval($_POST['product_id']);
            $sql = "DELETE FROM panier WHERE user_id = ? AND produit_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $userId, $productId);
            if ($stmt->execute()) {
                header('Location: magasin1.php');
                exit();
            }
            break;

        case 'update':
            $productId = intval($_POST['product_id']);
            $newQuantity = max(1, min(10, intval($_POST['quantity'])));
            $sql = "UPDATE panier SET quantite = ? WHERE user_id = ? AND produit_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iii", $newQuantity, $userId, $productId);
            if ($stmt->execute()) {
                header('Location: magasin1.php');
                exit();
            }
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Panier - SiteDeAdam</title>
    <link rel="stylesheet" href="../CSS/StyleMagasin1.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="icon" href="../Images/iconSite.png">
</head>
<body>
    <header>
        <div class="logo-container">
            <a href="./accueil1.php" class="logo">
                <img src="../Images/iconSite.png" alt="Logo Image" class="logo-image">
                SiteDeAdam
            </a>
        </div>
        <input type="checkbox" id="nav_check" hidden>
        <nav>
            <ul>
                <li><a href="#">Mon Panier</a></li>
                <li class="dropdown">
                    <a href="#" id="userEmail" class="dropdown-toggle">
                        <?php echo htmlspecialchars($userEmail); ?> ▼
                    </a>
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

    <main>
        <h1>Mon Panier</h1>
        
        <?php if (empty($cartItems)): ?>
            <p class="empty-cart">Votre panier est vide.</p>
            <a href="accueil1.php" class="btn-continue">Continuer mes achats</a>
        <?php else: ?>
            <div class="cart-container">
                <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item">
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                             class="item-image">
                        
                        <div class="item-details">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p><?php echo htmlspecialchars($item['description']); ?></p>
                            <p class="price">Prix unitaire: <?php echo number_format($item['price'], 2); ?>€</p>
                            
                            <form method="POST" class="quantity-form">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <label for="quantity-<?php echo $item['product_id']; ?>">Quantité:</label>
                                <input type="number" 
                                       id="quantity-<?php echo $item['product_id']; ?>" 
                                       name="quantity" 
                                       value="<?php echo $item['quantite']; ?>" 
                                       min="1" 
                                       max="10" 
                                       onchange="this.form.submit()">
                            </form>
                            
                            <p class="subtotal">Sous-total: <?php echo number_format($item['total'], 2); ?>€</p>
                            
                            <form method="POST" class="remove-form">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <button type="submit" class="btn-remove">Supprimer</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="cart-summary">
                    <p class="total">Total: <?php echo number_format($totalGeneral, 2); ?>€</p>
                    <a href="accueil1.php" class="btn-continue">Continuer mes achats</a>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="checkout">
                        <button type="submit" class="btn-checkout">Procéder au paiement</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <div class="modal-overlay" id="logout-modal">
        <div class="modal-content">
            <h2>Voulez-vous vraiment vous déconnecter ?</h2>
            <div class="modal-buttons">
                <button class="confirm-btn" id="confirm-logout">Oui</button>
                <button class="cancel-btn" id="cancel-logout">Non</button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const logoutBtn = document.getElementById('logout-btn');
        const logoutModal = document.getElementById('logout-modal');
        const confirmLogout = document.getElementById('confirm-logout');
        const cancelLogout = document.getElementById('cancel-logout');
        const checkoutForm = document.getElementById('checkout-form');

        // Gestion de la déconnexion
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            logoutModal.style.display = 'flex';
        });

        confirmLogout.addEventListener('click', function() {
            window.location.href = './accueil.php';
        });

        cancelLogout.addEventListener('click', function() {
            logoutModal.style.display = 'none';
        });

        logoutModal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });

        // Gestion du checkout
        if (checkoutForm) {
            checkoutForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                fetch('magasin1.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=checkout'
                })
                .then(response => {
                    if (response.ok) {
                        window.location.href = 'historique.php';
                    } else {
                        throw new Error('Erreur lors du traitement de la commande');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors du traitement de votre commande.');
                });
            });
        }
    });
    </script>
    <script src="../JS/navbar.js"></script>
</body>
</html>