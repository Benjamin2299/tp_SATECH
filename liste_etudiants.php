<?php
session_start();

$host = 'localhost'; 
$db = 'Gestion_Etudiant'; 
$user = 'root'; 
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) { 
    die("Erreur de connexion : " . $e->getMessage()); 
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Étudiants - Liste</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Styles spécifiques pour cette page */
        .table-header-with-filter {
            position: relative;
            padding-bottom: 40px !important; /* Espace pour les filtres */
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
            bottom: 5px;
            left: 5px;
            right: 5px;
            width: calc(100% - 10px) !important;
            padding: 6px 8px !important;
            font-size: 0.75rem !important;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.9);
            color: #333;
        }
        
        .filter-input-small:focus,
        .filter-select-small:focus,
        .filter-date-small:focus {
            background: white;
            border-color: var(--system-color);
            outline: none;
        }
        
        .checkbox-cell {
            width: 40px;
            text-align: center;
        }
        
        .no-results {
            text-align: center;
            padding: 40px;
            color: var(--medium-gray);
            font-style: italic;
        }
        
        .crud-actions {
            display: flex;
            gap: 10px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .crud-button {
            padding: 10px 20px;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .crud-button.create {
            background: linear-gradient(135deg, var(--success-color), #17b894);
            color: white;
        }
        
        .crud-button.update {
            background: linear-gradient(135deg, var(--warning-color), #f9ca24);
            color: var(--dark-color);
        }
        
        .crud-button.delete {
            background: linear-gradient(135deg, var(--danger-color), var(--secondary-dark));
            color: white;
        }
        
        .crud-button.print {
            background: linear-gradient(135deg, var(--info-color), #2e86de);
            color: white;
        }
        
        .crud-button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .crud-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-item {
            background: white;
            padding: 15px;
            border-radius: var(--border-radius);
            text-align: center;
            box-shadow: var(--shadow-light);
            border-top: 4px solid var(--system-color);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-dark);
            margin: 5px 0;
        }
        
        .stat-label {
            color: var(--dark-gray);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Styles pour les filtres compacts */
        .filters-compact {
            background: var(--light-color);
            padding: 12px;
            border-radius: var(--border-radius-sm);
            margin-bottom: 15px;
        }
        
        /* Améliorations pour le tableau compact */
        .table-compact th {
            height: 60px; /* Hauteur fixe pour l'en-tête avec filtre */
            vertical-align: bottom;
        }
        
        /* Suppression de la ligne de filtres séparée */
        .filter-row {
            display: none;
        }
        
        /* Espacement réduit */
        .table-compact td {
            padding: 6px 8px !important;
            font-size: 0.85rem;
        }
        
        /* Badges compacts */
        .badge-compact {
            padding: 3px 6px !important;
            font-size: 0.7rem !important;
            min-width: 50px !important;
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
                <button id="open-create-modal" class="btn btn-success btn-sm">
                    <i class="fas fa-user-plus"></i> Nouvel Étudiant
                </button>
                <a href="dashboard.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-chart-bar"></i> Tableau de Bord
                </a>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="logout.php" class="btn btn-danger btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                <?php endif; ?>
            </nav>
        </header>

        <main>
            <h2 class="section-title">
                <i class="fas fa-list"></i> Liste des Étudiants
                <span style="font-size: 1rem; color: var(--medium-gray); margin-left: 10px;">
                    (<?= $etudiantsFiltres ?> sur <?= $totalEtudiants ?>)
                </span>
            </h2>
            
            <!-- Barre de statistiques -->
            <div class="stats-bar">
                <div class="stat-item">
                    <div class="stat-label">Total</div>
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
                <form method="GET" action="" id="filters-form">
                    <div class="search-bar" style="display: flex; gap: 10px;">
                        <input type="text" name="search" 
                               placeholder="Rechercher un étudiant..." 
                               value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>"
                               style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search"></i> Rechercher
                        </button>
                        <button type="button" onclick="resetFilters()" class="btn btn-secondary btn-sm">
                            <i class="fas fa-redo"></i> Réinitialiser
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tableau compact -->
            <div class="table-wrapper">
                <div class="table-responsive">
                    <table class="table table-compact">
                        <thead>
                            <tr>
                                <th class="col-small checkbox-cell">
                                    <div class="table-header-label">Sélection</div>
                                    <input type="checkbox" id="select-all" class="no-print" style="position: absolute; bottom: 5px; left: 50%; transform: translateX(-50%);">
                                </th>
                                <th class="col-medium table-header-with-filter">
                                    <div class="table-header-label">Matricule</div>
                                    <input type="text" name="filter_matricule" class="filter-input-small" 
                                           placeholder="Filtrer..." 
                                           value="<?= isset($_GET['filter_matricule']) ? htmlspecialchars($_GET['filter_matricule']) : '' ?>"
                                           onchange="document.getElementById('filters-form').submit()">
                                </th>
                                <th class="col-medium table-header-with-filter">
                                    <div class="table-header-label">Nom</div>
                                    <input type="text" name="filter_nom" class="filter-input-small" 
                                           placeholder="Filtrer..." 
                                           value="<?= isset($_GET['filter_nom']) ? htmlspecialchars($_GET['filter_nom']) : '' ?>"
                                           onchange="document.getElementById('filters-form').submit()">
                                </th>
                                <th class="col-medium table-header-with-filter">
                                    <div class="table-header-label">Prénom</div>
                                    <input type="text" name="filter_prenom" class="filter-input-small" 
                                           placeholder="Filtrer..." 
                                           value="<?= isset($_GET['filter_prenom']) ? htmlspecialchars($_GET['filter_prenom']) : '' ?>"
                                           onchange="document.getElementById('filters-form').submit()">
                                </th>
                                <th class="col-small table-header-with-filter">
                                    <div class="table-header-label">Sexe</div>
                                    <select name="filter_sexe" class="filter-select-small" onchange="document.getElementById('filters-form').submit()">
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
                                    <input type="date" name="filter_date_naissance" class="filter-date-small" 
                                           value="<?= isset($_GET['filter_date_naissance']) ? htmlspecialchars($_GET['filter_date_naissance']) : '' ?>"
                                           onchange="document.getElementById('filters-form').submit()">
                                </th>
                                <th class="col-large table-header-with-filter">
                                    <div class="table-header-label">Filière</div>
                                    <select name="filter_filiere" class="filter-select-small" onchange="document.getElementById('filters-form').submit()">
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
                                    <select name="filter_niveau" class="filter-select-small" onchange="document.getElementById('filters-form').submit()">
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
                                    <select name="filter_departement" class="filter-select-small" onchange="document.getElementById('filters-form').submit()">
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
                                    <select name="filter_nationalite" class="filter-select-small" onchange="document.getElementById('filters-form').submit()">
                                        <option value="">Toutes</option>
                                        <?php foreach ($nationalites as $nat): ?>
                                            <option value="<?= $nat['code_nationalite'] ?>" 
                                                <?= (isset($_GET['filter_nationalite']) && $_GET['filter_nationalite'] == $nat['code_nationalite']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($nat['intitulé_Nat']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </th>
                                <th class="col-medium table-header-with-filter">
                                    <div class="table-header-label">Actions</div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($etudiants)): ?>
                                <tr>
                                    <td colspan="11" class="no-results">
                                        <i class="fas fa-search" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                                        Aucun étudiant trouvé avec les critères de recherche sélectionnés.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($etudiants as $e): ?>
                                <tr>
                                    <td class="checkbox-cell no-print">
                                        <input type="checkbox" name="matricules[]" 
                                               value="<?= $e['matricule'] ?>" 
                                               class="student-checkbox">
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
                                    <td><?= htmlspecialchars($e['filiere_intitule']) ?></td>
                                    <td>
                                        <span class="badge badge-primary badge-compact">
                                            <?= htmlspecialchars($e['niveau']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($e['departement_intitule']) ?></td>
                                    <td>
                                        <span class="badge badge-info badge-compact">
                                            <?= htmlspecialchars($e['nationalite_intitule']) ?>
                                        </span>
                                    </td>
                                    <td class="no-print">
                                        <div class="action-buttons">
                                            <a href="formulaire_modification.php?matricule=<?= $e['matricule'] ?>" 
                                               class="btn btn-icon btn-warning"
                                               title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="supprimer_etudiant.php?matricule=<?= $e['matricule'] ?>" 
                                               class="btn btn-icon btn-danger"
                                               onclick="return confirm('Supprimer cet étudiant ?')"
                                               title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Actions CRUD -->
            <div class="crud-actions">
                <button id="btn-create" class="crud-button create">
                    <i class="fas fa-user-plus"></i> Ajouter
                </button>
                <button id="update-selected" class="crud-button update" disabled>
                    <i class="fas fa-edit"></i> Modifier
                </button>
                <button id="delete-selected" class="crud-button delete" disabled>
                    <i class="fas fa-trash"></i> Supprimer
                </button>
                <button id="print-button" class="crud-button print">
                    <i class="fas fa-print"></i> Imprimer
                </button>
            </div>
        </main>

        <footer>
            <p>&copy; <?= date('Y') ?> - Gestion des Étudiants | TP_SATECH - Système de gestion des étudiants</p>
        </footer>
    </div>
</div>

<!-- Modal pour créer un étudiant (version simplifiée) -->
<div id="modal-create" class="modal modal-top-anchored" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> Ajouter un nouvel étudiant</h3>
            <button class="close-button" onclick="closeModal('modal-create')">&times;</button>
        </div>
        <div style="padding: 20px;">
            <p style="text-align: center; padding: 40px;">
                Redirection vers le formulaire d'ajout...
            </p>
        </div>
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

if (selectAll) {
    selectAll.addEventListener('change', function() {
        studentCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        checkSelection();
    });
}

studentCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', checkSelection);
});

// Gestion des modales
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Fermer les modales en cliquant en dehors
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal(this.id);
        }
    });
});

// Ouvrir la modal de création
document.getElementById('btn-create').addEventListener('click', function() {
    window.location.href = 'formulaire_creation.php';
});

// Actions CRUD
updateBtn.onclick = function() {
    const checkedBox = document.querySelector('.student-checkbox:checked');
    if (checkedBox) {
        window.location.href = 'formulaire_modification.php?matricule=' + checkedBox.value;
    }
};

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

// Réinitialisation des filtres
function resetFilters() {
    window.location.href = 'liste_etudiants.php';
}

// Configuration des filtres - soumission automatique
document.querySelectorAll('.filter-input-small, .filter-select-small, .filter-date-small').forEach(input => {
    input.addEventListener('change', function() {
        // Attendre un peu avant de soumettre pour éviter les soumissions multiples
        setTimeout(() => {
            document.getElementById('filters-form').submit();
        }, 300);
    });
});
</script>
</body>
</html>