<?php
require_once '../config/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['id_utilisateur']) || $_SESSION['id_role'] != 3) {
    header('Location: ../login.php');
    exit();
}

$id_demande = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT d.*, u.nom, u.prenom, dep.departement
    FROM demandes d
    JOIN utilisateurs u ON d.id_demandeur = u.id_utilisateur
    LEFT JOIN departements dep ON u.id_departement = dep.id_departement
    WHERE d.id_demande = ?
");
$stmt->execute([$id_demande]);
$demande = $stmt->fetch();

if (!$demande) {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirmer_decaissement') {
    $id_demande = $_POST['id_demande'] ?? 0;
    
    $stmt = $pdo->prepare("UPDATE demandes SET statut = 'confirmee', date_decaissement = NOW() WHERE id_demande = ? AND statut = 'facturee'");
    if ($stmt->execute([$id_demande])) {
        $sql_log = "INSERT INTO logs (date_action, id_utilisateur, action, statut, id_demande) 
                    VALUES (NOW(), ?, 'decaissement', 'confirmee', ?)";
        $stmt_log = $pdo->prepare($sql_log);
        $stmt_log->execute([$_SESSION['id_utilisateur'], $id_demande]);
        
        header('Location: dashboard.php');
        exit();
    }
}

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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture</title>
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/facture.css">
</head>
<body>

<div class="facture-container">
    <div class="facture-header">
        <div>
            <h1>FACTURE</h1>
            <p>N° <?php echo str_pad($demande['id_demande'], 6, '0', STR_PAD_LEFT); ?></p>
        </div>
        <div>
            <img src="../assets/Advans_Congo_Logo.svg" alt="Advans">
        </div>
    </div>

    <div class="facture-info">
        <div class="facture-info-item">
            <strong>Demandeur</strong>
            <span><?php echo htmlspecialchars($demande['prenom'] . ' ' . $demande['nom']); ?></span>
        </div>
        <div class="facture-info-item">
            <strong>Departement</strong>
            <span><?php echo $demande['departement'] ?? '-'; ?></span>
        </div>
        <div class="facture-info-item">
            <strong>Date de la demande</strong>
            <span><?php echo $demande['date_creation']; ?></span>
        </div>
        <div class="facture-info-item">
            <strong>Statut</strong>
            <span><?php echo getLibelleStatut($demande['statut']); ?></span>
        </div>
        <div class="facture-info-item">
            <strong>Date facture</strong>
            <span><?php echo $demande['date_facture'] ?? 'Non facturee'; ?></span>
        </div>
    </div>

    <table class="facture-table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Montant</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php echo htmlspecialchars($demande['objet']); ?></td>
                <td class="text-right"><?php echo number_format($demande['montant_demande'], 2); ?> <?php echo $demande['devise'] ?? 'USD'; ?></td>
            </tr>
        </tbody>
    </table>

    <div class="facture-total">
        Total : <?php echo number_format($demande['montant_demande'], 2); ?> <?php echo $demande['devise'] ?? 'USD'; ?>
    </div>

    <?php if ($demande['piece_jointe']): ?>
    <div class="piece-jointe">
        <strong>Piece jointe :</strong>
        <a href="../<?php echo $demande['piece_jointe']; ?>" target="_blank">Telecharger</a>
    </div>
    <?php endif; ?>

    <div class="actions no-print">
        <button onclick="window.print()" class="btn-imprimer">Imprimer la facture</button>
        <a href="dashboard.php" class="btn-annuler">Annuler</a>
    </div>

    <div class="actions no-print">
        <form method="POST" style="display:inline;">
            <input type="hidden" name="action" value="confirmer_decaissement">
            <input type="hidden" name="id_demande" value="<?php echo $demande['id_demande']; ?>">
            <button type="submit" class="btn-confirmer" onclick="return confirm('Confirmer le decaissement de cette demande ?')">
                Confirmer le decaissement
            </button>
        </form>
    </div>
</div>

</body>
</html>