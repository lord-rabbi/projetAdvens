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

$id_departement = $_SESSION['id_departement'];
$erreur = '';
$succes = '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'valider') {
        $id_demande = $_POST['id_demande'] ?? 0;
        $stmt = $pdo->prepare("UPDATE demandes SET statut = 'pendinglogistique', date_validation_chef = NOW() WHERE id_demande = ?");
        if ($stmt->execute([$id_demande])) {
            $sql_log = "INSERT INTO logs (date_action, id_utilisateur, action, statut, id_demande) VALUES (NOW(), ?, 'validation', 'pendinglogistique', ?)";
            $stmt_log = $pdo->prepare($sql_log);
            $stmt_log->execute([$_SESSION['id_utilisateur'], $id_demande]);
            header('Location: chef/dashboard.php?page=' . $page);
            exit();
        } else {
            $erreur = 'Erreur lors de la validation.';
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'rejeter') {
        $id_demande = $_POST['id_demande'] ?? 0;
        $justification = trim($_POST['justification'] ?? '');
        if (empty($justification)) {
            $erreur = 'La justification est obligatoire pour un rejet.';
        } else {
            $stmt = $pdo->prepare("UPDATE demandes SET statut = 'rejetee', justification_rejet = ? WHERE id_demande = ?");
            if ($stmt->execute([$justification, $id_demande])) {
                $sql_log = "INSERT INTO logs (date_action, id_utilisateur, action, statut, justification, id_demande) VALUES (NOW(), ?, 'rejet', 'rejetee', ?, ?)";
                $stmt_log = $pdo->prepare($sql_log);
                $stmt_log->execute([$_SESSION['id_utilisateur'], $justification, $id_demande]);
                header('Location: chef/dashboard.php?page=' . $page);
                exit();
            } else {
                $erreur = 'Erreur lors du rejet.';
            }
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'reactiver') {
        $id_demande = $_POST['id_demande'] ?? 0;
        $stmt = $pdo->prepare("UPDATE demandes SET statut = 'pending', renvoyee = 1 WHERE id_demande = ?");
        if ($stmt->execute([$id_demande])) {
            $sql_log = "INSERT INTO logs (date_action, id_utilisateur, action, statut, id_demande) VALUES (NOW(), ?, 'reactivation', 'pending', ?)";
            $stmt_log = $pdo->prepare($sql_log);
            $stmt_log->execute([$_SESSION['id_utilisateur'], $id_demande]);
            header('Location: chef/dashboard.php?page=' . $page);
            exit();
        } else {
            $erreur = 'Erreur lors de la reactivation.';
        }
    }
}

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
    LIMIT $limit OFFSET $offset
");
$stmt->execute([$id_departement]);
$demandes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Chef</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/dashboard.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<div class="header">
    <div class="header-flow">
        <img src="../assets/Advans_Congo_Logo.svg" alt="svg advans">
    </div>
    <h2>Bienvenue <span><?php echo $_SESSION['prenom']; ?></span></h2>
    <a href="../logout.php"><button class="btn-deconnexion">Deconnexion</button></a>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="sidebar">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="#">
                        <i class="fas fa-check-circle me-2"></i> Demandes à valider
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="historique.php">
                        <i class="fas fa-history me-2"></i> Historique
                    </a>
                </li>
            </ul>
        </div>

        <div class="content">
            <?php if ($erreur): ?>
                <div class="alert alert-danger"><?php echo $erreur; ?></div>
            <?php endif; ?>
            <?php if ($succes): ?>
                <div class="alert alert-success"><?php echo $succes; ?></div>
            <?php endif; ?>

            <div class="card">
                <h2>Demandes de mon departement</h2>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Demandeur</th>
                                <th>Motif</th>
                                <th>Montant</th>
                                <th>Statut</th>
                                <th>Renvoyee</th>
                                <th>Piece jointe</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="demandesTableBody">
                            <?php foreach ($demandes as $demande): ?>
                                <?php
                                $statut = $demande['statut'];
                                $libelle = getLibelleStatut($statut);
                                $badge = getBadgeClass($statut);
                                ?>
                                <tr>
                                    <td><?php echo $demande['id_demande']; ?></td>
                                    <td><?php echo htmlspecialchars($demande['prenom'] . ' ' . $demande['nom']); ?></td>
                                    <td>
                                        <button type="button" class="btn-detail" data-bs-toggle="modal" data-bs-target="#detailModal" 
                                            data-objet="<?php echo htmlspecialchars($demande['objet']); ?>"
                                            data-montant="<?php echo number_format($demande['montant_demande'], 2); ?>"
                                            data-devise="<?php echo $demande['devise'] ?? 'USD'; ?>"
                                            data-demandeur="<?php echo htmlspecialchars($demande['prenom'] . ' ' . $demande['nom']); ?>"
                                            data-date="<?php echo $demande['date_creation']; ?>"
                                            data-statut="<?php echo $libelle; ?>"
                                            data-renvoyee="<?php echo $demande['renvoyee'] == 1 ? 'Oui' : 'Non'; ?>"
                                            data-justification="<?php echo htmlspecialchars($demande['justification_rejet'] ?? ''); ?>"
                                            data-piece="<?php echo $demande['piece_jointe'] ?? ''; ?>">
                                            <i class="fas fa-eye me-1"></i> Voir
                                        </button>
                                    </td>
                                    <td><?php echo number_format($demande['montant_demande'], 2); ?> <?php echo $demande['devise'] ?? 'USD'; ?></td>
                                    <td><span class="badge <?php echo $badge; ?>"><?php echo $libelle; ?></span></td>
                                    <td>
                                        <?php if ($demande['renvoyee'] == 1): ?>
                                            <span class="badge bg-warning text-dark">Oui</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Non</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($demande['piece_jointe']): ?>
                                            <a href="../<?php echo $demande['piece_jointe']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">Voir</a>
                                        <?php else: ?>
                                            <span class="text-muted">Aucune</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $demande['date_creation']; ?></td>
                                    <td>
                                        <?php if ($statut == 'pending'): ?>
                                            <form method="POST" style="display:inline-block;">
                                                <input type="hidden" name="action" value="valider">
                                                <input type="hidden" name="id_demande" value="<?php echo $demande['id_demande']; ?>">
                                                <button type="submit" class="btn-success-sm">Valider</button>
                                            </form>
                                            <button type="button" class="btn-danger-sm" data-bs-toggle="modal" data-bs-target="#rejetModal" data-id="<?php echo $demande['id_demande']; ?>">Rejeter</button>
                                        <?php elseif ($statut == 'rejetee'): ?>
                                            <form method="POST" style="display:inline-block;">
                                                <input type="hidden" name="action" value="reactiver">
                                                <input type="hidden" name="id_demande" value="<?php echo $demande['id_demande']; ?>">
                                                <button type="submit" class="btn-warning-sm">Revenir sur rejet</button>
                                            </form>
                                        <?php endif; ?>
                                    </p>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    <tr>
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

<div class="modal fade" id="rejetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Rejeter la demande</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="rejeter">
                <input type="hidden" name="id_demande" id="rejet_id_demande">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Justification *</label>
                        <textarea name="justification" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">Rejeter</button>
                </div>
            </form>
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
                <p><strong>Demandeur :</strong> <span id="detail_demandeur"></span></p>
                <p><strong>Montant :</strong> <span id="detail_montant"></span> <span id="detail_devise"></span></p>
                <p><strong>Date :</strong> <span id="detail_date"></span></p>
                <p><strong>Statut :</strong> <span id="detail_statut"></span></p>
                <p><strong>Renvoyee apres rejet :</strong> <span id="detail_renvoyee"></span></p>
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
            url: '../refresh.php?action=demandes&page=' + page,
            type: 'GET',
            dataType: 'html',
            success: function(data) {
                $('#demandesTableBody').html(data);
            }
        });
    }
    setInterval(refreshDemandes, 5000);

    const rejetModal = document.getElementById('rejetModal');
    rejetModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        document.getElementById('rejet_id_demande').value = id;
    });

    const detailModal = document.getElementById('detailModal');
    detailModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        document.getElementById('detail_demandeur').innerText = button.getAttribute('data-demandeur');
        document.getElementById('detail_montant').innerText = button.getAttribute('data-montant');
        document.getElementById('detail_devise').innerText = button.getAttribute('data-devise');
        document.getElementById('detail_date').innerText = button.getAttribute('data-date');
        document.getElementById('detail_statut').innerText = button.getAttribute('data-statut');
        document.getElementById('detail_renvoyee').innerText = button.getAttribute('data-renvoyee');
        document.getElementById('detail_objet').innerText = button.getAttribute('data-objet');
        const justification = button.getAttribute('data-justification');
        document.getElementById('detail_justification').innerText = justification || 'Aucune';
        const piece = button.getAttribute('data-piece');
        if (piece) {
            document.getElementById('detail_piece').innerHTML = '<a href="../' + piece + '" target="_blank" class="btn btn-sm btn-outline-primary">Telecharger la piece jointe</a>';
        } else {
            document.getElementById('detail_piece').innerHTML = 'Aucune piece jointe';
        }
    });
</script>
</body>
</html>