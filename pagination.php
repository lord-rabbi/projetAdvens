<?php
require_once 'config/session.php';
require_once 'config/database.php';

if (!isset($_SESSION['id_utilisateur'])) {
    exit();
}

$role = $_SESSION['id_role'];
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$total = 0;

if ($role == 4) {
    $id_user = $_SESSION['id_utilisateur'];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM demandes WHERE id_demandeur = ?");
    $stmt->execute([$id_user]);
    $total = $stmt->fetchColumn();
    $url = "dashboard.php?page=mes_demandes";
} elseif ($role == 2) {
    $id_departement = $_SESSION['id_departement'];
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM demandes d
        JOIN utilisateurs u ON d.id_demandeur = u.id_utilisateur
        WHERE u.id_departement = ?
    ");
    $stmt->execute([$id_departement]);
    $total = $stmt->fetchColumn();
    $url = "chef/dashboard.php";
} elseif ($role == 3) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM demandes WHERE statut = 'pendinglogistique' OR statut = 'facturee'");
    $total = $stmt->fetchColumn();
    $url = "logistique/dashboard.php";
} elseif ($role == 1) {
    $onglet = $_GET['onglet'] ?? 'utilisateurs';
    if ($onglet == 'utilisateurs') {
        $stmt = $pdo->query("SELECT COUNT(*) FROM utilisateurs");
        $total = $stmt->fetchColumn();
        $url = "admin/index.php?onglet=utilisateurs";
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) FROM demandes");
        $total = $stmt->fetchColumn();
        $url = "admin/index.php?onglet=demandes";
    }
}

$total_pages = ceil($total / $limit);
$current_page = $page;

if ($total_pages <= 1) {
    echo '<div class="pagination-info">Total: ' . $total . ' élément(s)</div>';
    exit();
}
?>
<div class="pagination-info">
    Page <?php echo $current_page; ?> sur <?php echo $total_pages; ?> (<?php echo $total; ?> éléments)
</div>
<ul class="pagination">
    <?php if ($current_page > 1): ?>
        <li><a href="<?php echo $url; ?>&page=<?php echo $current_page - 1; ?>">« Précédent</a></li>
    <?php else: ?>
        <li class="disabled"><span>« Précédent</span></li>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <?php if ($i == $current_page): ?>
            <li class="active"><span><?php echo $i; ?></span></li>
        <?php else: ?>
            <li><a href="<?php echo $url; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
        <?php endif; ?>
    <?php endfor; ?>

    <?php if ($current_page < $total_pages): ?>
        <li><a href="<?php echo $url; ?>&page=<?php echo $current_page + 1; ?>">Suivant »</a></li>
    <?php else: ?>
        <li class="disabled"><span>Suivant »</span></li>
    <?php endif; ?>
</ul>