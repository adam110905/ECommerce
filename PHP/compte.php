<?php
session_start();

// Connexion à la base de données
try {
    $conn = new PDO('mysql:host=localhost;dbname=sitemartin;charset=utf8', 'root', '');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion: " . $e->getMessage());
}

$messages = [];

// Inscription
if (isset($_POST['inscription'])) {
    $nom = isset($_POST['nom_utilisateur']) ? trim($_POST['nom_utilisateur']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';

    // Validation des champs
    if (empty($nom)) {
        $messages[] = ["type" => "error", "text" => "Le nom d'utilisateur est obligatoire."];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $messages[] = ["type" => "error", "text" => "Adresse email invalide."];
    } else {
        // Vérification si l'email est déjà pris
        $query = $conn->prepare("SELECT * FROM utilisateurs WHERE email = :email");
        $query->execute(['email' => $email]);

        if ($query->rowCount() > 0) {
            $messages[] = ["type" => "error", "text" => "L'email est déjà utilisé."];
        } else {
            // Crypter le mot de passe
            $mot_de_passe_crypte = password_hash($mot_de_passe, PASSWORD_DEFAULT);

            // Insertion dans la base de données
            $insertQuery = $conn->prepare(
                "INSERT INTO utilisateurs (email, password, nom_utilisateur) 
                 VALUES (:email, :password, :nom_utilisateur)"
            );

            try {
                if ($insertQuery->execute([
                    ':email' => $email,
                    ':password' => $mot_de_passe_crypte,
                    ':nom_utilisateur' => $nom
                ])) {
                    $messages[] = ["type" => "success", "text" => "Inscription réussie ! Vous pouvez maintenant vous connecter."];
                }
            } catch (PDOException $e) {
                $messages[] = ["type" => "error", "text" => "Erreur lors de l'inscription : " . $e->getMessage()];
            }
        }
    }
}

// Connexion
if (isset($_POST['connexion'])) {
    $email = trim($_POST['email']);
    $mot_de_passe = $_POST['mot_de_passe'];

    // Requête pour vérifier l'utilisateur
    $query = $conn->prepare("SELECT * FROM utilisateurs WHERE email = :email");
    $query->execute(['email' => $email]);

    if ($query->rowCount() == 1) {
        $user = $query->fetch(PDO::FETCH_ASSOC);

        // Vérification du mot de passe
        if (password_verify($mot_de_passe, $user['password'])) { // Notez 'password' au lieu de 'mot_de_passe'
            // Stocker les informations dans la session
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_id'] = $user['id'];  // Changé de 'id_utilisateur' à 'user_id'
            $_SESSION['nom_utilisateur'] = $user['nom_utilisateur'];

            header('Location: accueil1.php');
            exit();
        } else {
            $messages[] = ["type" => "error", "text" => "Mot de passe incorrect."];
        }
    } else {
        $messages[] = ["type" => "error", "text" => "Aucun utilisateur trouvé avec cet email."];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../CSS/StyleCompte.css" rel="stylesheet">
    <link href="../CSS/navbar.css" rel="stylesheet">
    <link rel="icon" href="../Images/iconSite.png">
    <title>Compte - SiteDeAdam</title>
</head>
<body>
    <header>
        <div class="logo-container">
            <a href="./accueil.php" class="logo">
                <img src="../Images/iconSite.png" alt="Logo Image" class="logo-image">
                SiteDeAdam
            </a>
        </div>
        <input type="checkbox" id="nav_check" hidden>
        <nav>
            <ul>
                <li><a href="./magasin.php">Mon Panier</a></li>
                <li><a href="#" class="active">Compte</a></li>
            </ul>
        </nav>
        <label for="nav_check" class="hamburger">
            <div></div>
            <div></div>
            <div></div>
        </label>
    </header>

    <main class="content">
        <div class="messages">
            <?php foreach ($messages as $message): ?>
                <p class="<?php echo $message['type'] === 'success' ? 'success-message' : 'error-message'; ?>">
                    <?php echo htmlspecialchars($message['text']); ?>
                </p>
            <?php endforeach; ?>
        </div>

        <div class="forms-container">
            <!-- Formulaire d'inscription -->
            <div class="form-section">
                <h2>Inscription</h2>
                <form action="compte.php" method="POST">
                    <label for="nom_utilisateur">Nom d'utilisateur :</label>
                    <input type="text" id="nom_utilisateur" name="nom_utilisateur" required>

                    <label for="email_inscription">Email :</label>
                    <input type="email" id="email_inscription" name="email" required>

                    <label for="mot_de_passe_inscription">Mot de passe :</label>
                    <input type="password" id="mot_de_passe_inscription" name="mot_de_passe" required>

                    <button type="submit" name="inscription" class="btn">S'inscrire</button>
                </form>
            </div>

            <!-- Formulaire de connexion -->
            <div class="form-section">
                <h2>Connexion</h2>
                <form action="compte.php" method="POST">
                    <label for="email_connexion">Email :</label>
                    <input type="email" id="email_connexion" name="email" required>

                    <label for="mot_de_passe_connexion">Mot de passe :</label>
                    <input type="password" id="mot_de_passe_connexion" name="mot_de_passe" required>

                    <button type="submit" name="connexion" class="btn">Se connecter</button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>