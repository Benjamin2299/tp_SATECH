<?php
session_start();

// Inclure la configuration
require_once 'config/database.php';

// Vérifier la connexion
if (!isset($pdo)) {
    die("Erreur : Connexion à la base de données non établie");
}

// Récupérer les données pour les filtres
$filieres = $pdo->query("SELECT id_filiere, intitulé FROM `Filière` ORDER BY intitulé")->fetchAll();
$departements = $pdo->query("SELECT id_Dep, intitulé_Dep FROM `Département` ORDER BY intitulé_Dep")->fetchAll();
$nationalites = $pdo->query("SELECT code_nationalite, intitulé_Nat FROM `Nationalité` ORDER BY intitulé_Nat")->fetchAll();

$sexesDistincts = $pdo->query("SELECT DISTINCT sexe FROM Etudiant WHERE sexe IS NOT NULL ORDER BY sexe")->fetchAll();
$niveauxDistincts = $pdo->query("SELECT DISTINCT niveau FROM Etudiant WHERE niveau IS NOT NULL ORDER BY niveau")->fetchAll();

// Construction de la requête avec filtres
$sql = "SELECT E.*, 
               F.intitulé AS filiere_intitule, 
               D.intitulé_Dep AS departement_intitule, 
               N.intitulé_Nat AS nationalite_intitule
        FROM Etudiant E 
        LEFT JOIN `Filière` F ON E.id_filiere = F.id_filiere
        LEFT JOIN `Département` D ON F.id_Dep = D.id_Dep 
        LEFT JOIN `Nationalité` N ON E.code_nationalite = N.code_nationalite
        WHERE 1=1";

$params = [];

// Filtre de recherche globale
if (!empty($_GET['search'])) {
    $sql .= " AND (E.nom LIKE ? OR E.prenom LIKE ? OR E.matricule LIKE ? OR F.intitulé LIKE ? OR D.intitulé_Dep LIKE ? OR N.intitulé_Nat LIKE ?)";
    $searchTerm = '%' . $_GET['search'] . '%';
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

// Filtres par colonne
$filters = [
    'filter_matricule' => 'E.matricule LIKE ?',
    'filter_nom' => 'E.nom LIKE ?',
    'filter_prenom' => 'E.prenom LIKE ?',
    'filter_sexe' => 'E.sexe = ?',
    'filter_date_naissance' => 'DATE(E.date_de_naissance) = ?',
    'filter_niveau' => 'E.niveau = ?',
    'filter_filiere' => 'E.id_filiere = ?',
    'filter_departement' => 'D.id_Dep = ?',
    'filter_nationalite' => 'E.code_nationalite = ?'
];

foreach ($filters as $get_key => $condition) {
    if (!empty($_GET[$get_key])) {
        $sql .= " AND $condition";
        $params[] = (in_array($get_key, ['filter_matricule', 'filter_nom', 'filter_prenom'])) 
                    ? '%' . $_GET[$get_key] . '%' 
                    : $_GET[$get_key];
    }
}

// Tri
$sql .= " ORDER BY E.matricule DESC, E.nom, E.prenom";

// Exécution
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $etudiants = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erreur SQL : " . $e->getMessage());
}

// Statistiques
$totalEtudiants = $pdo->query("SELECT COUNT(*) as total FROM Etudiant")->fetch()['total'];
$etudiantsFiltres = count($etudiants);

$statsSexe = $pdo->query("SELECT sexe, COUNT(*) as count FROM Etudiant GROUP BY sexe")->fetchAll();
$statsSexeArray = [];
foreach ($statsSexe as $stat) {
    $statsSexeArray[$stat['sexe']] = $stat['count'];
}

$pageTitle = 'Liste des Étudiants - TP_SATECH';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Styles spécifiques pour la liste des étudiants */
        .table-header-with-filter {
            position: relative;
            padding-bottom: 50px !important;
        }
        
        .table-header-label {
            position: absolute;
            top: 10px;
            left: 10px;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .filter-input-small,
        .filter-select-small,
        .filter-date-small {
            position: absolute;
            bottom: 10px;
            left: 5px;
            right: 5px;
            width: calc(100% - 10px);
            padding: 8px 10px;
            font-size: 0.8rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.9);
            color: #333;
        }
        
        .filter-input-small:focus,
        .filter-select-small:focus,
        .filter-date-small:focus {
            background: white;
            border-color: #2c5282;
            outline: none;
        }
        
        .search-bar-enhanced {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
        }
        
        .search-bar-enhanced input[type="text"] {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .search-bar-enhanced input[type="text"]:focus {
            border-color: #2c5282;
            outline: none;
            box-shadow: 0 0 0 3px rgba(44, 82, 130, 0.1);
        }
        
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-item {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-top: 4px solid #2c5282;
            transition: transform 0.3s ease;
        }
        
        .stat-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2c5282;
            margin: 10px 0;
            line-height: 1;
        }
        
        .stat-label {
            color: #718096;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        .crud-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .crud-button {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .crud-button.create {
            background: linear-gradient(135deg, #38a169, #2f855a);
            color: white;
        }
        
        .crud-button.update {
            background: linear-gradient(135deg, #d69e2e, #b7791f);
            color: white;
        }
        
        .crud-button.delete {
            background: linear-gradient(135deg, #e53e3e, #c53030);
            color: white;
        }
        
        .crud-button.print {
            background: linear-gradient(135deg, #3182ce, #2c5282);
            color: white;
        }
        
        .crud-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .crud-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }
        
        .checkbox-cell {
            width: 50px;
            text-align: center;
        }
        
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
            font-style: italic;
        }
        
        .no-results i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #cbd5e0;
            display: block;
        }
        
        .badge-compact {
            padding: 4px 8px !important;
            font-size: 0.75rem !important;
            min-width: 50px !important;
        }
        
        /* Colonnes avec largeurs spécifiques */
        .col-small {
            width: 80px;
        }
        
        .col-medium {
            width: 120px;
        }
        
        .col-large {
            width: 150px;
        }
        
        /* Filtres compacts */
        .filters-compact {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
        }
        
        /* Pour l'impression */
        @media print {
            .no-print {
                display: none !important;
            }
            
            .filters-compact {
                display: none !important;
            }
            
            .crud-actions {
                display: none !important;
            }
            
            .stats-bar {
                display: none !important;
            }
        }
        
        /* Animation pour les alertes */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert {
            animation: fadeIn 0.3s ease;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="main-container">
        <header>
            <h1><i class="fas fa-users"></i> Gestion des Étudiants</h1>
            <p class="header-subtitle">Système de gestion des étudiants - TP_SATECH</p>
            <nav>
                <a href="formulaire_creation.php" class="btn btn-success btn-sm">
                    <i class="fas fa-user-plus"></i> Nouvel Étudiant
                </a>
                <a href="dashboard.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-chart-bar"></i> Tableau de Bord
                </a>
            </nav>
        </header>

        <main>
            <h2 class="section-title">
                <i class="fas fa-list"></i> Liste des Étudiants
                <span style="font-size: 1rem; color: #718096; margin-left: 10px;">
                    (<?= $etudiantsFiltres ?> sur <?= $totalEtudiants ?>)
                </span>
            </h2>
            
            <!-- Barre de statistiques -->
            <div class="stats-bar">
                <div class="stat-item">
                    <div class="stat-label">Total Étudiants</div>
                    <div class="stat-value"><?= $totalEtudiants ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Masculin</div>
                    <div class="stat-value"><?= $statsSexeArray['M'] ?? 0 ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Féminin</div>
                    <div class="stat-value"><?= $statsSexeArray['F'] ?? 0 ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Affichés</div>
                    <div class="stat-value"><?= $etudiantsFiltres ?></div>
                </div>
            </div>

            <!-- Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> 
                    <?= htmlspecialchars($_SESSION['success_message']) ?>
                    <?php unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['form_error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> 
                    <?= htmlspecialchars($_SESSION['form_error']) ?>
                    <?php unset($_SESSION['form_error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> 
                    <?= htmlspecialchars($_GET['message']) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> 
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>

            <!-- Filtres compacts -->
            <div class="filters-compact">
                <form method="GET" action="" id="filters-form" class="search-bar-enhanced">
                    <input type="text" name="search" 
                           placeholder="Rechercher un étudiant..." 
                           value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>"
                           class="no-print">
                    <button type="submit" class="btn btn-primary btn-sm no-print">
                        <i class="fas fa-search"></i> Rechercher
                    </button>
                    <button type="button" onclick="resetFilters()" class="btn btn-secondary btn-sm no-print">
                        <i class="fas fa-redo"></i> Réinitialiser
                    </button>
                </form>
            </div>

            <!-- Tableau avec filtres intégrés -->
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="col-small checkbox-cell no-print">
                                <div class="table-header-label">Sel.</div>
                                <input type="checkbox" id="select-all" class="no-print" style="margin-top: 5px;">
                            </th>
                            <th class="col-medium table-header-with-filter">
                                <div class="table-header-label">Matricule</div>
                                <input type="text" name="filter_matricule" class="filter-input-small no-print" 
                                       placeholder="Filtrer..." 
                                       value="<?= isset($_GET['filter_matricule']) ? htmlspecialchars($_GET['filter_matricule']) : '' ?>"
                                       onchange="applyFilter()">
                            </th>
                            <th class="col-medium table-header-with-filter">
                                <div class="table-header-label">Nom</div>
                                <input type="text" name="filter_nom" class="filter-input-small no-print" 
                                       placeholder="Filtrer..." 
                                       value="<?= isset($_GET['filter_nom']) ? htmlspecialchars($_GET['filter_nom']) : '' ?>"
                                       onchange="applyFilter()">
                            </th>
                            <th class="col-medium table-header-with-filter">
                                <div class="table-header-label">Prénom</div>
                                <input type="text" name="filter_prenom" class="filter-input-small no-print" 
                                       placeholder="Filtrer..." 
                                       value="<?= isset($_GET['filter_prenom']) ? htmlspecialchars($_GET['filter_prenom']) : '' ?>"
                                       onchange="applyFilter()">
                            </th>
                            <th class="col-small table-header-with-filter">
                                <div class="table-header-label">Sexe</div>
                                <select name="filter_sexe" class="filter-select-small no-print" onchange="applyFilter()">
                                    <option value="">Tous</option>
                                    <?php foreach ($sexesDistincts as $sexe): ?>
                                        <option value="<?= $sexe['sexe'] ?>" 
                                            <?= (isset($_GET['filter_sexe']) && $_GET['filter_sexe'] == $sexe['sexe']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($sexe['sexe']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </th>
                            <th class="col-medium table-header-with-filter">
                                <div class="table-header-label">Date Naiss.</div>
                                <input type="date" name="filter_date_naissance" class="filter-date-small no-print" 
                                       value="<?= isset($_GET['filter_date_naissance']) ? htmlspecialchars($_GET['filter_date_naissance']) : '' ?>"
                                       onchange="applyFilter()">
                            </th>
                            <th class="col-large table-header-with-filter">
                                <div class="table-header-label">Filière</div>
                                <select name="filter_filiere" class="filter-select-small no-print" onchange="applyFilter()">
                                    <option value="">Toutes</option>
                                    <?php foreach ($filieres as $filiere): ?>
                                        <option value="<?= $filiere['id_filiere'] ?>" 
                                            <?= (isset($_GET['filter_filiere']) && $_GET['filter_filiere'] == $filiere['id_filiere']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($filiere['intitulé']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </th>
                            <th class="col-small table-header-with-filter">
                                <div class="table-header-label">Niveau</div>
                                <select name="filter_niveau" class="filter-select-small no-print" onchange="applyFilter()">
                                    <option value="">Tous</option>
                                    <?php foreach ($niveauxDistincts as $niv): ?>
                                        <option value="<?= $niv['niveau'] ?>" 
                                            <?= (isset($_GET['filter_niveau']) && $_GET['filter_niveau'] == $niv['niveau']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($niv['niveau']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </th>
                            <th class="col-large table-header-with-filter">
                                <div class="table-header-label">Département</div>
                                <select name="filter_departement" class="filter-select-small no-print" onchange="applyFilter()">
                                    <option value="">Tous</option>
                                    <?php foreach ($departements as $dep): ?>
                                        <option value="<?= $dep['id_Dep'] ?>" 
                                            <?= (isset($_GET['filter_departement']) && $_GET['filter_departement'] == $dep['id_Dep']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($dep['intitulé_Dep']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </th>
                            <th class="col-medium table-header-with-filter">
                                <div class="table-header-label">Nationalité</div>
                                <select name="filter_nationalite" class="filter-select-small no-print" onchange="applyFilter()">
                                    <option value="">Toutes</option>
                                    <?php foreach ($nationalites as $nat): ?>
                                        <option value="<?= $nat['code_nationalite'] ?>" 
                                            <?= (isset($_GET['filter_nationalite']) && $_GET['filter_nationalite'] == $nat['code_nationalite']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($nat['intitulé_Nat']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($etudiants)): ?>
                            <tr>
                                <td colspan="10" class="no-results">
                                    <i class="fas fa-search"></i>
                                    Aucun étudiant trouvé avec les critères de recherche sélectionnés.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($etudiants as $e): ?>
                            <tr>
                                <td class="checkbox-cell no-print">
                                    <input type="checkbox" name="matricules[]" 
                                           value="<?= htmlspecialchars($e['matricule']) ?>" 
                                           class="student-checkbox no-print">
                                </td>
                                <td><strong><?= htmlspecialchars($e['matricule']) ?></strong></td>
                                <td><?= htmlspecialchars($e['nom']) ?></td>
                                <td><?= htmlspecialchars($e['prenom']) ?></td>
                                <td>
                                    <?php if ($e['sexe'] == 'M'): ?>
                                        <span class="badge badge-male badge-compact">M</span>
                                    <?php else: ?>
                                        <span class="badge badge-female badge-compact">F</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= !empty($e['date_de_naissance']) ? date('d/m/Y', strtotime($e['date_de_naissance'])) : '' ?>
                                </td>
                                <td><?= htmlspecialchars($e['filiere_intitule'] ?? 'N/A') ?></td>
                                <td>
                                    <span class="badge badge-primary badge-compact">
                                        <?= htmlspecialchars($e['niveau']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($e['departement_intitule'] ?? 'N/A') ?></td>
                                <td>
                                    <span class="badge badge-info badge-compact">
                                        <?= htmlspecialchars($e['nationalite_intitule'] ?? 'N/A') ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Actions CRUD -->
            <div class="crud-actions no-print">
                <a href="formulaire_creation.php" class="crud-button create">
                    <i class="fas fa-user-plus"></i> Ajouter un étudiant
                </a>
                <button id="update-selected" class="crud-button update" disabled>
                    <i class="fas fa-edit"></i> Modifier la sélection
                </button>
                <button id="delete-selected" class="crud-button delete" disabled>
                    <i class="fas fa-trash"></i> Supprimer la sélection
                </button>
                <button onclick="window.print()" class="crud-button print">
                    <i class="fas fa-print"></i> Imprimer la liste
                </button>
            </div>
        </main>

        <footer>
            <p>&copy; <?= date('Y') ?> - Gestion des Étudiants | TP_SATECH - Système de gestion des étudiants</p>
        </footer>
    </div>
</div>

<script>
// Gestion de la sélection
const selectAll = document.getElementById('select-all');
const studentCheckboxes = document.querySelectorAll('.student-checkbox');
const updateBtn = document.getElementById('update-selected');
const deleteBtn = document.getElementById('delete-selected');

function checkSelection() {
    const checkedCount = document.querySelectorAll('.student-checkbox:checked').length;
    updateBtn.disabled = checkedCount !== 1;
    deleteBtn.disabled = checkedCount === 0;
}

// Sélectionner/désélectionner tout
if (selectAll) {
    selectAll.addEventListener('change', function() {
        studentCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        checkSelection();
    });
}

// Vérifier la sélection individuelle
studentCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', checkSelection);
});

// Appliquer les filtres avec délai
let filterTimeout;
function applyFilter() {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        document.getElementById('filters-form').submit();
    }, 300);
}

// Réinitialiser les filtres
function resetFilters() {
    window.location.href = 'liste_etudiants.php';
}

// Modifier la sélection
updateBtn.onclick = function() {
    const checkedBox = document.querySelector('.student-checkbox:checked');
    if (checkedBox) {
        window.location.href = 'formulaire_modification.php?matricule=' + encodeURIComponent(checkedBox.value);
    }
};

// Supprimer la sélection
deleteBtn.onclick = function() {
    const checkedBoxes = document.querySelectorAll('.student-checkbox:checked');
    if (checkedBoxes.length === 0) return;
    
    const message = checkedBoxes.length === 1 
        ? "Voulez-vous vraiment supprimer cet étudiant ? Cette action est irréversible."
        : `Voulez-vous vraiment supprimer les ${checkedBoxes.length} étudiants sélectionnés ? Cette action est irréversible.`;
    
    if (confirm(message)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'supprimer_etudiant.php';
        
        checkedBoxes.forEach(checkbox => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'matricules[]';
            input.value = checkbox.value;
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
    }
};

// Gestion des filtres automatiques
document.querySelectorAll('.filter-input-small, .filter-select-small, .filter-date-small').forEach(input => {
    input.addEventListener('change', applyFilter);
});

// Initialiser la vérification de sélection au chargement
document.addEventListener('DOMContentLoaded', checkSelection);

// Auto-hide les alertes après 5 secondes
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 500);
        }, 5000);
    });
});

// Gestion de l'impression
function printTable() {
    window.print();
}
</script>
</body>
</html>