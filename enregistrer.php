<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $sexe = $_POST['sexe'] ?? '';
    $date_naissance = $_POST['date_naissance'] ?? '';
    $id_filiere = $_POST['id_filiere'] ?? '';
    $niveau = $_POST['niveau'] ?? '';
    $code_nationalite = $_POST['code_nationalite'] ?? '';
    
    $errors = [];
    
    if (empty($nom) || strlen($nom) < 2) {
        $errors[] = "Le nom doit contenir au moins 2 caractères.";
    }
    
    if (empty($prenom) || strlen($prenom) < 2) {
        $errors[] = "Le prénom doit contenir au moins 2 caractères.";
    }
    
    if (!in_array($sexe, ['M', 'F'])) {
        $errors[] = "Le sexe doit être M ou F.";
    }
    
    if (empty($date_naissance)) {
        $errors[] = "La date de naissance est requise.";
    } else {
        $birthDate = new DateTime($date_naissance);
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;
        
        if ($age < 16) {
            $errors[] = "L'étudiant doit avoir au moins 16 ans.";
        }
    }
    
    if (empty($id_filiere) || !is_numeric($id_filiere)) {
        $errors[] = "Une filière valide doit être sélectionnée.";
    }
    
    if (empty($niveau)) {
        $errors[] = "Le niveau est requis.";
    }
    
    if (empty($code_nationalite)) {
        $errors[] = "La nationalité est requise.";
    }
    
    if (empty($errors)) {
        try {
            // Récupérer les informations de la filière et du département
            $stmt = $pdo->prepare("
                SELECT F.intitulé as filiere_nom, 
                       D.intitulé_Dep as departement_nom
                FROM `Filière` F 
                LEFT JOIN `Département` D ON F.id_Dep = D.id_Dep 
                WHERE F.id_filiere = ?
            ");
            $stmt->execute([$id_filiere]);
            $filiere = $stmt->fetch();
            
            if (!$filiere) {
                throw new Exception("Filière non trouvée");
            }
            
            $departement = $filiere['departement_nom'] ?? 'GENERAL';
            
            // ========== NOUVEAU FORMAT DU MATRICULE ==========
            // Format: UNIV-XXX-YY-NNNNN
            // Où: XXX = 3 premières lettres du département
            //      YY = 2 derniers chiffres de l'année
            //      NNNNN = nombre auto-incrémenté GLOBAL sur 5 chiffres
            
            // 1. Extraire les 3 premières lettres du département (en majuscule)
            $codeDep = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $departement), 0, 3));
            if (strlen($codeDep) < 3) {
                $codeDep = str_pad($codeDep, 3, 'X');
            }
            
            // 2. Les 2 derniers chiffres de l'année
            $annee = date('y'); // Format '24' pour 2024
            
            // ========== MODIFICATION IMPORTANTE ==========
            // 3. Trouver le dernier numéro auto-incrémenté GLOBAL (tous départements confondus)
            $stmt = $pdo->prepare("
                SELECT MAX(
                    CAST(
                        SUBSTRING(matricule, -5) 
                        AS UNSIGNED
                    )
                ) as global_last_num
                FROM Etudiant 
                WHERE matricule LIKE ?
            ");
            
            // Chercher tous les matricules de l'année courante
            $patternAnnee = "UNIV-%-{$annee}-%";
            $stmt->execute([$patternAnnee]);
            $result = $stmt->fetch();
            
            $globalLastNum = $result['global_last_num'] ?? 0;
            $nextGlobalNum = $globalLastNum + 1;
            
            // Si le numéro dépasse 99999, on recommence à 1
            if ($nextGlobalNum > 99999) {
                // Chercher le plus petit numéro non utilisé
                $nextGlobalNum = 1;
                for ($i = 1; $i <= 99999; $i++) {
                    $checkNumStmt = $pdo->prepare("
                        SELECT COUNT(*) as count 
                        FROM Etudiant 
                        WHERE CAST(SUBSTRING(matricule, -5) AS UNSIGNED) = ?
                        AND matricule LIKE ?
                    ");
                    $checkNumStmt->execute([$i, $patternAnnee]);
                    $exists = $checkNumStmt->fetch()['count'] > 0;
                    
                    if (!$exists) {
                        $nextGlobalNum = $i;
                        break;
                    }
                }
            }
            
            // 4. Formater le numéro sur 5 chiffres
            $formattedNum = str_pad($nextGlobalNum, 5, '0', STR_PAD_LEFT);
            
            // 5. Construire le matricule final
            $matricule = "UNIV-{$codeDep}-{$annee}-{$formattedNum}";
            
            // ========== VÉRIFICATION D'UNICITÉ ==========
            
            // Vérifier que le matricule n'existe pas déjà
            $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM Etudiant WHERE matricule = ?");
            $checkStmt->execute([$matricule]);
            $exists = $checkStmt->fetch()['count'] > 0;
            
            $matriculeUnique = false;
            $tentatives = 0;
            
            if ($exists) {
                // Si le matricule existe déjà, on cherche le prochain numéro disponible
                for ($i = 1; $i <= 100; $i++) {
                    $tentatives++;
                    $nextTryNum = $globalLastNum + $i;
                    $formattedNum = str_pad($nextTryNum, 5, '0', STR_PAD_LEFT);
                    $matriculeTest = "UNIV-{$codeDep}-{$annee}-{$formattedNum}";
                    
                    $checkStmt->execute([$matriculeTest]);
                    $exists = $checkStmt->fetch()['count'] > 0;
                    
                    if (!$exists) {
                        $matricule = $matriculeTest;
                        $matriculeUnique = true;
                        break;
                    }
                }
                
                // Si toujours pas trouvé, essayer avec des numéros aléatoires
                if (!$matriculeUnique) {
                    for ($j = 0; $j < 50; $j++) {
                        $tentatives++;
                        $randomNum = mt_rand(1, 99999);
                        $formattedNum = str_pad($randomNum, 5, '0', STR_PAD_LEFT);
                        $matriculeTest = "UNIV-{$codeDep}-{$annee}-{$formattedNum}";
                        
                        $checkStmt->execute([$matriculeTest]);
                        $exists = $checkStmt->fetch()['count'] > 0;
                        
                        if (!$exists) {
                            $matricule = $matriculeTest;
                            $matriculeUnique = true;
                            break;
                        }
                    }
                }
                
                if (!$matriculeUnique) {
                    throw new Exception("Impossible de générer un matricule unique après $tentatives tentatives");
                }
            } else {
                $matriculeUnique = true;
            }
            
            // ========== DEBUG: Afficher la construction ==========
            error_log("=== GÉNÉRATION MATRICULE GLOBAL ===");
            error_log("Département: $departement");
            error_log("Code département: $codeDep");
            error_log("Année: $annee");
            error_log("Dernier numéro global: $globalLastNum");
            error_log("Prochain numéro global: $nextGlobalNum");
            error_log("Matricule généré: $matricule");
            error_log("Pattern utilisé: $patternAnnee");
            error_log("Matricule unique: " . ($matriculeUnique ? "OUI" : "NON"));
            
            // ========== INSÉRER L'ÉTUDIANT ==========
            
            if ($matriculeUnique) {
                $sql = "INSERT INTO Etudiant (matricule, nom, prenom, sexe, date_de_naissance, niveau, id_filiere, code_nationalite) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $matricule,
                    $nom,
                    $prenom,
                    $sexe,
                    $date_naissance,
                    $niveau,
                    $id_filiere,
                    $code_nationalite
                ]);
                
                $_SESSION['success_message'] = "Étudiant $nom $prenom ajouté avec succès. Matricule: $matricule";
                header("Location: liste_etudiants.php");
                exit;
            } else {
                throw new Exception("Matricule non unique après vérification");
            }
            
        } catch (PDOException $e) {
            // Vérifier si c'est une erreur de duplication
            if (strpos($e->getMessage(), 'Duplicate entry') !== false || 
                strpos($e->getMessage(), '1062') !== false ||
                strpos($e->getMessage(), '23000') !== false) {
                
                $_SESSION['error_message'] = "Erreur: Ce matricule existe déjà. Veuillez réessayer.";
                header("Location: liste_etudiants.php");
                exit;
                
            } else {
                $_SESSION['error_message'] = "Erreur de base de données : " . $e->getMessage();
                header("Location: liste_etudiants.php");
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
            header("Location: liste_etudiants.php");
            exit;
        }
    } else {
        $_SESSION['error_message'] = implode("<br>", $errors);
        header("Location: liste_etudiants.php");
        exit;
    }
} else {
    header("Location: liste_etudiants.php");
    exit;
}