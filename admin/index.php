<?php
require_once '../config/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['id_utilisateur']) || $_SESSION['id_role'] != 1) {
    header('Location: ../login.php');
    exit();
}

$onglet = $_GET['onglet'] ?? 'utilisateurs';

$erreur = '';
$succes = '';

$roles = $pdo->query("SELECT id_role, nom_role FROM roles WHERE id_role != 1 ORDER BY id_role")->fetchAll();
$departements = $pdo->query("SELECT id_departement, departement FROM departements ORDER BY id_departement")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {

    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $email = $_POST['email'] ?? '';
    $mdp = $_POST['mdp'] ?? '';
    $id_role = $_POST['id_role'] ?? '';
    $id_departement = $_POST['id_departement'] ?? null;
    $new_departement = trim($_POST['new_departement'] ?? '');

    if (empty($nom) || empty($prenom) || empty($email) || empty($mdp) || empty($id_role)) {
        $erreur = 'Tous les champs obligatoires doivent etre remplis.';
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
                ':id_departement' => $id_departement ?: null
            ]);

            if ($resultat) {
                $succes = 'Utilisateur ajoute avec succes.';
                echo '<meta http-equiv="refresh" content="1;url=index.php?onglet=utilisateurs">';
            } else {
                $erreur = 'Erreur lors de l ajout.';
            }
        }
    }
}
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
    <link rel="stylesheet" href="../assets/ajouter.css">
</head>
<body>

<div class="header">
    <h2> Administration</h2>
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
                <tbody>
                <?php
                $stmt = $pdo->query("
                    SELECT u.*, r.nom_role, d.departement 
                    FROM utilisateurs u 
                    LEFT JOIN roles r ON u.id_role = r.id_role 
                    LEFT JOIN departements d ON u.id_departement = d.id_departement
                    ORDER BY u.id_utilisateur ASC
                ");
                $i = 1;
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
                            <a href="modifier.php?id=<?php echo $row['id_utilisateur']; ?>" class="btn-modifier">
                                <i class="fas fa-edit"></i> Modifier
                            </a>
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
                        <th>Statut</th>
                        <th>Date creation</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $stmt = $pdo->query("
                    SELECT d.*, CONCAT(u.nom, ' ', u.prenom) as demandeur, dep.departement
                    FROM demandes d
                    JOIN utilisateurs u ON d.id_demandeur = u.id_utilisateur
                    LEFT JOIN departements dep ON u.id_departement = dep.id_departement
                    ORDER BY d.id_demande DESC
                ");
                while ($row = $stmt->fetch()):
                ?>
                    <tr>
                        <td><?php echo $row['id_demande']; ?></td>
                        <td><?php echo htmlspecialchars($row['objet']); ?></td>
                        <td><?php echo number_format($row['montant_demande'], 2); ?> USD</p></td>
                        <td><?php echo htmlspecialchars($row['demandeur']); ?></td>
                        <td><?php echo $row['statut']; ?></td>
                        <td><?php echo $row['date_creation']; ?></td>
                        <td class="actions">
                            <a href="../demandes/detail.php?id=<?php echo $row['id_demande']; ?>" class="btn-modifier">
                                <i class="fas fa-eye"></i> Voir
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>

        <h3 class="mb-4"><i class="fas fa-history me-2" style="color: #008C45;"></i> Historique des logs</h3>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Utilisateur</th>
                        <th>Action</th>
                        <th>Statut</th>
                        <th>Justification</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $stmt = $pdo->query("
                    SELECT l.*, CONCAT(u.nom, ' ', u.prenom) as utilisateur
                    FROM logs l
                    JOIN utilisateurs u ON l.id_utilisateur = u.id_utilisateur
                    ORDER BY l.id_log DESC LIMIT 100
                ");
                while ($row = $stmt->fetch()):
                ?>
                    <tr>
                        <td><?php echo $row['id_log']; ?></td>
                        <td><?php echo $row['date_action']; ?></td>
                        <td><?php echo htmlspecialchars($row['utilisateur']); ?></td>
                        <td><?php echo $row['action']; ?></td>
                        <td><?php echo $row['nouveau_statut']; ?></td>
                        <td><?php echo htmlspecialchars($row['justification'] ?? '-'); ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>
</div>

<div class="modal fade" id="ajouterModal" tabindex="-1" aria-labelledby="ajouterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ajouterModalLabel">
                    <i class="fas fa-user-plus me-2"></i> Ajouter un utilisateur
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
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
                        <input type="text" name="new_departement" class="form-control">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    <?php if ($erreur && $onglet == 'utilisateurs'): ?>
        var myModal = new bootstrap.Modal(document.getElementById('ajouterModal'));
        myModal.show();
    <?php endif; ?>
</script>
</body>
</html>