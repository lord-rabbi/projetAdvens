<?php
require_once 'config/session.php';
require_once 'config/database.php';

if (!isset($_SESSION['id_utilisateur'])) {
    exit();
}

$id_role = $_SESSION['id_role'];
$action = $_GET['action'] ?? '';

if ($id_role == 4 && $action == 'mes_demandes') {
    $id_user = $_SESSION['id_utilisateur'];
    
    $stmt = $pdo->prepare("SELECT * FROM demandes WHERE id_demandeur = ? ORDER BY date_creation DESC");
    $stmt->execute([$id_user]);
    $demandes = $stmt->fetchAll();
    
    foreach ($demandes as $demande):
        $statut = $demande['statut'];
        $badge = '';
        if ($statut == 'pending') $badge = 'badge-attente';
        elseif ($statut == 'pendinglogistique') $badge = 'badge-logistique';
        elseif ($statut == 'facturee') $badge = 'badge-facturee';
        elseif ($statut == 'confirmee') $badge = 'badge-succes';
        elseif ($statut == 'rejetee') $badge = 'badge-rejet';
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
                <i class="fas fa-eye"></i>
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
            <?php if ($statut == 'rejetee' && !empty($demande['justification_rejet'])): ?>
                <?php echo htmlspecialchars(substr($demande['justification_rejet'], 0, 50)); ?>...
            <?php elseif ($statut == 'rejetee'): ?>
                <span class="text-danger">Rejetee</span>
            <?php else: ?>
                -
            <?php endif; ?>
        </td>
        <td>
            <?php if ($statut == 'rejetee'): ?>
                <a href="dashboard.php?page=modifier&modifier=<?php echo $demande['id_demande']; ?>" class="btn-warning-sm">Modifier</a>
            <?php endif; ?>
            <?php if ($statut == 'pending'): ?>
                <a href="dashboard.php?annuler=<?php echo $demande['id_demande']; ?>&page=mes_demandes" class="btn-danger-sm" onclick="return confirm('Annuler ?')">Annuler</a>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach;
    
} elseif ($id_role == 2 && $action == 'demandes') {
    $id_departement = $_SESSION['id_departement'];
    
    $stmt = $pdo->prepare("
        SELECT d.*, u.nom, u.prenom 
        FROM demandes d
        JOIN utilisateurs u ON d.id_demandeur = u.id_utilisateur
        WHERE u.id_departement = ? 
        ORDER BY d.date_creation DESC
    ");
    $stmt->execute([$id_departement]);
    $demandes = $stmt->fetchAll();
    
    foreach ($demandes as $demande):
        $statut = $demande['statut'];
        $badge = '';
        if ($statut == 'pending') $badge = 'badge-attente';
        elseif ($statut == 'pendinglogistique') $badge = 'badge-logistique';
        elseif ($statut == 'facturee') $badge = 'badge-facturee';
        elseif ($statut == 'confirmee') $badge = 'badge-succes';
        elseif ($statut == 'rejetee') $badge = 'badge-rejet';
        elseif ($statut == 'annulee') $badge = 'badge-annule';
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
                data-statut="<?php echo str_replace('_', ' ', $statut); ?>"
                data-renvoyee="<?php echo $demande['renvoyee'] == 1 ? 'Oui' : 'Non'; ?>"
                data-justification="<?php echo htmlspecialchars($demande['justification_rejet'] ?? ''); ?>"
                data-piece="<?php echo $demande['piece_jointe'] ?? ''; ?>">
                <i class="fas fa-eye"></i>
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
                    <button type="submit" class="btn-warning-sm">Revenir</button>
                </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach;
    
} elseif ($id_role == 3 && $action == 'demandes') {
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
    
    foreach ($demandes as $demande):
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
                <i class="fas fa-eye"></i>
            </button>
        </td>
        <td><?php echo number_format($demande['montant_demande'], 2); ?> <?php echo $demande['devise'] ?? 'USD'; ?></td>
        <td><span class="badge <?php echo $badge; ?>"><?php echo str_replace('_', ' ', $statut); ?></span></td>
        <td>
            <?php if ($demande['piece_jointe']): ?>
                <a href="../<?php echo $demande['piece_jointe']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">Voir</a>
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
                    <button type="submit" class="btn-facturer">Facturer</button>
                </form>
            <?php elseif ($statut == 'facturee'): ?>
                <form method="POST" style="display:inline-block;">
                    <input type="hidden" name="action" value="decaisser">
                    <input type="hidden" name="id_demande" value="<?php echo $demande['id_demande']; ?>">
                    <button type="submit" class="btn-decaisser">Decaisser</button>
                </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach;
    
} elseif ($id_role == 1 && $action == 'utilisateurs') {
    
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
            <button type="button" class="btn-modifier" data-bs-toggle="modal" data-bs-target="#modifierModal" 
                data-id="<?php echo $row['id_utilisateur']; ?>"
                data-nom="<?php echo htmlspecialchars($row['nom']); ?>"
                data-prenom="<?php echo htmlspecialchars($row['prenom']); ?>"
                data-email="<?php echo htmlspecialchars($row['email']); ?>"
                data-role="<?php echo $row['id_role']; ?>"
                data-departement="<?php echo $row['id_departement']; ?>">
                Modifier
            </button>
            <?php if ($row['id_role'] != 1): ?>
                <a href="supprimer.php?id=<?php echo $row['id_utilisateur']; ?>" class="btn-supprimer" onclick="return confirm('Supprimer ?')">Supprimer</a>
            <?php else: ?>
                <span class="btn-supprimer-disabled">Supprimer</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile;
    
} elseif ($id_role == 1 && $action == 'demandes') {
    
    $stmt = $pdo->query("
        SELECT d.*, CONCAT(u.nom, ' ', u.prenom) as demandeur, dep.departement
        FROM demandes d
        JOIN utilisateurs u ON d.id_demandeur = u.id_utilisateur
        LEFT JOIN departements dep ON u.id_departement = dep.id_departement
        ORDER BY d.id_demande DESC
    ");
    while ($row = $stmt->fetch()):
        $statut = $row['statut'];
        $badge = '';
        if ($statut == 'pending') $badge = 'badge-attente';
        elseif ($statut == 'pendinglogistique') $badge = 'badge-logistique';
        elseif ($statut == 'facturee') $badge = 'badge-facturee';
        elseif ($statut == 'confirmee') $badge = 'badge-succes';
        elseif ($statut == 'rejetee') $badge = 'badge-rejet';
        elseif ($statut == 'annulee') $badge = 'badge-annule';
    ?>
    <tr>
        <td><?php echo $row['id_demande']; ?></td>
        <td><?php echo htmlspecialchars($row['objet']); ?></td>
        <td><?php echo number_format($row['montant_demande'], 2); ?> <?php echo $row['devise'] ?? 'USD'; ?></td>
        <td><?php echo htmlspecialchars($row['demandeur']); ?></td>
        <td><?php echo $row['departement'] ?? '-'; ?></td>
        <td><span class="badge <?php echo $badge; ?>"><?php echo str_replace('_', ' ', $statut); ?></span></td>
        <td>
            <?php if ($row['renvoyee'] == 1): ?>
                <span class="badge bg-warning text-dark">Oui</span>
            <?php else: ?>
                <span class="badge bg-secondary">Non</span>
            <?php endif; ?>
        </td>
        <td>
            <?php if ($row['piece_jointe']): ?>
                <a href="../<?php echo $row['piece_jointe']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">Voir</a>
            <?php else: ?>
                <span class="text-muted">Aucune</span>
            <?php endif; ?>
        </td>
        <td><?php echo $row['date_creation']; ?></td>
        <td>
            <button type="button" class="btn-detail" data-bs-toggle="modal" data-bs-target="#detailModal" 
                data-id="<?php echo $row['id_demande']; ?>"
                data-objet="<?php echo htmlspecialchars($row['objet']); ?>"
                data-montant="<?php echo number_format($row['montant_demande'], 2); ?>"
                data-devise="<?php echo $row['devise'] ?? 'USD'; ?>"
                data-demandeur="<?php echo htmlspecialchars($row['demandeur']); ?>"
                data-departement="<?php echo $row['departement'] ?? '-'; ?>"
                data-statut="<?php echo str_replace('_', ' ', $statut); ?>"
                data-renvoyee="<?php echo $row['renvoyee'] == 1 ? 'Oui' : 'Non'; ?>"
                data-justification="<?php echo htmlspecialchars($row['justification_rejet'] ?? ''); ?>"
                data-date="<?php echo $row['date_creation']; ?>"
                data-piece="<?php echo $row['piece_jointe'] ?? ''; ?>">
                Voir
            </button>
        </td>
    </tr>
    <?php endwhile;
}
?>