<?php
/**
 * Script de configuration de la base de données SNTF
 * Exécuter ce script une seule fois pour initialiser la base de données
 */

// Configuration de la connexion (modifiez selon votre configuration)
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'sntf_reclamations';

try {
    // Connexion sans base de données pour la créer
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "📡 Connexion au serveur MySQL réussie.\n";
    
    // Création de la base de données
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $database");
    echo "🏗️ Base de données '$database' créée ou existe déjà.\n";
    
    // Sélection de la base de données
    $pdo->exec("USE $database");
    
    // Création de la table des réclamations
    $createReclamationsTable = "
        CREATE TABLE IF NOT EXISTS reclamations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            numero_reference VARCHAR(20) UNIQUE NOT NULL,
            nom VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL,
            telephone VARCHAR(20),
            ligne VARCHAR(50) NOT NULL,
            categorie VARCHAR(50) NOT NULL,
            priorite ENUM('faible', 'moyenne', 'elevee') DEFAULT 'moyenne',
            date_incident DATE,
            description TEXT NOT NULL,
            statut ENUM('en-attente', 'en-cours', 'resolue') DEFAULT 'en-attente',
            date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ";
    $pdo->exec($createReclamationsTable);
    echo "📋 Table 'reclamations' créée avec succès.\n";
    
    // Création de la table des agents
    $createAgentsTable = "
        CREATE TABLE IF NOT EXISTS agents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            nom_complet VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL,
            statut ENUM('actif', 'inactif') DEFAULT 'actif',
            date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            derniere_connexion TIMESTAMP NULL
        )
    ";
    $pdo->exec($createAgentsTable);
    echo "👥 Table 'agents' créée avec succès.\n";
    
    // Vérification si des agents existent déjà
    $checkAgents = $pdo->query("SELECT COUNT(*) FROM agents");
    $agentCount = $checkAgents->fetchColumn();
    
    if ($agentCount == 0) {
        // Insertion des agents par défaut (mot de passe: 'admin123')
		echo password_hash('admin123', PASSWORD_DEFAULT);
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        
        $insertAgents = "
            INSERT INTO agents (username, password, nom_complet, email) VALUES
            ('admin', ?, 'Administrateur SNTF', 'admin@sntf.dz'),
            ('agent1', ?, 'Ahmed Benali', 'ahmed.benali@sntf.dz'),
            ('agent2', ?, 'Fatima Zohra', 'fatima.zohra@sntf.dz')
        ";
        
        $stmt = $pdo->prepare($insertAgents);
        $stmt->execute([$hashedPassword, $hashedPassword, $hashedPassword]);
        echo "🔐 Agents par défaut créés avec succès.\n";
        echo "   - admin / admin123\n";
        echo "   - agent1 / admin123\n";
        echo "   - agent2 / admin123\n";
    } else {
        echo "👤 Des agents existent déjà dans la base de données.\n";
    }
    
    // Création des index pour améliorer les performances
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_statut ON reclamations(statut)",
        "CREATE INDEX IF NOT EXISTS idx_categorie ON reclamations(categorie)",
        "CREATE INDEX IF NOT EXISTS idx_priorite ON reclamations(priorite)",
        "CREATE INDEX IF NOT EXISTS idx_date_creation ON reclamations(date_creation)",
        "CREATE INDEX IF NOT EXISTS idx_agent_username ON agents(username)"
    ];
    
    foreach ($indexes as $index) {
        $pdo->exec($index);
    }
    echo "📊 Index créés pour améliorer les performances.\n";
    
    // Insertion de données de test (optionnel)
    $checkReclamations = $pdo->query("SELECT COUNT(*) FROM reclamations");
    $reclamationCount = $checkReclamations->fetchColumn();
    
    if ($reclamationCount == 0) {
        $insertTestData = "
            INSERT INTO reclamations (numero_reference, nom, email, telephone, ligne, categorie, priorite, date_incident, description, statut) VALUES
            ('REC-20241201-001', 'Ahmed Benali', 'ahmed.benali@email.com', '0555123456', 'alger-oran', 'retard', 'elevee', '2024-05-25', 'Retard de 2 heures sur le train Alger-Oran du matin sans aucune information.', 'en-attente'),
            ('REC-20241201-002', 'Fatima Zohra', 'fatima.z@email.com', '0661234567', 'alger-constantine', 'proprete', 'moyenne', '2024-05-24', 'Toilettes en très mauvais état dans le train de 14h.', 'en-cours'),
            ('REC-20241201-003', 'Mohamed Larbi', 'mohamed.larbi@email.com', '0777891234', 'oran-tlemcen', 'personnel', 'elevee', '2024-05-23', 'Comportement inapproprié du contrôleur envers les passagers.', 'resolue')
        ";
        $pdo->exec($insertTestData);
        echo "🧪 Données de test insérées avec succès.\n";
    }
    
    echo "\n✅ Configuration de la base de données terminée avec succès!\n";
    echo "🌐 Vous pouvez maintenant accéder à l'application:\n";
    echo "   - Passagers: http://localhost/sntf/passenger_page.html\n";
    echo "   - Agents: http://localhost/sntf/agent_login.php\n";
    
} catch (PDOException $e) {
    echo "❌ Erreur de base de données: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
?>