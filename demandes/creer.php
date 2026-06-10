<?php
require_once '../config/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: ../login.php');
    exit();
}

$erreur = '';
$succes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $objet = trim($_POST['objet'] ?? '');
    $montant = floatval($_POST['montant'] ?? 0);
    $piece_jointe = '';

    if (empty($objet)) {
        $erreur = 'Le motif est obligatoire.';
    } elseif ($montant <= 0) {
        $erreur = 'Le montant doit etre superieur a 0.';
    } else {
        if (isset($_FILES['piece_jointe']) && $_FILES['piece_jointe']['error'] == 0) {
            $upload_dir = '../uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $nom_fichier = time() . '_' . basename($_FILES['piece_jointe']['name']);
            $chemin = $upload_dir . $nom_fichier;
            if (move_uploaded_file($_FILES['piece_jointe']['tmp_name'], $chemin)) {
                $piece_jointe = 'uploads/' . $nom_fichier;
            }
        }

        $sql = "INSERT INTO demandes (objet, montant_demande, date_creation, id_demandeur, statut, piece_jointe)
                VALUES (?, ?, NOW(), ?, 'en_attente_chef', ?)";
        $stmt = $pdo->prepare($sql);
        $resultat = $stmt->execute([$objet, $montant, $_SESSION['id_utilisateur'], $piece_jointe]);

        if ($resultat) {
            $succes = 'Demande envoyee.';
        } else {
            $erreur = 'Erreur lors de l envoi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Demande</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/demandes.css">
</head>
<body>

<div class="header">
    <h2>Nouvelle demande</h2>
    <a href="../dashboard.php">Retour</a>
</div>

<div class="container">
    <div class="form-card">
        <h4>Formulaire</h4>

        <?php if ($erreur): ?>
            <div class="alert alert-danger"><?php echo $erreur; ?></div>
        <?php endif; ?>

        <?php if ($succes): ?>
            <div class="alert alert-success"><?php echo $succes; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label>Motif</label>
                <textarea name="objet" class="form-control" rows="3" required></textarea>
            </div>

            <div class="mb-3">
                <label>Montant (USD)</label>
                <input type="number" name="montant" class="form-control" step="0.01" min="0.01" required>
            </div>

            <div class="mb-3">
                <label>Piece jointe</label>
                <input type="file" name="piece_jointe" class="form-control">
            </div>

            <button type="submit" class="btn-submit">Envoyer</button>
        </form>
    </div>
</div>

</body>
</html>