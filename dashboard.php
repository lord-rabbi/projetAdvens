<?php
require_once 'config/session.php';

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/dashboard.css">
</head>
<body>

<div class="header">
    <h2>Bienvenue <?php echo $_SESSION['prenom']; ?></h2>

    <a href="logout.php">
        <button>Déconnexion</button>
    </a>
</div>

<div class="container">

    <div class="sidebar">

        <?php if ($_SESSION['id_role'] == 1) { ?>

            <ul>
                <li><a href="#">Gestion utilisateurs</a></li>
                <li><a href="#">Gestion rôles</a></li>
                <li><a href="#">Toutes les demandes</a></li>
            </ul>

        <?php } elseif ($_SESSION['id_role'] == 2) { ?>

            <ul>
                <li><a href="#">Demandes à valider</a></li>
                <li><a href="#">Demandes rejetées</a></li>
            </ul>

        <?php } elseif ($_SESSION['id_role'] == 3) { ?>


            <ul>
                <li><a href="#">Facturation</a></li>
                <li><a href="#">Décaissements</a></li>
            </ul>

        <?php } elseif ($_SESSION['id_role'] == 4) { ?>

            <ul>
                <li><a href="#">Nouvelle demande</a></li>
                <li><a href="#">Mes demandes</a></li>
            </ul>

        <?php } ?>

    </div>

    <div class="content">

        <?php if ($_SESSION['id_role'] == 1) { ?>

            <div class="card">
                <h2>Espace Administrateur</h2>
                <p>Gestion complète du système.</p>
            </div>

        <?php } elseif ($_SESSION['id_role'] == 2) { ?>

            <div class="card">
                <h2>Espace Chef</h2>
                <p>Validation et rejet des demandes.</p>
            </div>

        <?php } elseif ($_SESSION['id_role'] == 3) { ?>

            <div class="card">
                <h2>Espace Logistique</h2>
                <p>Facturation et décaissements.</p>
            </div>

        <?php } elseif ($_SESSION['id_role'] == 4) { ?>

            <div class="card">
                <h2>Espace Demandeur</h2>
                <p>Création et suivi des demandes.</p>
            </div>

        <?php } ?>

    </div>

</div>

</body>
</html>