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

// Vérifier si la suppression vient d'un formulaire POST (suppression multiple)
// ou d'un paramètre GET (suppression unitaire)
$matricules = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['matricules'])) {
    // Suppression multiple depuis le formulaire POST
    $matricules = is_array($_POST['matricules']) ? $_POST['matricules'] : [$_POST['matricules']];
} elseif (isset($_GET['matricule'])) {
    // Suppression unitaire depuis le paramètre GET
    $matricules = [$_GET['matricule']];
} else {
    header("Location: liste_etudiants.php?error=Aucun étudiant sélectionné pour la suppression");
    exit;
}

// Filtrer et valider les matricules
$matricules = array_filter(array_map('trim', $matricules), function($matricule) {
    return !empty($matricule);
});

if (empty($matricules)) {
    header("Location: liste_etudiants.php?error=Aucun matricule valide fourni");
    exit;
}

try {
    // Récupérer les noms des étudiants avant suppression pour le message
    $placeholders = str_repeat('?,', count($matricules) - 1) . '?';
    $sqlSelect = "SELECT nom, prenom FROM Etudiant WHERE matricule IN ($placeholders)";
    $stmtSelect = $pdo->prepare($sqlSelect);
    $stmtSelect->execute($matricules);
    $etudiantsASupprimer = $stmtSelect->fetchAll();
    
    if (empty($etudiantsASupprimer)) {
        header("Location: liste_etudiants.php?error=Aucun étudiant trouvé avec les matricules fournis");
        exit;
    }
    
    // Supprimer les étudiants
    $sqlDelete = "DELETE FROM Etudiant WHERE matricule IN ($placeholders)";
    $stmtDelete = $pdo->prepare($sqlDelete);
    $stmtDelete->execute($matricules);
    
    $rowsDeleted = $stmtDelete->rowCount();
    
    // Construire le message de succès
    if ($rowsDeleted === 1) {
        $etudiant = $etudiantsASupprimer[0];
        $message = "Étudiant " . $etudiant['nom'] . " " . $etudiant['prenom'] . " supprimé avec succès.";
    } else {
        $message = "$rowsDeleted étudiants supprimés avec succès.";
    }
    
    // Redirection avec message de succès
    $_SESSION['message'] = $message;
    header("Location: liste_etudiants.php?message=" . urlencode($message));
    exit;
    
} catch (PDOException $e) {
    // Gestion des erreurs de base de données
    $errorMessage = "Erreur lors de la suppression : " . $e->getMessage();
    header("Location: liste_etudiants.php?error=" . urlencode($errorMessage));
    exit;
}