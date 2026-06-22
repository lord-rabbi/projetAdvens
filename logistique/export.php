<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['id_utilisateur']) || $_SESSION['id_role'] != 3) {
    header('Location: ../login.php');
    exit();
}

$format = $_GET['format'] ?? 'pdf';

$filtre_date_debut = $_GET['filtre_date_debut'] ?? '';
$filtre_date_fin = $_GET['filtre_date_fin'] ?? '';
$filtre_demandeur = $_GET['filtre_demandeur'] ?? '';
$filtre_departement = $_GET['filtre_departement'] ?? '';
$filtre_montant_min = $_GET['filtre_montant_min'] ?? '';
$filtre_montant_max = $_GET['filtre_montant_max'] ?? '';

$where = " WHERE d.statut = 'confirmee'";
$params = array();

if (!empty($filtre_date_debut)) {
    $where .= " AND DATE(d.date_decaissement) >= ?";
    $params[] = $filtre_date_debut;
}
if (!empty($filtre_date_fin)) {
    $where .= " AND DATE(d.date_decaissement) <= ?";
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

$sql = "
    SELECT d.id_demande, d.objet, d.montant_demande, d.devise,
           d.date_creation, d.date_facture, d.date_decaissement,
           u.nom, u.prenom, dep.departement
    FROM demandes d
    JOIN utilisateurs u ON d.id_demandeur = u.id_utilisateur
    LEFT JOIN departements dep ON u.id_departement = dep.id_departement
    " . $where . "
    ORDER BY d.date_decaissement DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$demandes = $stmt->fetchAll();

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

if ($format == 'pdf') {
    $total_montant = 0;
    foreach ($demandes as $d) {
        $total_montant += $d['montant_demande'];
    }

    $html = '
    <html>
    <head>
        <meta charset="utf-8">
        <title>Rapport des decaissements</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                font-size: 10px;
                padding: 15px;
                margin: 0;
            }
            .header {
                text-align: center;
                border-bottom: 2px solid #008C45;
                padding-bottom: 15px;
                margin-bottom: 15px;
            }
            .header h1 {
                color: #008C45;
                font-size: 18px;
                margin: 0;
            }
            .header-info {
                text-align: center;
                margin-bottom: 15px;
                font-size: 11px;
            }
            .header-info p {
                margin: 3px 0;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                font-size: 9px;
                table-layout: fixed;
            }
            th {
                background-color: #008C45;
                color: white;
                padding: 5px 4px;
                text-align: left;
                border: 1px solid #000;
                font-weight: bold;
                word-wrap: break-word;
            }
            td {
                padding: 5px 4px;
                border: 1px solid #000;
                vertical-align: top;
                word-wrap: break-word;
                overflow-wrap: break-word;
                white-space: normal;
            }
            .col-id { width: 5%; }
            .col-demandeur { width: 12%; }
            .col-departement { width: 10%; }
            .col-objet { width: 25%; }
            .col-montant { width: 8%; text-align: right; }
            .col-devise { width: 5%; text-align: center; }
            .col-date-creation { width: 10%; }
            .col-date-facture { width: 10%; }
            .col-date-decaissement { width: 10%; }
            .total {
                font-weight: bold;
                font-size: 13px;
                margin-top: 15px;
                text-align: right;
            }
            .total span {
                color: #008C45;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>RAPPORT DES DECAISSEMENTS</h1>
        </div>

        <div class="header-info">
            <p><strong>Date du rapport :</strong> ' . date('d/m/Y H:i') . '</p>
            <p><strong>Nombre de decaissements :</strong> ' . count($demandes) . '</p>
            <p><strong>Total decaisse :</strong> ' . number_format($total_montant, 2) . ' USD</p>
            <p><strong>Filtres appliques :</strong> 
                ' . (!empty($filtre_date_debut) ? 'Date debut : ' . $filtre_date_debut . ' | ' : '') . '
                ' . (!empty($filtre_date_fin) ? 'Date fin : ' . $filtre_date_fin . ' | ' : '') . '
                ' . (!empty($filtre_demandeur) ? 'Demandeur : ' . $filtre_demandeur . ' | ' : '') . '
                ' . (!empty($filtre_departement) ? 'Departement : ' . $filtre_departement : '') . '
            </p>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="col-id">ID</th>
                    <th class="col-demandeur">Demandeur</th>
                    <th class="col-departement">Departement</th>
                    <th class="col-objet">Objet</th>
                    <th class="col-montant">Montant</th>
                    <th class="col-devise">Devise</th>
                    <th class="col-date-creation">Date creation</th>
                    <th class="col-date-facture">Date facture</th>
                    <th class="col-date-decaissement">Date decaissement</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($demandes as $demande) {
        $objet = htmlspecialchars($demande['objet']);
        $html .= '
                <tr>
                    <td class="col-id">' . $demande['id_demande'] . '</td>
                    <td class="col-demandeur">' . htmlspecialchars($demande['prenom'] . ' ' . $demande['nom']) . '</td>
                    <td class="col-departement">' . ($demande['departement'] ?? '-') . '</td>
                    <td class="col-objet">' . $objet . '</td>
                    <td class="col-montant">' . number_format($demande['montant_demande'], 2) . '</td>
                    <td class="col-devise">' . ($demande['devise'] ?? 'USD') . '</td>
                    <td class="col-date-creation">' . $demande['date_creation'] . '</td>
                    <td class="col-date-facture">' . ($demande['date_facture'] ?? '') . '</td>
                    <td class="col-date-decaissement">' . $demande['date_decaissement'] . '</td>
                </tr>';
    }

    $html .= '
            </tbody>
        </table>

        <div class="total">
            <p><strong>Total decaisse :</strong> <span>' . number_format($total_montant, 2) . ' USD</span></p>
        </div>
    </body>
    </html>';

    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isRemoteEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->loadHtml($html);
    $dompdf->render();

    $dompdf->stream('Rapport_decaissements_' . date('Y-m-d') . '.pdf', array('Attachment' => true));
    exit();
}
?>