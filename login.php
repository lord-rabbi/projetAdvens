<?php
require_once 'config/session.php';
require_once 'config/database.php';

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mdp = $_POST['mdp'] ?? '';

    if (empty($email) || empty($mdp)) {
        $erreur = 'Veuillez remplir tous les champs.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Adresse email invalide.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
            $stmt->execute([$email]);
            $utilisateur = $stmt->fetch();

            if ($utilisateur && password_verify($mdp, $utilisateur['mdp'])) {
                $_SESSION['id_utilisateur'] = $utilisateur['id_utilisateur'];
                $_SESSION['nom'] = $utilisateur['nom'];
                $_SESSION['prenom'] = $utilisateur['prenom'];
                $_SESSION['id_role'] = $utilisateur['id_role'];
                $_SESSION['id_departement'] = $utilisateur['id_departement'];

                $role = $utilisateur['id_role'];
                $routes = [
                    1 => 'admin/index.php',
                    2 => 'chef/dashboard.php',
                    3 => 'logistique/dashboard.php',
                    4 => 'dashboard.php',
                ];
                $redirection = $routes[$role] ?? 'dashboard.php';
                header('Location: ' . $redirection);
                exit();
            } else {
                $erreur = 'Email ou mot de passe incorrect';
            }
        } catch (PDOException $e) {
            $erreur = 'Erreur de connexion. Veuillez reessayer.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center vh-100">
            <div class="col-md-5 col-lg-4">
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4 fw-bold" style="color: #008C45;">Connexion</h2>

                        <?php if ($erreur): ?>
                            <div class="alert alert-danger text-center py-2 rounded-pill" id="message-erreur">
                                <?php echo $erreur; ?>
                            </div>
                            <script>setTimeout(function(){ var msg = document.getElementById('message-erreur'); if(msg) msg.remove(); }, 3000);</script>
                        <?php endif; ?>

                        <form method="POST" novalidate>
                            <div class="mb-3">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="fas fa-envelope text-muted"></i>
                                    </span>
                                    <input type="email" name="email" class="form-control border-start-0 ps-0" placeholder="Email" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="fas fa-lock text-muted"></i>
                                    </span>
                                    <input type="password" name="mdp" class="form-control border-start-0 ps-0" placeholder="Mot de passe" required>
                                </div>
                            </div>

                            <button type="submit" class="btn w-100 py-2 fw-semibold text-white border-0 rounded-pill" style="background: #008C45; font-size: 16px;">
                                Se connecter
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>