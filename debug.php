<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>DEBUG - Vérification de la base de données</h1>";

$host = 'localhost'; 
$db = 'Gestion_Etudiant'; 
$user = 'root'; 
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<p style='color:green'>✓ Connexion à la base de données réussie</p>";
} catch (PDOException $e) {
    die("<p style='color:red'>✗ Erreur de connexion : " . $e->getMessage() . "</p>");
}

// 1. Vérifier la structure de la table Etudiant
echo "<h2>1. Structure de la table Etudiant</h2>";
try {
    $stmt = $pdo->query("DESCRIBE Etudiant");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "<p style='color:red'>✗ Erreur : " . $e->getMessage() . "</p>";
}

// 2. Vérifier la structure de la table Filière
echo "<h2>2. Structure de la table Filière</h2>";
try {
    $stmt = $pdo->query("DESCRIBE Filière");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "<p style='color:red'>✗ Erreur : " . $e->getMessage() . "</p>";
}

// 3. Vérifier la structure de la table Nationalité
echo "<h2>3. Structure de la table Nationalité</h2>";
try {
    $stmt = $pdo->query("DESCRIBE Nationalité");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "<p style='color:red'>✗ Erreur : " . $e->getMessage() . "</p>";
}

// 4. Tester la requête principale
echo "<h2>4. Test de la requête principale</h2>";
$test_sql = "SELECT E.*, F.intitulé AS filiere_intitule, D.intitulé_Dep AS departement_intitule, N.intitulé_Nat AS nationalite_intitule
        FROM Etudiant E 
        JOIN Filière F ON E.id_filiere = F.id_filiere
        JOIN Département D ON F.id_Dep = D.id_Dep 
        JOIN Nationalité N ON E.code_nationalite = N.code_nationalite
        LIMIT 1";

try {
    $stmt = $pdo->query($test_sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "<p style='color:green'>✓ Requête SQL exécutée avec succès</p>";
        echo "<pre>" . print_r($result, true) . "</pre>";
    } else {
        echo "<p style='color:orange'>⚠ Requête exécutée mais aucun résultat</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>✗ Erreur dans la requête : " . $e->getMessage() . "</p>";
    echo "<p>SQL : <code>" . htmlspecialchars($test_sql) . "</code></p>";
}

// 5. Vérifier les données de test
echo "<h2>5. Vérification des données</h2>";
echo "<h3>Étudiants :</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM Etudiant");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Nombre d'étudiants : " . $result['count'] . "</p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>✗ Erreur : " . $e->getMessage() . "</p>";
}

echo "<h3>Filières :</h3>";
try {
    $stmt = $pdo->query("SELECT id_filiere, intitulé FROM Filière LIMIT 5");
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($result, true) . "</pre>";
} catch (PDOException $e) {
    echo "<p style='color:red'>✗ Erreur : " . $e->getMessage() . "</p>";
}
?>