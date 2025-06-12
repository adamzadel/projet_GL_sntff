<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];
        
        // Validation des données
        if (empty($username) || empty($password)) {
            throw new Exception("Nom d'utilisateur et mot de passe requis.");
        }
        
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT id, username, password, nom_complet, email FROM agents WHERE username = ? AND statut = 'actif'");
        $stmt->execute([$username]);
        $agent = $stmt->fetch();
        
        if ($agent && password_verify($password, $agent['password'])) {
            // Connexion réussie
            $_SESSION['agent_id'] = $agent['id'];
            $_SESSION['agent_username'] = $agent['username'];
            $_SESSION['agent_nom'] = $agent['nom_complet'];
            $_SESSION['agent_email'] = $agent['email'];
            
            // Mise à jour de la dernière connexion
            $updateStmt = $pdo->prepare("UPDATE agents SET derniere_connexion = NOW() WHERE id = ?");
            $updateStmt->execute([$agent['id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Connexion réussie',
                'agent' => [
                    'nom' => $agent['nom_complet'],
                    'username' => $agent['username']
                ]
            ]);
        } else {
            throw new Exception("Nom d'utilisateur ou mot de passe incorrect.");
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