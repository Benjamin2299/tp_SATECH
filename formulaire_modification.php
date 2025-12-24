<?php
<<<<<<< HEAD
// formulaire_modification.php - Version corrigée

session_start();

// Inclure la configuration et fonctions
require_once 'config/database.php';
require_once 'includes/fonctions.php';

// Récupérer l'instance PDO
$pdo = getPDO();

// Vérifier si un matricule est passé en paramètre
if (!isset($_GET['matricule']) || empty($_GET['matricule'])) {
    redirectWithError('liste_etudiants.php', 'Matricule non spécifié');
}

$matricule = sanitize($_GET['matricule']);
=======
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

// Vérifier si un matricule est passé en paramètre
if (!isset($_GET['matricule']) || empty($_GET['matricule'])) {
    header("Location: liste_etudiants.php?error=Matricule non spécifié");
    exit();
}

$matricule = $_GET['matricule'];
>>>>>>> 8342b65ad63fab5ca317108f4a9f20060c67fdef

// Récupérer les données de l'étudiant
try {
    $sql = "SELECT E.*, 
                   F.intitulé AS filiere_intitule, 
                   D.intitulé_Dep AS departement_intitule, 
                   N.intitulé_Nat AS nationalite_intitule
            FROM Etudiant E 
            LEFT JOIN `Filière` F ON E.id_filiere = F.id_filiere
            LEFT JOIN `Département` D ON F.id_Dep = D.id_Dep 
            LEFT JOIN `Nationalité` N ON E.code_nationalite = N.code_nationalite
            WHERE E.matricule = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$matricule]);
    $etudiant = $stmt->fetch();
    
    if (!$etudiant) {
<<<<<<< HEAD
        redirectWithError('liste_etudiants.php', 'Étudiant non trouvé');
    }
} catch (PDOException $e) {
    error_log("Erreur formulaire_modification.php: " . $e->getMessage());
    redirectWithError('liste_etudiants.php', 'Erreur lors de la récupération des données');
=======
        header("Location: liste_etudiants.php?error=Étudiant non trouvé");
        exit();
    }
} catch (PDOException $e) {
    die("Erreur lors de la récupération des données : " . $e->getMessage());
>>>>>>> 8342b65ad63fab5ca317108f4a9f20060c67fdef
}

// Récupérer les données pour les listes déroulantes
$filieres = $pdo->query('SELECT id_filiere, intitulé FROM `Filière` ORDER BY intitulé')->fetchAll();
$nationalites = $pdo->query('SELECT code_nationalite, intitulé_Nat FROM `Nationalité` ORDER BY intitulé_Nat')->fetchAll();

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
<<<<<<< HEAD

// Gérer les messages de session
$errorMessage = '';
$successMessage = '';

if (isset($_SESSION['form_error'])) {
    $errorMessage = $_SESSION['form_error'];
    unset($_SESSION['form_error']);
}

if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Récupérer les données du formulaire sauvegardées (en cas d'erreur)
$formData = [
    'nom' => getFormData('nom', $etudiant['nom']),
    'prenom' => getFormData('prenom', $etudiant['prenom']),
    'sexe' => getFormData('sexe', $etudiant['sexe']),
    'date_naissance' => getFormData('date_naissance', $etudiant['date_de_naissance']),
    'id_filiere' => getFormData('id_filiere', $etudiant['id_filiere']),
    'niveau' => getFormData('niveau', $etudiant['niveau']),
    'code_nationalite' => getFormData('code_nationalite', $etudiant['code_nationalite'])
];

// Définir les niveaux
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
=======
>>>>>>> 8342b65ad63fab5ca317108f4a9f20060c67fdef
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<<<<<<< HEAD
    <title>Modifier un Étudiant - Gestion des Étudiants</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .current-info {
            background: linear-gradient(135deg, #f8fdff, #e6f7ff);
            padding: 20px;
            border-radius: var(--border-radius);
            border-left: 4px solid var(--primary-color);
            margin-bottom: 25px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }
        
        .info-item {
            padding: 10px;
            background: white;
            border-radius: var(--border-radius-sm);
            border: 1px solid var(--light-gray);
        }
        
        .matricule-display {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            padding: 12px 15px;
            border-radius: var(--border-radius-sm);
            font-family: monospace;
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-dark);
            border: 2px solid var(--primary-light);
            text-align: center;
        }
        
        .form-field-hint {
            font-size: 0.85rem;
            color: var(--medium-gray);
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .form-field-hint i {
            font-size: 0.8rem;
        }
        
        .danger-zone {
            background: linear-gradient(135deg, #ffebee, #ffcdd2);
            border: 2px solid var(--danger-color);
            padding: 20px;
            border-radius: var(--border-radius);
            margin-top: 30px;
        }
        
        .danger-zone h4 {
            color: var(--danger-color);
            margin-bottom: 15px;
        }
    </style>
=======
    <title>Modifier un Étudiant</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
>>>>>>> 8342b65ad63fab5ca317108f4a9f20060c67fdef
</head>
<body>
<div class="container">
    <div class="main-container">
        <header>
            <h1><i class="fas fa-edit"></i> Modifier un Étudiant</h1>
            <p class="header-subtitle">Modifiez les informations de l'étudiant</p>
            <nav>
                <a href="liste_etudiants.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour à la liste
                </a>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Tableau de Bord
                </a>
            </nav>
        </header>

        <main>
            <div class="form-container">
                <h2 class="section-title">
                    <i class="fas fa-user-edit"></i> Modification de l'étudiant
<<<<<<< HEAD
                    <small style="font-size: 1rem; color: var(--primary-color); display: block; margin-top: 5px;">
=======
                    <small style="font-size: 1rem; color: var(--primary-color);">
>>>>>>> 8342b65ad63fab5ca317108f4a9f20060c67fdef
                        Matricule : <?= htmlspecialchars($etudiant['matricule']) ?>
                    </small>
                </h2>
                
<<<<<<< HEAD
                <!-- Message d'information -->
                <div class="alert alert-info mb-4">
                    <div style="display: flex; align-items: flex-start; gap: 10px;">
                        <i class="fas fa-info-circle" style="margin-top: 2px;"></i>
                        <div>
                            <strong>Instructions :</strong>
                            <ul style="margin: 5px 0 0 0; padding-left: 20px;">
                                <li>Le matricule ne peut pas être modifié</li>
                                <li>Tous les champs marqués d'un astérisque (*) sont obligatoires</li>
                                <li>L'étudiant doit avoir au moins 16 ans</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Messages d'erreur/succès -->
                <?php if ($errorMessage): ?>
                    <div class="alert alert-error mb-4">
                        <div style="display: flex; align-items: flex-start; gap: 10px;">
                            <i class="fas fa-exclamation-circle" style="margin-top: 2px;"></i>
                            <div><?= htmlspecialchars($errorMessage) ?></div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($successMessage): ?>
                    <div class="alert alert-success mb-4">
                        <div style="display: flex; align-items: flex-start; gap: 10px;">
                            <i class="fas fa-check-circle" style="margin-top: 2px;"></i>
                            <div><?= $successMessage ?></div>
                        </div>
=======
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle"></i>
                    Modifiez les champs nécessaires. Le matricule ne peut pas être modifié.
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
>>>>>>> 8342b65ad63fab5ca317108f4a9f20060c67fdef
                    </div>
                <?php endif; ?>
                
                <!-- Informations actuelles -->
<<<<<<< HEAD
                <div class="current-info">
                    <h3 style="color: var(--primary-dark); margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-user-check"></i> Informations actuelles
                    </h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <strong>Nom :</strong> <?= htmlspecialchars($etudiant['nom']) ?>
                        </div>
                        <div class="info-item">
                            <strong>Prénom :</strong> <?= htmlspecialchars($etudiant['prenom']) ?>
                        </div>
                        <div class="info-item">
                            <strong>Sexe :</strong> 
                            <?php if ($etudiant['sexe'] == 'M'): ?>
                                <span class="badge badge-male">Masculin</span>
                            <?php else: ?>
                                <span class="badge badge-female">Féminin</span>
                            <?php endif; ?>
                        </div>
                        <div class="info-item">
                            <strong>Date Naissance :</strong> 
                            <?= date('d/m/Y', strtotime($etudiant['date_de_naissance'])) ?>
                        </div>
                        <div class="info-item">
                            <strong>Filière :</strong> <?= htmlspecialchars($etudiant['filiere_intitule']) ?>
                        </div>
                        <div class="info-item">
                            <strong>Niveau :</strong> <?= htmlspecialchars($etudiant['niveau']) ?>
                        </div>
                        <div class="info-item">
                            <strong>Département :</strong> <?= htmlspecialchars($etudiant['departement_intitule']) ?>
                        </div>
                        <div class="info-item">
                            <strong>Nationalité :</strong> <?= htmlspecialchars($etudiant['nationalite_intitule']) ?>
                        </div>
                    </div>
                </div>
                
                <!-- Formulaire de modification -->
=======
                <div class="current-info mb-4" style="
                    background: linear-gradient(135deg, #f8fdff, #e6f7ff);
                    padding: 20px;
                    border-radius: var(--border-radius);
                    border-left: 4px solid var(--primary-color);
                ">
                    <h3 style="color: var(--primary-dark); margin-bottom: 15px;">
                        <i class="fas fa-user-check"></i> Informations actuelles
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div>
                            <strong>Nom :</strong> <?= htmlspecialchars($etudiant['nom']) ?>
                        </div>
                        <div>
                            <strong>Prénom :</strong> <?= htmlspecialchars($etudiant['prenom']) ?>
                        </div>
                        <div>
                            <strong>Filière :</strong> <?= htmlspecialchars($etudiant['filiere_intitule']) ?>
                        </div>
                        <div>
                            <strong>Département :</strong> <?= htmlspecialchars($etudiant['departement_intitule']) ?>
                        </div>
                    </div>
                </div>
                
>>>>>>> 8342b65ad63fab5ca317108f4a9f20060c67fdef
                <form action="modifier_etudiant.php" method="POST" id="update-form" onsubmit="return validateForm()">
                    <input type="hidden" name="matricule" value="<?= htmlspecialchars($etudiant['matricule']) ?>">
                    
                    <div class="form-grid">
<<<<<<< HEAD
                        <!-- Nom -->
                        <div class="form-group">
                            <label class="required">Nom</label>
                            <input type="text" name="nom" id="nom" class="form-control" 
                                   value="<?= htmlspecialchars($formData['nom']) ?>"
                                   placeholder="Entrez le nom de l'étudiant"
                                   oninput="validateNameField(this, 'nom')"
                                   required>
                            <div class="form-field-hint">
                                <i class="fas fa-info-circle"></i>
                                Lettres, espaces, tirets et apostrophes uniquement
                            </div>
                            <div class="error-message" id="error-nom"></div>
                        </div>
                        
                        <!-- Prénom -->
                        <div class="form-group">
                            <label class="required">Prénom</label>
                            <input type="text" name="prenom" id="prenom" class="form-control"
                                   value="<?= htmlspecialchars($formData['prenom']) ?>"
                                   placeholder="Entrez le prénom de l'étudiant"
                                   oninput="validateNameField(this, 'prenom')"
                                   required>
                            <div class="form-field-hint">
                                <i class="fas fa-info-circle"></i>
                                Lettres, espaces, tirets et apostrophes uniquement
                            </div>
                            <div class="error-message" id="error-prenom"></div>
                        </div>
                        
                        <!-- Sexe -->
                        <div class="form-group">
                            <label class="required">Sexe</label>
                            <select name="sexe" id="sexe" class="form-control" required>
                                <option value="M" <?= $formData['sexe'] === 'M' ? 'selected' : '' ?>>Masculin</option>
                                <option value="F" <?= $formData['sexe'] === 'F' ? 'selected' : '' ?>>Féminin</option>
=======
                        <!-- Nom et Prénom -->
                        <div class="form-group">
                            <label class="required">Nom</label>
                            <input type="text" name="nom" id="nom" class="form-control" 
                                   value="<?= htmlspecialchars($etudiant['nom']) ?>"
                                   placeholder="Entrez le nom de l'étudiant"
                                   required>
                            <div class="error-message" id="error-nom"></div>
                        </div>
                        
                        <div class="form-group">
                            <label class="required">Prénom</label>
                            <input type="text" name="prenom" id="prenom" class="form-control"
                                   value="<?= htmlspecialchars($etudiant['prenom']) ?>"
                                   placeholder="Entrez le prénom de l'étudiant"
                                   required>
                            <div class="error-message" id="error-prenom"></div>
                        </div>
                        
                        <!-- Sexe et Date de naissance -->
                        <div class="form-group">
                            <label class="required">Sexe</label>
                            <select name="sexe" id="sexe" class="form-control" required>
                                <option value="M" <?= $etudiant['sexe'] == 'M' ? 'selected' : '' ?>>Masculin</option>
                                <option value="F" <?= $etudiant['sexe'] == 'F' ? 'selected' : '' ?>>Féminin</option>
>>>>>>> 8342b65ad63fab5ca317108f4a9f20060c67fdef
                            </select>
                            <div class="error-message" id="error-sexe"></div>
                        </div>
                        
<<<<<<< HEAD
                        <!-- Date de naissance -->
=======
>>>>>>> 8342b65ad63fab5ca317108f4a9f20060c67fdef
                        <div class="form-group">
                            <label class="required">Date de Naissance</label>
                            <input type="date" name="date_naissance" id="date_naissance" 
                                   class="form-control" 
<<<<<<< HEAD
                                   value="<?= htmlspecialchars($formData['date_naissance']) ?>"
                                   max="<?= date('Y-m-d') ?>"
                                   min="<?= date('Y-m-d', strtotime('-60 years')) ?>"
                                   onchange="validateBirthDate()"
                                   required>
                            <div class="form-field-hint">
                                <i class="fas fa-calendar-alt"></i>
                                Âge requis : 16 à 60 ans
                            </div>
                            <div class="error-message" id="error-date"></div>
                        </div>
                        
                        <!-- Filière -->
                        <div class="form-group">
                            <label class="required">Filière</label>
                            <select name="id_filiere" id="filiere_select" class="form-control" required>
                                <option value="">Sélectionner une filière</option>
                                <?php foreach ($filieresWithDepartements as $f): ?>
                                    <option value="<?= $f['id'] ?>" 
                                            data-departement="<?= htmlspecialchars($f['departement']) ?>"
                                            <?= $formData['id_filiere'] == $f['id'] ? 'selected' : '' ?>>
=======
                                   value="<?= $etudiant['date_de_naissance'] ?>"
                                   max="<?= date('Y-m-d') ?>"
                                   required>
                            <div class="error-message" id="error-date"></div>
                        </div>
                        
                        <!-- Filière et Niveau -->
                        <div class="form-group">
                            <label class="required">Filière</label>
                            <select name="id_filiere" id="filiere_select" class="form-control" required>
                                <option value="" disabled>Sélectionner une filière</option>
                                <?php foreach ($filieresWithDepartements as $f): ?>
                                    <option value="<?= $f['id'] ?>" 
                                            data-departement="<?= htmlspecialchars($f['departement']) ?>"
                                            <?= $etudiant['id_filiere'] == $f['id'] ? 'selected' : '' ?>>
>>>>>>> 8342b65ad63fab5ca317108f4a9f20060c67fdef
                                        <?= htmlspecialchars($f['intitule']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="error-message" id="error-filiere"></div>
                        </div>
                        
<<<<<<< HEAD
                        <!-- Niveau -->
                        <div class="form-group">
                            <label class="required">Niveau</label>
                            <select name="niveau" id="niveau" class="form-control" required>
                                <option value="">Sélectionner un niveau</option>
                                <?php foreach ($niveaux as $code => $label): ?>
                                    <option value="<?= $code ?>" 
                                            <?= $formData['niveau'] === $code ? 'selected' : '' ?>>
=======
                        <div class="form-group">
                            <label class="required">Niveau</label>
                            <select name="niveau" id="niveau" class="form-control" required>
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
                                    <option value="<?= $code ?>" 
                                        <?= $etudiant['niveau'] == $code ? 'selected' : '' ?>>
>>>>>>> 8342b65ad63fab5ca317108f4a9f20060c67fdef
                                        <?= $label ?> (<?= $code ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="error-message" id="error-niveau"></div>
                        </div>
                        
                        <!-- Nationalité -->
                        <div class="form-group">
                            <label class="required">Nationalité</label>
                            <select name="code_nationalite" id="code_nationalite" class="form-control" required>
<<<<<<< HEAD
                                <option value="">Sélectionner une nationalité</option>
                                <?php foreach ($nationalites as $nat): ?>
                                    <option value="<?= $nat['code_nationalite'] ?>" 
                                            <?= $formData['code_nationalite'] === $nat['code_nationalite'] ? 'selected' : '' ?>>
=======
                                <option value="" disabled>Sélectionner une nationalité</option>
                                <?php foreach ($nationalites as $nat): ?>
                                    <option value="<?= $nat['code_nationalite'] ?>"
                                        <?= $etudiant['code_nationalite'] == $nat['code_nationalite'] ? 'selected' : '' ?>>
>>>>>>> 8342b65ad63fab5ca317108f4a9f20060c67fdef
                                        <?= htmlspecialchars($nat['intitulé_Nat']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="error-message" id="error-nationalite"></div>
                        </div>
                        
                        <!-- Matricule (non modifiable) -->
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Matricule</label>
<<<<<<< HEAD
                            <div class="matricule-display">
                                <i class="fas fa-id-card"></i> 
                                <?= htmlspecialchars($etudiant['matricule']) ?>
                            </div>
                            <div class="form-field-hint">
                                <i class="fas fa-lock"></i>
                                Le matricule est généré automatiquement et ne peut pas être modifié.
                            </div>
                        </div>
                        
                        <!-- Boutons principaux -->
                        <div class="form-actions" style="grid-column: 1 / -1;">
                            <button type="submit" class="btn btn-success" id="submit-btn">
                                <i class="fas fa-save"></i> Enregistrer les modifications
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                <i class="fas fa-redo"></i> Réinitialiser
                            </button>
                            <a href="liste_etudiants.php" class="btn btn-outline">
                                <i class="fas fa-times"></i> Annuler
                            </a>
                        </div>
                    </div>
                </form>
                
                <!-- Zone de danger -->
                <div class="danger-zone">
                    <h4><i class="fas fa-exclamation-triangle"></i> Zone de danger</h4>
                    <p>Les actions dans cette section sont irréversibles. Soyez certain de vouloir continuer.</p>
                    <div style="margin-top: 15px;">
                        <button type="button" class="btn btn-danger" 
                                onclick="confirmDelete('<?= $etudiant['matricule'] ?>')">
                            <i class="fas fa-trash"></i> Supprimer définitivement cet étudiant
                        </button>
                        <small style="display: block; margin-top: 10px; color: var(--danger-color);">
                            <i class="fas fa-warning"></i> Cette action supprimera toutes les données de l'étudiant et ne peut pas être annulée.
                        </small>
                    </div>
                </div>
=======
                            <div style="
                                background: #f8f9fa;
                                padding: 12px 15px;
                                border-radius: var(--border-radius-sm);
                                font-family: monospace;
                                font-size: 1.2rem;
                                font-weight: bold;
                                color: var(--primary-dark);
                                border: 2px solid var(--light-gray);
                            ">
                                <i class="fas fa-id-card"></i> 
                                <?= htmlspecialchars($etudiant['matricule']) ?>
                            </div>
                            <small class="form-text">
                                Le matricule est généré automatiquement et ne peut pas être modifié.
                            </small>
                        </div>
                        
                        <!-- Boutons -->
                        <div class="form-actions" style="grid-column: 1 / -1;">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Enregistrer les modifications
                            </button>
                            <a href="liste_etudiants.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Annuler
                            </a>
                            <button type="button" class="btn btn-danger" 
                                    onclick="confirmDelete('<?= $etudiant['matricule'] ?>')">
                                <i class="fas fa-trash"></i> Supprimer l'étudiant
                            </button>
                        </div>
                    </div>
                </form>
>>>>>>> 8342b65ad63fab5ca317108f4a9f20060c67fdef
            </div>
        </main>

        <footer>
            <p>&copy; <?= date('Y') ?> - Gestion des Étudiants | TP_SATECH</p>
        </footer>
    </div>
</div>

<script>
<<<<<<< HEAD
// Données des filières pour JavaScript
const filieresData = <?= json_encode($filieresWithDepartements) ?>;

// Validation en temps réel des noms
function validateNameField(input, fieldName) {
    const errorElement = document.getElementById(`error-${fieldName}`);
    const value = input.value.trim();
    
    if (value.length === 0) {
        errorElement.textContent = '';
        errorElement.style.display = 'none';
        input.style.borderColor = '';
        return;
    }
    
    if (value.length < 2) {
        errorElement.textContent = 'Doit contenir au moins 2 caractères';
        errorElement.style.display = 'block';
        input.style.borderColor = 'var(--danger-color)';
        return;
    }
    
    // Expression régulière pour noms avec accents
    const nameRegex = /^[a-zA-ZÀ-ÿ\s\-\']+$/u;
    if (!nameRegex.test(value)) {
        errorElement.textContent = 'Caractères invalides. Utilisez uniquement des lettres, espaces, tirets et apostrophes';
        errorElement.style.display = 'block';
        input.style.borderColor = 'var(--danger-color)';
        return;
    }
    
    // Tout est bon
    errorElement.textContent = '';
    errorElement.style.display = 'none';
    input.style.borderColor = 'var(--success-color)';
}

// Validation de la date de naissance
function validateBirthDate() {
    const dateInput = document.getElementById('date_naissance');
    const errorElement = document.getElementById('error-date');
    const value = dateInput.value;
    
    if (!value) {
        errorElement.textContent = '';
        errorElement.style.display = 'none';
        dateInput.style.borderColor = '';
        return;
    }
    
    const birthDate = new Date(value);
    const today = new Date();
    const age = today.getFullYear() - birthDate.getFullYear();
    
    // Ajuster l'âge si l'anniversaire n'est pas encore passé cette année
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    
    if (age < 16) {
        errorElement.textContent = `L'étudiant a ${age} ans. L'âge minimum est 16 ans.`;
        errorElement.style.display = 'block';
        dateInput.style.borderColor = 'var(--danger-color)';
    } else if (age > 60) {
        errorElement.textContent = `L'étudiant a ${age} ans. L'âge maximum est 60 ans.`;
        errorElement.style.display = 'block';
        dateInput.style.borderColor = 'var(--danger-color)';
    } else {
        errorElement.textContent = '';
        errorElement.style.display = 'none';
        dateInput.style.borderColor = 'var(--success-color)';
    }
}

// Validation complète du formulaire
function validateForm() {
    let isValid = true;
    
    // Réinitialiser toutes les erreurs
    document.querySelectorAll('.error-message').forEach(el => {
        el.style.display = 'none';
        el.textContent = '';
    });
    
    document.querySelectorAll('.form-control').forEach(input => {
        input.style.borderColor = '';
=======
// Validation du formulaire
function validateForm() {
    let isValid = true;
    const errors = document.querySelectorAll('.error-message');
    errors.forEach(error => {
        error.textContent = '';
        error.style.display = 'none';
>>>>>>> 8342b65ad63fab5ca317108f4a9f20060c67fdef
    });
    
    // Validation du nom
    const nom = document.getElementById('nom').value.trim();
    if (nom.length < 2) {
        document.getElementById('error-nom').textContent = 'Le nom doit contenir au moins 2 caractères';
        document.getElementById('error-nom').style.display = 'block';
<<<<<<< HEAD
        document.getElementById('nom').style.borderColor = 'var(--danger-color)';
=======
>>>>>>> 8342b65ad63fab5ca317108f4a9f20060c67fdef
        isValid = false;
    }
    
    // Validation du prénom
    const prenom = document.getElementById('prenom').value.trim();
    if (prenom.length < 2) {
        document.getElementById('error-prenom').textContent = 'Le prénom doit contenir au moins 2 caractères';
        document.getElementById('error-prenom').style.display = 'block';
<<<<<<< HEAD
        document.getElementById('prenom').style.borderColor = 'var(--danger-color)';
=======
>>>>>>> 8342b65ad63fab5ca317108f4a9f20060c67fdef
        isValid = false;
    }
    
    // Validation de la date de naissance
    const dateNaissance = document.getElementById('date_naissance').value;
    if (dateNaissance) {
        const birthDate = new Date(dateNaissance);
        const today = new Date();
<<<<<<< HEAD
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        
        if (age < 16) {
            document.getElementById('error-date').textContent = `L'étudiant doit avoir au moins 16 ans`;
            document.getElementById('error-date').style.display = 'block';
            document.getElementById('date_naissance').style.borderColor = 'var(--danger-color)';
=======
        const age = today.getFullYear() - birthDate.getFullYear();
        
        if (age < 16) {
            document.getElementById('error-date').textContent = 'L\'étudiant doit avoir au moins 16 ans';
            document.getElementById('error-date').style.display = 'block';
>>>>>>> 8342b65ad63fab5ca317108f4a9f20060c67fdef
            isValid = false;
        }
    }
    
<<<<<<< HEAD
    // Validation de la filière
    const filiere = document.getElementById('filiere_select').value;
    if (!filiere) {
        document.getElementById('error-filiere').textContent = 'Veuillez sélectionner une filière';
        document.getElementById('error-filiere').style.display = 'block';
        document.getElementById('filiere_select').style.borderColor = 'var(--danger-color)';
        isValid = false;
    }
    
    // Validation du niveau
    const niveau = document.getElementById('niveau').value;
    if (!niveau) {
        document.getElementById('error-niveau').textContent = 'Veuillez sélectionner un niveau';
        document.getElementById('error-niveau').style.display = 'block';
        document.getElementById('niveau').style.borderColor = 'var(--danger-color)';
        isValid = false;
    }
    
    // Validation de la nationalité
    const nationalite = document.getElementById('code_nationalite').value;
    if (!nationalite) {
        document.getElementById('error-nationalite').textContent = 'Veuillez sélectionner une nationalité';
        document.getElementById('error-nationalite').style.display = 'block';
        document.getElementById('code_nationalite').style.borderColor = 'var(--danger-color)';
        isValid = false;
    }
    
    // Désactiver le bouton si validation échoue
    const submitBtn = document.getElementById('submit-btn');
    if (!isValid) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-exclamation-circle"></i> Veuillez corriger les erreurs';
        submitBtn.style.backgroundColor = 'var(--danger-color)';
        
        // Réactiver après 2 secondes
        setTimeout(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Enregistrer les modifications';
            submitBtn.style.backgroundColor = '';
        }, 2000);
        
=======
    if (!isValid) {
>>>>>>> 8342b65ad63fab5ca317108f4a9f20060c67fdef
        // Défiler vers la première erreur
        const firstError = document.querySelector('.error-message[style*="display: block"]');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
    
    return isValid;
}

<<<<<<< HEAD
// Réinitialiser le formulaire
function resetForm() {
    if (confirm('Êtes-vous sûr de vouloir réinitialiser le formulaire ? Toutes les modifications seront perdues.')) {
        // Réinitialiser aux valeurs originales
        document.getElementById('nom').value = '<?= addslashes($etudiant['nom']) ?>';
        document.getElementById('prenom').value = '<?= addslashes($etudiant['prenom']) ?>';
        document.getElementById('sexe').value = '<?= $etudiant['sexe'] ?>';
        document.getElementById('date_naissance').value = '<?= $etudiant['date_de_naissance'] ?>';
        document.getElementById('filiere_select').value = '<?= $etudiant['id_filiere'] ?>';
        document.getElementById('niveau').value = '<?= $etudiant['niveau'] ?>';
        document.getElementById('code_nationalite').value = '<?= $etudiant['code_nationalite'] ?>';
        
        // Réinitialiser les styles d'erreur
        document.querySelectorAll('.error-message').forEach(el => {
            el.style.display = 'none';
            el.textContent = '';
        });
        
        document.querySelectorAll('.form-control').forEach(input => {
            input.style.borderColor = '';
        });
        
        // Revalider les champs
        validateNameField(document.getElementById('nom'), 'nom');
        validateNameField(document.getElementById('prenom'), 'prenom');
        validateBirthDate();
    }
}

// Confirmation de suppression
function confirmDelete(matricule) {
    const confirmation = confirm(
        '⚠️ ATTENTION - ACTION IRRÉVERSIBLE ⚠️\n\n' +
        'Voulez-vous vraiment supprimer définitivement cet étudiant ?\n' +
        'Matricule : ' + matricule + '\n\n' +
        'Cette action supprimera toutes les données associées à cet étudiant et ne peut pas être annulée.\n\n' +
        'Tapez "SUPPRIMER" pour confirmer :'
    );
    
    if (confirmation) {
        const userInput = prompt('Pour confirmer la suppression, tapez "SUPPRIMER" :');
        if (userInput === 'SUPPRIMER') {
            window.location.href = 'supprimer_etudiant.php?matricule=' + encodeURIComponent(matricule);
        } else {
            alert('Suppression annulée. Le texte entré ne correspond pas.');
        }
=======
// Confirmation de suppression
function confirmDelete(matricule) {
    if (confirm('Voulez-vous vraiment supprimer cet étudiant ? Cette action est irréversible.')) {
        window.location.href = 'supprimer_etudiant.php?matricule=' + matricule;
>>>>>>> 8342b65ad63fab5ca317108f4a9f20060c67fdef
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Mettre la date max pour la date de naissance (aujourd'hui)
    document.getElementById('date_naissance').max = new Date().toISOString().split('T')[0];
<<<<<<< HEAD
    
    // Min date (60 ans)
    const minDate = new Date();
    minDate.setFullYear(minDate.getFullYear() - 60);
    document.getElementById('date_naissance').min = minDate.toISOString().split('T')[0];
    
    // Validation en temps réel
    document.getElementById('nom').addEventListener('blur', function() {
        validateNameField(this, 'nom');
    });
    
    document.getElementById('prenom').addEventListener('blur', function() {
        validateNameField(this, 'prenom');
    });
    
    document.getElementById('date_naissance').addEventListener('blur', validateBirthDate);
    
    // Prévenir la perte de données
    let formModified = false;
    const formInputs = document.querySelectorAll('#update-form input, #update-form select, #update-form textarea');
    
    formInputs.forEach(input => {
        input.addEventListener('input', () => {
            formModified = true;
        });
    });
    
    window.addEventListener('beforeunload', (e) => {
        if (formModified) {
            e.preventDefault();
            e.returnValue = 'Vous avez des modifications non enregistrées. Êtes-vous sûr de vouloir quitter ?';
        }
    });
    
    // Soumission du formulaire
    document.getElementById('update-form').addEventListener('submit', () => {
        formModified = false;
    });
=======
>>>>>>> 8342b65ad63fab5ca317108f4a9f20060c67fdef
});
</script>
</body>
</html>