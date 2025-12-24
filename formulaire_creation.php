<?php
session_start();
<<<<<<< HEAD
require_once 'config/database.php';
require_once 'includes/fonctions.php';

$pdo = getPDO();
=======

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
>>>>>>> 8342b65ad63fab5ca317108f4a9f20060c67fdef

// Récupérer les données pour les listes déroulantes
$filieres = $pdo->query('SELECT id_filiere, intitulé FROM `Filière` ORDER BY intitulé')->fetchAll();
$nationalites = $pdo->query('SELECT code_nationalite, intitulé_Nat FROM `Nationalité` ORDER BY intitulé_Nat')->fetchAll();

<<<<<<< HEAD
// Niveaux
$niveaux = [
    'L1' => 'Licence 1',
    'L2' => 'Licence 2', 
    'L3' => 'Licence 3',
    'M1' => 'Master 1',
    'M2' => 'Master 2',
    'D1' => 'Doctorat 1',
    'D2' => 'Doctorat 2',
    'D3' => 'Doctorat 3'
];

// Messages de session
$errorMessage = $_SESSION['form_error'] ?? '';
$successMessage = $_SESSION['success_message'] ?? '';
$formData = $_SESSION['form_data'] ?? [];

// Nettoyer les messages
unset($_SESSION['form_error']);
unset($_SESSION['success_message']);
unset($_SESSION['form_data']);

$pageTitle = 'Ajouter un Étudiant - TP_SATECH';
=======
// Récupérer les filières avec leurs départements
$filieresWithDepartements = [];
foreach ($filieres as $filiere) {
    $query = $pdo->prepare("SELECT D.intitulé_Dep FROM Filière F 
                            LEFT JOIN Département D ON F.id_Dep = D.id_Dep 
                            WHERE F.id_filiere = ?");
    $query->execute([$filiere['id_filiere']]);
    $departement = $query->fetch();
    $filieresWithDepartements[] = [
        'id' => $filiere['id_filiere'],
        'intitule' => $filiere['intitulé'],
        'departement' => $departement ? $departement['intitulé_Dep'] : 'Non spécifié'
    ];
}
>>>>>>> 8342b65ad63fab5ca317108f4a9f20060c67fdef
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<<<<<<< HEAD
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Style pour la modale -->
    <style>
        /* Styles de base pour la modale */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-container {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            animation: modalAppear 0.3s ease-out;
        }
        
        @keyframes modalAppear {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        
        .modal-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            margin: 0;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-close {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: background 0.3s;
        }
        
        .modal-close:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .modal-body {
            padding: 30px;
            overflow-y: auto;
            max-height: calc(90vh - 140px);
        }
        
        /* Style du formulaire dans la modale */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .form-full-width {
            grid-column: 1 / -1;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
        }
        
        .form-group label.required::after {
            content: " *";
            color: #dc3545;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        
        .modal-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eaeaea;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #0056b3, #004085);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #545b62);
            color: white;
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, #545b62, #4a5056);
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .modal-container {
                width: 95%;
                max-height: 95vh;
            }
        }
        
        /* Style pour la page principale */
        .open-modal-btn {
            padding: 15px 30px;
            font-size: 1.2rem;
            margin: 40px 0;
        }
    </style>
=======
    <title>Ajouter un Étudiant</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
>>>>>>> 8342b65ad63fab5ca317108f4a9f20060c67fdef
</head>
<body>
<div class="container">
    <div class="main-container">
<<<<<<< HEAD
        <!-- Header principal -->
        <header>
            <h1><i class="fas fa-users"></i> Gestion des Étudiants</h1>
            <p class="header-subtitle">Système de gestion des étudiants - TP_SATECH</p>
            <nav>
                <a href="liste_etudiants.php" class="btn btn-secondary">
                    <i class="fas fa-list"></i> Liste des Étudiants
                </a>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-chart-bar"></i> Tableau de Bord
=======
        <header>
            <h1><i class="fas fa-user-plus"></i> Ajouter un Étudiant</h1>
            <p class="header-subtitle">Remplissez le formulaire pour ajouter un nouvel étudiant</p>
            <nav>
                <a href="liste_etudiants.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour à la liste
                </a>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Tableau de Bord
>>>>>>> 8342b65ad63fab5ca317108f4a9f20060c67fdef
                </a>
            </nav>
        </header>

<<<<<<< HEAD
        <main style="text-align: center; padding: 40px 20px;">
            <!-- Bouton pour ouvrir la modale -->
            <button id="openModalBtn" class="btn btn-primary open-modal-btn">
                <i class="fas fa-user-plus"></i> Ajouter un Nouvel Étudiant
            </button>
            
            <!-- Messages généraux -->
            <?php if ($errorMessage): ?>
                <div class="alert alert-danger" style="max-width: 600px; margin: 20px auto;">
                    <i class="fas fa-exclamation-circle"></i> 
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($successMessage): ?>
                <div class="alert alert-success" style="max-width: 600px; margin: 20px auto;">
                    <i class="fas fa-check-circle"></i> 
                    <?= htmlspecialchars($successMessage) ?>
                </div>
            <?php endif; ?>
=======
        <main>
            <div class="form-container">
                <h2 class="section-title">
                    <i class="fas fa-user-graduate"></i> Informations de l'étudiant
                </h2>
                
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle"></i>
                    Tous les champs marqués d'un astérisque (*) sont obligatoires.
                </div>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-error mb-4">
                        <i class="fas fa-exclamation-circle"></i> 
                        <?= htmlspecialchars($_GET['error']) ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success mb-4">
                        <i class="fas fa-check-circle"></i> 
                        <?= htmlspecialchars($_GET['success']) ?>
                    </div>
                <?php endif; ?>
                
                <form action="enregistrer.php" method="POST" id="create-form" onsubmit="return validateForm()">
                    <div class="form-grid">
                        <!-- Nom et Prénom -->
                        <div class="form-group">
                            <label class="required">Nom</label>
                            <input type="text" name="nom" id="nom" class="form-control" 
                                   placeholder="Entrez le nom de l'étudiant"
                                   oninput="generateMatriculePreview()"
                                   required>
                            <div class="error-message" id="error-nom"></div>
                        </div>
                        
                        <div class="form-group">
                            <label class="required">Prénom</label>
                            <input type="text" name="prenom" id="prenom" class="form-control"
                                   placeholder="Entrez le prénom de l'étudiant"
                                   oninput="generateMatriculePreview()"
                                   required>
                            <div class="error-message" id="error-prenom"></div>
                        </div>
                        
                        <!-- Sexe et Date de naissance -->
                        <div class="form-group">
                            <label class="required">Sexe</label>
                            <select name="sexe" id="sexe" class="form-control" required>
                                <option value="" selected disabled>Sélectionner le sexe</option>
                                <option value="M">Masculin</option>
                                <option value="F">Féminin</option>
                            </select>
                            <div class="error-message" id="error-sexe"></div>
                        </div>
                        
                        <div class="form-group">
                            <label class="required">Date de Naissance</label>
                            <input type="date" name="date_naissance" id="date_naissance" 
                                   class="form-control" required
                                   max="<?= date('Y-m-d') ?>">
                            <div class="error-message" id="error-date"></div>
                        </div>
                        
                        <!-- Filière et Niveau -->
                        <div class="form-group">
                            <label class="required">Filière</label>
                            <select name="id_filiere" id="filiere_select" class="form-control" 
                                    required onchange="generateMatriculePreview()">
                                <option value="" selected disabled>Sélectionner une filière</option>
                                <?php foreach ($filieresWithDepartements as $f): ?>
                                    <option value="<?= $f['id'] ?>" 
                                            data-departement="<?= htmlspecialchars($f['departement']) ?>">
                                        <?= htmlspecialchars($f['intitule']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="error-message" id="error-filiere"></div>
                        </div>
                        
                        <div class="form-group">
                            <label class="required">Niveau</label>
                            <select name="niveau" id="niveau" class="form-control" required>
                                <option value="" selected disabled>Sélectionner un niveau</option>
                                <?php 
                                $niveaux = [
                                    'L1' => 'Licence 1',
                                    'L2' => 'Licence 2', 
                                    'L3' => 'Licence 3',
                                    'M1' => 'Master 1',
                                    'M2' => 'Master 2',
                                    'D1' => 'Doctorat 1',
                                    'D2' => 'Doctorat 2',
                                    'D3' => 'Doctorat 3'
                                ];
                                foreach ($niveaux as $code => $label): 
                                ?>
                                    <option value="<?= $code ?>"><?= $label ?> (<?= $code ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <div class="error-message" id="error-niveau"></div>
                        </div>
                        
                        <!-- Nationalité -->
                        <div class="form-group">
                            <label class="required">Nationalité</label>
                            <select name="code_nationalite" id="code_nationalite" class="form-control" required>
                                <option value="" selected disabled>Sélectionner une nationalité</option>
                                <?php foreach ($nationalites as $nat): ?>
                                    <option value="<?= $nat['code_nationalite'] ?>">
                                        <?= htmlspecialchars($nat['intitulé_Nat']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="error-message" id="error-nationalite"></div>
                        </div>
                        
                        <!-- Aperçu du matricule -->
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Matricule (généré automatiquement)</label>
                            <div id="matricule_preview" style="
                                background: linear-gradient(135deg, #f8fdff, #e6f7ff);
                                border: 2px dashed var(--primary-light);
                                padding: 15px;
                                border-radius: var(--border-radius);
                                text-align: center;
                                font-family: monospace;
                                font-size: 1.2rem;
                                color: var(--primary-dark);
                                margin-top: 5px;
                                min-height: 60px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                flex-direction: column;
                                gap: 5px;
                            ">
                                <i class="fas fa-id-card" style="font-size: 1.5rem; color: var(--primary-color);"></i>
                                <span>Sélectionnez une filière pour générer le matricule</span>
                            </div>
                            <small class="form-text">
                                Format: UNIV + 3 lettres département + année + numéro séquentiel (5 chiffres)
                            </small>
                        </div>
                        
                        <!-- Boutons -->
                        <div class="form-actions" style="grid-column: 1 / -1;">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Enregistrer
                            </button>
                            <a href="liste_etudiants.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Annuler
                            </a>
                        </div>
                    </div>
                </form>
            </div>
>>>>>>> 8342b65ad63fab5ca317108f4a9f20060c67fdef
        </main>

        <footer>
            <p>&copy; <?= date('Y') ?> - Gestion des Étudiants | TP_SATECH</p>
        </footer>
    </div>
</div>

<<<<<<< HEAD
<!-- MODALE -->
<div id="studentModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-user-plus"></i> Ajouter un Nouvel Étudiant
            </h2>
            <button class="modal-close" id="closeModalBtn">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <!-- Messages de la modale -->
            <?php if ($errorMessage): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> 
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Tous les champs marqués d'un astérisque (*) sont obligatoires.
            </div>
            
            <!-- Formulaire -->
            <form action="enregister.php" method="POST" id="studentForm">
                <div class="form-row">
                    <!-- Colonne gauche -->
                    <div>
                        <!-- Nom -->
                        <div class="form-group">
                            <label class="required">Nom</label>
                            <input type="text" name="nom" id="nom" class="form-control" 
                                   value="<?= htmlspecialchars($formData['nom'] ?? '') ?>"
                                   placeholder="Entrez le nom de l'étudiant"
                                   required>
                        </div>
                        
                        <!-- Sexe -->
                        <div class="form-group">
                            <label class="required">Sexe</label>
                            <select name="sexe" id="sexe" class="form-control" required>
                                <option value="">Sélectionner le sexe</option>
                                <option value="M" <?= ($formData['sexe'] ?? '') === 'M' ? 'selected' : '' ?>>Masculin</option>
                                <option value="F" <?= ($formData['sexe'] ?? '') === 'F' ? 'selected' : '' ?>>Féminin</option>
                            </select>
                        </div>
                        
                        <!-- Filière -->
                        <div class="form-group">
                            <label class="required">Filière</label>
                            <select name="id_filiere" id="filiere" class="form-control" required>
                                <option value="">Sélectionner une filière</option>
                                <?php foreach ($filieres as $f): ?>
                                    <option value="<?= $f['id_filiere'] ?>" 
                                        <?= ($formData['id_filiere'] ?? '') == $f['id_filiere'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($f['intitulé']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Colonne droite -->
                    <div>
                        <!-- Prénom -->
                        <div class="form-group">
                            <label class="required">Prénom</label>
                            <input type="text" name="prenom" id="prenom" class="form-control"
                                   value="<?= htmlspecialchars($formData['prenom'] ?? '') ?>"
                                   placeholder="Entrez le prénom de l'étudiant"
                                   required>
                        </div>
                        
                        <!-- Date de naissance -->
                        <div class="form-group">
                            <label class="required">Date de Naissance</label>
                            <input type="date" name="date_naissance" id="date_naissance" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($formData['date_naissance'] ?? '') ?>"
                                   max="<?= date('Y-m-d') ?>"
                                   required>
                        </div>
                        
                        <!-- Niveau -->
                        <div class="form-group">
                            <label class="required">Niveau</label>
                            <select name="niveau" id="niveau" class="form-control" required>
                                <option value="">Sélectionner un niveau</option>
                                <?php foreach ($niveaux as $code => $label): ?>
                                    <option value="<?= $code ?>" 
                                        <?= ($formData['niveau'] ?? '') === $code ? 'selected' : '' ?>>
                                        <?= $label ?> (<?= $code ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Nationalité (pleine largeur) -->
                <div class="form-full-width">
                    <div class="form-group" style="max-width: 400px; margin: 0 auto;">
                        <label class="required">Nationalité</label>
                        <select name="code_nationalite" id="nationalite" class="form-control" required>
                            <option value="">Sélectionner une nationalité</option>
                            <?php foreach ($nationalites as $nat): ?>
                                <option value="<?= $nat['code_nationalite'] ?>" 
                                    <?= ($formData['code_nationalite'] ?? '') === $nat['code_nationalite'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($nat['intitulé_Nat']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Boutons d'action -->
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                    <button type="button" class="btn btn-secondary" id="cancelBtn">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts JavaScript -->
<script>
// Gestion de la modale
const modal = document.getElementById('studentModal');
const openBtn = document.getElementById('openModalBtn');
const closeBtn = document.getElementById('closeModalBtn');
const cancelBtn = document.getElementById('cancelBtn');

// Ouvrir la modale
openBtn.addEventListener('click', () => {
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Focus sur le premier champ
    setTimeout(() => {
        document.getElementById('nom').focus();
    }, 300);
});

// Fermer la modale
function closeModal() {
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

closeBtn.addEventListener('click', closeModal);
cancelBtn.addEventListener('click', closeModal);

// Fermer en cliquant à l'extérieur
modal.addEventListener('click', (e) => {
    if (e.target === modal) {
        closeModal();
    }
});

// Fermer avec la touche Échap
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.style.display === 'flex') {
        closeModal();
    }
});

// Validation du formulaire
document.getElementById('studentForm').addEventListener('submit', function(e) {
    const nom = document.getElementById('nom').value.trim();
    const prenom = document.getElementById('prenom').value.trim();
    const dateNaissance = document.getElementById('date_naissance').value;
    
    // Validation basique
    let errors = [];
    
    if (nom.length < 2) {
        errors.push('Le nom doit contenir au moins 2 caractères.');
        document.getElementById('nom').style.borderColor = '#dc3545';
    }
    
    if (prenom.length < 2) {
        errors.push('Le prénom doit contenir au moins 2 caractères.');
        document.getElementById('prenom').style.borderColor = '#dc3545';
    }
    
=======
<script>
// Données des filières pour JavaScript
const filieresData = <?= json_encode($filieresWithDepartements) ?>;
const filieresMap = {};
filieresData.forEach(f => {
    filieresMap[f.id] = f;
});

// Générer un matricule basé sur les informations
async function generateMatriculePreview() {
    const filiereId = document.getElementById('filiere_select').value;
    const nom = document.getElementById('nom').value.trim();
    const prenom = document.getElementById('prenom').value.trim();
    
    if (!filiereId) {
        document.getElementById('matricule_preview').innerHTML = `
            <i class="fas fa-id-card" style="font-size: 1.5rem; color: var(--primary-color);"></i>
            <span>Sélectionnez une filière pour générer le matricule</span>
        `;
        return;
    }
    
    // Afficher un indicateur de chargement
    document.getElementById('matricule_preview').innerHTML = `
        <div class="loading" style="margin: 0 auto;"></div>
        <span style="font-size: 0.9rem; color: var(--medium-gray);">Génération en cours...</span>
    `;
    
    try {
        const response = await fetch('get_next_matricule.php?id_filiere=' + filiereId);
        const data = await response.json();
        
        if (data.success) {
            const filiereInfo = filieresMap[filiereId];
            document.getElementById('matricule_preview').innerHTML = `
                <div style="font-size: 1.4rem; font-weight: bold; color: var(--primary-dark);">
                    ${data.matricule}
                </div>
                <div style="font-size: 0.9rem; color: var(--dark-gray);">
                    <i class="fas fa-graduation-cap"></i> ${filiereInfo.intitule}
                    <br>
                    <i class="fas fa-building"></i> ${filiereInfo.departement}
                </div>
            `;
        } else {
            document.getElementById('matricule_preview').innerHTML = `
                <i class="fas fa-exclamation-triangle" style="color: var(--danger-color);"></i>
                <span style="color: var(--danger-color);">Erreur de génération</span>
            `;
        }
    } catch (error) {
        document.getElementById('matricule_preview').innerHTML = `
            <i class="fas fa-exclamation-triangle" style="color: var(--danger-color);"></i>
            <span style="color: var(--danger-color);">Erreur de connexion au serveur</span>
        `;
    }
}

// Validation du formulaire
function validateForm() {
    let isValid = true;
    const errors = document.querySelectorAll('.error-message');
    errors.forEach(error => {
        error.textContent = '';
        error.style.display = 'none';
    });
    
    // Validation du nom
    const nom = document.getElementById('nom').value.trim();
    if (nom.length < 2) {
        document.getElementById('error-nom').textContent = 'Le nom doit contenir au moins 2 caractères';
        document.getElementById('error-nom').style.display = 'block';
        isValid = false;
    }
    
    // Validation du prénom
    const prenom = document.getElementById('prenom').value.trim();
    if (prenom.length < 2) {
        document.getElementById('error-prenom').textContent = 'Le prénom doit contenir au moins 2 caractères';
        document.getElementById('error-prenom').style.display = 'block';
        isValid = false;
    }
    
    // Validation de la date de naissance
    const dateNaissance = document.getElementById('date_naissance').value;
>>>>>>> 8342b65ad63fab5ca317108f4a9f20060c67fdef
    if (dateNaissance) {
        const birthDate = new Date(dateNaissance);
        const today = new Date();
        const age = today.getFullYear() - birthDate.getFullYear();
        
        if (age < 16) {
<<<<<<< HEAD
            errors.push('L\'étudiant doit avoir au moins 16 ans.');
            document.getElementById('date_naissance').style.borderColor = '#dc3545';
        }
    }
    
    if (errors.length > 0) {
        e.preventDefault();
        alert('Veuillez corriger les erreurs suivantes :\n\n' + errors.join('\n'));
    }
});

// Réinitialiser les bordures lors de la saisie
document.querySelectorAll('.form-control').forEach(input => {
    input.addEventListener('input', function() {
        this.style.borderColor = '';
    });
});

// Si des messages d'erreur existent, ouvrir automatiquement la modale
<?php if ($errorMessage && !empty($formData)): ?>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }, 500);
});
<?php endif; ?>
=======
            document.getElementById('error-date').textContent = 'L\'étudiant doit avoir au moins 16 ans';
            document.getElementById('error-date').style.display = 'block';
            isValid = false;
        }
    }
    
    // Validation de la filière
    const filiere = document.getElementById('filiere_select').value;
    if (!filiere) {
        document.getElementById('error-filiere').textContent = 'Veuillez sélectionner une filière';
        document.getElementById('error-filiere').style.display = 'block';
        isValid = false;
    }
    
    if (!isValid) {
        // Défiler vers la première erreur
        const firstError = document.querySelector('.error-message[style*="display: block"]');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
    
    return isValid;
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Mettre la date max pour la date de naissance (aujourd'hui)
    document.getElementById('date_naissance').max = new Date().toISOString().split('T')[0];
    
    // Ajouter un écouteur pour la génération du matricule
    document.getElementById('filiere_select').addEventListener('change', generateMatriculePreview);
});
>>>>>>> 8342b65ad63fab5ca317108f4a9f20060c67fdef
</script>
</body>
</html>