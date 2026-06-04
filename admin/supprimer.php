<?php
require_once '../config/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['id_utilisateur']) || $_SESSION['id_role'] != 1) {
    header('Location: ../login.php');
    exit();
}

$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id_utilisateur = ?");
    $stmt->execute([$id]);
}

header("Location: index.php?onglet=utilisateurs");
exit();
?>