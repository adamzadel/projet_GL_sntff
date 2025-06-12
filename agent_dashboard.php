<?php
// Include the configuration file, which handles session_start() and other functions
require_once 'config.php';

// Require the agent to be logged in. If not, this function redirects to agent_login.php and exits.
requireAgentLogin();

// If the script reaches this point, the agent is successfully logged in.
// You can now access agent information from the session:
$agent_id = $_SESSION['agent_id'];
$agent_username = $_SESSION['agent_username'];
$agent_nom = $_SESSION['agent_nom'];
$agent_email = $_SESSION['agent_email'];

// You might also want to log this access
logAction('Agent Dashboard Access', 'Agent ' . $agent_username . ' accessed the dashboard.');

// This is where you would fetch data for the dashboard, e.g.,
// - List of pending reclamations
// - Statistics
// - Agent-specific tasks

$totalReclamations = 'N/A';
$pendingReclamations = 'N/A';
$resolvedReclamations = 'N/A';
$highPriorityReclamations = 'N/A';

try {
    $pdo = getConnection();

    // Fetch total number of reclamations
    $stmt = $pdo->query("SELECT COUNT(*) AS total_reclamations FROM reclamations");
    $totalReclamations = $stmt->fetchColumn();

    // Fetch number of pending reclamations
    $stmt = $pdo->prepare("SELECT COUNT(*) AS pending_reclamations FROM reclamations WHERE statut = ?");
    $stmt->execute(['en-attente']);
    $pendingReclamations = $stmt->fetchColumn();

    // Fetch number of resolved reclamations
    $stmt = $pdo->prepare("SELECT COUNT(*) AS resolved_reclamations FROM reclamations WHERE statut = ?");
    $stmt->execute(['resolue']);
    $resolvedReclamations = $stmt->fetchColumn();

    // Fetch number of high priority reclamations
    $stmt = $pdo->prepare("SELECT COUNT(*) AS high_priority_reclamations FROM reclamations WHERE priorite = ? AND statut = 'en-attente'");
    $stmt->execute(['elevee']);
    $highPriorityReclamations = $stmt->fetchColumn();

} catch (PDOException $e) {
    error_log("Error fetching dashboard data: " . $e->getMessage());
    // Keep N/A if there's a database error
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SNTF - Tableau de Bord Agent</title>
    <style>
        /* CSS from agent_dashboard.html */
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
            max-width: 1200px;
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

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-details {
            text-align: right;
        }

        .user-name {
            font-weight: 600;
            color: #1e3c72;
        }

        .user-role {
            font-size: 12px;
            color: #666;
        }

        .logout-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none; /* Added for anchor tag */
        }

        .logout-btn:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        /* Added new styles for dashboard content */
        .dashboard-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }

        .card-icon {
            font-size: 48px;
            color: #2a5298;
            margin-bottom: 15px;
        }

        .card-title {
            font-size: 1.2em;
            color: #1e3c72;
            margin-bottom: 10px;
        }

        .card-value {
            font-size: 2.5em;
            font-weight: bold;
            color: #333;
        }

        .card-description {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }

        .action-links {
            margin-top: 40px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
        }

        .action-link {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
        }

        .action-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 123, 255, 0.4);
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="logo">
                <div class="logo-icon">SNTF</div>
                <h1>Tableau de Bord Agent</h1>
            </div>
            <div class="user-info">
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($agent_nom); ?></div>
                    <div class="user-role"><?php echo htmlspecialchars($agent_username === 'admin' ? 'Administrateur' : 'Agent'); ?></div>
                </div>
                <a href="logout.php" class="logout-btn">Déconnexion <i class="fas fa-sign-out-alt"></i></a>
            </div>
        </header>

        <main class="dashboard-content">
            <div class="card">
                <div class="card-icon"><i class="fas fa-ticket-alt"></i></div>
                <div class="card-title">Total Réclamations</div>
                <div class="card-value"><?php echo $totalReclamations; ?></div>
                <div class="card-description">Toutes les réclamations enregistrées.</div>
            </div>

            <div class="card">
                <div class="card-icon"><i class="fas fa-hourglass-half"></i></div>
                <div class="card-title">Réclamations en Attente</div>
                <div class="card-value"><?php echo $pendingReclamations; ?></div>
                <div class="card-description">Nécessitent une action.</div>
            </div>

            <div class="card">
                <div class="card-icon"><i class="fas fa-check-circle"></i></div>
                <div class="card-title">Réclamations Résolues</div>
                <div class="card-value"><?php echo $resolvedReclamations; ?></div>
                <div class="card-description">Complétées et fermées.</div>
            </div>

            <div class="card">
                <div class="card-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="card-title">Haute Priorité</div>
                <div class="card-value"><?php echo $highPriorityReclamations; ?></div>
                <div class="card-description">Réclamations urgentes en attente.</div>
            </div>
            </main>

        <section class="action-links">
            <a href="manage_reclamations.php" class="action-link"><i class="fas fa-list-alt"></i> Gérer les Réclamations</a>
            <a href="view_reports.php" class="action-link"><i class="fas fa-chart-line"></i> Voir les Rapports</a>
            <?php if ($agent_username === 'admin'): // Only show for admin user ?>
                <a href="manage_agents.php" class="action-link"><i class="fas fa-users-cog"></i> Gérer les Agents</a>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>