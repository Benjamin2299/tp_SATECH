<?php
header('Content-Type: application/json');

$host = 'localhost'; 
$db = 'Gestion_Etudiant'; 
$user = 'root'; 
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur de connexion']);
    exit;
}

if (!isset($_GET['id_filiere'])) {
    echo json_encode(['success' => false, 'error' => 'ID filière manquant']);
    exit;
}

$id_filiere = $_GET['id_filiere'];

try {
    // Récupérer les informations de la filière
    $stmt = $pdo->prepare("
        SELECT F.intitulé, D.intitulé_Dep 
        FROM Filière F 
        LEFT JOIN Département D ON F.id_Dep = D.id_Dep 
        WHERE F.id_filiere = ?
    ");
    $stmt->execute([$id_filiere]);
    $filiere = $stmt->fetch();
    
    if (!$filiere) {
        echo json_encode(['success' => false, 'error' => 'Filière non trouvée']);
        exit;
    }
    
    // Générer un code de département (3 premières lettres)
    $departement = $filiere['intitulé_Dep'] ?? 'GEN';
    $codeDep = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $departement), 0, 3));
    if (strlen($codeDep) < 3) {
        $codeDep = str_pad($codeDep, 3, 'X');
    }
    
    // Compter le nombre d'étudiants dans cette filière pour le numéro séquentiel
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM Etudiant WHERE id_filiere = ?");
    $stmt->execute([$id_filiere]);
    $count = $stmt->fetch()['count'];
    
    // S'assurer que le matricule est unique
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
    
    echo json_encode([
        'success' => true,
        'matricule' => $matricule,
        'filiere' => $filiere['intitulé'],
        'departement' => $departement,
        'numero' => $count + 1
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur de base de données: ' . $e->getMessage()]);
}