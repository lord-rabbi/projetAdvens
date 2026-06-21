<?php
require_once '../config/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SESSION['id_role'] != 3) {
    header('Location: ../dashboard.php');
    exit();
}

$erreur = '';
$succes = '';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'facturer') {
        $id_demande = $_POST['id_demande'] ?? 0;
        $stmt = $pdo->prepare("UPDATE demandes SET statut = 'facturee', date_facture = NOW() WHERE id_demande = ? AND statut = 'pendinglogistique'");
        if ($stmt->execute([$id_demande])) {
            $sql_log = "INSERT INTO logs (date_action, id_utilisateur, action, statut, id_demande) VALUES (NOW(), ?, 'facturation', 'facturee', ?)";
            $stmt_log = $pdo->prepare($sql_log);
            $stmt_log->execute([$_SESSION['id_utilisateur'], $id_demande]);
            $succes = 'Demande facturee avec succes.';
        } else {
            $erreur = 'Erreur lors de la facturation.';
        }
    }
}

$filtre_statut = $_GET['filtre_statut'] ?? '';
$filtre_date_debut = $_GET['filtre_date_debut'] ?? '';
$filtre_date_fin = $_GET['filtre_date_fin'] ?? '';
$filtre_demandeur = $_GET['filtre_demandeur'] ?? '';
$filtre_departement = $_GET['filtre_departement'] ?? '';
$filtre_montant_min = $_GET['filtre_montant_min'] ?? '';
$filtre_montant_max = $_GET['filtre_montant_max'] ?? '';

$where = " WHERE d.statut IN ('pendinglogistique', 'facturee')";
$params = array();

if (!empty($filtre_statut)) {
    $where .= " AND d.statut = ?";
    $params[] = $filtre_statut;
}

if (!empty($filtre_date_debut)) {
    $where .= " AND DATE(d.date_creation) >= ?";
    $params[] = $filtre_date_debut;
}

if (!empty($filtre_date_fin)) {
    $where .= " AND DATE(d.date_creation) <= ?";
    $params[] = $filtre_date_fin;
}

if (!empty($filtre_demandeur)) {
    $where .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR CONCAT(u.prenom, ' ', u.nom) LIKE ?)";
    $params[] = '%' . $filtre_demandeur . '%';
    $params[] = '%' . $filtre_demandeur . '%';
    $params[] = '%' . $filtre_demandeur . '%';
}

if (!empty($filtre_departement)) {
    $where .= " AND dep.departement = ?";
    $params[] = $filtre_departement;
}

if (!empty($filtre_montant_min) && !empty($filtre_montant_max)) {
    $where .= " AND d.montant_demande BETWEEN ? AND ?";
    $params[] = floatval($filtre_montant_min);
    $params[] = floatval($filtre_montant_max);
} elseif (!empty($filtre_montant_min)) {
    $where .= " AND d.montant_demande >= ?";
    $params[] = floatval($filtre_montant_min);
} elseif (!empty($filtre_montant_max)) {
    $where .= " AND d.montant_demande <= ?";
    $params[] = floatval($filtre_montant_max);
}

$count_sql = "SELECT COUNT(*) FROM demandes d JOIN utilisateurs u ON d.id_demandeur = u.id_utilisateur LEFT JOIN departements dep ON u.id_departement = dep.id_departement " . $where;
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total = $stmt->fetchColumn();
$total_pages = ceil($total / $limit);

$sql = "
    SELECT d.*, u.nom, u.prenom, dep.departement
    FROM demandes d
    JOIN utilisateurs u ON d.id_demandeur = u.id_utilisateur
    LEFT JOIN departements dep ON u.id_departement = dep.id_departement
    " . $where . "
    ORDER BY d.date_creation DESC
    LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$demandes = $stmt->fetchAll();

$departements_list = $pdo->query("SELECT departement FROM departements ORDER BY departement")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logistique</title>
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
        <h2>Bonjour <span><?php echo $_SESSION['prenom']; ?></span></h2>
        <a href="../logout.php"><button class="btn-deconnexion">Deconnexion</button></a>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="sidebar">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">
                            <i class="fas fa-file-invoice me-2"></i> Demandes a traiter
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
                    <div class="alert alert-danger" id="message-erreur"><?php echo $erreur; ?></div>
                    <script>setTimeout(function(){ var msg = document.getElementById('message-erreur'); if(msg) msg.remove(); }, 3000);</script>
                <?php endif; ?>

                <?php if ($succes): ?>
                    <div class="alert alert-success" id="message-succes"><?php echo $succes; ?></div>
                    <script>setTimeout(function(){ var msg = document.getElementById('message-succes'); if(msg) msg.remove(); }, 3000);</script>
                <?php endif; ?>

                <div class="card">
                    <h2>Demandes a traiter</h2>

                    <div class="filters">
                        <form method="GET" class="row g-3">
                            <input type="hidden" name="page" value="1">

                            <div class="col-md-2">
                                <label class="form-label">Statut</label>
                                <select name="filtre_statut" class="form-select">
                                    <option value="">Tous</option>
                                    <option value="pendinglogistique" <?php echo $filtre_statut == 'pendinglogistique' ? 'selected' : ''; ?>>Attente facture</option>
                                    <option value="facturee" <?php echo $filtre_statut == 'facturee' ? 'selected' : ''; ?>>Attente paiement</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Date debut</label>
                                <input type="date" name="filtre_date_debut" class="form-control" value="<?php echo $filtre_date_debut; ?>">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Date fin</label>
                                <input type="date" name="filtre_date_fin" class="form-control" value="<?php echo $filtre_date_fin; ?>">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Demandeur</label>
                                <input type="text" name="filtre_demandeur" class="form-control" placeholder="Nom ou prenom" value="<?php echo htmlspecialchars($filtre_demandeur); ?>">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Departement</label>
                                <select name="filtre_departement" class="form-select">
                                    <option value="">Tous</option>
                                    <?php foreach ($departements_list as $dep): ?>
                                        <option value="<?php echo $dep['departement']; ?>" <?php echo $filtre_departement == $dep['departement'] ? 'selected' : ''; ?>>
                                            <?php echo $dep['departement']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <div class="row">
                                    <div class="col-6">
                                        <label class="form-label">Montant Min</label>
                                        <input type="number" name="filtre_montant_min" class="form-control" placeholder="0" step="0.01" value="<?php echo $filtre_montant_min; ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Montant Max</label>
                                        <input type="number" name="filtre_montant_max" class="form-control" placeholder="999" step="0.01" value="<?php echo $filtre_montant_max; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12 d-flex gap-2 align-items-end">
                                <button type="submit" class="btn btn-primary">Filtrer</button>
                                <a href="dashboard.php" class="btn btn-secondary">Reinitialiser</a>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive mt-3">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Demandeur</th>
                                    <th>Departement</th>
                                    <th>Motif</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="demandesTableBody">
                                <?php if (count($demandes) > 0): ?>
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
                                            <td><?php echo $demande['departement'] ?? '-'; ?></td>
                                            <td>
                                                <button type="button" class="btn-detail" data-bs-toggle="modal" data-bs-target="#detailModal"
                                                    data-id="<?php echo $id; ?>"
                                                    data-objet="<?php echo htmlspecialchars($demande['objet']); ?>"
                                                    data-montant="<?php echo number_format($demande['montant_demande'], 2); ?>"
                                                    data-devise="<?php echo $demande['devise'] ?? 'USD'; ?>"
                                                    data-demandeur="<?php echo htmlspecialchars($demande['prenom'] . ' ' . $demande['nom']); ?>"
                                                    data-date="<?php echo $demande['date_creation']; ?>"
                                                    data-statut="<?php echo $libelle; ?>"
                                                    data-piece="<?php echo $demande['piece_jointe'] ?? ''; ?>">
                                                    <i class="fas fa-eye me-1"></i> Voir
                                                </button>
                                            </td>
                                            <td><?php echo number_format($demande['montant_demande'], 2); ?> <?php echo $demande['devise'] ?? 'USD'; ?></td>
                                            <td class="statut-cell" id="statut-<?php echo $id; ?>">
                                                <span class="badge <?php echo $badge; ?>"><?php echo $libelle; ?></span>
                                            </td>
                                            <td class="actions-cell" id="actions-<?php echo $id; ?>">
                                                <?php if ($statut == 'pendinglogistique'): ?>
                                                    <form method="POST" style="display:inline-block;">
                                                        <input type="hidden" name="action" value="facturer">
                                                        <input type="hidden" name="id_demande" value="<?php echo $id; ?>">
                                                        <button type="submit" class="btn-facturer">Facturer</button>
                                                    </form>
                                                <?php elseif ($statut == 'facturee'): ?>
                                                    <a href="facture.php?id=<?php echo $id; ?>&action=decaisser" class="btn-decaisser">Decaisser</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Aucune demande trouvee</td>
                                    </tr>
                                <?php endif; ?>
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
                                <li><a href="?page=<?php echo $page - 1; ?>&filtre_statut=<?php echo $filtre_statut; ?>&filtre_date_debut=<?php echo $filtre_date_debut; ?>&filtre_date_fin=<?php echo $filtre_date_fin; ?>&filtre_demandeur=<?php echo urlencode($filtre_demandeur); ?>&filtre_departement=<?php echo urlencode($filtre_departement); ?>&filtre_montant_min=<?php echo $filtre_montant_min; ?>&filtre_montant_max=<?php echo $filtre_montant_max; ?>">« Précédent</a></li>
                            <?php else: ?>
                                <li class="disabled"><span>« Précédent</span></li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <li class="active"><span><?php echo $i; ?></span></li>
                                <?php else: ?>
                                    <li><a href="?page=<?php echo $i; ?>&filtre_statut=<?php echo $filtre_statut; ?>&filtre_date_debut=<?php echo $filtre_date_debut; ?>&filtre_date_fin=<?php echo $filtre_date_fin; ?>&filtre_demandeur=<?php echo urlencode($filtre_demandeur); ?>&filtre_departement=<?php echo urlencode($filtre_departement); ?>&filtre_montant_min=<?php echo $filtre_montant_min; ?>&filtre_montant_max=<?php echo $filtre_montant_max; ?>"><?php echo $i; ?></a></li>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li><a href="?page=<?php echo $page + 1; ?>&filtre_statut=<?php echo $filtre_statut; ?>&filtre_date_debut=<?php echo $filtre_date_debut; ?>&filtre_date_fin=<?php echo $filtre_date_fin; ?>&filtre_demandeur=<?php echo urlencode($filtre_demandeur); ?>&filtre_departement=<?php echo urlencode($filtre_departement); ?>&filtre_montant_min=<?php echo $filtre_montant_min; ?>&filtre_montant_max=<?php echo $filtre_montant_max; ?>">Suivant »</a></li>
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
                    <p><strong>Date :</strong> <span id="detail_date"></span></p>
                    <p><strong>Statut :</strong> <span id="detail_statut"></span></p>
                    <p><strong>Motif :</strong></p>
                    <div class="border p-2 rounded bg-light" id="detail_objet" style="white-space: pre-wrap;"></div>
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
            var statut = '<?php echo $filtre_statut; ?>';
            var date_debut = '<?php echo $filtre_date_debut; ?>';
            var date_fin = '<?php echo $filtre_date_fin; ?>';
            var demandeur = '<?php echo addslashes($filtre_demandeur); ?>';
            var departement = '<?php echo addslashes($filtre_departement); ?>';
            var montant_min = '<?php echo $filtre_montant_min; ?>';
            var montant_max = '<?php echo $filtre_montant_max; ?>';
            $.ajax({
                url: '../refresh.php?action=demandes&page=' + page + '&filtre_statut=' + statut + '&filtre_date_debut=' + date_debut + '&filtre_date_fin=' + date_fin + '&filtre_demandeur=' + demandeur + '&filtre_departement=' + departement + '&filtre_montant_min=' + montant_min + '&filtre_montant_max=' + montant_max,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (data.error) {
                        return;
                    }
                    var idsActifs = [];
                    $.each(data, function(index, item) {
                        idsActifs.push(item.id);
                        if ($('#statut-' + item.id).length > 0) {
                            $('#statut-' + item.id).html(item.statut_html);
                            $('#actions-' + item.id).html(item.actions_html);
                        } else {
                            var newRow = '<tr id="demande-' + item.id + '">' +
                                '<td>' + item.id + '</td>' +
                                '<td>' + item.demandeur + '</td>' +
                                '<td>' + item.departement + '</td>' +
                                '<td><button type="button" class="btn-detail" data-bs-toggle="modal" data-bs-target="#detailModal" ' +
                                'data-id="' + item.id + '" data-objet="' + item.objet + '" data-montant="' + item.montant + '" ' +
                                'data-devise="' + item.devise + '" data-demandeur="' + item.demandeur + '" ' +
                                'data-date="' + item.date + '" data-statut="' + item.libelle + '" ' +
                                'data-piece="' + item.piece + '"><i class="fas fa-eye me-1"></i> Voir</button></td>' +
                                '<td>' + item.montant + ' ' + item.devise + '</td>' +
                                '<td class="statut-cell" id="statut-' + item.id + '">' + item.statut_html + '</td>' +
                                '<td class="actions-cell" id="actions-' + item.id + '">' + item.actions_html + '</td>' +
                                '</tr>';
                            $('#demandesTableBody').append(newRow);
                        }
                    });
                    $('tr[id^="demande-"]').each(function() {
                        var id = $(this).attr('id').replace('demande-', '');
                        if ($.inArray(parseInt(id), idsActifs) === -1) {
                            $(this).remove();
                        }
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
            document.getElementById('detail_date').innerText = button.getAttribute('data-date');
            document.getElementById('detail_statut').innerText = button.getAttribute('data-statut');
            document.getElementById('detail_objet').innerText = button.getAttribute('data-objet');
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