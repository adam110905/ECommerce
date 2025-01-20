<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'sitemartin');

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$userEmail = $_SESSION['email'] ?? '';
$userNom = $_SESSION['nom_utilisateur'] ?? 'Invité';

// Récupérer l'historique des commandes
$sql = "SELECT c.id, c.date_commande, c.total_commande, c.statut,
               cd.produit_id, cd.quantite, cd.prix_unitaire, cd.sous_total,
               p.name as produit_nom, p.image_url
        FROM commandes c
        LEFT JOIN commande_details cd ON c.id = cd.commande_id
        LEFT JOIN products p ON cd.produit_id = p.id
        WHERE c.user_id = ?
        ORDER BY c.date_commande DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$commandes = [];
while ($row = $result->fetch_assoc()) {
    $commande_id = $row['id'];
    if (!isset($commandes[$commande_id])) {
        $commandes[$commande_id] = [
            'date' => $row['date_commande'],
            'total' => $row['total_commande'],
            'statut' => $row['statut'],
            'produits' => []
        ];
    }
    if ($row['produit_id']) {
        $commandes[$commande_id]['produits'][] = [
            'nom' => $row['produit_nom'],
            'quantite' => $row['quantite'],
            'prix_unitaire' => $row['prix_unitaire'],
            'sous_total' => $row['sous_total'],
            'image_url' => $row['image_url']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../CSS/navbar.css" rel="stylesheet">
    <link href="../CSS/StyleHistorique.css" rel="stylesheet">
    <link rel="icon" href="../Images/iconSite.png">
    <title>Historique des commandes - SiteDeAdam</title>
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
                <li><a href="magasin1.php">Mon Panier</a></li>
                <li class="dropdown">
                    <a href="#" id="userEmail" class="dropdown-toggle"><?php echo htmlspecialchars($userEmail); ?> ▼</a>
                    <ul class="dropdown-menu">
                        <li><a href="./mdp.php">Changer de mot de passe</a></li>
                        <li><a href="#">Historique des Achats</a></li>
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

    <main class="container">
        <h1>Historique des commandes</h1>
        
        <?php if (empty($commandes)): ?>
            <div class="empty-history">
                <p>Vous n'avez pas encore effectué de commande.</p>
                <a href="accueil1.php" class="btn-primary">Découvrir nos produits</a>
            </div>
        <?php else: ?>
            <div class="commandes-list">
                <?php foreach ($commandes as $id => $commande): ?>
                    <div class="commande-card">
                        <div class="commande-header">
                            <h2>Commande #<?php echo htmlspecialchars($id); ?></h2>
                            <div class="commande-info">
                                <p>Date: <?php echo date('d/m/Y H:i', strtotime($commande['date'])); ?></p>
                                <p class="statut <?php echo strtolower($commande['statut']); ?>">
                                    Statut: <?php echo ucfirst(htmlspecialchars($commande['statut'])); ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="produits-list">
                            <?php foreach ($commande['produits'] as $produit): ?>
                                <div class="produit-item">
                                    <div class="produit-image">
                                        <img src="<?php echo htmlspecialchars($produit['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($produit['nom']); ?>">
                                    </div>
                                    <div class="produit-details">
                                        <h3><?php echo htmlspecialchars($produit['nom']); ?></h3>
                                        <div class="produit-info">
                                            <p>Quantité: <span><?php echo htmlspecialchars($produit['quantite']); ?></span></p>
                                            <p>Prix unitaire: <span><?php echo number_format($produit['prix_unitaire'], 2, ',', ' '); ?>€</span></p>
                                            <p>Sous-total: <span><?php echo number_format($produit['sous_total'], 2, ',', ' '); ?>€</span></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="commande-footer">
                            <p class="total">Total de la commande: <span><?php echo number_format($commande['total'], 2, ',', ' '); ?>€</span></p>
                        </div>
                    </div>
                <?php endforeach; ?>
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
    });
    </script>
    <script src="../JS/navbar.js"></script>
</body>
</html>