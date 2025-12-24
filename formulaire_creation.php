<?php
session_start();
require_once 'config/database.php';
require_once 'includes/fonctions.php';

$pdo = getPDO();

// Récupérer les données pour les listes déroulantes
$filieres = $pdo->query('SELECT id_filiere, intitulé FROM `Filière` ORDER BY intitulé')->fetchAll();
$nationalites = $pdo->query('SELECT code_nationalite, intitulé_Nat FROM `Nationalité` ORDER BY intitulé_Nat')->fetchAll();

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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
</head>
<body>
<div class="container">
    <div class="main-container">
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
                </a>
            </nav>
        </header>

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
        </main>

        <footer>
            <p>&copy; <?= date('Y') ?> - Gestion des Étudiants | TP_SATECH</p>
        </footer>
    </div>
</div>

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
    
    if (dateNaissance) {
        const birthDate = new Date(dateNaissance);
        const today = new Date();
        const age = today.getFullYear() - birthDate.getFullYear();
        
        if (age < 16) {
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
</script>
</body>
</html>