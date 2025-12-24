<?php
<<<<<<< HEAD
// modifier_etudiant.php - Version optimisée

session_start();

require_once 'config/database.php';
require_once 'includes/fonctions.php';

$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithError('liste_etudiants.php', 'Méthode non autorisée');
}

$data = sanitize($_POST);
$matricule = $data['matricule'] ?? '';

if (empty($matricule)) {
    redirectWithError('liste_etudiants.php', 'Matricule manquant');
}

// Récupérer les données actuelles
try {
    $stmt = $pdo->prepare("SELECT * FROM Etudiant WHERE matricule = ?");
    $stmt->execute([$matricule]);
    $currentData = $stmt->fetch();
    
    if (!$currentData) {
        redirectWithError('liste_etudiants.php', 'Étudiant non trouvé');
    }
} catch (PDOException $e) {
    error_log("Erreur récupération données: " . $e->getMessage());
    redirectWithError('liste_etudiants.php', 'Erreur de récupération des données');
}

// Préparer les données à mettre à jour
$updates = [];
$params = [];

// Validation et préparation des champs
$fields = [
    'nom' => [
        'value' => $data['nom'] ?? '',
        'validation' => function($value) {
            $error = validateName($value, 'Le nom');
            if (!empty($error)) throw new Exception($error);
            return formatName($value);
        }
    ],
    'prenom' => [
        'value' => $data['prenom'] ?? '',
        'validation' => function($value) {
            $error = validateName($value, 'Le prénom');
            if (!empty($error)) throw new Exception($error);
            return formatName($value);
        }
    ],
    'sexe' => [
        'value' => $data['sexe'] ?? '',
        'validation' => function($value) {
            if (!in_array($value, ['M', 'F'])) {
                throw new Exception("Le sexe doit être M ou F.");
            }
            return $value;
        }
    ],
    'date_de_naissance' => [
        'value' => $data['date_naissance'] ?? '',
        'validation' => function($value) use ($currentData) {
            if (empty($value)) {
                throw new Exception("La date de naissance est requise.");
            }
            if (!validateBirthDate($value, 16, 60)) {
                throw new Exception("L'étudiant doit avoir entre 16 et 60 ans.");
            }
            return $value;
        }
    ],
    'niveau' => [
        'value' => $data['niveau'] ?? '',
        'validation' => function($value) {
            $niveauxValides = ['L1', 'L2', 'L3', 'M1', 'M2', 'D1', 'D2', 'D3'];
            if (empty($value) || !in_array($value, $niveauxValides)) {
                throw new Exception("Le niveau doit être parmi : " . implode(', ', $niveauxValides));
            }
            return $value;
        }
    ],
    'id_filiere' => [
        'value' => $data['id_filiere'] ?? '',
        'validation' => function($value) use ($pdo) {
            if (empty($value) || !is_numeric($value)) {
                throw new Exception("Une filière valide doit être sélectionnée.");
            }
            if (!existsInTable($pdo, 'Filière', 'id_filiere', $value)) {
                throw new Exception("La filière sélectionnée n'existe pas.");
            }
            return $value;
        }
    ],
    'code_nationalite' => [
        'value' => $data['code_nationalite'] ?? '',
        'validation' => function($value) use ($pdo) {
            if (empty($value)) {
                throw new Exception("La nationalité est requise.");
            }
            if (!existsInTable($pdo, 'Nationalité', 'code_nationalite', $value)) {
                throw new Exception("La nationalité sélectionnée n'existe pas.");
            }
            return $value;
        }
    ]
];

// Valider et préparer les mises à jour
try {
    foreach ($fields as $field => $config) {
        $newValue = $config['validation']($config['value']);
        
        // Ne mettre à jour que si la valeur a changé
        if ($currentData[$field] != $newValue) {
            $updates[] = "$field = ?";
            $params[] = $newValue;
        }
    }
    
    // Si aucune modification
    if (empty($updates)) {
        redirectWithSuccess(
            'formulaire_modification.php?matricule=' . urlencode($matricule),
            "Aucune modification détectée. Les données sont déjà à jour."
        );
    }
    
} catch (Exception $e) {
    // Sauvegarder les données et rediriger avec erreur
    setFormData([
        'nom' => $data['nom'],
        'prenom' => $data['prenom'],
        'sexe' => $data['sexe'],
        'date_naissance' => $data['date_naissance'],
        'id_filiere' => $data['id_filiere'],
        'niveau' => $data['niveau'],
        'code_nationalite' => $data['code_nationalite']
    ]);
    
    redirectWithError(
        'formulaire_modification.php?matricule=' . urlencode($matricule),
        $e->getMessage()
    );
}

// Exécuter la mise à jour
try {
    $pdo->beginTransaction();
    
    // Ajouter updated_at
    $updates[] = "updated_at = NOW()";
    
    // Ajouter le matricule comme dernier paramètre
    $params[] = $matricule;
    
    // Construire la requête SQL
    $sql = "UPDATE Etudiant SET " . implode(', ', $updates) . " WHERE matricule = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $rowCount = $stmt->rowCount();
    
    if ($rowCount === 0) {
        throw new Exception("Aucune ligne affectée. Vérifiez le matricule.");
    }
    
    // Nettoyer les données de formulaire
    if (isset($_SESSION['form_data'])) {
        unset($_SESSION['form_data']);
    }
    
    $pdo->commit();
    
    // Message de succès
    $message = "Étudiant <strong>" . ($data['nom'] ?? '') . " " . ($data['prenom'] ?? '') . "</strong> modifié avec succès.";
    if (count($updates) - 1 > 1) { // -1 pour exclure updated_at
        $message .= " (" . (count($updates) - 1) . " champ(s) mis à jour)";
    }
    
    redirectWithSuccess('liste_etudiants.php', $message);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Erreur modification: " . $e->getMessage());
    
    setFormData([
        'nom' => $data['nom'],
        'prenom' => $data['prenom'],
        'sexe' => $data['sexe'],
        'date_naissance' => $data['date_naissance'],
        'id_filiere' => $data['id_filiere'],
        'niveau' => $data['niveau'],
        'code_nationalite' => $data['code_nationalite']
    ]);
    
    redirectWithError(
        'formulaire_modification.php?matricule=' . urlencode($matricule),
        "Erreur technique : " . $e->getMessage()
    );
}
?>
=======
session_start();

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
    $matricule = trim($_POST['matricule'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $sexe = $_POST['sexe'] ?? '';
    $date_naissance = $_POST['date_naissance'] ?? '';
    $id_filiere = $_POST['id_filiere'] ?? '';
    $niveau = $_POST['niveau'] ?? '';
    $code_nationalite = $_POST['code_nationalite'] ?? '';
    
    // Validation
    $errors = [];
    
    if (empty($matricule)) {
        $errors[] = "Matricule manquant.";
    }
    
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
    
    // Si pas d'erreurs, procéder à la mise à jour
    if (empty($errors)) {
        try {
            // Vérifier si l'étudiant existe
            $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM Etudiant WHERE matricule = ?");
            $checkStmt->execute([$matricule]);
            $exists = $checkStmt->fetch()['count'] > 0;
            
            if (!$exists) {
                header("Location: formulaire_modification.php?matricule=" . urlencode($matricule) . "&error=Étudiant non trouvé");
                exit;
            }
            
            // Mettre à jour l'étudiant
            $sql = "UPDATE Etudiant SET 
                    nom = ?, 
                    prenom = ?, 
                    sexe = ?, 
                    date_de_naissance = ?, 
                    niveau = ?, 
                    id_filiere = ?, 
                    code_nationalite = ?,
                    updated_at = NOW()
                    WHERE matricule = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nom,
                $prenom,
                $sexe,
                $date_naissance,
                $niveau,
                $id_filiere,
                $code_nationalite,
                $matricule
            ]);
            
            // Redirection avec message de succès
            $_SESSION['message'] = "Étudiant $nom $prenom modifié avec succès.";
            header("Location: liste_etudiants.php?message=" . urlencode("Étudiant modifié avec succès"));
            exit;
            
        } catch (PDOException $e) {
            // Gestion des erreurs de base de données
            header("Location: formulaire_modification.php?matricule=" . urlencode($matricule) . "&error=" . urlencode("Erreur de base de données : " . $e->getMessage()));
            exit;
        }
    } else {
        // Redirection avec les erreurs
        header("Location: formulaire_modification.php?matricule=" . urlencode($matricule) . "&error=" . urlencode(implode(" ", $errors)));
        exit;
    }
} else {
    // Si pas POST, rediriger vers la liste
    header("Location: liste_etudiants.php");
    exit;
}
>>>>>>> 8342b65ad63fab5ca317108f4a9f20060c67fdef
