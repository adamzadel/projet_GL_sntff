-- Création de la base de données SNTF
CREATE DATABASE IF NOT EXISTS sntf_reclamations;
USE sntf_reclamations;

-- Création de la table des réclamations
CREATE TABLE reclamations (
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
);

-- Création de la table des agents SNTF
CREATE TABLE agents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nom_complet VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    statut ENUM('actif', 'inactif') DEFAULT 'actif',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    derniere_connexion TIMESTAMP NULL
);

-- Insertion des agents par défaut (mot de passe: 'admin123' hashé)
INSERT INTO agents (username, password, nom_complet, email) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrateur SNTF', 'admin@sntf.dz'),
('agent1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ahmed Benali', 'ahmed.benali@sntf.dz'),
('agent2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Fatima Zohra', 'fatima.zohra@sntf.dz');

-- Insertion de quelques données de test pour les réclamations
INSERT INTO reclamations (numero_reference, nom, email, telephone, ligne, categorie, priorite, date_incident, description, statut) VALUES
('REC-2024-001', 'Ahmed Benali', 'ahmed.benali@email.com', '0555123456', 'alger-oran', 'retard', 'elevee', '2024-05-25', 'Retard de 2 heures sur le train Alger-Oran du matin sans aucune information.', 'en-attente'),
('REC-2024-002', 'Fatima Zohra', 'fatima.z@email.com', '0661234567', 'alger-constantine', 'proprete', 'moyenne', '2024-05-24', 'Toilettes en très mauvais état dans le train de 14h.', 'en-cours'),
('REC-2024-003', 'Mohamed Larbi', 'mohamed.larbi@email.com', '0777891234', 'oran-tlemcen', 'personnel', 'elevee', '2024-05-23', 'Comportement inapproprié du contrôleur envers les passagers.', 'resolue');

-- Index pour améliorer les performances
CREATE INDEX idx_statut ON reclamations(statut);
CREATE INDEX idx_categorie ON reclamations(categorie);
CREATE INDEX idx_priorite ON reclamations(priorite);
CREATE INDEX idx_date_creation ON reclamations(date_creation);
CREATE INDEX idx_agent_username ON agents(username);

-- Note: Le mot de passe par défaut pour tous les agents est 'admin123'
-- Il est recommandé de changer ces mots de passe après la première connexion