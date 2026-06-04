<?php
require_once 'config/session.php';
require_once 'config/database.php';

$stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email = 'admin@gmail.com'");
$stmt->execute();
$adminExiste = $stmt->fetchColumn();

if (!$adminExiste) {
    $mdp_hash = password_hash('password', PASSWORD_DEFAULT);
    $sql = "INSERT INTO utilisateurs (nom, prenom, email, mdp, id_role) 
            VALUES ('Mylord', 'Admin', 'admin@gmail.com', :mdp, 1)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':mdp' => $mdp_hash]);
}

if (isset($_SESSION['id_utilisateur'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit();
?>