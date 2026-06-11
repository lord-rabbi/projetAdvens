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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'facturer') {
        $id_demande = $_POST['id_demande'] ?? 0;
        $stmt = $pdo->prepare("UPDATE demandes SET statut = 'facturee', date_facture = NOW() WHERE id_demande = ? AND statut = 'pendinglogistique'");
        if ($stmt->execute([$id_demande])) {
            $sql_log = "INSERT INTO logs (date_action, id_utilisateur, action, statut, id_demande) VALUES (NOW(), ?, 'facturation', 'facturee', ?)";
            $stmt_log = $pdo->prepare($sql_log);
            $stmt_log->execute([$_SESSION['id_utilisateur'], $id_demande]);
            header('Location: dashboard.php');
            exit();
        } else {
            $erreur = 'Erreur lors de la facturation.';
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'decaisser') {
        $id_demande = $_POST['id_demande'] ?? 0;
        $stmt = $pdo->prepare("UPDATE demandes SET statut = 'confirmee', date_decaissement = NOW() WHERE id_demande = ? AND statut = 'facturee'");
        if ($stmt->execute([$id_demande])) {
            $sql_log = "INSERT INTO logs (date_action, id_utilisateur, action, statut, id_demande) VALUES (NOW(), ?, 'decaissement', 'confirmee', ?)";
            $stmt_log = $pdo->prepare($sql_log);
            $stmt_log->execute([$_SESSION['id_utilisateur'], $id_demande]);
            header('Location: dashboard.php');
            exit();
        } else {
            $erreur = 'Erreur lors du decaissement.';
        }
    }
}

$stmt = $pdo->prepare("
SELECT d.*, u.nom, u.prenom, dep.departement
FROM demandes d
JOIN utilisateurs u ON d.id_demandeur = u.id_utilisateur
LEFT JOIN departements dep ON u.id_departement = dep.id_departement
WHERE d.statut = 'pendinglogistique' OR d.statut = 'facturee'
ORDER BY d.date_creation DESC
");
$stmt->execute();
$demandes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Logistique</title>
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
        <h2>Logistique <span><?php echo $_SESSION['prenom']; ?></span></h2>
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
                    <div class="alert alert-danger"><?php echo $erreur; ?></div>
                <?php endif; ?>
                <?php if ($succes): ?>
                    <div class="alert alert-success"><?php echo $succes; ?></div>
                <?php endif; ?>

                <div class="card">
                    <h2>Demandes a traiter</h2>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Demandeur</th>
                                    <th>Departement</th>
                                    <th>Motif</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th>Piece jointe</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                <tr>
                            </thead>
                            <tbody id="demandesTableBody">
                                <?php foreach ($demandes as $demande): ?>
                                    <?php
                                    $statut = $demande['statut'];
                                    $badge = '';
                                    if ($statut == 'pendinglogistique') $badge = 'badge-logistique';
                                    elseif ($statut == 'facturee') $badge = 'badge-facturee';
                                    ?>
                                    <tr>
                                        <td><?php echo $demande['id_demande']; ?></td>
                                        <td><?php echo htmlspecialchars($demande['prenom'] . ' ' . $demande['nom']); ?></td>
                                        <td><?php echo $demande['departement'] ?? '-'; ?></td>
                                        <td>
                                            <button type="button" class="btn-detail" data-bs-toggle="modal" data-bs-target="#detailModal"
                                                data-objet="<?php echo htmlspecialchars($demande['objet']); ?>"
                                                data-montant="<?php echo number_format($demande['montant_demande'], 2); ?>"
                                                data-devise="<?php echo $demande['devise'] ?? 'USD'; ?>"
                                                data-demandeur="<?php echo htmlspecialchars($demande['prenom'] . ' ' . $demande['nom']); ?>"
                                                data-date="<?php echo $demande['date_creation']; ?>"
                                                data-statut="<?php echo str_replace('_', ' ', $statut); ?>"
                                                data-piece="<?php echo $demande['piece_jointe'] ?? ''; ?>">
                                                <i class="fas fa-eye me-1"></i> Voir
                                            </button>
                                        </td>
                                        <td><?php echo number_format($demande['montant_demande'], 2); ?> <?php echo $demande['devise'] ?? 'USD'; ?></td>
                                        <td><span class="badge <?php echo $badge; ?>"><?php echo str_replace('_', ' ', $statut); ?></span></td>
                                        <td>
                                            <?php if ($demande['piece_jointe']): ?>
                                                <a href="../<?php echo $demande['piece_jointe']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-download"></i> Voir
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">Aucune</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $demande['date_creation']; ?></td>
                                        <td>
                                            <?php if ($statut == 'pendinglogistique'): ?>
                                                <form method="POST" style="display:inline-block;">
                                                    <input type="hidden" name="action" value="facturer">
                                                    <input type="hidden" name="id_demande" value="<?php echo $demande['id_demande']; ?>">
                                                    <button type="submit" class="btn-facturer">Faire une facture</button>
                                                </form>
                                            <?php elseif ($statut == 'facturee'): ?>
                                                <form method="POST" style="display:inline-block;">
                                                    <input type="hidden" name="action" value="decaisser">
                                                    <input type="hidden" name="id_demande" value="<?php echo $demande['id_demande']; ?>">
                                                    <button type="submit" class="btn-decaisser">Confirmer decaissement</button>
                                                </form>
                                            <?php endif; ?>
                                            </p>
                                    </tr>
                                    <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
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
            $.ajax({
                url: '../refresh.php?action=demandes',
                type: 'GET',
                dataType: 'html',
                success: function(data) {
                    $('#demandesTableBody').html(data);
                }
            });
        }
        setInterval(refreshDemandes, 5000);

        const detailModal = document.getElementById('detailModal');
        detailModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            document.getElementById('detail_demandeur').innerText = button.getAttribute('data-demandeur');
            document.getElementById('detail_montant').innerText = button.getAttribute('data-montant');
            document.getElementById('detail_devise').innerText = button.getAttribute('data-devise');
            document.getElementById('detail_date').innerText = button.getAttribute('data-date');
            document.getElementById('detail_statut').innerText = button.getAttribute('data-statut');
            document.getElementById('detail_objet').innerText = button.getAttribute('data-objet');
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