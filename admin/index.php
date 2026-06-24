<?php
require_once '../config/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['id_utilisateur']) || $_SESSION['id_role'] != 1) {
    header('Location: ../login.php');
    exit();
}

function getLibelleStatut($statut) {
    switch ($statut) {
        case 'pending': return 'Attente de validation';
        case 'pending_superviseur': return 'Attente validation superviseur';
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
        case 'pending_superviseur': return 'badge-superviseur';
        case 'pendinglogistique': return 'badge-logistique';
        case 'facturee': return 'badge-facturee';
        case 'confirmee': return 'badge-succes';
        case 'rejetee': return 'badge-rejet';
        case 'annulee': return 'badge-annule';
        default: return '';
    }
}

$onglet = $_GET['onglet'] ?? 'utilisateurs';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

$erreur = '';
$succes = '';

$roles = $pdo->query("SELECT id_role, nom_role FROM roles WHERE id_role != 1 ORDER BY id_role")->fetchAll();
$departements = $pdo->query("SELECT id_departement, departement FROM departements ORDER BY id_departement")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {

    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mdp = $_POST['mdp'] ?? '';
    $id_role = $_POST['id_role'] ?? '';
    $id_departement = $_POST['id_departement'] ?? null;
    $new_departement = trim($_POST['new_departement'] ?? '');

    if ($id_departement === '') {
        $id_departement = null;
    }

    if (empty($nom) || empty($prenom) || empty($email) || empty($mdp) || empty($id_role)) {
        $erreur = 'Tous les champs obligatoires doivent etre remplis.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Email invalide.';
    } else {

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetchColumn() > 0) {
            $erreur = 'Cet email est deja utilise.';
        } else {

            if (!empty($new_departement)) {
                $check = $pdo->prepare("SELECT id_departement FROM departements WHERE departement = ?");
                $check->execute([$new_departement]);
                $dep = $check->fetch();

                if ($dep) {
                    $id_departement = $dep['id_departement'];
                } else {
                    $ins = $pdo->prepare("INSERT INTO departements (departement) VALUES (?)");
                    $ins->execute([$new_departement]);
                    $id_departement = $pdo->lastInsertId();
                }
            }

            $mdp_hash = password_hash($mdp, PASSWORD_DEFAULT);

            $sql = "INSERT INTO utilisateurs (nom, prenom, email, mdp, id_role, id_departement)
                    VALUES (:nom, :prenom, :email, :mdp, :id_role, :id_departement)";

            $stmt = $pdo->prepare($sql);
            $resultat = $stmt->execute([
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':email' => $email,
                ':mdp' => $mdp_hash,
                ':id_role' => $id_role,
                ':id_departement' => $id_departement
            ]);

            if ($resultat) {
                $succes = 'Utilisateur ajoute avec succes.';
                echo '<meta http-equiv="refresh" content="2;url=index.php?onglet=utilisateurs">';
                exit();
            } else {
                $erreur = 'Erreur lors de l ajout.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier_utilisateur') {
    $id_utilisateur = $_POST['id_utilisateur'] ?? 0;
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $id_role = $_POST['id_role'] ?? '';
    $id_departement = $_POST['id_departement'] ?? null;
    $new_departement = trim($_POST['new_departement'] ?? '');
    $mdp = trim($_POST['mdp'] ?? '');

    if ($id_departement === '') {
        $id_departement = null;
    }

    if (empty($nom) || empty($prenom) || empty($email) || empty($id_role)) {
        $erreur = 'Tous les champs obligatoires doivent etre remplis.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Email invalide.';
    } else {

        $id_departement_final = null;

        if (!empty($new_departement)) {
            $check = $pdo->prepare("SELECT id_departement FROM departements WHERE departement = ?");
            $check->execute([$new_departement]);
            $dep = $check->fetch();

            if ($dep) {
                $id_departement_final = $dep['id_departement'];
            } else {
                $ins = $pdo->prepare("INSERT INTO departements (departement) VALUES (?)");
                $ins->execute([$new_departement]);
                $id_departement_final = $pdo->lastInsertId();
            }
        } else {
            $id_departement_final = $id_departement;
        }

        if (!empty($mdp)) {
            $mdp_hash = password_hash($mdp, PASSWORD_DEFAULT);
            $sql = "UPDATE utilisateurs SET nom = ?, prenom = ?, email = ?, mdp = ?, id_role = ?, id_departement = ? WHERE id_utilisateur = ?";
            $stmt = $pdo->prepare($sql);
            $resultat = $stmt->execute([$nom, $prenom, $email, $mdp_hash, $id_role, $id_departement_final, $id_utilisateur]);
        } else {
            $sql = "UPDATE utilisateurs SET nom = ?, prenom = ?, email = ?, id_role = ?, id_departement = ? WHERE id_utilisateur = ?";
            $stmt = $pdo->prepare($sql);
            $resultat = $stmt->execute([$nom, $prenom, $email, $id_role, $id_departement_final, $id_utilisateur]);
        }

        if ($resultat) {
            $succes = 'Utilisateur modifie avec succes.';
            echo '<meta http-equiv="refresh" content="2;url=index.php?onglet=utilisateurs">';
            exit();
        } else {
            $erreur = 'Erreur lors de la modification.';
        }
    }
}

$count_logs = $pdo->query("SELECT COUNT(*) FROM logs")->fetchColumn();
$total_pages_logs = ceil($count_logs / $limit);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body>

<div class="header">
    <h2>Administration</h2>
    <div>
        <a href="../logout.php"><i class="fas fa-sign-out-alt me-1"></i> Deconnexion</a>
    </div>
</div>

<div class="onglets">
    <a href="?onglet=utilisateurs" class="<?php echo $onglet == 'utilisateurs' ? 'active' : ''; ?>">
        <i class="fas fa-users me-1"></i> Utilisateurs
    </a>
    <a href="?onglet=demandes" class="<?php echo $onglet == 'demandes' ? 'active' : ''; ?>">
        <i class="fas fa-file-invoice me-1"></i> Demandes
    </a>
    <a href="?onglet=logs" class="<?php echo $onglet == 'logs' ? 'active' : ''; ?>">
        <i class="fas fa-history me-1"></i> Logs
    </a>
</div>

<div class="content">
    <?php if ($onglet == 'utilisateurs'): ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3><i class="fas fa-users me-2" style="color: #008C45;"></i> Liste des utilisateurs</h3>
            <button type="button" class="btn-ajouter" data-bs-toggle="modal" data-bs-target="#ajouterModal">
                <i class="fas fa-user-plus me-1"></i> Ajouter un utilisateur
            </button>
        </div>

        <?php if ($succes): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo $succes; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($erreur): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $erreur; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Nom</th>
                        <th>Prenom</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Departement</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="utilisateursTableBody">
                <?php
                $stmt = $pdo->query("
                    SELECT u.*, r.nom_role, d.departement 
                    FROM utilisateurs u 
                    LEFT JOIN roles r ON u.id_role = r.id_role 
                    LEFT JOIN departements d ON u.id_departement = d.id_departement
                    ORDER BY u.id_utilisateur ASC
                    LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
                ");
                $i = $offset + 1;
                while ($row = $stmt->fetch()):
                ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($row['nom']); ?></td>
                        <td><?php echo htmlspecialchars($row['prenom']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><span class="badge-role"><?php echo $row['nom_role']; ?></span></td>
                        <td><?php echo $row['departement'] ?? '-'; ?></td>
                        <td class="actions">
                            <button type="button" class="btn-modifier" data-bs-toggle="modal" data-bs-target="#modifierModal" 
                                data-id="<?php echo $row['id_utilisateur']; ?>"
                                data-nom="<?php echo htmlspecialchars($row['nom']); ?>"
                                data-prenom="<?php echo htmlspecialchars($row['prenom']); ?>"
                                data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                data-role="<?php echo $row['id_role']; ?>"
                                data-departement="<?php echo $row['id_departement']; ?>">
                                <i class="fas fa-edit"></i> Modifier
                            </button>
                            <?php if ($row['id_role'] != 1): ?>
                                <a href="supprimer.php?id=<?php echo $row['id_utilisateur']; ?>" class="btn-supprimer" onclick="return confirm('Supprimer ?')">
                                    <i class="fas fa-trash-alt"></i> Supprimer
                                </a>
                            <?php else: ?>
                                <span class="btn-supprimer-disabled"><i class="fas fa-trash-alt"></i> Supprimer</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <?php
        $total_users = $pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
        $total_pages_users = ceil($total_users / $limit);
        if ($total_pages_users > 1):
        ?>
        <div class="pagination-container">
            <div class="pagination-info">
                Page <?php echo $page; ?> sur <?php echo $total_pages_users; ?> (<?php echo $total_users; ?> utilisateurs)
            </div>
            <ul class="pagination">
                <?php if ($page > 1): ?>
                    <li><a href="?onglet=utilisateurs&page=<?php echo $page - 1; ?>">« Précédent</a></li>
                <?php else: ?>
                    <li class="disabled"><span>« Précédent</span></li>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 1);
                $end_page = min($total_pages_users, $page + 1);

                if ($page <= 2) {
                    $start_page = 1;
                    $end_page = min(3, $total_pages_users);
                }

                if ($page >= $total_pages_users - 1) {
                    $start_page = max(1, $total_pages_users - 2);
                    $end_page = $total_pages_users;
                }

                $show_first = ($start_page > 1);
                $show_last = ($end_page < $total_pages_users);
                ?>

                <?php if ($show_first): ?>
                    <li><a href="?onglet=utilisateurs&page=1">1</a></li>
                    <?php if ($start_page > 2): ?>
                        <li class="disabled"><span>…</span></li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <?php if ($i == $page): ?>
                        <li class="active"><span><?php echo $i; ?></span></li>
                    <?php else: ?>
                        <li><a href="?onglet=utilisateurs&page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($show_last): ?>
                    <?php if ($end_page < $total_pages_users - 1): ?>
                        <li class="disabled"><span>…</span></li>
                    <?php endif; ?>
                    <li><a href="?onglet=utilisateurs&page=<?php echo $total_pages_users; ?>"><?php echo $total_pages_users; ?></a></li>
                <?php endif; ?>

                <?php if ($page < $total_pages_users): ?>
                    <li><a href="?onglet=utilisateurs&page=<?php echo $page + 1; ?>">Suivant »</a></li>
                <?php else: ?>
                    <li class="disabled"><span>Suivant »</span></li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>

    <?php elseif ($onglet == 'demandes'): ?>

        <h3 class="mb-4"><i class="fas fa-file-invoice me-2" style="color: #008C45;"></i> Liste des demandes</h3>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Objet</th>
                        <th>Montant</th>
                        <th>Demandeur</th>
                        <th>Departement</th>
                        <th>Statut</th>
                        <th>Renvoyee</th>
                        <th>Piece jointe</th>
                        <th>Date creation</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="demandesTableBody">
                <?php
                $stmt = $pdo->query("
                    SELECT d.*, CONCAT(u.nom, ' ', u.prenom) as demandeur, dep.departement
                    FROM demandes d
                    JOIN utilisateurs u ON d.id_demandeur = u.id_utilisateur
                    LEFT JOIN departements dep ON u.id_departement = dep.id_departement
                    ORDER BY d.id_demande DESC
                    LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
                ");
                while ($row = $stmt->fetch()):
                    $statut = $row['statut'];
                    $badge = getBadgeClass($statut);
                    $libelle = getLibelleStatut($statut);
                ?>
                <tr>
                    <td><?php echo $row['id_demande']; ?></td>
                    <td><?php echo htmlspecialchars($row['objet']); ?></td>
                    <td><?php echo number_format($row['montant_demande'], 2); ?> <?php echo $row['devise'] ?? 'USD'; ?></td>
                    <td><?php echo htmlspecialchars($row['demandeur']); ?></td>
                    <td><?php echo $row['departement'] ?? '-'; ?></td>
                    <td><span class="badge <?php echo $badge; ?>"><?php echo $libelle; ?></span></td>
                    <td>
                        <?php if ($row['renvoyee'] == 1): ?>
                            <span class="badge bg-warning text-dark">Oui</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Non</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['piece_jointe']): ?>
                            <a href="../<?php echo $row['piece_jointe']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download"></i> Voir
                            </a>
                        <?php else: ?>
                            <span class="text-muted">Aucune</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $row['date_creation']; ?></td>
                    <td class="actions">
                        <button type="button" class="btn-detail" data-bs-toggle="modal" data-bs-target="#detailModal" 
                            data-id="<?php echo $row['id_demande']; ?>"
                            data-objet="<?php echo htmlspecialchars($row['objet']); ?>"
                            data-montant="<?php echo number_format($row['montant_demande'], 2); ?>"
                            data-devise="<?php echo $row['devise'] ?? 'USD'; ?>"
                            data-demandeur="<?php echo htmlspecialchars($row['demandeur']); ?>"
                            data-departement="<?php echo $row['departement'] ?? '-'; ?>"
                            data-statut="<?php echo $libelle; ?>"
                            data-renvoyee="<?php echo $row['renvoyee'] == 1 ? 'Oui' : 'Non'; ?>"
                            data-justification="<?php echo htmlspecialchars($row['justification_rejet'] ?? ''); ?>"
                            data-date="<?php echo $row['date_creation']; ?>"
                            data-piece="<?php echo $row['piece_jointe'] ?? ''; ?>">
                            <i class="fas fa-eye"></i> Voir
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <?php
        $total_demandes = $pdo->query("SELECT COUNT(*) FROM demandes")->fetchColumn();
        $total_pages_demandes = ceil($total_demandes / $limit);
        if ($total_pages_demandes > 1):
        ?>
        <div class="pagination-container">
            <div class="pagination-info">
                Page <?php echo $page; ?> sur <?php echo $total_pages_demandes; ?> (<?php echo $total_demandes; ?> demandes)
            </div>
            <ul class="pagination">
                <?php if ($page > 1): ?>
                    <li><a href="?onglet=demandes&page=<?php echo $page - 1; ?>">« Précédent</a></li>
                <?php else: ?>
                    <li class="disabled"><span>« Précédent</span></li>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 1);
                $end_page = min($total_pages_demandes, $page + 1);

                if ($page <= 2) {
                    $start_page = 1;
                    $end_page = min(3, $total_pages_demandes);
                }

                if ($page >= $total_pages_demandes - 1) {
                    $start_page = max(1, $total_pages_demandes - 2);
                    $end_page = $total_pages_demandes;
                }

                $show_first = ($start_page > 1);
                $show_last = ($end_page < $total_pages_demandes);
                ?>

                <?php if ($show_first): ?>
                    <li><a href="?onglet=demandes&page=1">1</a></li>
                    <?php if ($start_page > 2): ?>
                        <li class="disabled"><span>…</span></li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <?php if ($i == $page): ?>
                        <li class="active"><span><?php echo $i; ?></span></li>
                    <?php else: ?>
                        <li><a href="?onglet=demandes&page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($show_last): ?>
                    <?php if ($end_page < $total_pages_demandes - 1): ?>
                        <li class="disabled"><span>…</span></li>
                    <?php endif; ?>
                    <li><a href="?onglet=demandes&page=<?php echo $total_pages_demandes; ?>"><?php echo $total_pages_demandes; ?></a></li>
                <?php endif; ?>

                <?php if ($page < $total_pages_demandes): ?>
                    <li><a href="?onglet=demandes&page=<?php echo $page + 1; ?>">Suivant »</a></li>
                <?php else: ?>
                    <li class="disabled"><span>Suivant »</span></li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>

    <?php else: ?>

        <h3 class="mb-4"><i class="fas fa-history me-2" style="color: #008C45;"></i> Historique des logs</h3>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Utilisateur</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $stmt = $pdo->prepare("
                    SELECT l.*, CONCAT(u.nom, ' ', u.prenom) as utilisateur, r.nom_role
                    FROM logs l
                    JOIN utilisateurs u ON l.id_utilisateur = u.id_utilisateur
                    LEFT JOIN roles r ON u.id_role = r.id_role
                    ORDER BY l.id_log DESC
                    LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
                ");
                $stmt->execute();
                while ($row = $stmt->fetch()):
                ?>
                    <tr>
                        <td><?php echo $row['id_log']; ?></td>
                        <td><?php echo $row['date_action']; ?></td>
                        <td><?php echo htmlspecialchars($row['utilisateur']); ?></td>
                        <td><?php echo $row['nom_role']; ?></td>
                        <td>
                            <button type="button" class="btn-detail" data-bs-toggle="modal" data-bs-target="#logDetailModal" 
                                data-action="<?php echo $row['action']; ?>"
                                data-statut="<?php echo $row['statut']; ?>"
                                data-justification="<?php echo htmlspecialchars($row['justification'] ?? ''); ?>"
                                data-id_demande="<?php echo $row['id_demande']; ?>"
                                data-date="<?php echo $row['date_action']; ?>"
                                data-utilisateur="<?php echo htmlspecialchars($row['utilisateur']); ?>"
                                data-role="<?php echo $row['nom_role']; ?>">
                                <i class="fas fa-eye"></i> Voir
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages_logs > 1): ?>
        <div class="pagination-container">
            <div class="pagination-info">
                Page <?php echo $page; ?> sur <?php echo $total_pages_logs; ?> (<?php echo $count_logs; ?> logs)
            </div>
            <ul class="pagination">
                <?php if ($page > 1): ?>
                    <li><a href="?onglet=logs&page=<?php echo $page - 1; ?>">« Précédent</a></li>
                <?php else: ?>
                    <li class="disabled"><span>« Précédent</span></li>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 1);
                $end_page = min($total_pages_logs, $page + 1);

                if ($page <= 2) {
                    $start_page = 1;
                    $end_page = min(3, $total_pages_logs);
                }

                if ($page >= $total_pages_logs - 1) {
                    $start_page = max(1, $total_pages_logs - 2);
                    $end_page = $total_pages_logs;
                }

                $show_first = ($start_page > 1);
                $show_last = ($end_page < $total_pages_logs);
                ?>

                <?php if ($show_first): ?>
                    <li><a href="?onglet=logs&page=1">1</a></li>
                    <?php if ($start_page > 2): ?>
                        <li class="disabled"><span>…</span></li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <?php if ($i == $page): ?>
                        <li class="active"><span><?php echo $i; ?></span></li>
                    <?php else: ?>
                        <li><a href="?onglet=logs&page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($show_last): ?>
                    <?php if ($end_page < $total_pages_logs - 1): ?>
                        <li class="disabled"><span>…</span></li>
                    <?php endif; ?>
                    <li><a href="?onglet=logs&page=<?php echo $total_pages_logs; ?>"><?php echo $total_pages_logs; ?></a></li>
                <?php endif; ?>

                <?php if ($page < $total_pages_logs): ?>
                    <li><a href="?onglet=logs&page=<?php echo $page + 1; ?>">Suivant »</a></li>
                <?php else: ?>
                    <li class="disabled"><span>Suivant »</span></li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<!-- MODAL AJOUTER -->
<div class="modal fade" id="ajouterModal" tabindex="-1" aria-labelledby="ajouterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ajouterModalLabel">
                    <i class="fas fa-user-plus me-2"></i> Ajouter un utilisateur
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="index.php?onglet=utilisateurs">
                <input type="hidden" name="action" value="ajouter">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nom *</label>
                            <input type="text" name="nom" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prenom *</label>
                            <input type="text" name="prenom" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mot de passe *</label>
                        <input type="password" name="mdp" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Role *</label>
                        <select name="id_role" class="form-select" required>
                            <option value="">Selectionner un role</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id_role']; ?>">
                                    <?php echo ucfirst($role['nom_role']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Departement existant</label>
                        <select name="id_departement" class="form-select">
                            <option value="">Aucun</option>
                            <?php foreach ($departements as $dep): ?>
                                <option value="<?php echo $dep['id_departement']; ?>">
                                    <?php echo htmlspecialchars($dep['departement']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nouveau departement</label>
                        <input type="text" name="new_departement" class="form-control" placeholder="Creer un nouveau departement">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL MODIFIER -->
<div class="modal fade" id="modifierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-edit me-2"></i> Modifier l'utilisateur
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="index.php?onglet=utilisateurs">
                <input type="hidden" name="action" value="modifier_utilisateur">
                <input type="hidden" name="id_utilisateur" id="modif_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nom *</label>
                            <input type="text" name="nom" id="modif_nom" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prenom *</label>
                            <input type="text" name="prenom" id="modif_prenom" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" id="modif_email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nouveau mot de passe</label>
                        <input type="password" name="mdp" class="form-control" placeholder="Laisser vide pour ne pas changer">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Role *</label>
                        <select name="id_role" id="modif_role" class="form-select" required>
                            <option value="">Selectionner un role</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id_role']; ?>">
                                    <?php echo ucfirst($role['nom_role']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Departement existant</label>
                        <select name="id_departement" id="modif_departement" class="form-select">
                            <option value="">Aucun</option>
                            <?php foreach ($departements as $dep): ?>
                                <option value="<?php echo $dep['id_departement']; ?>">
                                    <?php echo htmlspecialchars($dep['departement']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nouveau departement</label>
                        <input type="text" name="new_departement" class="form-control" placeholder="Creer un nouveau departement">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL DETAIL DEMANDE -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Detail de la demande</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>ID :</strong> <span id="detail_id"></span></p>
                <p><strong>Demandeur :</strong> <span id="detail_demandeur"></span></p>
                <p><strong>Departement :</strong> <span id="detail_departement"></span></p>
                <p><strong>Montant :</strong> <span id="detail_montant"></span> <span id="detail_devise"></span></p>
                <p><strong>Statut :</strong> <span id="detail_statut"></span></p>
                <p><strong>Renvoyee apres rejet :</strong> <span id="detail_renvoyee"></span></p>
                <p><strong>Date creation :</strong> <span id="detail_date"></span></p>
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

<!-- MODAL DETAIL LOG -->
<div class="modal fade" id="logDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Detail du log</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Date :</strong> <span id="log_date"></span></p>
                <p><strong>Utilisateur :</strong> <span id="log_utilisateur"></span></p>
                <p><strong>Role :</strong> <span id="log_role"></span></p>
                <p><strong>Action :</strong> <span id="log_action"></span></p>
                <p><strong>Statut :</strong> <span id="log_statut"></span></p>
                <p><strong>ID demande :</strong> <span id="log_id_demande"></span></p>
                <p><strong>Justification :</strong></p>
                <div class="border p-2 rounded bg-light" id="log_justification" style="white-space: pre-wrap;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    <?php if ($erreur && $onglet == 'utilisateurs'): ?>
        var myModal = new bootstrap.Modal(document.getElementById('ajouterModal'));
        myModal.show();
    <?php endif; ?>

    const modifierModal = document.getElementById('modifierModal');
    modifierModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        document.getElementById('modif_id').value = button.getAttribute('data-id');
        document.getElementById('modif_nom').value = button.getAttribute('data-nom');
        document.getElementById('modif_prenom').value = button.getAttribute('data-prenom');
        document.getElementById('modif_email').value = button.getAttribute('data-email');
        document.getElementById('modif_role').value = button.getAttribute('data-role');
        document.getElementById('modif_departement').value = button.getAttribute('data-departement') || '';
    });

    const detailModal = document.getElementById('detailModal');
    detailModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        document.getElementById('detail_id').innerText = button.getAttribute('data-id');
        document.getElementById('detail_demandeur').innerText = button.getAttribute('data-demandeur');
        document.getElementById('detail_departement').innerText = button.getAttribute('data-departement');
        document.getElementById('detail_montant').innerText = button.getAttribute('data-montant');
        document.getElementById('detail_devise').innerText = button.getAttribute('data-devise');
        document.getElementById('detail_statut').innerText = button.getAttribute('data-statut');
        document.getElementById('detail_renvoyee').innerText = button.getAttribute('data-renvoyee');
        document.getElementById('detail_date').innerText = button.getAttribute('data-date');
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

    const logDetailModal = document.getElementById('logDetailModal');
    logDetailModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        document.getElementById('log_date').innerText = button.getAttribute('data-date');
        document.getElementById('log_utilisateur').innerText = button.getAttribute('data-utilisateur');
        document.getElementById('log_role').innerText = button.getAttribute('data-role');
        document.getElementById('log_action').innerText = button.getAttribute('data-action');
        document.getElementById('log_statut').innerText = button.getAttribute('data-statut');
        document.getElementById('log_id_demande').innerText = button.getAttribute('data-id_demande');
        const justification = button.getAttribute('data-justification');
        document.getElementById('log_justification').innerText = justification || 'Aucune justification';
    });
</script>
</body>
</html>