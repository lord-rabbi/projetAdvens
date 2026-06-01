<?php
require_once 'config/session.php';

if (isset($_SESSION['id_utilisateur'])) {
header('Location: dashboard.php');
} else {
header('Location: login.php');
}
exit();
?>