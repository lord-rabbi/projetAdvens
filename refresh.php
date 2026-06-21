<?php
require_once 'config/session.php';
require_once 'config/database.php';

if (!isset($_SESSION['id_utilisateur'])) {
    exit();
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

function renderStatut($statut) {
    $libelle = getLibelleStatut($statut);
    $badge = getBadgeClass($statut);
    return '<span class="badge ' . $badge . '">' . $libelle . '</span>';
}

function renderRenvoyee($renvoyee) {
    if ($renvoyee == 1) {
        return '<span class="badge bg-warning text-dark">Oui</span>';
    } else {
        return '<span class="badge bg-secondary">Non</span>';
    }
}

function renderActionsDemandeur($statut, $id, $page) {
    $html = '';
    if ($statut == 'rejetee') {
        $html .= '<a href="dashboard.php?page=modifier&modifier=' . $id . '" class="btn-warning-sm">Modifier</a>';
    }
    if ($statut == 'pending') {
        $html .= '<a href="dashboard.php?annuler=' . $id . '&page=mes_demandes&p=' . $page . '" class="btn-danger-sm" onclick="return confirm(\'Annuler ?\')">Annuler</a>';
    }
    return $html;
}

function renderActionsChef($statut, $id) {
    $html = '';
    if ($statut == 'pending') {
        $html .= '<form method="POST" style="display:inline-block;">
                    <input type="hidden" name="action" value="valider">
                    <input type="hidden" name="id_demande" value="' . $id . '">
                    <button type="submit" class="btn-success-sm">Valider</button>
                  </form>
                  <button type="button" class="btn-danger-sm" data-bs-toggle="modal" data-bs-target="#rejetModal" data-id="' . $id . '">Rejeter</button>';
    } elseif ($statut == 'rejetee') {
        $html .= '<form method="POST" style="display:inline-block;">
                    <input type="hidden" name="action" value="reactiver">
                    <input type="hidden" name="id_demande" value="' . $id . '">
                    <button type="submit" class="btn-warning-sm">Revenir sur rejet</button>
                  </form>';
    }
    return $html;
}

function renderActionsLogistique($statut, $id) {
    $html = '';
    if ($statut == 'pendinglogistique') {
        $html .= '<form method="POST" style="display:inline-block;">
                    <input type="hidden" name="action" value="facturer">
                    <input type="hidden" name="id_demande" value="' . $id . '">
                    <button type="submit" class="btn-facturer">Facturer</button>
                  </form>';
    } elseif ($statut == 'facturee') {
        $html .= '<form method="POST" style="display:inline-block;">
                    <input type="hidden" name="action" value="decaisser">
                    <input type="hidden" name="id_demande" value="' . $id . '">
                    <button type="submit" class="btn-decaisser">Decaisser</button>
                  </form>';
    }
    return $html;
}

$id_role = $_SESSION['id_role'];
$action = $_GET['action'] ?? '';
$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

$actions_autorisees = ['mes_demandes', 'demandes', 'historique', 'historique_chef'];
if (!in_array($action, $actions_autorisees)) {
    exit();
}

$response = array();

try {
    if ($id_role == 4 && $action == 'mes_demandes') {
        $id_user = $_SESSION['id_utilisateur'];
        
        $sql = "SELECT * FROM demandes WHERE id_demandeur = ? ORDER BY date_creation DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_user]);
        $demandes = $stmt->fetchAll();
        
        foreach ($demandes as $demande) {
            $id = $demande['id_demande'];
            $statut = $demande['statut'];
            $libelle = getLibelleStatut($statut);
            $response[] = array(
                'id' => $id,
                'objet' => htmlspecialchars($demande['objet']),
                'montant' => number_format($demande['montant_demande'], 2),
                'devise' => $demande['devise'] ?? 'USD',
                'demandeur' => $_SESSION['prenom'] . ' ' . $_SESSION['nom'],
                'date' => $demande['date_creation'],
                'libelle' => $libelle,
                'renvoyee' => $demande['renvoyee'] == 1 ? 'Oui' : 'Non',
                'renvoyee_badge' => renderRenvoyee($demande['renvoyee']),
                'justification' => htmlspecialchars($demande['justification_rejet'] ?? ''),
                'piece' => $demande['piece_jointe'] ?? '',
                'statut_html' => renderStatut($statut),
                'actions_html' => renderActionsDemandeur($statut, $id, $page)
            );
        }
        
    } elseif ($id_role == 2 && $action == 'demandes') {
        $id_departement = $_SESSION['id_departement'];
        
        $sql = "
            SELECT d.*, u.nom, u.prenom 
            FROM demandes d
            JOIN utilisateurs u ON d.id_demandeur = u.id_utilisateur
            WHERE u.id_departement = ? AND (d.statut = 'pending' OR d.statut = 'rejetee')
            ORDER BY d.date_creation DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_departement]);
        $demandes = $stmt->fetchAll();
        
        foreach ($demandes as $demande) {
            $id = $demande['id_demande'];
            $statut = $demande['statut'];
            $libelle = getLibelleStatut($statut);
            $response[] = array(
                'id' => $id,
                'demandeur' => htmlspecialchars($demande['prenom'] . ' ' . $demande['nom']),
                'objet' => htmlspecialchars($demande['objet']),
                'montant' => number_format($demande['montant_demande'], 2),
                'devise' => $demande['devise'] ?? 'USD',
                'date' => $demande['date_creation'],
                'libelle' => $libelle,
                'renvoyee' => $demande['renvoyee'] == 1 ? 'Oui' : 'Non',
                'renvoyee_badge' => renderRenvoyee($demande['renvoyee']),
                'justification' => htmlspecialchars($demande['justification_rejet'] ?? ''),
                'piece' => $demande['piece_jointe'] ?? '',
                'statut_html' => renderStatut($statut),
                'actions_html' => renderActionsChef($statut, $id)
            );
        }
        
    } elseif ($id_role == 2 && $action == 'historique_chef') {
        $id_departement = $_SESSION['id_departement'];
        
        $sql = "
            SELECT d.id_demande, d.statut
            FROM demandes d
            JOIN utilisateurs u ON d.id_demandeur = u.id_utilisateur
            WHERE u.id_departement = ?
            ORDER BY d.date_creation DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_departement]);
        $demandes = $stmt->fetchAll();
        
        foreach ($demandes as $demande) {
            $id = $demande['id_demande'];
            $statut = $demande['statut'];
            $response[] = array(
                'id' => $id,
                'statut_html' => renderStatut($statut)
            );
        }
        
    } elseif ($id_role == 3 && $action == 'demandes') {
        $sql = "
            SELECT d.*, u.nom, u.prenom, dep.departement
            FROM demandes d
            JOIN utilisateurs u ON d.id_demandeur = u.id_utilisateur
            LEFT JOIN departements dep ON u.id_departement = dep.id_departement
            WHERE d.statut IN ('pendinglogistique', 'facturee')
            ORDER BY d.date_creation DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $demandes = $stmt->fetchAll();
        
        foreach ($demandes as $demande) {
            $id = $demande['id_demande'];
            $statut = $demande['statut'];
            $libelle = getLibelleStatut($statut);
            $response[] = array(
                'id' => $id,
                'demandeur' => htmlspecialchars($demande['prenom'] . ' ' . $demande['nom']),
                'departement' => $demande['departement'] ?? '-',
                'objet' => htmlspecialchars($demande['objet']),
                'montant' => number_format($demande['montant_demande'], 2),
                'devise' => $demande['devise'] ?? 'USD',
                'date' => $demande['date_creation'],
                'libelle' => $libelle,
                'piece' => $demande['piece_jointe'] ?? '',
                'statut_html' => renderStatut($statut),
                'actions_html' => renderActionsLogistique($statut, $id)
            );
        }
        
    } elseif ($id_role == 3 && $action == 'historique') {
        $sql = "
            SELECT d.id_demande, d.statut
            FROM demandes d
            JOIN utilisateurs u ON d.id_demandeur = u.id_utilisateur
            LEFT JOIN departements dep ON u.id_departement = dep.id_departement
            WHERE d.statut = 'confirmee'
            ORDER BY d.date_decaissement DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $demandes = $stmt->fetchAll();
        
        foreach ($demandes as $demande) {
            $id = $demande['id_demande'];
            $statut = $demande['statut'];
            $response[] = array(
                'id' => $id,
                'statut_html' => renderStatut($statut)
            );
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (PDOException $e) {
    echo json_encode(array('error' => 'Erreur de chargement des donnees'));
}
?>