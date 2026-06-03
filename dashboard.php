<?php
require_once 'config/session.php';
require_once 'config/database.php';

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: login.php');
    exit();
}

$id_role = $_SESSION['id_role'];

$stmt = $pdo->prepare("SELECT libelle, url FROM menus WHERE id_role = ? ORDER BY ordre");
$stmt->execute([$id_role]);
$menus = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT titre, description FROM blocs WHERE id_role = ? ORDER BY ordre");
$stmt->execute([$id_role]);
$blocs = $stmt->fetchAll();
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
    <a href="logout.php"><button>Déconnexion</button></a>
</div>

<div class="container">
    <div class="sidebar">
        <ul>
            <?php foreach ($menus as $menu): ?>
                <li>
                    <a href="<?php echo htmlspecialchars($menu['url']); ?>">
                        <?php echo htmlspecialchars($menu['libelle']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="content">
        <?php foreach ($blocs as $bloc): ?>
            <div class="card">
                <h2><?php echo htmlspecialchars($bloc['titre']); ?></h2>
                <p><?php echo htmlspecialchars($bloc['description']); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>