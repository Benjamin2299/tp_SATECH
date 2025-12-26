<?php
session_start();
require_once 'config/database.php';

// Récupérer tous les étudiants triés par les 7 derniers chiffres du matricule
$sql = "SELECT E.*, F.intitulé AS filiere_intitule, D.intitulé_Dep AS departement_intitule, 
               N.intitulé_Nat AS nationalite_intitule
        FROM Etudiant E 
        LEFT JOIN `Filière` F ON E.id_filiere = F.id_filiere
        LEFT JOIN `Département` D ON F.id_Dep = D.id_Dep 
        LEFT JOIN `Nationalité` N ON E.code_nationalite = N.code_nationalite
        ORDER BY CAST(SUBSTRING(matricule, -7) AS UNSIGNED) DESC, matricule DESC";

$stmt = $pdo->query($sql);
$etudiants = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Étudiants - Impression</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: white;
            color: #333;
        }
        
        @page {
            size: A4 portrait;
            margin: 15mm;
        }
        
        .print-container {
            max-width: 100%;
        }
        
        .print-header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 3px solid #1a365d;
            padding-bottom: 15px;
        }
        
        .print-header h1 {
            color: #1a365d;
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        
        .print-header .info {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #666;
        }
        
        .print-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
            margin-top: 15px;
        }
        
        .print-table th {
            background-color: #1a365d;
            color: white;
            padding: 10px 8px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 9pt;
        }
        
        .print-table td {
            padding: 8px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        
        .print-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 9pt;
            font-weight: 600;
        }
        
        .badge-male {
            background-color: #dbeafe;
            color: #1e40af;
        }
        
        .badge-female {
            background-color: #fce7f3;
            color: #9d174d;
        }
        
        .badge-level {
            background-color: #e0e7ff;
            color: #3730a3;
        }
        
        .print-footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9pt;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        .summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 20px 0;
            font-size: 10pt;
        }
        
        .summary-item {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }
        
        .summary-value {
            font-size: 14pt;
            font-weight: bold;
            color: #1a365d;
        }
        
        .summary-label {
            font-size: 9pt;
            color: #666;
        }
        
        @media print {
            body {
                padding: 0;
            }
            
            .no-print {
                display: none;
            }
            
            .print-table {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <div class="print-header">
            <h1>📋 Liste des Étudiants</h1>
            <div class="info">
                <div>Date d'édition : <?= date('d/m/Y à H:i') ?></div>
                <div>Total : <?= count($etudiants) ?> étudiants</div>
                <div>Page : 1/1</div>
            </div>
        </div>
        
        <div class="summary">
            <div class="summary-item">
                <div class="summary-value"><?= count($etudiants) ?></div>
                <div class="summary-label">Total Étudiants</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">
                    <?= count(array_filter($etudiants, fn($e) => $e['sexe'] == 'M')) ?>
                </div>
                <div class="summary-label">Masculin</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">
                    <?= count(array_filter($etudiants, fn($e) => $e['sexe'] == 'F')) ?>
                </div>
                <div class="summary-label">Féminin</div>
            </div>
        </div>
        
        <table class="print-table">
            <thead>
                <tr>
                    <th width="12%">Matricule</th>
                    <th width="15%">Nom</th>
                    <th width="15%">Prénom</th>
                    <th width="8%">Sexe</th>
                    <th width="10%">Naissance</th>
                    <th width="15%">Filière</th>
                    <th width="8%">Niveau</th>
                    <th width="15%">Nationalité</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($etudiants)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 20px;">
                            Aucun étudiant à afficher
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($etudiants as $e): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($e['matricule']) ?></strong></td>
                        <td><?= htmlspecialchars($e['nom']) ?></td>
                        <td><?= htmlspecialchars($e['prenom']) ?></td>
                        <td>
                            <span class="badge badge-<?= $e['sexe'] == 'M' ? 'male' : 'female' ?>">
                                <?= $e['sexe'] == 'M' ? '♂' : '♀' ?>
                            </span>
                        </td>
                        <td>
                            <?= !empty($e['date_de_naissance']) 
                                ? date('d/m/Y', strtotime($e['date_de_naissance'])) 
                                : '' ?>
                        </td>
                        <td><?= htmlspecialchars($e['filiere_intitule'] ?? 'N/A') ?></td>
                        <td>
                            <span class="badge badge-level"><?= htmlspecialchars($e['niveau']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($e['nationalite_intitule'] ?? 'N/A') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="print-footer">
            <p>Gestion des Étudiants - TP_SATECH &copy; <?= date('Y') ?></p>
            <p>Document généré le <?= date('d/m/Y à H:i:s') ?></p>
        </div>
        
        <div class="no-print" style="text-align: center; margin-top: 30px;">
            <button onclick="window.print()" style="
                background: #1a365d;
                color: white;
                border: none;
                padding: 12px 24px;
                border-radius: 5px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 600;
            ">
                <i class="fas fa-print"></i> Imprimer
            </button>
            <button onclick="window.close()" style="
                background: #666;
                color: white;
                border: none;
                padding: 12px 24px;
                border-radius: 5px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 600;
                margin-left: 10px;
            ">
                <i class="fas fa-times"></i> Fermer
            </button>
        </div>
    </div>
    
    <script>
        // Impression automatique optionnelle
        window.onload = function() {
            // Auto-print après 1 seconde (optionnel)
            // setTimeout(() => window.print(), 1000);
        };
    </script>
</body>
</html>