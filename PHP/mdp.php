<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (isset($_SESSION['email'])) {
    $userEmail = $_SESSION['email'];
    $userNom = isset($_SESSION['nom_utilisateur']) ? $_SESSION['nom_utilisateur'] : "Invité"; // Récupérer le nom avec la bonne clé
} else {
    $userEmail = '';
    $userNom = "Invité";
}

try {
    $conn = new PDO('mysql:host=localhost;dbname=sitemartin;charset=utf8', 'root', '');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

$messages = [];

if (isset($_POST['changer_mdp'])) {
    // Récupérer les valeurs du formulaire
    $email = $_POST['email'] ?? '';
    $ancien_mdp = $_POST['old_password'] ?? '';
    $nouveau_mdp = $_POST['new_password'] ?? '';
    $confirmer_mdp = $_POST['confirm_password'] ?? '';

    // Vérification des champs
    if (empty($email) || empty($ancien_mdp) || empty($nouveau_mdp) || empty($confirmer_mdp)) {
        $messages[] = ["type" => "error", "text" => "Veuillez remplir tous les champs."];
    } elseif ($nouveau_mdp !== $confirmer_mdp) {
        $messages[] = ["type" => "error", "text" => "Les mots de passe ne correspondent pas."];
    } else {
        // Vérifie si l'utilisateur existe
        $query = $conn->prepare("SELECT * FROM utilisateurs WHERE email = :email");
        $query->execute(['email' => $email]);
        $user = $query->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($ancien_mdp, $user['mot_de_passe'])) {
            // Changer le mot de passe
            $nouveau_mdp_hash = password_hash($nouveau_mdp, PASSWORD_BCRYPT);

            $updateQuery = $conn->prepare("UPDATE utilisateurs SET mot_de_passe = :nouveau_mdp WHERE email = :email");
            $updateQuery->execute(['nouveau_mdp' => $nouveau_mdp_hash, 'email' => $email]);

            $messages[] = ["type" => "success", "text" => "Mot de passe mis à jour avec succès."];
        } else {
            $messages[] = ["type" => "error", "text" => "Email ou mot de passe incorrect."];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modification de mot de passe</title>
    <link href="../CSS/navbar.css" rel="stylesheet">
    <link href="../CSS/StyleMdp.css" rel="stylesheet">
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
            <li><a href="./magasin1.php" id="Accueil">Mon Panier</a></li>
            <li class="dropdown">
                <a href="#" id="userEmail" class="dropdown-toggle"><?php echo htmlspecialchars($userEmail); ?> ▼</a>
                <ul class="dropdown-menu">
                    <li><a href="#">Changer de mot de passe</a></li>
                    <li><a href="./historique.php">Historique des Achats</a></li>
                    <li><a id="logout-btn" href="./accueil.php">Déconnexion</a></li>
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


<div class="modal-overlay1" id="logout-modal">
    <div class="modal-content1">
        <h2>Voulez-vous vraiment vous déconnecter ?</h2>
        <button class="confirm-btn" id="confirm-logout">Oui</button>
        <button class="cancel-btn" id="cancel-logout">Non</button>
    </div>
</div>

        <!-- Bouton Déconnexion -->

    <!-- Messages de succès ou d'erreur -->
    <div class="form-container">
        <h1>Changement de mot de passe</h1>

        <!-- Messages de succès ou d'erreur -->
        <div class="messages">
            <?php foreach ($messages as $message): ?>
                <p class="<?php echo $message['type'] === 'success' ? 'success-message' : 'error-message'; ?>">
                    <?php echo htmlspecialchars($message['text']); ?>
                </p>
            <?php endforeach; ?>
        </div>

        <!-- Formulaire -->
        <form action="mdp.php" method="POST">
            <label for="email">Email :</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userEmail); ?>" readonly>

            <label for="old_password">Ancien mot de passe :</label>
            <input type="password" name="old_password" id="old_password" required>

            <label for="new_password">Nouveau mot de passe :</label>
            <input type="password" name="new_password" id="new_password" required>

            <label for="confirm_password">Confirmer le mot de passe :</label>
            <input type="password" name="confirm_password" id="confirm_password" required>

            <button type="submit" name="changer_mdp">Changer le mot de passe</button>
        </form>

</body>

<script src="../JS/navbar.js"></script>
</html>