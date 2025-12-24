<?php
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