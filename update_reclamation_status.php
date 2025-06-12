<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Vérification de la session
if (!isset($_SESSION['agent_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Session expirée. Veuillez vous reconnecter.'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validation des données
        if (!isset($_POST['id']) || !isset($_POST['status'])) {
            throw new Exception("Données manquantes.");
        }
        
        $reclamationId = (int)$_POST['id'];
        $newStatus = sanitizeInput($_POST['status']);
        
        // Validation du statut
        $validStatuses = ['en-attente', 'en-cours', 'resolue'];
        if (!in_array($newStatus, $validStatuses)) {
            throw new Exception("Statut invalide.");
        }
        
        $pdo = getConnection();
        
        // Vérifier que la réclamation existe
        $checkStmt = $pdo->prepare("SELECT id FROM reclamations WHERE id = ?");
        $checkStmt->execute([$reclamationId]);
        
        if (!$checkStmt->fetch()) {
            throw new Exception("Réclamation introuvable.");
        }
        
        // Mettre à jour le statut
        $updateStmt = $pdo->prepare("
            UPDATE reclamations 
            SET statut = ?, date_modification = NOW() 
            WHERE id = ?
        ");
        
        if ($updateStmt->execute([$newStatus, $reclamationId])) {
            // Log de l'action (optionnel) - REMOVED: No longer attempting to insert into agent_actions
            // $logStmt = $pdo->prepare("
            //     INSERT INTO agent_actions (agent_id, action_type, reclamation_id, details, date_action)
            //     VALUES (?, 'status_update', ?, ?, NOW())
            // ");
            // // This table can be created later for logging
            // // $logStmt->execute([$_SESSION['agent_id'], $reclamationId, "Statut changé vers: $newStatus"]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Statut mis à jour avec succès.',
                'new_status' => $newStatus
            ]);
        } else {
            throw new Exception("Erreur lors de la mise à jour.");
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