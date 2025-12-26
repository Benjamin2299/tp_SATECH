<?php
session_start();

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
    die("Erreur de connexion : " . $e->getMessage());
}

// Statistiques générales
$totalEtudiants = $pdo->query("SELECT COUNT(*) as total FROM Etudiant")->fetch()['total'];

// Statistiques par sexe
$statsSexe = $pdo->query("SELECT sexe, COUNT(*) as count FROM Etudiant GROUP BY sexe")->fetchAll();
$statsSexeArray = [];
foreach ($statsSexe as $stat) {
    $statsSexeArray[$stat['sexe']] = $stat['count'];
}

// Statistiques par niveau
$statsNiveau = $pdo->query("SELECT niveau, COUNT(*) as count FROM Etudiant GROUP BY niveau ORDER BY niveau")->fetchAll();

// Statistiques par filière
$statsFiliere = $pdo->query("
    SELECT F.intitulé as filiere, COUNT(E.matricule) as count 
    FROM Etudiant E 
    LEFT JOIN Filière F ON E.id_filiere = F.id_filiere 
    GROUP BY F.intitulé 
    ORDER BY count DESC
")->fetchAll();

// Statistiques par nationalité
$statsNationalite = $pdo->query("
    SELECT N.intitulé_Nat as nationalite, COUNT(E.matricule) as count 
    FROM Etudiant E 
    LEFT JOIN Nationalité N ON E.code_nationalite = N.code_nationalite 
    GROUP BY N.intitulé_Nat 
    ORDER BY count DESC
")->fetchAll();

// Derniers étudiants ajoutés (tri par matricule décroissant car pas de date de création)
$derniersEtudiants = $pdo->query("
    SELECT E.*, F.intitulé as filiere 
    FROM Etudiant E 
    LEFT JOIN Filière F ON E.id_filiere = F.id_filiere 
    ORDER BY E.matricule DESC 
    LIMIT 5
")->fetchAll();

// Statistiques par département
$statsDepartement = $pdo->query("
    SELECT D.intitulé_Dep as departement, COUNT(E.matricule) as count 
    FROM Etudiant E 
    LEFT JOIN Filière F ON E.id_filiere = F.id_filiere 
    LEFT JOIN Département D ON F.id_Dep = D.id_Dep 
    GROUP BY D.intitulé_Dep 
    ORDER BY count DESC
")->fetchAll();

// Calculer le pourcentage par sexe
$pourcentageMasculin = $totalEtudiants > 0 ? round(($statsSexeArray['M'] ?? 0) / $totalEtudiants * 100, 1) : 0;
$pourcentageFeminin = $totalEtudiants > 0 ? round(($statsSexeArray['F'] ?? 0) / $totalEtudiants * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Gestion des Étudiants</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            margin-bottom: 30px;
            height: 300px;
        }
        
        .stat-card-large {
            grid-column: span 2;
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            text-align: center;
        }
        
        .stat-card-large .stat-value {
            font-size: 3.5rem;
            font-weight: bold;
            color: var(--primary-dark);
            margin: 15px 0;
        }
        
        .stat-card-large .stat-label {
            color: var(--dark-gray);
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .progress-bar {
            height: 10px;
            background: var(--light-gray);
            border-radius: 5px;
            margin: 15px 0;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 5px;
        }
        
        .male-progress {
            background: linear-gradient(135deg, #36d1dc, #5b86e5);
        }
        
        .female-progress {
            background: linear-gradient(135deg, #ff9a9e, #fad0c4);
        }
        
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .quick-stat-card {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            text-align: center;
            border-top: 4px solid var(--primary-color);
        }
        
        .quick-stat-value {
            font-size: 2.2rem;
            font-weight: bold;
            color: var(--primary-dark);
            margin: 10px 0;
        }
        
        .quick-stat-label {
            color: var(--dark-gray);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="main-container">
        <header>
            <h1><i class="fas fa-chart-bar"></i> Tableau de Bord</h1>
            <p class="header-subtitle">Statistiques et analyses du système de gestion des étudiants</p>
            <nav>
                <a href="index.php" class="btn btn-home">
                    <i class="fas fa-home"></i> Accueil
                </a>
                <a href="liste_etudiants.php" class="btn btn-primary">
                    <i class="fas fa-list"></i> Liste des Étudiants
                </a>
                <a href="formulaire_creation.php" class="btn btn-success">
                    <i class="fas fa-user-plus"></i> Nouvel Étudiant
                </a>
            </nav>
        </header>

        <main> 
            <!-- Statistiques principales en ligne -->
            <div class="horizontal-stats-container mb-4">
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
                            <i class="fas fa-male"></i>
                        </div>
                        <div class="horizontal-stat-content">
                            <div class="horizontal-stat-value"><?= $statsSexeArray['M'] ?? 0 ?></div>
                            <div class="horizontal-stat-label">Masculin</div>
                        </div>
                    </div>
                    
                    <div class="horizontal-stat-card">
                        <div class="horizontal-stat-icon">
                            <i class="fas fa-female"></i>
                        </div>
                        <div class="horizontal-stat-content">
                            <div class="horizontal-stat-value"><?= $statsSexeArray['F'] ?? 0 ?></div>
                            <div class="horizontal-stat-label">Féminin</div>
                        </div>
                    </div>
                    
                    <div class="horizontal-stat-card">
                        <div class="horizontal-stat-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="horizontal-stat-content">
                            <div class="horizontal-stat-value"><?= count($statsFiliere) ?></div>
                            <div class="horizontal-stat-label">Filières</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques rapides -->
            <div class="quick-stats">
                <div class="quick-stat-card">
                    <div class="quick-stat-label">Taux Masculin</div>
                    <div class="quick-stat-value"><?= $pourcentageMasculin ?>%</div>
                    <div class="progress-bar">
                        <div class="progress-fill male-progress" style="width: <?= $pourcentageMasculin ?>%"></div>
                    </div>
                </div>
                
                <div class="quick-stat-card">
                    <div class="quick-stat-label">Taux Féminin</div>
                    <div class="quick-stat-value"><?= $pourcentageFeminin ?>%</div>
                    <div class="progress-bar">
                        <div class="progress-fill female-progress" style="width: <?= $pourcentageFeminin ?>%"></div>
                    </div>
                </div>
                
                <div class="quick-stat-card">
                    <div class="quick-stat-label">Niveaux</div>
                    <div class="quick-stat-value"><?= count($statsNiveau) ?></div>
                    <i class="fas fa-layer-group" style="font-size: 2rem; color: var(--accent-color); margin-top: 10px;"></i>
                </div>
                
                <div class="quick-stat-card">
                    <div class="quick-stat-label">Nationalités</div>
                    <div class="quick-stat-value"><?= count($statsNationalite) ?></div>
                    <i class="fas fa-globe" style="font-size: 2rem; color: var(--info-color); margin-top: 10px;"></i>
                </div>
            </div>

            <!-- Graphiques -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 40px;">
                <!-- Graphique des sexes -->
                <div class="chart-container">
                    <h3 style="color: var(--primary-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-venus-mars"></i> Répartition par Sexe
                    </h3>
                    <div style="height: 200px;">
                        <canvas id="sexeChart"></canvas>
                    </div>
                </div>

                <!-- Graphique des niveaux -->
                <div class="chart-container">
                    <h3 style="color: var(--primary-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-chart-line"></i> Répartition par Niveau
                    </h3>
                    <div style="height: 200px;">
                        <canvas id="niveauChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Derniers étudiants ajoutés -->
            <div style="background: white; padding: 25px; border-radius: var(--border-radius); box-shadow: var(--shadow-light); margin-bottom: 40px;">
                <h3 style="color: var(--primary-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-history"></i> Derniers étudiants inscrits
                </h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Matricule</th>
                                <th>Nom & Prénom</th>
                                <th>Sexe</th>
                                <th>Niveau</th>
                                <th>Filière</th>
                                <th>Date Naissance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($derniersEtudiants)): ?>
                                <tr>
                                    <td colspan="6" class="text-center" style="padding: 30px;">
                                        <i class="fas fa-user-slash" style="font-size: 2rem; color: var(--medium-gray); margin-bottom: 10px; display: block;"></i>
                                        Aucun étudiant trouvé
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($derniersEtudiants as $e): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($e['matricule']) ?></strong></td>
                                    <td>
                                        <strong><?= htmlspecialchars($e['nom']) ?></strong><br>
                                        <small><?= htmlspecialchars($e['prenom']) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($e['sexe'] == 'M'): ?>
                                            <span class="badge badge-male">
                                                <i class="fas fa-male"></i> M
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-female">
                                                <i class="fas fa-female"></i> F
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge badge-primary"><?= $e['niveau'] ?></span></td>
                                    <td><?= htmlspecialchars($e['filiere']) ?></td>
                                    <td>
                                        <?= !empty($e['date_de_naissance']) ? date('d/m/Y', strtotime($e['date_de_naissance'])) : 'N/A' ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Statistiques par filière et département -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
                <!-- Top filières -->
                <div style="background: white; padding: 25px; border-radius: var(--border-radius); box-shadow: var(--shadow-light);">
                    <h3 style="color: var(--primary-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-university"></i> Top 5 Filières
                    </h3>
                    <div style="max-height: 300px; overflow-y: auto;">
                        <?php if (empty($statsFiliere)): ?>
                            <p class="text-center" style="color: var(--medium-gray); padding: 20px;">
                                Aucune donnée disponible
                            </p>
                        <?php else: ?>
                            <?php $topCount = 0; ?>
                            <?php foreach ($statsFiliere as $filiere): ?>
                                <?php if ($topCount++ < 5): ?>
                                <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--light-gray);">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <span style="
                                                background: var(--primary-color);
                                                color: white;
                                                width: 30px;
                                                height: 30px;
                                                border-radius: 50%;
                                                display: flex;
                                                align-items: center;
                                                justify-content: center;
                                                font-weight: bold;
                                            ">
                                                <?= $topCount ?>
                                            </span>
                                            <strong style="flex: 1;"><?= htmlspecialchars($filiere['filiere']) ?></strong>
                                        </div>
                                        <span class="badge badge-primary"><?= $filiere['count'] ?></span>
                                    </div>
                                    <div style="background: var(--light-gray); height: 8px; border-radius: 4px; overflow: hidden;">
                                        <?php 
                                        $maxCount = !empty($statsFiliere[0]['count']) ? $statsFiliere[0]['count'] : 1;
                                        $percentage = ($filiere['count'] / $maxCount) * 100;
                                        ?>
                                        <div style="
                                            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
                                            height: 100%;
                                            width: <?= min(100, $percentage) ?>%;
                                            border-radius: 4px;
                                        "></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Top départements -->
                <div style="background: white; padding: 25px; border-radius: var(--border-radius); box-shadow: var(--shadow-light);">
                    <h3 style="color: var(--primary-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-building"></i> Top 5 Départements
                    </h3>
                    <div style="max-height: 300px; overflow-y: auto;">
                        <?php if (empty($statsDepartement)): ?>
                            <p class="text-center" style="color: var(--medium-gray); padding: 20px;">
                                Aucune donnée disponible
                            </p>
                        <?php else: ?>
                            <?php $topCount = 0; ?>
                            <?php foreach ($statsDepartement as $dep): ?>
                                <?php if ($topCount++ < 5): ?>
                                <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--light-gray);">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <span style="
                                                background: var(--accent-color);
                                                color: white;
                                                width: 30px;
                                                height: 30px;
                                                border-radius: 50%;
                                                display: flex;
                                                align-items: center;
                                                justify-content: center;
                                                font-weight: bold;
                                            ">
                                                <?= $topCount ?>
                                            </span>
                                            <strong style="flex: 1;"><?= htmlspecialchars($dep['departement']) ?></strong>
                                        </div>
                                        <span class="badge badge-info"><?= $dep['count'] ?></span>
                                    </div>
                                    <div style="background: var(--light-gray); height: 8px; border-radius: 4px; overflow: hidden;">
                                        <?php 
                                        $maxCount = !empty($statsDepartement[0]['count']) ? $statsDepartement[0]['count'] : 1;
                                        $percentage = ($dep['count'] / $maxCount) * 100;
                                        ?>
                                        <div style="
                                            background: linear-gradient(135deg, var(--accent-color), #3ab0a8);
                                            height: 100%;
                                            width: <?= min(100, $percentage) ?>%;
                                            border-radius: 4px;
                                        "></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Résumé nationalités -->
            <?php if (!empty($statsNationalite)): ?>
            <div style="background: white; padding: 25px; border-radius: var(--border-radius); box-shadow: var(--shadow-light);">
                <h3 style="color: var(--primary-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-globe-africa"></i> Répartition par Nationalité
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
                    <?php $natCount = 0; ?>
                    <?php foreach ($statsNationalite as $nat): ?>
                        <?php if ($natCount++ < 6): ?>
                        <div style="
                            background: linear-gradient(135deg, #f8fdff, #e6f7ff);
                            padding: 15px;
                            border-radius: var(--border-radius-sm);
                            border-left: 4px solid var(--info-color);
                        ">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                                <strong style="font-size: 0.9rem;"><?= htmlspecialchars($nat['nationalite']) ?></strong>
                                <span style="
                                    background: var(--info-color);
                                    color: white;
                                    padding: 3px 10px;
                                    border-radius: 12px;
                                    font-size: 0.8rem;
                                    font-weight: bold;
                                ">
                                    <?= $nat['count'] ?>
                                </span>
                            </div>
                            <div style="
                                background: rgba(84, 160, 255, 0.2);
                                height: 6px;
                                border-radius: 3px;
                                overflow: hidden;
                            ">
                                <?php 
                                $maxCount = !empty($statsNationalite[0]['count']) ? $statsNationalite[0]['count'] : 1;
                                $percentage = ($nat['count'] / $maxCount) * 100;
                                ?>
                                <div style="
                                    background: var(--info-color);
                                    height: 100%;
                                    width: <?= min(100, $percentage) ?>%;
                                "></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php if (count($statsNationalite) > 6): ?>
                    <div style="text-align: center; margin-top: 15px;">
                        <small style="color: var(--medium-gray);">
                            + <?= count($statsNationalite) - 6 ?> autres nationalités
                        </small>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </main>

        <footer>
            <p>&copy; <?= date('Y') ?> - Gestion des Étudiants | TP_SATECH - Tableau de Bord</p>
            <small style="opacity: 0.7; display: block; margin-top: 5px;">
                Dernière mise à jour : <?= date('d/m/Y à H:i:s') ?>
            </small>
        </footer>
    </div>
</div>

<script>
// Données pour les graphiques
const masculinCount = <?= $statsSexeArray['M'] ?? 0 ?>;
const femininCount = <?= $statsSexeArray['F'] ?? 0 ?>;
const totalEtudiants = <?= $totalEtudiants ?>;

// Graphique de répartition par sexe
const sexeCtx = document.getElementById('sexeChart').getContext('2d');
const sexeChart = new Chart(sexeCtx, {
    type: 'doughnut',
    data: {
        labels: ['Masculin', 'Féminin'],
        datasets: [{
            data: [masculinCount, femininCount],
            backgroundColor: [
                'rgba(86, 235, 225, 0.8)',  // Bleu ciel clair
                'rgba(255, 154, 158, 0.8)'   // Rose clair
            ],
            borderColor: [
                'rgba(86, 235, 225, 1)',
                'rgba(255, 154, 158, 1)'
            ],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    usePointStyle: true,
                    font: {
                        size: 12
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        if (label) {
                            label += ': ';
                        }
                        const value = context.raw;
                        const percentage = totalEtudiants > 0 ? Math.round((value / totalEtudiants) * 100) : 0;
                        return `${label}${value} étudiants (${percentage}%)`;
                    }
                }
            }
        },
        cutout: '65%'
    }
});

// Préparation des données pour le graphique des niveaux
const niveauLabels = [];
const niveauData = [];

<?php foreach($statsNiveau as $n): ?>
niveauLabels.push('<?= $n['niveau'] ?>');
niveauData.push(<?= $n['count'] ?>);
<?php endforeach; ?>

// Graphique de répartition par niveau
const niveauCtx = document.getElementById('niveauChart').getContext('2d');
const niveauChart = new Chart(niveauCtx, {
    type: 'bar',
    data: {
        labels: niveauLabels,
        datasets: [{
            label: 'Nombre d\'étudiants',
            data: niveauData,
            backgroundColor: 'rgba(56, 182, 255, 0.7)',
            borderColor: 'rgba(56, 182, 255, 1)',
            borderWidth: 2,
            borderRadius: 5,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1,
                    precision: 0
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        },
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return `${context.dataset.label}: ${context.raw}`;
                    }
                }
            }
        }
    }
});

// Animation des statistiques
document.addEventListener('DOMContentLoaded', function() {
    // Animer les progress bars
    const progressBars = document.querySelectorAll('.progress-fill');
    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0';
        setTimeout(() => {
            bar.style.transition = 'width 1.5s ease';
            bar.style.width = width;
        }, 500);
    });
    
    // Mise à jour automatique toutes les 30 secondes (optionnel)
    // setTimeout(() => {
    //     window.location.reload();
    // }, 30000);
});
</script>
</body>
</html>