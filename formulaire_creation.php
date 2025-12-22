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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Étudiant</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="container">
    <div class="main-container">
        <header>
            <h1><i class="fas fa-user-plus"></i> Ajouter un Étudiant</h1>
            <p class="header-subtitle">Remplissez le formulaire pour ajouter un nouvel étudiant</p>
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
        </main>

        <footer>
            <p>&copy; <?= date('Y') ?> - Gestion des Étudiants | TP_SATECH</p>
        </footer>
    </div>
</div>

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
    if (dateNaissance) {
        const birthDate = new Date(dateNaissance);
        const today = new Date();
        const age = today.getFullYear() - birthDate.getFullYear();
        
        if (age < 16) {
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
</script>
</body>
</html>