<?php
// supprimer_etudiant.php - Version avec confirmation

session_start();

require_once 'config/database.php';
require_once 'includes/fonctions.php';

$pdo = getPDO();

// Vérifier l'action demandée
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'confirm':
        handleDeletionConfirmation($pdo);
        break;
    case 'execute':
        handleDeletionExecution($pdo);
        break;
    case 'cancel':
        handleDeletionCancellation();
        break;
    default:
        redirectWithError('liste_etudiants.php', 'Action non reconnue.');
}

/**
 * Gérer la confirmation de suppression
 */
function handleDeletionConfirmation($pdo) {
    $matricules = getValidMatricules();
    
    if (empty($matricules)) {
        redirectWithError('liste_etudiants.php', 'Aucun matricule valide fourni.');
    }
    
    // Récupérer les informations des étudiants
    $etudiants = getStudentsInfo($pdo, $matricules);
    
    if (empty($etudiants)) {
        redirectWithError('liste_etudiants.php', 'Aucun étudiant trouvé avec les matricules fournis.');
    }
    
    // Stocker les matricules en session pour l'étape d'exécution
    $_SESSION['pending_deletion'] = [
        'matricules' => $matricules,
        'timestamp' => time(),
        'etudiants' => $etudiants
    ];
    
    // Afficher la page de confirmation
    showConfirmationPage($etudiants);
    exit;
}

/**
 * Gérer l'exécution de la suppression
 */
function handleDeletionExecution($pdo) {
    // Vérifier la session de suppression en attente
    if (!isset($_SESSION['pending_deletion']) || 
        !isset($_SESSION['pending_deletion']['matricules']) ||
        !isset($_SESSION['pending_deletion']['timestamp'])) {
        redirectWithError('liste_etudiants.php', 'Aucune suppression en attente.');
    }
    
    // Vérifier l'expiration (5 minutes)
    $expirationTime = 300; // 5 minutes en secondes
    if (time() - $_SESSION['pending_deletion']['timestamp'] > $expirationTime) {
        unset($_SESSION['pending_deletion']);
        redirectWithError('liste_etudiants.php', 'La confirmation de suppression a expiré.');
    }
    
    // Vérifier la confirmation
    if (!isset($_POST['confirm']) || $_POST['confirm'] !== 'yes') {
        redirectWithError('liste_etudiants.php', 'Suppression non confirmée.');
    }
    
    $matricules = $_SESSION['pending_deletion']['matricules'];
    $etudiants = $_SESSION['pending_deletion']['etudiants'];
    
    // Exécuter la suppression
    $result = executeDeletion($pdo, $matricules, $etudiants);
    
    // Nettoyer la session
    unset($_SESSION['pending_deletion']);
    
    // Rediriger avec le résultat
    if ($result['success']) {
        redirectWithSuccess('liste_etudiants.php', $result['message']);
    } else {
        redirectWithError('liste_etudiants.php', $result['message']);
    }
}

/**
 * Gérer l'annulation
 */
function handleDeletionCancellation() {
    if (isset($_SESSION['pending_deletion'])) {
        unset($_SESSION['pending_deletion']);
    }
    
    redirectWithSuccess('liste_etudiants.php', 'Suppression annulée.');
}

/**
 * Exécuter la suppression effective
 */
function executeDeletion($pdo, $matricules, $etudiants) {
    try {
        $pdo->beginTransaction();
        
        $placeholders = str_repeat('?,', count($matricules) - 1) . '?';
        $sqlDelete = "DELETE FROM Etudiant WHERE matricule IN ($placeholders)";
        
        $stmtDelete = $pdo->prepare($sqlDelete);
        $stmtDelete->execute($matricules);
        
        $rowsDeleted = $stmtDelete->rowCount();
        
        $pdo->commit();
        
        // Construire le message
        if ($rowsDeleted === 1) {
            $etudiant = $etudiants[0];
            $message = "Étudiant <strong>" . htmlspecialchars($etudiant['nom']) . " " . 
                      htmlspecialchars($etudiant['prenom']) . "</strong> supprimé avec succès.";
        } else {
            $message = "<strong>$rowsDeleted étudiants</strong> supprimés avec succès.";
        }
        
        return [
            'success' => true,
            'message' => $message,
            'count' => $rowsDeleted
        ];
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        return [
            'success' => false,
            'message' => "Erreur lors de la suppression : " . $e->getMessage()
        ];
    }
}

/**
 * Afficher la page de confirmation
 */
function showConfirmationPage($etudiants) {
    $count = count($etudiants);
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Confirmation de suppression</title>
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            .confirmation-container {
                max-width: 800px;
                margin: 50px auto;
                padding: 30px;
                background: white;
                border-radius: var(--border-radius);
                box-shadow: var(--shadow-medium);
            }
            
            .danger-header {
                background: linear-gradient(135deg, #ff5252, #d32f2f);
                color: white;
                padding: 20px;
                border-radius: var(--border-radius) var(--border-radius) 0 0;
                text-align: center;
            }
            
            .student-list {
                max-height: 300px;
                overflow-y: auto;
                margin: 20px 0;
                padding: 15px;
                background: #f8f9fa;
                border-radius: var(--border-radius-sm);
                border: 1px solid var(--light-gray);
            }
            
            .student-item {
                padding: 10px 15px;
                margin-bottom: 10px;
                background: white;
                border-radius: var(--border-radius-sm);
                border-left: 4px solid var(--danger-color);
            }
            
            .warning-box {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                color: #856404;
                padding: 15px;
                border-radius: var(--border-radius-sm);
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
    <div class="container">
        <div class="confirmation-container">
            <div class="danger-header">
                <h1><i class="fas fa-exclamation-triangle"></i> Confirmation de suppression</h1>
                <p>Vous êtes sur le point de supprimer <?= $count ?> étudiant(s)</p>
            </div>
            
            <div class="warning-box">
                <h3><i class="fas fa-warning"></i> Attention !</h3>
                <p>Cette action est <strong>irréversible</strong>. Toutes les données des étudiants seront définitivement supprimées.</p>
            </div>
            
            <div class="student-list">
                <h4>Étudiants à supprimer :</h4>
                <?php foreach ($etudiants as $etudiant): ?>
                <div class="student-item">
                    <strong><?= htmlspecialchars($etudiant['nom']) ?> <?= htmlspecialchars($etudiant['prenom']) ?></strong>
                    <br>
                    <small>Matricule: <?= htmlspecialchars($etudiant['matricule']) ?> | 
                    Filière: <?= htmlspecialchars($etudiant['filiere_intitule'] ?? 'N/A') ?> | 
                    Niveau: <?= htmlspecialchars($etudiant['niveau']) ?></small>
                </div>
                <?php endforeach; ?>
            </div>
            
            <form action="supprimer_etudiant.php?action=execute" method="POST" style="text-align: center;">
                <!-- Token CSRF (optionnel) -->
                <!-- <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>"> -->
                
                <div style="margin: 25px 0;">
                    <label style="display: block; margin-bottom: 10px; font-weight: bold;">
                        <input type="checkbox" name="confirm" value="yes" required>
                        Je confirme vouloir supprimer définitivement ces étudiants
                    </label>
                    
                    <label style="display: block; margin-bottom: 15px;">
                        <input type="checkbox" name="understand" value="yes" required>
                        Je comprends que cette action est irréversible
                    </label>
                </div>
                
                <div style="display: flex; gap: 15px; justify-content: center;">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Confirmer la suppression
                    </button>
                    <a href="supprimer_etudiant.php?action=cancel" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                    <a href="liste_etudiants.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Retour à la liste
                    </a>
                </div>
            </form>
            
            <footer style="margin-top: 30px; text-align: center; color: var(--medium-gray); font-size: 0.9rem;">
                <p><i class="fas fa-info-circle"></i> Cette confirmation expirera dans 5 minutes.</p>
            </footer>
        </div>
    </div>
    </body>
    </html>
    <?php
}

/**
 * Récupérer les matricules valides
 */
function getValidMatricules() {
    $matricules = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['matricules'])) {
        $matricules = is_array($_POST['matricules']) ? $_POST['matricules'] : [$_POST['matricules']];
    } elseif (isset($_GET['matricule'])) {
        $matricules = [$_GET['matricule']];
    }
    
    // Filtrer et valider
    $matricules = array_filter(array_map('sanitize', $matricules), function($matricule) {
        return !empty($matricule) && preg_match('/^UNIV[A-Z]{3}\d{7}$/', $matricule);
    });
    
    return array_values(array_unique($matricules));
}

/**
 * Récupérer les informations des étudiants
 */
function getStudentsInfo($pdo, $matricules) {
    if (empty($matricules)) {
        return [];
    }
    
    $placeholders = str_repeat('?,', count($matricules) - 1) . '?';
    
    $sql = "SELECT E.matricule, E.nom, E.prenom, E.niveau, F.intitulé as filiere_intitule
            FROM Etudiant E
            LEFT JOIN Filière F ON E.id_filiere = F.id_filiere
            WHERE E.matricule IN ($placeholders)
            ORDER BY E.nom, E.prenom";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($matricules);
    
    return $stmt->fetchAll();
}
?>