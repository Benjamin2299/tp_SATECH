<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricule = trim($_POST['matricule'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $sexe = $_POST['sexe'] ?? '';
    $date_naissance = $_POST['date_naissance'] ?? '';
    $id_filiere = $_POST['id_filiere'] ?? '';
    $niveau = $_POST['niveau'] ?? '';
    $code_nationalite = $_POST['code_nationalite'] ?? '';
    
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
    } else {
        $birthDate = new DateTime($date_naissance);
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;
        
        if ($age < 16) {
            $errors[] = "L'étudiant doit avoir au moins 16 ans.";
        }
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
    
    if (empty($errors)) {
        try {
            $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM Etudiant WHERE matricule = ?");
            $checkStmt->execute([$matricule]);
            $exists = $checkStmt->fetch()['count'] > 0;
            
            if (!$exists) {
                $_SESSION['error_message'] = "Étudiant non trouvé";
                header("Location: liste_etudiants.php");
                exit;
            }
            
            $sql = "UPDATE Etudiant SET 
                    nom = ?, 
                    prenom = ?, 
                    sexe = ?, 
                    date_de_naissance = ?, 
                    niveau = ?, 
                    id_filiere = ?, 
                    code_nationalite = ?
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
            
            $_SESSION['success_message'] = "Étudiant $nom $prenom modifié avec succès.";
            header("Location: liste_etudiants.php");
            exit;
            
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Erreur de base de données : " . $e->getMessage();
            header("Location: liste_etudiants.php");
            exit;
        }
    } else {
        $_SESSION['error_message'] = implode(" ", $errors);
        header("Location: liste_etudiants.php");
        exit;
    }
} else {
    header("Location: liste_etudiants.php");
    exit;
}