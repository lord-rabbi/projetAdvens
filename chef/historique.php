<?php
require_once '../config/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SESSION['id_role'] != 2) {
    header('Location: ../dashboard.php');
    exit();
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

function getLibelleStatut($statut) {
    switch ($statut) {
        case 'pending': return 'Attente de validation';
        case 'pendinglogistique': return 'Attente facture';
        case 'facturee': return 'Attente de paiement';
        case 'confirmee': return 'Décaissée';
        case 'rejetee': return 'Rejetée';
        case 'annulee': return 'Annulée';
        default: return $statut;
    }
}

function getBadgeClass($statut) {
    switch ($statut) {
        case 'pending': return 'badge-attente';
        case 'pendinglogistique': return 'badge-logistique';
        case 'facturee': return 'badge-facturee';
        case 'confirmee': return 'badge-succes';
        case 'rejetee': return 'badge-rejet';
        case 'annulee': return 'badge-annule';
        default: return '';
    }
}

$id_departement = $_SESSION['id_departement'];

$total_stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM demandes d
    JOIN utilisateurs u ON d.id_demandeur = u.id_utilisateur
    WHERE u.id_departement = ?
");
$total_stmt->execute([$id_departement]);
$total = $total_stmt->fetchColumn();
$total_pages = ceil($total / $limit);

$stmt = $pdo->prepare("
    SELECT d.*, u.nom, u.prenom 
    FROM demandes d
    JOIN utilisateurs u ON d.id_demandeur = u.id_utilisateur
    WHERE u.id_departement = ? 
    ORDER BY d.date_creation DESC
    LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
");
$stmt->execute([$id_departement]);
$demandes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/dashboard.css?v=2">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<div class="header">
    <div class="header-flow">
        <img src="../assets/Advans_Congo_Logo.svg" alt="svg advans">
    </div>
    <h2>Historique des demandes</h2>
    <a href="../logout.php"><button class="btn-deconnexion">Deconnexion</button></a>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="sidebar">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-check-circle me-2"></i> Demandes à valider
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="historique.php">
                        <i class="fas fa-history me-2"></i> Historique
                    </a>
                </li>
            </ul>
        </div>

        <div class="content">
            <div class="card">
                <h2>L'historique des demandes</h2>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Demandeur</th>
                                <th>Montant</th>
                                <th>Statut</th>
                                <th>Date creation</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="demandesTableBody">
                            <?php foreach ($demandes as $demande): ?>
                                <?php
                                $statut = $demande['statut'];
                                $libelle = getLibelleStatut($statut);
                                $badge = getBadgeClass($statut);
                                $id = $demande['id_demande'];
                                ?>
                                <tr id="demande-<?php echo $id; ?>">
                                    <td><?php echo $id; ?></td>
                                    <td><?php echo htmlspecialchars($demande['prenom'] . ' ' . $demande['nom']); ?></td>
                                    <td><?php echo number_format($demande['montant_demande'], 2); ?> <?php echo $demande['devise'] ?? 'USD'; ?></td>
                                    <td class="statut-cell" id="statut-<?php echo $id; ?>">
                                        <span class="badge <?php echo $badge; ?>"><?php echo $libelle; ?></span>
                                    </td>
                                    <td><?php echo $demande['date_creation']; ?></td>
                                    <td>
                                        <button type="button" class="btn-detail" data-bs-toggle="modal" data-bs-target="#detailModal" 
                                            data-id="<?php echo $id; ?>"
                                            data-objet="<?php echo htmlspecialchars($demande['objet']); ?>"
                                            data-montant="<?php echo number_format($demande['montant_demande'], 2); ?>"
                                            data-devise="<?php echo $demande['devise'] ?? 'USD'; ?>"
                                            data-demandeur="<?php echo htmlspecialchars($demande['prenom'] . ' ' . $demande['nom']); ?>"
                                            data-date="<?php echo $demande['date_creation']; ?>"
                                            data-statut="<?php echo $libelle; ?>"
                                            data-renvoyee="<?php echo $demande['renvoyee'] == 1 ? 'Oui' : 'Non'; ?>"
                                            data-justification="<?php echo htmlspecialchars($demande['justification_rejet'] ?? ''); ?>"
                                            data-validation="<?php echo $demande['date_validation_chef'] ?? ''; ?>"
                                            data-facture="<?php echo $demande['date_facture'] ?? ''; ?>"
                                            data-decaissement="<?php echo $demande['date_decaissement'] ?? ''; ?>"
                                            data-piece="<?php echo $demande['piece_jointe'] ?? ''; ?>">
                                            <i class="fas fa-eye me-1"></i> Voir
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <div class="pagination-info">
                        Page <?php echo $page; ?> sur <?php echo $total_pages; ?> (<?php echo $total; ?> demandes)
                    </div>
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li><a href="?page=<?php echo $page - 1; ?>">« Précédent</a></li>
                        <?php else: ?>
                            <li class="disabled"><span>« Précédent</span></li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <li class="active"><span><?php echo $i; ?></span></li>
                            <?php else: ?>
                                <li><a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li><a href="?page=<?php echo $page + 1; ?>">Suivant »</a></li>
                        <?php else: ?>
                            <li class="disabled"><span>Suivant »</span></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Detail de la demande</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>ID :</strong> <span id="detail_id"></span></p>
                <p><strong>Demandeur :</strong> <span id="detail_demandeur"></span></p>
                <p><strong>Montant :</strong> <span id="detail_montant"></span> <span id="detail_devise"></span></p>
                <p><strong>Statut :</strong> <span id="detail_statut"></span></p>
                <p><strong>Renvoyee apres rejet :</strong> <span id="detail_renvoyee"></span></p>
                <p><strong>Date creation :</strong> <span id="detail_date"></span></p>
                <p><strong>Date validation chef :</strong> <span id="detail_validation"></span></p>
                <p><strong>Date facture :</strong> <span id="detail_facture"></span></p>
                <p><strong>Date decaissement :</strong> <span id="detail_decaissement"></span></p>
                <p><strong>Motif :</strong></p>
                <div class="border p-2 rounded bg-light" id="detail_objet" style="white-space: pre-wrap;"></div>
                <p class="mt-2"><strong>Justification rejet :</strong></p>
                <div class="border p-2 rounded bg-light" id="detail_justification" style="white-space: pre-wrap;"></div>
                <p class="mt-2"><strong>Piece jointe :</strong></p>
                <div class="border p-2 rounded bg-light" id="detail_piece"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function refreshDemandes() {
        var page = <?php echo $page; ?>;
        $.ajax({
            url: '../refresh.php?action=historique_chef&page=' + page,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.error) {
                    return;
                }
                $.each(data, function(index, item) {
                    $('#statut-' + item.id).html(item.statut_html);
                });
            }
        });
    }
    setInterval(refreshDemandes, 5000);

    const detailModal = document.getElementById('detailModal');
    detailModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        document.getElementById('detail_id').innerText = button.getAttribute('data-id');
        document.getElementById('detail_demandeur').innerText = button.getAttribute('data-demandeur');
        document.getElementById('detail_montant').innerText = button.getAttribute('data-montant');
        document.getElementById('detail_devise').innerText = button.getAttribute('data-devise');
        document.getElementById('detail_statut').innerText = button.getAttribute('data-statut');
        document.getElementById('detail_renvoyee').innerText = button.getAttribute('data-renvoyee');
        document.getElementById('detail_date').innerText = button.getAttribute('data-date');
        document.getElementById('detail_validation').innerText = button.getAttribute('data-validation') || 'Non validee';
        document.getElementById('detail_facture').innerText = button.getAttribute('data-facture') || 'Non facturee';
        document.getElementById('detail_decaissement').innerText = button.getAttribute('data-decaissement') || 'Non decaisse';
        document.getElementById('detail_objet').innerText = button.getAttribute('data-objet');
        const justification = button.getAttribute('data-justification');
        document.getElementById('detail_justification').innerText = justification || 'Aucune';
        const piece = button.getAttribute('data-piece');
        if (piece) {
            document.getElementById('detail_piece').innerHTML = '<a href="../' + piece + '" target="_blank" class="btn btn-sm btn-outline-primary">Telecharger</a>';
        } else {
            document.getElementById('detail_piece').innerHTML = 'Aucune piece jointe';
        }
    });
</script>
</body>
</html>