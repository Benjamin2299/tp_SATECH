<?php
session_start();
require_once 'config/database.php';
require_once 'includes/fonctions.php';

$pageTitle = 'Accueil - Gestion des Étudiants';
?>

<?php include 'includes/header.php'; ?>

<header>
    <h1><i class="fas fa-graduation-cap"></i> Gestion des Étudiants</h1>
    <p class="header-subtitle">Système de gestion académique - TP_SATECH</p>
    <nav>
        <a href="dashboard.php" class="btn btn-primary">
            <i class="fas fa-chart-bar"></i> Tableau de Bord
        </a>
        <a href="liste_etudiants.php" class="btn btn-success">
            <i class="fas fa-users"></i> Voir les Étudiants
        </a>
        <a href="formulaire_creation.php" class="btn btn-secondary">
            <i class="fas fa-user-plus"></i> Ajouter un Étudiant
        </a>
    </nav>
</header>

<main>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-university"></i>
            </div>
            <div class="stat-label">Système de Gestion</div>
            <div class="stat-value">TP_SATECH</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="stat-label">Application</div>
            <div class="stat-value">Étudiants</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-database"></i>
            </div>
            <div class="stat-label">Base de Données</div>
            <div class="stat-value">MySQL</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-code"></i>
            </div>
            <div class="stat-label">Technologie</div>
            <div class="stat-value">PHP</div>
        </div>
    </div>
    
    <div class="quick-stats">
        <div class="quick-stat-card">
            <h3><i class="fas fa-rocket"></i> Démarrage Rapide</h3>
            <p>Commencez par explorer le tableau de bord ou ajouter un nouvel étudiant.</p>
        </div>
        
        <div class="quick-stat-card">
            <h3><i class="fas fa-chart-line"></i> Statistiques</h3>
            <p>Visualisez les données avec des graphiques interactifs.</p>
        </div>
        
        <div class="quick-stat-card">
            <h3><i class="fas fa-cogs"></i> Gestion Complète</h3>
            <p>Création, modification et suppression d'étudiants.</p>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>