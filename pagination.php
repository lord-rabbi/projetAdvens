<?php
require_once 'config/session.php';
require_once 'config/database.php';

if (!isset($_SESSION['id_utilisateur'])) {
    exit();
}

$role = $_SESSION['id_role'];
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 5;
$offset = ($page - 1) * $limit;
$total = 0;

if ($role == 4) {
    $id_user = $_SESSION['id_utilisateur'];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM demandes WHERE id_demandeur = ?");
    $stmt->execute([$id_user]);
    $total = $stmt->fetchColumn();
    $url = "dashboard.php?page=mes_demandes&p";
    $label = 'demandes';
} elseif ($role == 2) {
    $id_departement = $_SESSION['id_departement'];
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM demandes d
        JOIN utilisateurs u ON d.id_demandeur = u.id_utilisateur
        WHERE u.id_departement = ? AND (d.statut = 'pending' OR d.statut = 'rejetee')
    ");
    $stmt->execute([$id_departement]);
    $total = $stmt->fetchColumn();
    $url = "chef/dashboard.php?page";
    $label = 'demandes';
} elseif ($role == 3) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM demandes WHERE statut = 'pendinglogistique' OR statut = 'facturee'");
    $stmt->execute();
    $total = $stmt->fetchColumn();
    $url = "logistique/dashboard.php?page";
    $label = 'demandes';
} elseif ($role == 1) {
    $onglet = isset($_GET['onglet']) && in_array($_GET['onglet'], ['utilisateurs', 'demandes', 'logs']) ? $_GET['onglet'] : 'utilisateurs';
    if ($onglet == 'utilisateurs') {
        $stmt = $pdo->query("SELECT COUNT(*) FROM utilisateurs");
        $total = $stmt->fetchColumn();
        $url = "index.php?onglet=utilisateurs&page";
        $label = 'utilisateurs';
    } elseif ($onglet == 'demandes') {
        $stmt = $pdo->query("SELECT COUNT(*) FROM demandes");
        $total = $stmt->fetchColumn();
        $url = "index.php?onglet=demandes&page";
        $label = 'demandes';
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) FROM logs");
        $total = $stmt->fetchColumn();
        $url = "index.php?onglet=logs&page";
        $label = 'logs';
    }
}

$total_pages = ceil($total / $limit);
$current_page = $page;
$offset_display = ($current_page - 1) * $limit;
$from = $offset_display + 1;
$to = min($offset_display + $limit, $total);

if ($total_pages <= 1) {
    echo '<div class="pagination-info">' . $total . ' ' . $label . '</div>';
    exit();
}

$start_page = max(1, $current_page);
$end_page = min($total_pages, $current_page + 3);

if ($current_page > $total_pages - 3) {
    $end_page = $total_pages;
    $start_page = max(1, $total_pages - 3);
}

if ($start_page > 1) {
    $show_first = true;
} else {
    $show_first = false;
}

if ($end_page < $total_pages) {
    $show_last = true;
} else {
    $show_last = false;
}
?>
<div class="pagination-container">
    <div class="pagination-info">
        Page <?php echo $current_page; ?> sur <?php echo $total_pages; ?> (<?php echo $total; ?> <?php echo $label; ?>)
    </div>
    <ul class="pagination">
        <?php if ($current_page > 1): ?>
            <li><a href="<?php echo $url; ?>=<?php echo $current_page - 1; ?>">« Précédent</a></li>
        <?php else: ?>
            <li class="disabled"><span>« Précédent</span></li>
        <?php endif; ?>

        <?php if ($show_first): ?>
            <li><a href="<?php echo $url; ?>=1">1</a></li>
            <?php if ($start_page > 2): ?>
                <li class="disabled"><span>…</span></li>
            <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
            <?php if ($i == $current_page): ?>
                <li class="active"><span><?php echo $i; ?></span></li>
            <?php else: ?>
                <li><a href="<?php echo $url; ?>=<?php echo $i; ?>"><?php echo $i; ?></a></li>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($show_last): ?>
            <?php if ($end_page < $total_pages - 1): ?>
                <li class="disabled"><span>…</span></li>
            <?php endif; ?>
            <li><a href="<?php echo $url; ?>=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a></li>
        <?php endif; ?>

        <?php if ($current_page < $total_pages): ?>
            <li><a href="<?php echo $url; ?>=<?php echo $current_page + 1; ?>">Suivant »</a></li>
        <?php else: ?>
            <li class="disabled"><span>Suivant »</span></li>
        <?php endif; ?>
    </ul>
</div>