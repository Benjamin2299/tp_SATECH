<?php
// config/database.php

// Configuration de la base de données
$host = 'localhost';
$db = 'Gestion_Etudiant';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4", 
        $user, 
        $pass, 
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Fonction getPDO() pour compatibilité
function getPDO() {
    global $pdo;
    return $pdo;
}
?>