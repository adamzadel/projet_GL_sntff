<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validation des données requises
        $requiredFields = ['nom', 'email', 'ligne', 'categorie', 'description'];
        foreach ($requiredFields as $field) {
            if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
                throw new Exception("Le champ '$field' est requis.");
            }
        }
        
        // Nettoyage et validation des données
        $nom = sanitizeInput($_POST['nom']);
        $email = sanitizeInput($_POST['email']);
        $telephone = isset($_POST['telephone']) ? sanitizeInput($_POST['telephone']) : null;
        $ligne = sanitizeInput($_POST['ligne']);
        $categorie = sanitizeInput($_POST['categorie']);
        $priorite = isset($_POST['priorite']) ? sanitizeInput($_POST['priorite']) : 'moyenne';
        $dateIncident = isset($_POST['date-incident']) && !empty($_POST['date-incident']) ? $_POST['date-incident'] : null;
        $description = sanitizeInput($_POST['description']);
        
        // Validation de l'email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Format d'email invalide.");
        }
        
        // Validation des valeurs enum
        $validLignes = ['alger-oran', 'alger-constantine', 'alger-annaba', 'oran-tlemcen', 'constantine-touggourt', 'autre'];
        if (!in_array($ligne, $validLignes)) {
            throw new Exception("Ligne ferroviaire invalide.");
        }
        
        $validCategories = ['retard', 'personnel', 'proprete', 'billetterie', 'equipement', 'securite', 'autre'];
        if (!in_array($categorie, $validCategories)) {
            throw new Exception("Catégorie invalide.");
        }
        
        $validPriorites = ['faible', 'moyenne', 'elevee'];
        if (!in_array($priorite, $validPriorites)) {
            throw new Exception("Priorité invalide.");
        }
        
        // Validation de la date d'incident
        if ($dateIncident) {
            $today = date('Y-m-d');
            if ($dateIncident > $today) {
                throw new Exception("La date d'incident ne peut pas être dans le futur.");
            }
        }
        
        // Génération du numéro de référence unique
        $numeroReference = generateReferenceNumber();
        
        // Connexion à la base de données
        $pdo = getConnection();
        
        // Vérification de l'unicité du numéro de référence
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM reclamations WHERE numero_reference = ?");
        $checkStmt->execute([$numeroReference]);
        
        // Si le numéro existe déjà, générer un nouveau
        while ($checkStmt->fetchColumn() > 0) {
            $numeroReference = generateReferenceNumber();
            $checkStmt->execute([$numeroReference]);
        }
        
        // Insertion de la réclamation
        $insertStmt = $pdo->prepare("
            INSERT INTO reclamations (
                numero_reference, nom, email, telephone, ligne, categorie, 
                priorite, date_incident, description, statut, date_creation
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'en-attente', NOW())
        ");
        
        $result = $insertStmt->execute([
            $numeroReference,
            $nom,
            $email,
            $telephone,
            $ligne,
            $categorie,
            $priorite,
            $dateIncident,
            $description
        ]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Votre réclamation a été soumise avec succès.',
                'reference' => $numeroReference
            ]);
        } else {
            throw new Exception("Erreur lors de l'enregistrement de la réclamation.");
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée.'
    ]);
}
?>