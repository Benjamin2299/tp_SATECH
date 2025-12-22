<?php
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