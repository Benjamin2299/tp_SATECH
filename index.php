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

$totalEtudiants = $pdo->query("SELECT COUNT(*) as total FROM Etudiant")->fetch()['total'];
$totalFilieres = $pdo->query("SELECT COUNT(*) as total FROM `Filière`")->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Gestion des Étudiants</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="container">
    <div class="main-container">
        <header>
            <h1><i class="fas fa-graduation-cap"></i> Gestion des Étudiants</h1>
            <p class="header-subtitle">Système de gestion académique - TP_SATECH</p>
            <nav>
                <a href="dashboard.php" class="btn btn-outline">
                    <i class="fas fa-chart-bar"></i> Tableau de Bord
                </a>
                <a href="liste_etudiants.php" class="btn btn-success">
                    <i class="fas fa-users"></i> Voir les Étudiants
                </a>
            </nav>
        </header>

        <main>
            <div class="horizontal-stats-container">
    <div class="horizontal-stats-grid">
        <div class="horizontal-stat-card">
            <div class="horizontal-stat-icon">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="horizontal-stat-content">
                <div class="horizontal-stat-value"><?= $totalEtudiants ?></div>
                <div class="horizontal-stat-label">Total Étudiants</div>
            </div>
        </div>
        
        <div class="horizontal-stat-card">
            <div class="horizontal-stat-icon">
                <i class="fas fa-university"></i>
            </div>
            <div class="horizontal-stat-content">
                <div class="horizontal-stat-value"><?= $totalFilieres ?></div>
                <div class="horizontal-stat-label">Filières</div>
            </div>
        </div>
        
        <div class="horizontal-stat-card">
            <div class="horizontal-stat-icon">
                <i class="fas fa-database"></i>
            </div>
            <div class="horizontal-stat-content">
                <div class="horizontal-stat-value">MySQL</div>
                <div class="horizontal-stat-label">Base de Données</div>
            </div>
        </div>
        
        <div class="horizontal-stat-card">
            <div class="horizontal-stat-icon">
                <i class="fas fa-code"></i>
            </div>
            <div class="horizontal-stat-content">
                <div class="horizontal-stat-value">PHP</div>
                <div class="horizontal-stat-label">Technologie</div>
            </div>
        </div>
    </div>
</div>
            
            <div style="background: white; padding: 30px; border-radius: var(--border-radius); box-shadow: var(--shadow-light); margin-top: 30px;">
                <h2 style="color: var(--primary-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-rocket"></i> Démarrage Rapide
                </h2>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <div style="background: linear-gradient(135deg, #f8fdff, #e6f7ff); padding: 20px; border-radius: var(--border-radius); border-left: 4px solid var(--success-color);">
                        <h3 style="color: var(--success-color); margin-bottom: 10px;">
                            <i class="fas fa-user-plus"></i> Ajouter un étudiant
                        </h3>
                        <p>Cliquez sur "Nouvel Étudiant" pour ouvrir le formulaire d'ajout.</p>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, #f8fdff, #e6f7ff); padding: 20px; border-radius: var(--border-radius); border-left: 4px solid var(--primary-color);">
                        <h3 style="color: var(--primary-color); margin-bottom: 10px;">
                            <i class="fas fa-list"></i> Gérer les étudiants
                        </h3>
                        <p>Sélectionnez un ou plusieurs étudiants pour modifier ou supprimer.</p>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, #f8fdff, #e6f7ff); padding: 20px; border-radius: var(--border-radius); border-left: 4px solid var(--info-color);">
                        <h3 style="color: var(--info-color); margin-bottom: 10px;">
                            <i class="fas fa-chart-bar"></i> Statistiques
                        </h3>
                        <p>Consultez le tableau de bord pour les analyses et statistiques.</p>
                    </div>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; <?= date('Y') ?> - Gestion des Étudiants | TP_SATECH</p>
        </footer>
    </div>
</div>
</body>
</html>