<?php
require_once 'config.php';
requireAgentLogin();

// Check if user is admin
if ($_SESSION['agent_username'] !== 'admin') {
    header('Location: agent_dashboard.php');
    exit();
}

$agent_id = $_SESSION['agent_id'];
$agent_username = $_SESSION['agent_username'];
$agent_nom = $_SESSION['agent_nom'];

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getConnection();
        
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_agent':
                    $username = sanitizeInput($_POST['username']);
                    $password = $_POST['password'];
                    $nom_complet = sanitizeInput($_POST['nom_complet']);
                    $email = sanitizeInput($_POST['email']);
                    
                    // Validate inputs
                    if (empty($username) || empty($password) || empty($nom_complet) || empty($email)) {
                        throw new Exception("Tous les champs sont obligatoires.");
                    }
                    
                    if (strlen($password) < 6) {
                        throw new Exception("Le mot de passe doit contenir au moins 6 caractères.");
                    }
                    
                    // Check if username already exists
                    $checkStmt = $pdo->prepare("SELECT id FROM agents WHERE username = ?");
                    $checkStmt->execute([$username]);
                    if ($checkStmt->fetch()) {
                        throw new Exception("Ce nom d'utilisateur existe déjà.");
                    }
                    
                    // Hash password and insert
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO agents (username, password, nom_complet, email, statut) 
                        VALUES (?, ?, ?, ?, 'actif')
                    ");
                    
                    if ($stmt->execute([$username, $hashedPassword, $nom_complet, $email])) {
                        $message = "Agent ajouté avec succès.";
                        $messageType = "success";
                    } else {
                        throw new Exception("Erreur lors de l'ajout de l'agent.");
                    }
                    break;
                    
                case 'update_status':
                    $agentId = (int)$_POST['agent_id'];
                    $newStatus = sanitizeInput($_POST['status']);
                    
                    if (!in_array($newStatus, ['actif', 'inactif'])) {
                        throw new Exception("Statut invalide.");
                    }
                    
                    $stmt = $pdo->prepare("UPDATE agents SET statut = ? WHERE id = ? AND id != ?");
                    if ($stmt->execute([$newStatus, $agentId, $_SESSION['agent_id']])) {
                        $message = "Statut mis à jour avec succès.";
                        $messageType = "success";
                    } else {
                        throw new Exception("Erreur lors de la mise à jour du statut.");
                    }
                    break;
                    
                case 'delete_agent':
                    $agentId = (int)$_POST['agent_id'];
                    
                    // Don't allow deletion of current admin
                    if ($agentId == $_SESSION['agent_id']) {
                        throw new Exception("Vous ne pouvez pas supprimer votre propre compte.");
                    }
                    
                    $stmt = $pdo->prepare("DELETE FROM agents WHERE id = ? AND id != ?");
                    if ($stmt->execute([$agentId, $_SESSION['agent_id']])) {
                        $message = "Agent supprimé avec succès.";
                        $messageType = "success";
                    } else {
                        throw new Exception("Erreur lors de la suppression de l'agent.");
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = "error";
    }
}

// Fetch all agents
try {
    $pdo = getConnection();
    $stmt = $pdo->query("
        SELECT id, username, nom_complet, email, statut, date_creation, derniere_connexion
        FROM agents 
        ORDER BY date_creation DESC
    ");
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching agents: " . $e->getMessage());
    $agents = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SNTF - Gérer les Agents</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(45deg, #1e3c72, #2a5298);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }

        .back-btn, .logout-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            margin-left: 10px;
        }

        .back-btn {
            background: #6c757d;
        }

        .back-btn:hover {
            background: #5a6268;
        }

        .logout-btn:hover {
            background: #c82333;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            color: #1e3c72;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 5px;
            font-weight: 500;
            color: #1e3c72;
        }

        .form-group input, .form-group select {
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #2a5298;
        }

        .submit-btn {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(40, 167, 69, 0.3);
        }

        .agents-table {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #1e3c72;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-actif {
            background: #d4edda;
            color: #155724;
        }

        .status-inactif {
            background: #f8d7da;
            color: #721c24;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            margin-right: 5px;
            transition: all 0.3s ease;
        }

        .btn-toggle {
            background: #ffc107;
            color: #212529;
        }

        .btn-toggle:hover {
            background: #e0a800;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .no-agents {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 400px;
            text-align: center;
        }

        .modal-buttons {
            margin-top: 20px;
        }

        .modal-btn {
            padding: 10px 20px;
            margin: 0 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
        }

        .modal-btn.confirm {
            background: #dc3545;
            color: white;
        }

        .modal-btn.cancel {
            background: #6c757d;
            color: white;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="logo">
                <div class="logo-icon">SNTF</div>
                <h1>Gérer les Agents</h1>
            </div>
            <div>
                <a href="agent_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Retour</a>
                <a href="logout.php" class="logout-btn">Déconnexion <i class="fas fa-sign-out-alt"></i></a>
            </div>
        </header>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Add New Agent Form -->
        <div class="form-section">
            <h3 class="section-title">
                <i class="fas fa-user-plus"></i>
                Ajouter un Nouvel Agent
            </h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_agent">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="username">Nom d'utilisateur</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label for="nom_complet">Nom complet</label>
                        <input type="text" id="nom_complet" name="nom_complet" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>
                <button type="submit" class="submit-btn">
                    <i class="fas fa-plus"></i> Ajouter l'Agent
                </button>
            </form>
        </div>

        <!-- Agents List -->
        <div class="agents-table">
            <h3 class="section-title">
                <i class="fas fa-users"></i>
                Liste des Agents (<?php echo count($agents); ?>)
            </h3>
            
            <?php if (empty($agents)): ?>
                <div class="no-agents">
                    <i class="fas fa-users" style="font-size: 48px; color: #dee2e6; margin-bottom: 15px;"></i>
                    <p>Aucun agent trouvé.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom d'utilisateur</th>
                            <th>Nom complet</th>
                            <th>Email</th>
                            <th>Statut</th>
                            <th>Date création</th>
                            <th>Dernière connexion</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agents as $agent): ?>
                            <tr>
                                <td><?php echo $agent['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($agent['username']); ?>
                                    <?php if ($agent['id'] == $_SESSION['agent_id']): ?>
                                        <span style="color: #007bff; font-size: 12px;">(Vous)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($agent['nom_complet']); ?></td>
                                <td><?php echo htmlspecialchars($agent['email']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $agent['statut']; ?>">
                                        <?php echo ucfirst($agent['statut']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($agent['date_creation'])); ?></td>
                                <td>
                                    <?php if ($agent['derniere_connexion']): ?>
                                        <?php echo date('d/m/Y H:i', strtotime($agent['derniere_connexion'])); ?>
                                    <?php else: ?>
                                        <span style="color: #6c757d;">Jamais</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($agent['id'] != $_SESSION['agent_id']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="agent_id" value="<?php echo $agent['id']; ?>">
                                            <input type="hidden" name="status" value="<?php echo $agent['statut'] === 'actif' ? 'inactif' : 'actif'; ?>">
                                            <button type="submit" class="action-btn btn-toggle" title="Changer le statut">
                                                <i class="fas fa-toggle-<?php echo $agent['statut'] === 'actif' ? 'on' : 'off'; ?>"></i>
                                            </button>
                                        </form>
                                        <button class="action-btn btn-delete" onclick="confirmDelete(<?php echo $agent['id']; ?>, '<?php echo htmlspecialchars($agent['nom_complet']); ?>')" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php else: ?>
                                        <span style="color: #6c757d; font-size: 12px;">Admin actuel</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h4>Confirmer la suppression</h4>
            <p id="deleteMessage"></p>
            <div class="modal-buttons">
                <button class="modal-btn cancel" onclick="closeDeleteModal()">Annuler</button>
                <button class="modal-btn confirm" onclick="deleteAgent()">Supprimer</button>
            </div>
        </div>
    </div>

    <script>
        let agentToDelete = null;

        function confirmDelete(agentId, agentName) {
            agentToDelete = agentId;
            document.getElementById('deleteMessage').textContent = 
                `Êtes-vous sûr de vouloir supprimer l'agent "${agentName}" ? Cette action est irréversible.`;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            agentToDelete = null;
        }

        function deleteAgent() {
            if (agentToDelete) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_agent">
                    <input type="hidden" name="agent_id" value="${agentToDelete}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                closeDeleteModal();
            }
        }
    </script>
</body>
</html>