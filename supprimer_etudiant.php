<?php
session_start();
require_once 'config/database.php';

$matricules = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['matricules'])) {
    $matricules = is_array($_POST['matricules']) ? $_POST['matricules'] : [$_POST['matricules']];
} elseif (isset($_GET['matricule'])) {
    $matricules = [$_GET['matricule']];
} else {
    $_SESSION['error_message'] = "Aucun étudiant sélectionné";
    header("Location: liste_etudiants.php");
    exit;
}

$matricules = array_filter(array_map('trim', $matricules), function($matricule) {
    return !empty($matricule);
});

if (empty($matricules)) {
    $_SESSION['error_message'] = "Aucun matricule valide";
    header("Location: liste_etudiants.php");
    exit;
}

try {
    $placeholders = str_repeat('?,', count($matricules) - 1) . '?';
    
    $sqlSelect = "SELECT nom, prenom FROM Etudiant WHERE matricule IN ($placeholders)";
    $stmtSelect = $pdo->prepare($sqlSelect);
    $stmtSelect->execute($matricules);
    $etudiantsASupprimer = $stmtSelect->fetchAll();
    
    if (empty($etudiantsASupprimer)) {
        $_SESSION['error_message'] = "Aucun étudiant trouvé";
        header("Location: liste_etudiants.php");
        exit;
    }
    
    $sqlDelete = "DELETE FROM Etudiant WHERE matricule IN ($placeholders)";
    $stmtDelete = $pdo->prepare($sqlDelete);
    $stmtDelete->execute($matricules);
    
    $rowsDeleted = $stmtDelete->rowCount();
    
    if ($rowsDeleted === 1) {
        $etudiant = $etudiantsASupprimer[0];
        $message = "Étudiant " . $etudiant['nom'] . " " . $etudiant['prenom'] . " supprimé avec succès.";
    } else {
        $message = "$rowsDeleted étudiants supprimés avec succès.";
    }
    
    $_SESSION['success_message'] = $message;
    header("Location: liste_etudiants.php");
    exit;
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Erreur lors de la suppression : " . $e->getMessage();
    header("Location: liste_etudiants.php");
    exit;
}