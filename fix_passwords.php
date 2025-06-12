<?php
/**
 * Script pour corriger les mots de passe des agents
 * Exécuter ce script une seule fois pour corriger les hashes de mots de passe
 */

require_once 'config.php';

try {
    $pdo = getConnection();
    
    echo "🔐 Correction des mots de passe des agents...\n";
    
    // Mot de passe par défaut
    $defaultPassword = 'admin123';
    $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);
    
    echo "Hash généré: " . $hashedPassword . "\n";
    
    // Mise à jour de tous les agents avec le nouveau hash
    $stmt = $pdo->prepare("UPDATE agents SET password = ? WHERE username IN ('admin', 'agent1', 'agent2')");
    $result = $stmt->execute([$hashedPassword]);
    
    if ($result) {
        $affectedRows = $stmt->rowCount();
        echo "✅ $affectedRows agents mis à jour avec succès!\n";
        echo "Vous pouvez maintenant vous connecter avec:\n";
        echo "   - admin / admin123\n";
        echo "   - agent1 / admin123\n";
        echo "   - agent2 / admin123\n";
    } else {
        echo "❌ Erreur lors de la mise à jour des mots de passe.\n";
    }
    
    // Vérification des agents dans la base
    echo "\n📋 Vérification des agents dans la base de données:\n";
    $stmt = $pdo->query("SELECT id, username, nom_complet, email, statut FROM agents");
    $agents = $stmt->fetchAll();
    
    foreach ($agents as $agent) {
        echo sprintf("ID: %d | Username: %s | Nom: %s | Email: %s | Statut: %s\n", 
            $agent['id'], 
            $agent['username'], 
            $agent['nom_complet'], 
            $agent['email'], 
            $agent['statut']
        );
    }
    
    // Test de vérification du mot de passe
    echo "\n🧪 Test de vérification du mot de passe:\n";
    $testResult = password_verify('admin123', $hashedPassword);
    echo "Vérification du mot de passe 'admin123': " . ($testResult ? "✅ SUCCÈS" : "❌ ÉCHEC") . "\n";
    
} catch (PDOException $e) {
    echo "❌ Erreur de base de données: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n🔗 Accès à l'application:\n";
echo "Connexion agents: http://localhost/GL/agent_login.php\n";
?>