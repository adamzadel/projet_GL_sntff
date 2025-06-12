<?php
session_start();
require_once 'config.php';

// Si l'agent est déjà connecté, rediriger vers le dashboard
if (isset($_SESSION['agent_id'])) {
    header('Location: agent_dashboard.php');
    exit();
}

$error_message = '';

// Traitement du formulaire de connexion
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
            
            // Redirection vers le dashboard
            header('Location: agent_dashboard.php');
            exit();
        } else {
            throw new Exception("Nom d'utilisateur ou mot de passe incorrect.");
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SNTF - Connexion Agent</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .logo {
            margin-bottom: 30px;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, #1e3c72, #2a5298);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 32px;
            margin: 0 auto 15px;
        }

        h1 {
            color: #1e3c72;
            margin-bottom: 10px;
            font-size: 24px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: #2a5298;
            box-shadow: 0 0 0 3px rgba(42, 82, 152, 0.1);
        }

        .btn {
            background: linear-gradient(45deg, #1e3c72, #2a5298);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(30, 60, 114, 0.3);
            width: 100%;
            margin-top: 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(30, 60, 114, 0.4);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid;
            text-align: center;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #666;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: #2a5298;
        }

        .demo-credentials {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }

        .demo-credentials h4 {
            color: #333;
            margin-bottom: 10px;
        }

        .demo-account {
            display: inline-block;
            background: #e9ecef;
            padding: 5px 10px;
            margin: 2px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .demo-account:hover {
            background: #dee2e6;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <div class="logo-icon">SNTF</div>
            <h1>Connexion Agent</h1>
            <p class="subtitle">Société Nationale des Transports Ferroviaires</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="message error">❌ <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">🔐 Se connecter</button>
        </form>

        <div class="demo-credentials">
            <h4>Comptes de démonstration :</h4>
            <div class="demo-account" onclick="fillCredentials('admin', 'admin123')">admin / admin123</div>
            <div class="demo-account" onclick="fillCredentials('agent1', 'admin123')">agent1 / admin123</div>
            <div class="demo-account" onclick="fillCredentials('agent2', 'admin123')">agent2 / admin123</div>
            <p style="margin-top: 10px; font-style: italic;">Cliquez sur un compte pour auto-remplir</p>
        </div>

        <a href="index.php" class="back-link">← Retour à l'accueil</a>
    </div>

    <script>
        // Fonction pour remplir automatiquement les identifiants
        function fillCredentials(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
        }

        // Auto-focus sur le champ username
        document.getElementById('username').focus();
    </script>
</body>
</html>