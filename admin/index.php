<?php
require_once '../config/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['id_utilisateur']) || $_SESSION['id_role'] != 1) {
    header('Location: ../login.php');
    exit();
}

$onglet = $_GET['onglet'] ?? 'utilisateurs';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Administration</title>
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body>

<div class="header">
    <h2>Administration</h2>
    <a href="../dashboard.php">Dashboard</a>
    <a href="../logout.php">Deconnexion</a>
</div>

<div class="onglets">
    <a href="?onglet=utilisateurs">Utilisateurs</a>
    <a href="?onglet=demandes">Demandes</a>
    <a href="?onglet=logs">Logs</a>
</div>

<div class="content">
    <?php if ($onglet == 'utilisateurs'): ?>
        <h3>Liste des utilisateurs</h3>
        <table border="1" cellpadding="8">
            <thead>
                <tr><th>ID</th><th>Nom</th><th>Prenom</th><th>Email</th><th>Role</th><th>Departement</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("
                    SELECT u.*, r.nom_role, d.departement 
                    FROM utilisateurs u 
                    LEFT JOIN roles r ON u.id_role = r.id_role 
                    LEFT JOIN departements d ON u.id_departement = d.id_departement
                    ORDER BY u.id_utilisateur DESC
                ");
                while ($row = $stmt->fetch()): ?>
                <tr>
                    <td><?php echo $row['id_utilisateur']; ?></td>
                    <td><?php echo htmlspecialchars($row['nom']); ?></td>
                    <td><?php echo htmlspecialchars($row['prenom']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo $row['nom_role']; ?></td>
                    <td><?php echo $row['departement'] ?? '-'; ?></td>
                    <td>
                        <a href="modifier.php?id=<?php echo $row['id_utilisateur']; ?>">Modifier</a>
                        <a href="supprimer.php?id=<?php echo $row['id_utilisateur']; ?>" onclick="return confirm('Supprimer ?')">Supprimer</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <a href="ajouter.php">Ajouter un utilisateur</a>

    <?php elseif ($onglet == 'demandes'): ?>
        <h3>Liste des demandes</h3>
        <table border="1" cellpadding="8">
            <thead>
                <tr><th>ID</th><th>Objet</th><th>Montant</th><th>Demandeur</th><th>Statut</th><th>Date creation</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("
                    SELECT d.*, CONCAT(u.nom, ' ', u.prenom) as demandeur, dep.departement
                    FROM demandes d
                    JOIN utilisateurs u ON d.id_demandeur = u.id_utilisateur
                    LEFT JOIN departements dep ON u.id_departement = dep.id_departement
                    ORDER BY d.id_demande DESC
                ");
                while ($row = $stmt->fetch()): ?>
                <tr>
                    <td><?php echo $row['id_demande']; ?></td>
                    <td><?php echo htmlspecialchars($row['objet']); ?></td>
                    <td><?php echo number_format($row['montant_demande'], 2); ?></td>
                    <td><?php echo htmlspecialchars($row['demandeur']); ?></td>
                    <td><?php echo $row['statut']; ?></td>
                    <td><?php echo $row['date_creation']; ?></td>
                    <td><a href="../demandes/detail.php?id=<?php echo $row['id_demande']; ?>">Voir</a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

    <?php else: ?>
        <h3>Historique des logs</h3>
        <table border="1" cellpadding="8">
            <thead>
                <tr><th>ID</th><th>Date</th><th>Utilisateur</th><th>Action</th><th>Statut</th><th>Justification</th></tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("
                    SELECT l.*, CONCAT(u.nom, ' ', u.prenom) as utilisateur
                    FROM logs l
                    JOIN utilisateurs u ON l.id_utilisateur = u.id_utilisateur
                    ORDER BY l.id_log DESC LIMIT 100
                ");
                while ($row = $stmt->fetch()): ?>
                <tr>
                    <td><?php echo $row['id_log']; ?></td>
                    <td><?php echo $row['date_action']; ?></td>
                    <td><?php echo htmlspecialchars($row['utilisateur']); ?></td>
                    <td><?php echo $row['action']; ?></td>
                    <td><?php echo $row['nouveau_statut']; ?></td>
                    <td><?php echo htmlspecialchars($row['justification'] ?? '-'); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>