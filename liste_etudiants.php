<?php
session_start();
require_once 'config/database.php';

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
$sql = "SELECT E.*, F.intitulé AS filiere_intitule, D.intitulé_Dep AS departement_intitule, N.intitulé_Nat AS nationalite_intitule
        FROM Etudiant E 
        LEFT JOIN `Filière` F ON E.id_filiere = F.id_filiere
        LEFT JOIN `Département` D ON F.id_Dep = D.id_Dep 
        LEFT JOIN `Nationalité` N ON E.code_nationalite = N.code_nationalite
        WHERE 1=1";
$params = [];

if (!empty($_GET['search'])) {
    $sql .= " AND (E.nom LIKE ? OR E.prenom LIKE ? OR E.matricule LIKE ? OR F.intitulé LIKE ? OR D.intitulé_Dep LIKE ? OR N.intitulé_Nat LIKE ?)";
    $searchTerm = '%' . $_GET['search'] . '%';
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

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

$sql .= " ORDER BY CAST(SUBSTRING(E.matricule, -7) AS UNSIGNED) DESC, E.matricule DESC, E.nom, E.prenom";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $etudiants = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erreur SQL : " . $e->getMessage());
}

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
    <title>Liste des Étudiants - TP_SATECH</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="container">
    <div class="main-container">
        <header>
            <h1><i class="fas fa-graduation-cap"></i> Gestion des Étudiants</h1>
            <p class="header-subtitle">Système moderne de gestion des étudiants - TP_SATECH</p>
            <nav>
                <a href="index.php" class="btn btn-home btn-sm">
                    <i class="fas fa-home"></i> Accueil
                </a>
                <a href="dashboard.php" class="btn btn-outline btn-sm">
                    <i class="fas fa-chart-line"></i> Tableau de Bord
                </a>
                <button onclick="openModal('modal-create')" class="btn btn-success btn-sm">
                    <i class="fas fa-user-plus"></i> Nouvel Étudiant
                </button>
            </nav>
        </header>

        <main>
            <h2 class="section-title">
                <i class="fas fa-list-ul"></i> Liste des Étudiants
                <span style="font-size: 1rem; color: var(--dark-gray); font-weight: 400; margin-left: 10px;">
                    (<?= $etudiantsFiltres ?> sur <?= $totalEtudiants ?>)
                </span>
            </h2>
            
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

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> 
                    <span><?= htmlspecialchars($_SESSION['success_message']) ?></span>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> 
                    <span><?= htmlspecialchars($_SESSION['error_message']) ?></span>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <div class="filters-compact">
                <form method="GET" action="" id="filters-form" class="search-bar">
                    <input type="text" name="search" 
                           placeholder="🔍 Rechercher un étudiant (nom, prénom, matricule...)" 
                           value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                    
                    <?php foreach ($filters as $key => $condition): ?>
                        <?php if (!empty($_GET[$key])): ?>
                            <input type="hidden" name="<?= $key ?>" value="<?= htmlspecialchars($_GET[$key]) ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>
                    
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-search"></i> Rechercher
                    </button>
                    <button type="button" onclick="resetFilters()" class="btn btn-secondary btn-sm">
                        <i class="fas fa-redo"></i> Réinitialiser
                    </button>
                </form>
            </div>

            <div class="table-wrapper">
                <div class="table-responsive">
                    <table class="table table-compact">
                        <thead>
                            <tr>
                                <th class="checkbox-cell no-print">
                                    <div class="table-header-label">Sél.</div>
                                    <input type="checkbox" id="select-all" style="position: absolute; bottom: 28px; left: 50%; transform: translateX(-50%);">
                                </th>
                                <th class="col-medium table-header-with-filter">
                                    <div class="table-header-label">Matricule</div>
                                    <input type="text" class="filter-input-small" data-filter="filter_matricule"
                                           placeholder="Filtrer..." 
                                           value="<?= htmlspecialchars($_GET['filter_matricule'] ?? '') ?>">
                                </th>
                                <th class="col-medium table-header-with-filter">
                                    <div class="table-header-label">Nom</div>
                                    <input type="text" class="filter-input-small" data-filter="filter_nom"
                                           placeholder="Filtrer..." 
                                           value="<?= htmlspecialchars($_GET['filter_nom'] ?? '') ?>">
                                </th>
                                <th class="col-medium table-header-with-filter">
                                    <div class="table-header-label">Prénom</div>
                                    <input type="text" class="filter-input-small" data-filter="filter_prenom"
                                           placeholder="Filtrer..." 
                                           value="<?= htmlspecialchars($_GET['filter_prenom'] ?? '') ?>">
                                </th>
                                <th class="col-small table-header-with-filter">
                                    <div class="table-header-label">Sexe</div>
                                    <select class="filter-select-small" data-filter="filter_sexe">
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
                                    <input type="date" class="filter-date-small" data-filter="filter_date_naissance"
                                           value="<?= htmlspecialchars($_GET['filter_date_naissance'] ?? '') ?>">
                                </th>
                                <th class="col-large table-header-with-filter">
                                    <div class="table-header-label">Filière</div>
                                    <select class="filter-select-small" data-filter="filter_filiere">
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
                                    <select class="filter-select-small" data-filter="filter_niveau">
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
                                    <select class="filter-select-small" data-filter="filter_departement">
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
                                    <select class="filter-select-small" data-filter="filter_nationalite">
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
                                        <input type="checkbox" class="student-checkbox" 
                                               value="<?= htmlspecialchars($e['matricule']) ?>"
                                               data-nom="<?= htmlspecialchars($e['nom']) ?>"
                                               data-prenom="<?= htmlspecialchars($e['prenom']) ?>"
                                               data-sexe="<?= htmlspecialchars($e['sexe']) ?>"
                                               data-date="<?= htmlspecialchars($e['date_de_naissance']) ?>"
                                               data-filiere="<?= htmlspecialchars($e['id_filiere']) ?>"
                                               data-niveau="<?= htmlspecialchars($e['niveau']) ?>"
                                               data-nationalite="<?= htmlspecialchars($e['code_nationalite']) ?>">
                                    </td>
                                    <td><strong><?= htmlspecialchars($e['matricule']) ?></strong></td>
                                    <td><?= htmlspecialchars($e['nom']) ?></td>
                                    <td><?= htmlspecialchars($e['prenom']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $e['sexe'] == 'M' ? 'male' : 'female' ?> badge-compact">
                                            <?= $e['sexe'] ?>
                                        </span>
                                    </td>
                                    <td><?= !empty($e['date_de_naissance']) ? date('d/m/Y', strtotime($e['date_de_naissance'])) : '' ?></td>
                                    <td><?= htmlspecialchars($e['filiere_intitule'] ?? 'N/A') ?></td>
                                    <td><span class="badge badge-primary badge-compact"><?= htmlspecialchars($e['niveau']) ?></span></td>
                                    <td><?= htmlspecialchars($e['departement_intitule'] ?? 'N/A') ?></td>
                                    <td><span class="badge badge-info badge-compact"><?= htmlspecialchars($e['nationalite_intitule'] ?? 'N/A') ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="crud-actions no-print">
                <button onclick="openModal('modal-create')" class="crud-button create">
                    <i class="fas fa-user-plus"></i> Ajouter
                </button>
                <button id="update-selected" class="crud-button update" disabled>
                    <i class="fas fa-edit"></i> Modifier
                </button>
                <button id="delete-selected" class="crud-button delete" disabled>
                    <i class="fas fa-trash"></i> Supprimer
                </button>
                <button onclick="printStudentList()" class="crud-button print">
                    <i class="fas fa-print"></i> Imprimer
                </button>
            </div>
        </main>

        <footer>
            <p>&copy; <?= date('Y') ?> - Gestion des Étudiants | TP_SATECH</p>
        </footer>
    </div>
</div>

<!-- Modal Création -->
<div id="modal-create" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> Ajouter un nouvel étudiant</h3>
            <button class="close-button" onclick="closeModal('modal-create')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="enregistrer.php">
                <div class="form-grid-2col">
                    <div class="form-group">
                        <label class="required">Nom</label>
                        <input type="text" name="nom" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="required">Prénom</label>
                        <input type="text" name="prenom" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="required">Sexe</label>
                        <select name="sexe" class="form-control" required>
                            <option value="">Sélectionner...</option>
                            <option value="M">Masculin</option>
                            <option value="F">Féminin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="required">Date de naissance</label>
                        <input type="date" name="date_naissance" class="form-control" max="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="required">Filière</label>
                        <select name="id_filiere" class="form-control" required>
                            <option value="">Sélectionner...</option>
                            <?php foreach ($filieres as $f): ?>
                                <option value="<?= $f['id_filiere'] ?>"><?= htmlspecialchars($f['intitulé']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="required">Niveau</label>
                        <select name="niveau" class="form-control" required>
                            <option value="">Sélectionner...</option>
                            <option value="L1">Licence 1 (L1)</option>
                            <option value="L2">Licence 2 (L2)</option>
                            <option value="L3">Licence 3 (L3)</option>
                            <option value="M1">Master 1 (M1)</option>
                            <option value="M2">Master 2 (M2)</option>
                        </select>
                    </div>
                </div>
                <div class="form-group center">
                    <label class="required">Nationalité</label>
                    <select name="code_nationalite" class="form-control" required>
                        <option value="">Sélectionner...</option>
                        <?php foreach ($nationalites as $n): ?>
                            <option value="<?= $n['code_nationalite'] ?>"><?= htmlspecialchars($n['intitulé_Nat']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modification -->
<div id="modal-update" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Modifier l'étudiant</h3>
            <button class="close-button" onclick="closeModal('modal-update')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="modifier_etudiant.php">
                <input type="hidden" name="matricule" id="update-matricule">
                <div class="form-grid-2col">
                    <div class="form-group">
                        <label class="required">Nom</label>
                        <input type="text" name="nom" id="update-nom" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="required">Prénom</label>
                        <input type="text" name="prenom" id="update-prenom" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="required">Sexe</label>
                        <select name="sexe" id="update-sexe" class="form-control" required>
                            <option value="M">Masculin</option>
                            <option value="F">Féminin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="required">Date de naissance</label>
                        <input type="date" name="date_naissance" id="update-date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="required">Filière</label>
                        <select name="id_filiere" id="update-filiere" class="form-control" required>
                            <?php foreach ($filieres as $f): ?>
                                <option value="<?= $f['id_filiere'] ?>"><?= htmlspecialchars($f['intitulé']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="required">Niveau</label>
                        <select name="niveau" id="update-niveau" class="form-control" required>
                            <option value="L1">Licence 1 (L1)</option>
                            <option value="L2">Licence 2 (L2)</option>
                            <option value="L3">Licence 3 (L3)</option>
                            <option value="M1">Master 1 (M1)</option>
                            <option value="M2">Master 2 (M2)</option>
                        </select>
                    </div>
                </div>
                <div class="form-group center">
                    <label class="required">Nationalité</label>
                    <select name="code_nationalite" id="update-nationalite" class="form-control" required>
                        <?php foreach ($nationalites as $n): ?>
                            <option value="<?= $n['code_nationalite'] ?>"><?= htmlspecialchars($n['intitulé_Nat']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Mettre à jour
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
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
        studentCheckboxes.forEach(checkbox => checkbox.checked = this.checked);
        checkSelection();
    });
}

studentCheckboxes.forEach(checkbox => checkbox.addEventListener('change', checkSelection));

function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
    document.body.style.overflow = 'auto';
}

document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) closeModal(this.id);
    });
});

// Gestion des filtres
document.querySelectorAll('.filter-input-small, .filter-select-small, .filter-date-small').forEach(input => {
    input.addEventListener('change', function() {
        const form = document.getElementById('filters-form');
        const filterName = this.dataset.filter;
        const filterValue = this.value;
        
        let existingInput = form.querySelector(`input[name="${filterName}"]`);
        if (existingInput) {
            existingInput.value = filterValue;
        } else {
            const newInput = document.createElement('input');
            newInput.type = 'hidden';
            newInput.name = filterName;
            newInput.value = filterValue;
            form.appendChild(newInput);
        }
        
        setTimeout(() => form.submit(), 300);
    });
});

function resetFilters() {
    window.location.href = 'liste_etudiants.php';
}

updateBtn.onclick = function() {
    const checkedBox = document.querySelector('.student-checkbox:checked');
    if (!checkedBox) return;
    
    document.getElementById('update-matricule').value = checkedBox.value;
    document.getElementById('update-nom').value = checkedBox.dataset.nom;
    document.getElementById('update-prenom').value = checkedBox.dataset.prenom;
    document.getElementById('update-sexe').value = checkedBox.dataset.sexe;
    document.getElementById('update-date').value = checkedBox.dataset.date;
    document.getElementById('update-filiere').value = checkedBox.dataset.filiere;
    document.getElementById('update-niveau').value = checkedBox.dataset.niveau;
    document.getElementById('update-nationalite').value = checkedBox.dataset.nationalite;
    
    openModal('modal-update');
};

deleteBtn.onclick = function() {
    const checkedBoxes = document.querySelectorAll('.student-checkbox:checked');
    if (checkedBoxes.length === 0) return;
    
    const message = checkedBoxes.length === 1 
        ? "⚠️ Voulez-vous vraiment supprimer cet étudiant ?\n\nCette action est irréversible !"
        : `⚠️ Voulez-vous vraiment supprimer les ${checkedBoxes.length} étudiants sélectionnés ?\n\nCette action est irréversible !`;
    
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

function printStudentList() {
    window.open('print_etudiants.php', '_blank');
}

document.addEventListener('DOMContentLoaded', function() {
    checkSelection();
    
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});
</script>
</body>
</html>