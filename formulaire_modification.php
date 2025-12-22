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

// Vérifier si un matricule est passé en paramètre
if (!isset($_GET['matricule']) || empty($_GET['matricule'])) {
    header("Location: liste_etudiants.php?error=Matricule non spécifié");
    exit();
}

$matricule = $_GET['matricule'];

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
        header("Location: liste_etudiants.php?error=Étudiant non trouvé");
        exit();
    }
} catch (PDOException $e) {
    die("Erreur lors de la récupération des données : " . $e->getMessage());
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
    <title>Modifier un Étudiant</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    <small style="font-size: 1rem; color: var(--primary-color);">
                        Matricule : <?= htmlspecialchars($etudiant['matricule']) ?>
                    </small>
                </h2>
                
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
                    </div>
                <?php endif; ?>
                
                <!-- Informations actuelles -->
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
                
                <form action="modifier_etudiant.php" method="POST" id="update-form" onsubmit="return validateForm()">
                    <input type="hidden" name="matricule" value="<?= htmlspecialchars($etudiant['matricule']) ?>">
                    
                    <div class="form-grid">
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
                            </select>
                            <div class="error-message" id="error-sexe"></div>
                        </div>
                        
                        <div class="form-group">
                            <label class="required">Date de Naissance</label>
                            <input type="date" name="date_naissance" id="date_naissance" 
                                   class="form-control" 
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
                                        <?= htmlspecialchars($f['intitule']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="error-message" id="error-filiere"></div>
                        </div>
                        
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
                                <option value="" disabled>Sélectionner une nationalité</option>
                                <?php foreach ($nationalites as $nat): ?>
                                    <option value="<?= $nat['code_nationalite'] ?>"
                                        <?= $etudiant['code_nationalite'] == $nat['code_nationalite'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($nat['intitulé_Nat']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="error-message" id="error-nationalite"></div>
                        </div>
                        
                        <!-- Matricule (non modifiable) -->
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Matricule</label>
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
            </div>
        </main>

        <footer>
            <p>&copy; <?= date('Y') ?> - Gestion des Étudiants | TP_SATECH</p>
        </footer>
    </div>
</div>

<script>
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
    
    if (!isValid) {
        // Défiler vers la première erreur
        const firstError = document.querySelector('.error-message[style*="display: block"]');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
    
    return isValid;
}

// Confirmation de suppression
function confirmDelete(matricule) {
    if (confirm('Voulez-vous vraiment supprimer cet étudiant ? Cette action est irréversible.')) {
        window.location.href = 'supprimer_etudiant.php?matricule=' + matricule;
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Mettre la date max pour la date de naissance (aujourd'hui)
    document.getElementById('date_naissance').max = new Date().toISOString().split('T')[0];
});
</script>
</body>
</html>