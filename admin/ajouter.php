<?php
require_once '../config/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['id_utilisateur']) || $_SESSION['id_role'] != 1) {
    header('Location: ../login.php');
    exit();
}

$erreur = '';
$succes = '';

$roles = $pdo->query("SELECT id_role, nom_role FROM roles WHERE id_role != 1 ORDER BY id_role")->fetchAll();

$departements = $pdo->query("SELECT id_departement, departement FROM departements ORDER BY id_departement")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $email = $_POST['email'] ?? '';
    $mdp = $_POST['mdp'] ?? '';
    $id_role = $_POST['id_role'] ?? '';
    $id_departement = $_POST['id_departement'] ?? null;

    if (empty($nom) || empty($prenom) || empty($email) || empty($mdp) || empty($id_role)) {
        $erreur = 'Tous les champs obligatoires doivent etre remplis.';
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $erreur = 'Cet email est deja utilise.';
        } else {
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
                header('refresh:2;url=index.php?onglet=utilisateurs');
            } else {
                $erreur = 'Erreur lors de l ajout.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ajouter un utilisateur</title>
    <link rel="stylesheet" href="../assets/admin.css">
    <link rel="stylesheet" href="../assets/ajouter.css">
</head>
<body>

<div class="header">
    <h2>Ajouter un utilisateur</h2>
    <a href="index.php?onglet=utilisateurs">Retour</a>
    <a href="../logout.php">Deconnexion</a>
</div>

<div class="content">
    <?php if ($erreur): ?>
        <div class="erreur"><?php echo $erreur; ?></div>
    <?php endif; ?>
    <?php if ($succes): ?>
        <div class="succes"><?php echo $succes; ?></div>
    <?php endif; ?>

    <form method="POST" class="form-ajout">
        <div class="form-group">
            <label>Nom *</label>
            <input type="text" name="nom" required>
        </div>

        <div class="form-group">
            <label>Prenom *</label>
            <input type="text" name="prenom" required>
        </div>

        <div class="form-group">
            <label>Email *</label>
            <input type="email" name="email" required>
        </div>

        <div class="form-group">
            <label>Mot de passe *</label>
            <input type="password" name="mdp" required>
        </div>

        <div class="form-group">
            <label>Role *</label>
            <select name="id_role" required>
                <option value="">Selectionner un role</option>
                <?php foreach ($roles as $role): ?>
                    <option value="<?php echo $role['id_role']; ?>">
                        <?php echo ucfirst($role['nom_role']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Departement</label>
            <select name="id_departement">
                <option value="">Aucun</option>
                <?php foreach ($departements as $dep): ?>
                    <option value="<?php echo $dep['id_departement']; ?>">
                        <?php echo htmlspecialchars($dep['departement']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <button type="submit" class="btn">Ajouter</button>
            <a href="index.php?onglet=utilisateurs" class="btn-annuler">Annuler</a>
        </div>
    </form>
</div>

</body>
</html>