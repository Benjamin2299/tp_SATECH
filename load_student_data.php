<?php
session_start();

// Configuration de la base de données
$host = 'localhost'; 
$db = 'Gestion_Etudiant'; 
$user = 'root'; 
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur de connexion à la base de données']);
    exit;
}

// Vérifier si un matricule est passé en paramètre
if (!isset($_GET['matricule']) || empty($_GET['matricule'])) {
    echo json_encode(['success' => false, 'error' => 'Matricule non spécifié']);
    exit;
}

$matricule = trim($_GET['matricule']);

try {
    // Récupérer les données de l'étudiant
    $sql = "SELECT * FROM Etudiant WHERE matricule = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$matricule]);
    $etudiant = $stmt->fetch();
    
    if (!$etudiant) {
        echo json_encode(['success' => false, 'error' => 'Étudiant non trouvé']);
        exit;
    }
    
    // Formater la date de naissance pour l'input HTML
    $date_naissance = '';
    if (!empty($etudiant['date_de_naissance'])) {
        $dateObj = new DateTime($etudiant['date_de_naissance']);
        $date_naissance = $dateObj->format('Y-m-d');
    }
    
    // Retourner les données au format JSON
    echo json_encode([
        'success' => true,
        'data' => [
            'matricule' => $etudiant['matricule'],
            'nom' => $etudiant['nom'],
            'prenom' => $etudiant['prenom'],
            'sexe' => $etudiant['sexe'],
            'date_naissance' => $date_naissance,
            'id_filiere' => $etudiant['id_filiere'],
            'niveau' => $etudiant['niveau'],
            'code_nationalite' => $etudiant['code_nationalite']
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur de base de données: ' . $e->getMessage()]);
}