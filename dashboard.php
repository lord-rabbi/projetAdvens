<?php
require_once 'config/session.php';
require_once 'config/database.php';

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: login.php');
    exit();
}

if ($_SESSION['id_role'] != 4) {
    header('Location: admin/index.php');
    exit();
}

$id_user = $_SESSION['id_utilisateur'];
$erreur = '';
$succes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'nouvelle_demande') {
    $objet = trim($_POST['objet'] ?? '');
    $montant = floatval($_POST['montant'] ?? 0);
    $devise = $_POST['devise'] ?? 'USD';
    $piece_jointe = '';

    if (empty($objet)) {
        $erreur = 'Le motif est obligatoire.';
    } elseif ($montant <= 0) {
        $erreur = 'Le montant doit etre superieur a 0.';
    } else {
        if (isset($_FILES['piece_jointe']) && $_FILES['piece_jointe']['error'] == 0) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $nom_fichier = time() . '_' . basename($_FILES['piece_jointe']['name']);
            $chemin = $upload_dir . $nom_fichier;
            if (move_uploaded_file($_FILES['piece_jointe']['tmp_name'], $chemin)) {
                $piece_jointe = 'uploads/' . $nom_fichier;
            }
        }

        $sql = "INSERT INTO demandes (objet, montant_demande, devise, date_creation, id_demandeur, statut, piece_jointe)
                VALUES (?, ?, ?, NOW(), ?, 'en_attente_chef', ?)";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$objet, $montant, $devise, $id_user, $piece_jointe])) {
            $succes = 'Demande envoyee avec succes.';
        } else {
            $erreur = 'Erreur lors de l envoi.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier_demande') {
    $id_demande = $_POST['id_demande'] ?? 0;
    $objet = trim($_POST['objet'] ?? '');
    $montant = floatval($_POST['montant'] ?? 0);
    $devise = $_POST['devise'] ?? 'USD';

    if (empty($objet)) {
        $erreur = 'Le motif est obligatoire.';
    } elseif ($montant <= 0) {
        $erreur = 'Le montant doit etre superieur a 0.';
    } else {
        $sql = "UPDATE demandes SET objet = ?, montant_demande = ?, devise = ?, statut = 'en_attente_chef', renvoyee = 1 WHERE id_demande = ? AND id_demandeur = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$objet, $montant, $devise, $id_demande, $id_user])) {
            $succes = 'Demande modifiee et renvoyee.';
        } else {
            $erreur = 'Erreur lors de la modification.';
        }
    }
}

if (isset($_GET['annuler']) && is_numeric($_GET['annuler'])) {
    $id_demande = $_GET['annuler'];
    $stmt = $pdo->prepare("UPDATE demandes SET statut = 'annulee' WHERE id_demande = ? AND id_demandeur = ? AND statut = 'en_attente_chef'");
    $stmt->execute([$id_demande, $id_user]);
    header('Location: dashboard.php?page=mes_demandes');
    exit();
}

$page = $_GET['page'] ?? 'mes_demandes';

$stmt = $pdo->prepare("SELECT * FROM demandes WHERE id_demandeur = ? ORDER BY date_creation DESC");
$stmt->execute([$id_user]);
$mes_demandes = $stmt->fetchAll();

$demande_modification = null;
if (isset($_GET['modifier']) && is_numeric($_GET['modifier'])) {
    $stmt = $pdo->prepare("SELECT * FROM demandes WHERE id_demande = ? AND id_demandeur = ? AND statut = 'rejetee_chef'");
    $stmt->execute([$_GET['modifier'], $id_user]);
    $demande_modification = $stmt->fetch();
    if ($demande_modification) {
        $page = 'modifier';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Demandeur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/dashboard.css">
</head>
<body>

<div class="header">
    <h2>Bienvenue, <span><?php echo $_SESSION['prenom']; ?></span></h2>
    <a href="logout.php"><button class="btn-deconnexion">Deconnexion</button></a>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="sidebar">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo $page == 'nouvelle' ? 'active' : ''; ?>" href="dashboard.php?page=nouvelle">
                        <i class="fas fa-plus me-2"></i> Nouvelle demande
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $page == 'mes_demandes' ? 'active' : ''; ?>" href="dashboard.php?page=mes_demandes">
                        <i class="fas fa-list me-2"></i> Mes demandes
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

            <?php if ($page == 'nouvelle'): ?>
                <div class="card">
                    <h2>Nouvelle demande</h2>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="nouvelle_demande">
                        <div class="mb-3">
                            <label class="form-label">Motif</label>
                            <textarea name="objet" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Montant</label>
                            <div class="input-group">
                                <input type="number" name="montant" class="form-control" step="0.01" min="0.01" required>
                                <select name="devise" class="form-select" style="width: 100px;">
                                    <option value="USD">USD</option>
                                    <option value="CDF">CDF</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Piece jointe</label>
                            <input type="file" name="piece_jointe" class="form-control">
                        </div>
                        <button type="submit" class="btn-submit">Envoyer</button>
                    </form>
                </div>

            <?php elseif ($page == 'modifier' && $demande_modification): ?>
                <div class="card">
                    <h2>Modifier ma demande</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="modifier_demande">
                        <input type="hidden" name="id_demande" value="<?php echo $demande_modification['id_demande']; ?>">
                        <div class="mb-3">
                            <label class="form-label">Motif</label>
                            <textarea name="objet" class="form-control" rows="3" required><?php echo htmlspecialchars($demande_modification['objet']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Montant</label>
                            <div class="input-group">
                                <input type="number" name="montant" class="form-control" step="0.01" value="<?php echo $demande_modification['montant_demande']; ?>" required>
                                <select name="devise" class="form-select" style="width: 100px;">
                                    <option value="USD" <?php echo ($demande_modification['devise'] ?? 'USD') == 'USD' ? 'selected' : ''; ?>>USD</option>
                                    <option value="CDF" <?php echo ($demande_modification['devise'] ?? 'USD') == 'CDF' ? 'selected' : ''; ?>>CDF</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn-submit">Renvoyer</button>
                        <a href="dashboard.php?page=mes_demandes" class="btn-secondary">Annuler</a>
                    </form>
                </div>

            <?php else: ?>
                <div class="card">
                    <h2>Mes demandes</h2>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Motif</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th>Renvoyee</th>
                                    <th>Date</th>
                                    <th>Justification rejet</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mes_demandes as $demande): ?>
                                    <?php
                                    $statut = $demande['statut'];
                                    $badge = '';
                                    if ($statut == 'en_attente_chef') $badge = 'badge-attente';
                                    elseif ($statut == 'en_attente_logistique') $badge = 'badge-logistique';
                                    elseif ($statut == 'facturee') $badge = 'badge-facturee';
                                    elseif ($statut == 'decaissement_confirme') $badge = 'badge-succes';
                                    elseif ($statut == 'rejetee_chef') $badge = 'badge-rejet';
                                    elseif ($statut == 'annulee') $badge = 'badge-annule';
                                    ?>
                                    <tr>
                                        <td><?php echo $demande['id_demande']; ?></td>
                                        <td>
                                            <button type="button" class="btn-detail" data-bs-toggle="modal" data-bs-target="#detailModal" 
                                                data-objet="<?php echo htmlspecialchars($demande['objet']); ?>"
                                                data-montant="<?php echo number_format($demande['montant_demande'], 2); ?>"
                                                data-devise="<?php echo $demande['devise'] ?? 'USD'; ?>"
                                                data-demandeur="<?php echo $_SESSION['prenom'] . ' ' . $_SESSION['nom']; ?>"
                                                data-date="<?php echo $demande['date_creation']; ?>"
                                                data-statut="<?php echo str_replace('_', ' ', $statut); ?>"
                                                data-renvoyee="<?php echo $demande['renvoyee'] == 1 ? 'Oui' : 'Non'; ?>"
                                                data-justification="<?php echo htmlspecialchars($demande['justification_rejet'] ?? ''); ?>"
                                                data-piece="<?php echo $demande['piece_jointe'] ?? ''; ?>">
                                                <i class="fas fa-eye me-1"></i> Voir
                                            </button>
                                        </td>
                                        <td><?php echo number_format($demande['montant_demande'], 2); ?> <?php echo $demande['devise'] ?? 'USD'; ?></td>
                                        <td><span class="badge <?php echo $badge; ?>"><?php echo str_replace('_', ' ', $statut); ?></span></td>
                                        <td>
                                            <?php if ($demande['renvoyee'] == 1): ?>
                                                <span class="badge bg-warning text-dark">Oui</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Non</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $demande['date_creation']; ?></td>
                                        <td>
                                            <?php if ($statut == 'rejetee_chef' && !empty($demande['justification_rejet'])): ?>
                                                <?php echo htmlspecialchars(substr($demande['justification_rejet'], 0, 50)); ?>...
                                            <?php elseif ($statut == 'rejetee_chef'): ?>
                                                <span class="text-danger">Rejetee</span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($statut == 'rejetee_chef'): ?>
                                                <a href="dashboard.php?page=modifier&modifier=<?php echo $demande['id_demande']; ?>" class="btn-warning-sm">Modifier</a>
                                            <?php endif; ?>
                                            <?php if ($statut == 'en_attente_chef'): ?>
                                                <a href="dashboard.php?annuler=<?php echo $demande['id_demande']; ?>&page=mes_demandes" class="btn-danger-sm" onclick="return confirm('Annuler ?')">Annuler</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
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
                <p class="mt-2"><strong>Justification du rejet :</strong></p>
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
        document.getElementById('detail_justification').innerText = justification || 'Aucune justification';
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