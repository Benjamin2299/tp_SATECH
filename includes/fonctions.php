<?php
// includes/functions.php

/**
 * Sanitise une chaîne de caractères
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}




function validateBirthDate($date, $minAge = 16, $maxAge = 60) {
    $birthDate = new DateTime($date);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;
    
    return $age >= $minAge && $age <= $maxAge;
}


function redirectWithError($url, $message) {
    $_SESSION['form_error'] = $message;
    header("Location: $url");
    exit;
}

function redirectWithSuccess($url, $message) {
    $_SESSION['success_message'] = $message;
    header("Location: $url");
    exit;
}

/**
 * Génération sécurisée de matricule
 */
function generateMatricule($pdo, $id_filiere) {
    // Récupérer info filière
    $stmt = $pdo->prepare("
        SELECT D.intitulé_Dep 
        FROM Filière F 
        LEFT JOIN Département D ON F.id_Dep = D.id_Dep 
        WHERE F.id_filiere = ?
    ");
    $stmt->execute([$id_filiere]);
    $filiere = $stmt->fetch();
    
    $departement = $filiere['intitulé_Dep'] ?? 'GEN';
    $codeDep = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $departement), 0, 3));
    if (strlen($codeDep) < 3) $codeDep = str_pad($codeDep, 3, 'X');
    
    $annee = date('y');
    
    // Trouver le prochain numéro séquentiel
    $stmt = $pdo->prepare("
        SELECT MAX(CAST(SUBSTRING(matricule, -5) AS UNSIGNED)) as last_num
        FROM Etudiant 
        WHERE id_filiere = ? 
        AND matricule LIKE ?
    ");
    $pattern = "UNIV{$codeDep}{$annee}%";
    $stmt->execute([$id_filiere, $pattern]);
    $result = $stmt->fetch();
    
    $lastNum = $result['last_num'] ?? 0;
    $nextNum = $lastNum + 1;
    
    // Formater le numéro (5 chiffres)
    $formattedNum = str_pad($nextNum, 5, '0', STR_PAD_LEFT);
    
    return "UNIV{$codeDep}{$annee}{$formattedNum}";
}

/**
 * Récupérer les données du formulaire en session
 */
function getFormData($field, $default = '') {
    if (isset($_SESSION['form_data'][$field])) {
        $value = $_SESSION['form_data'][$field];
        unset($_SESSION['form_data'][$field]);
        return $value;
    }
    return $default;
}

/**
 * Stocker les données du formulaire en session
 */
function setFormData($data) {
    $_SESSION['form_data'] = $data;
}

/**
 * Valider un nom ou prénom
 */
function validateName($name, $fieldName) {
    if (empty($name)) {
        return "$fieldName est requis.";
    }
    
    if (strlen($name) < 2) {
        return "$fieldName doit contenir au moins 2 caractères.";
    }
    
    if (!preg_match('/^[a-zA-ZÀ-ÿ\s\-\']+$/u', $name)) {
        return "$fieldName contient des caractères invalides.";
    }
    
    return '';
}

/**
 * Formater un nom (première lettre majuscule)
 */
function formatName($name) {
    return ucwords(strtolower(trim($name)));
}

/**
 * Vérifier si une valeur existe dans une table
 */
function existsInTable($pdo, $table, $column, $value) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM $table WHERE $column = ?");
    $stmt->execute([$value]);
    return $stmt->fetch()['count'] > 0;
}

/**
 * Générer un code de département à partir du nom
 */
function generateDepartementCode($departement) {
    $code = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $departement), 0, 3));
    if (strlen($code) < 3) {
        $code = str_pad($code, 3, 'X');
    }
    return $code;
}

/**
 * Log des modifications (optionnel)
 */
function logModifications($pdo, $oldData, $newData, $userId = 0) {
    // Cette fonction est optionnelle - à utiliser si vous avez une table de logs
    $changes = [];
    
    $fields = ['nom', 'prenom', 'sexe', 'date_de_naissance', 'niveau', 'id_filiere', 'code_nationalite'];
    
    foreach ($fields as $field) {
        if ($oldData[$field] != $newData[$field]) {
            $changes[$field] = [
                'old' => $oldData[$field],
                'new' => $newData[$field]
            ];
        }
    }
    
    if (!empty($changes)) {
        // Insérer dans une table de logs si elle existe
        // $stmt = $pdo->prepare("INSERT INTO logs_modifications (...) VALUES (...)");
        // $stmt->execute([...]);
    }
}

/**
 * Vérifier si un étudiant peut être modifié (pas de contraintes)
 */
function canUpdateStudent($pdo, $matricule) {
    // Vérifier si l'étudiant n'a pas de contraintes (notes, inscriptions, etc.)
    // Cette fonction est optionnelle - à adapter selon votre structure de base
    return true;
}
?>