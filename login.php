<?php
require_once 'config/session.php';
require_once 'config/database.php';

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = $_POST['email'] ?? '';
    $mdp = $_POST['mdp'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    $utilisateur = $stmt->fetch();

    if ($utilisateur && password_verify($mdp, $utilisateur['mdp'])) {

        $_SESSION['id_utilisateur'] = $utilisateur['id_utilisateur'];
        $_SESSION['nom'] = $utilisateur['nom'];
        $_SESSION['prenom'] = $utilisateur['prenom'];
        $_SESSION['id_role'] = $utilisateur['id_role'];
        $_SESSION['autoriser'] = $utilisateur['autoriser'];
        $_SESSION['id_departement'] = $utilisateur['id_departement'];

        header('Location: dashboard.php');
        exit();

    } else {
        $erreur = 'Email ou mot de passe incorrect';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Connexion</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="connexion">
        <h2>Connexion</h2>
        <?php if ($erreur): ?>
            <div class="erreur"><?php echo $erreur; ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="mdp" placeholder="Mot de passe" required>
            <button type="submit">Se connecter</button>
        </form>
    </div>
</body>
</html>