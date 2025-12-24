<?php
session_start();

require_once 'config/database.php';
require_once 'includes/fonctions.php';

$pdo = getPDO();

// Vérifier la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithError('liste_etudiants.php', 'Méthode non autorisée');
}

// Récupérer et nettoyer les données
$data = sanitize($_POST);

// Validation des données obligatoires
$requiredFields = ['nom', 'prenom', 'sexe', 'date_naissance', 'niveau', 'id_filiere', 'code_nationalite'];
$errors = [];

foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        $errors[] = "Le champ '$field' est obligatoire.";
    }
}

// Validation supplémentaire
if (empty($errors)) {
    // Validation des noms
    if (!validateName($data['nom'])) {
        $errors[] = "Le nom contient des caractères invalides.";
    }
    
    if (!validateName($data['prenom'])) {
        $errors[] = "Le prénom contient des caractères invalides.";
    }
    
    // Validation de la date de naissance
    if (!validateBirthDate($data['date_naissance'], 16, 60)) {
        $errors[] = "L'étudiant doit avoir entre 16 et 60 ans.";
    }
    
    // Validation du niveau
    $validLevels = ['L1', 'L2', 'L3', 'M1', 'M2', 'D1', 'D2', 'D3'];
    if (!in_array($data['niveau'], $validLevels)) {
        $errors[] = "Niveau invalide.";
    }
}

// Si erreurs, sauvegarder et rediriger
if (!empty($errors)) {
    setFormData($data);
    $_SESSION['form_error'] = implode(' ', $errors);
    header('Location: formulaire_creation.php');
    exit;
}

// Générer le matricule (fonction à créer si elle n'existe pas)
function generateMatricule($pdo) {
    // Exemple : UNIV20240001, UNIV20240002, etc.
    $year = date('Y');
    $sql = "SELECT MAX(matricule) as last_matricule FROM Etudiant WHERE matricule LIKE 'UNIV$year%'";
    $result = $pdo->query($sql)->fetch();
    
    if ($result['last_matricule']) {
        $lastNumber = (int) substr($result['last_matricule'], -4);
        $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $nextNumber = '0001';
    }
    
    return "UNIV$year$nextNumber";
}

// Insertion dans la base de données
try {
    $pdo->beginTransaction();
    
    $matricule = generateMatricule($pdo);
    
    $sql = "INSERT INTO Etudiant 
            (matricule, nom, prenom, sexe, date_de_naissance, id_filiere, niveau, code_nationalite, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $matricule,
        $data['nom'],
        $data['prenom'],
        $data['sexe'],
        $data['date_naissance'],
        $data['id_filiere'],
        $data['niveau'],
        $data['code_nationalite']
    ]);
    
    $pdo->commit();
    
    // Nettoyer les données de formulaire sauvegardées
    if (isset($_SESSION['form_data'])) {
        unset($_SESSION['form_data']);
    }
    
    $_SESSION['success_message'] = "Étudiant ajouté avec succès ! Matricule : $matricule";
    header('Location: liste_etudiants.php');
    exit;
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    setFormData($data);
    $_SESSION['form_error'] = "Erreur technique : " . $e->getMessage();
    header('Location: formulaire_creation.php');
    exit;
}
?>