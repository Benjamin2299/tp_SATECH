<?php
session_start();

<<<<<<< HEAD
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
=======
$host = 'localhost'; 
$db = 'Gestion_Etudiant'; 
$user = 'root'; 
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et validation des données
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $sexe = $_POST['sexe'] ?? '';
    $date_naissance = $_POST['date_naissance'] ?? '';
    $id_filiere = $_POST['id_filiere'] ?? '';
    $niveau = $_POST['niveau'] ?? '';
    $code_nationalite = $_POST['code_nationalite'] ?? '';
    
    // Validation basique
    $errors = [];
    
    if (empty($nom) || strlen($nom) < 2) {
        $errors[] = "Le nom doit contenir au moins 2 caractères.";
    }
    
    if (empty($prenom) || strlen($prenom) < 2) {
        $errors[] = "Le prénom doit contenir au moins 2 caractères.";
    }
    
    if (!in_array($sexe, ['M', 'F'])) {
        $errors[] = "Le sexe doit être M ou F.";
    }
    
    if (empty($date_naissance)) {
        $errors[] = "La date de naissance est requise.";
    } elseif (strtotime($date_naissance) > strtotime('now')) {
        $errors[] = "La date de naissance ne peut pas être dans le futur.";
    }
    
    if (empty($id_filiere) || !is_numeric($id_filiere)) {
        $errors[] = "Une filière valide doit être sélectionnée.";
    }
    
    if (empty($niveau)) {
        $errors[] = "Le niveau est requis.";
    }
    
    if (empty($code_nationalite)) {
        $errors[] = "La nationalité est requise.";
    }
    
    // Si pas d'erreurs, procéder à l'enregistrement
    if (empty($errors)) {
        try {
            // Récupérer les informations de la filière pour générer le matricule
            $stmt = $pdo->prepare("
                SELECT D.intitulé_Dep 
                FROM Filière F 
                LEFT JOIN Département D ON F.id_Dep = D.id_Dep 
                WHERE F.id_filiere = ?
            ");
            $stmt->execute([$id_filiere]);
            $filiere = $stmt->fetch();
            
            $departement = $filiere['intitulé_Dep'] ?? 'GEN';
            
            // Nettoyer le nom du département pour le code
            $codeDep = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $departement), 0, 3));
            if (strlen($codeDep) < 3) {
                $codeDep = str_pad($codeDep, 3, 'X');
            }
            
            // Générer un numéro séquentiel unique
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM Etudiant WHERE id_filiere = ?");
            $stmt->execute([$id_filiere]);
            $count = $stmt->fetch()['count'];
            
            // S'assurer que le numéro est unique
            do {
                $nextNumber = str_pad($count + 1, 5, '0', STR_PAD_LEFT);
                $annee = date('y');
                $matricule = "UNIV" . $codeDep . $annee . $nextNumber;
                
                // Vérifier si le matricule existe déjà
                $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM Etudiant WHERE matricule = ?");
                $checkStmt->execute([$matricule]);
                $exists = $checkStmt->fetch()['count'] > 0;
                
                if ($exists) {
                    $count++;
                }
            } while ($exists);
            
            // Insérer l'étudiant
            $sql = "INSERT INTO Etudiant (matricule, nom, prenom, sexe, date_de_naissance, niveau, id_filiere, code_nationalite, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $matricule,
                $nom,
                $prenom,
                $sexe,
                $date_naissance,
                $niveau,
                $id_filiere,
                $code_nationalite
            ]);
            
            // Redirection avec message de succès
            $_SESSION['message'] = "Étudiant $nom $prenom ajouté avec succès. Matricule: $matricule";
            header("Location: liste_etudiants.php?message=" . urlencode("Étudiant ajouté avec succès. Matricule: $matricule"));
            exit;
            
        } catch (PDOException $e) {
            // Gestion des erreurs de base de données
            if ($e->getCode() == 23000) { // Erreur de doublon
                header("Location: formulaire_creation.php?error=" . urlencode("Erreur : Ce matricule existe déjà."));
            } else {
                header("Location: formulaire_creation.php?error=" . urlencode("Erreur de base de données : " . $e->getMessage()));
            }
            exit;
        }
    } else {
        // Redirection avec les erreurs
        header("Location: formulaire_creation.php?error=" . urlencode(implode(" ", $errors)));
        exit;
    }
} else {
    // Si pas POST, rediriger vers le formulaire
    header("Location: formulaire_creation.php");
    exit;
}
>>>>>>> 8342b65ad63fab5ca317108f4a9f20060c67fdef
