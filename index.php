<?php
require_once 'config/session.php';
require_once 'config/database.php';

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email = 'admin@gmail.com' AND id_role = 1");
    $stmt->execute();
    $adminExiste = $stmt->fetchColumn();

    if (!$adminExiste) {
        $mdp_hash = password_hash('password', PASSWORD_DEFAULT);
        $sql = "INSERT INTO utilisateurs (nom, prenom, email, mdp, id_role, id_departement) 
                VALUES ('Mylord', 'Admin', 'admin@gmail.com', :mdp, 1, NULL)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':mdp' => $mdp_hash]);
    }

    if (isset($_SESSION['id_utilisateur'])) {
        $role = $_SESSION['id_role'] ?? 0;
        $routes = [
            1 => 'admin/index.php',
            2 => 'chef/dashboard.php',
            3 => 'logistique/dashboard.php',
            4 => 'dashboard.php',
        ];
        $redirection = $routes[$role] ?? 'dashboard.php';
        header('Location: ' . $redirection);
    } else {
        header('Location: login.php');
    }
} catch (PDOException $e) {
    header('Location: login.php?erreur=base');
}
exit();
?>