<?php
require_once 'config.php';
requireAgentLogin();

$agent_id = $_SESSION['agent_id'];
$agent_username = $_SESSION['agent_username'];
$agent_nom = $_SESSION['agent_nom'];

// Fetch reclamations with filtering
$filter_status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$filter_priority = isset($_GET['priority']) ? sanitizeInput($_GET['priority']) : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

try {
    $pdo = getConnection();
    
    $query = "SELECT * FROM reclamations WHERE 1=1";
    $params = [];
    
    if (!empty($filter_status)) {
        $query .= " AND statut = ?";
        $params[] = $filter_status;
    }
    
    if (!empty($filter_priority)) {
        $query .= " AND priorite = ?";
        $params[] = $filter_priority;
    }
    
    if (!empty($search)) {
        $query .= " AND (nom LIKE ? OR email LIKE ? OR numero_reference LIKE ? OR description LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    $query .= " ORDER BY date_creation DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error fetching reclamations: " . $e->getMessage());
    $reclamations = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SNTF - Gérer les Réclamations</title>
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

        .filters {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            margin-bottom: 5px;
            font-weight: 500;
            color: #1e3c72;
        }

        .filter-group input, .filter-group select {
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .filter-group input:focus, .filter-group select:focus {
            outline: none;
            border-color: #2a5298;
        }

        .filter-btn {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
        }

        .reclamations-table {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 20px;
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
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-en-attente {
            background: #fff3cd;
            color: #856404;
        }

        .status-en-cours {
            background: #cce5ff;
            color: #004085;
        }

        .status-resolue {
            background: #d4edda;
            color: #155724;
        }

        .priority-badge {
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 500;
        }

        .priority-faible {
            background: #e2e3e5;
            color: #6c757d;
        }

        .priority-moyenne {
            background: #fff3cd;
            color: #856404;
        }

        .priority-elevee {
            background: #f8d7da;
            color: #721c24;
        }

        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            margin-right: 5px;
            transition: all 0.3s ease;
        }

        .btn-update {
            background: #28a745;
            color: white;
        }

        .btn-update:hover {
            background: #218838;
        }

        .btn-view {
            background: #17a2b8;
            color: white;
        }

        .btn-view:hover {
            background: #138496;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="logo">
                <div class="logo-icon">SNTF</div>
                <h1>Gérer les Réclamations</h1>
            </div>
            <div>
                <a href="agent_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Retour</a>
                <a href="logout.php" class="logout-btn">Déconnexion <i class="fas fa-sign-out-alt"></i></a>
            </div>
        </header>

        <div class="filters">
            <form method="GET" class="filter-row">
                <div class="filter-group">
                    <label for="search">Recherche</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Nom, email, référence...">
                </div>
                <div class="filter-group">
                    <label for="status">Statut</label>
                    <select id="status" name="status">
                        <option value="">Tous les statuts</option>
                        <option value="en-attente" <?php echo $filter_status === 'en-attente' ? 'selected' : ''; ?>>En attente</option>
                        <option value="en-cours" <?php echo $filter_status === 'en-cours' ? 'selected' : ''; ?>>En cours</option>
                        <option value="resolue" <?php echo $filter_status === 'resolue' ? 'selected' : ''; ?>>Résolue</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="priority">Priorité</label>
                    <select id="priority" name="priority">
                        <option value="">Toutes les priorités</option>
                        <option value="faible" <?php echo $filter_priority === 'faible' ? 'selected' : ''; ?>>Faible</option>
                        <option value="moyenne" <?php echo $filter_priority === 'moyenne' ? 'selected' : ''; ?>>Moyenne</option>
                        <option value="elevee" <?php echo $filter_priority === 'elevee' ? 'selected' : ''; ?>>Élevée</option>
                    </select>
                </div>
                <div class="filter-group">
                    <button type="submit" class="filter-btn"><i class="fas fa-search"></i> Filtrer</button>
                </div>
            </form>
        </div>

        <div class="reclamations-table">
            <h3><i class="fas fa-list-alt"></i> Liste des Réclamations (<?php echo count($reclamations); ?>)</h3>
            
            <?php if (empty($reclamations)): ?>
                <div class="no-results">
                    <i class="fas fa-inbox" style="font-size: 48px; color: #dee2e6; margin-bottom: 15px;"></i>
                    <p>Aucune réclamation trouvée.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Nom</th>
                            <th>Ligne</th>
                            <th>Catégorie</th>
                            <th>Priorité</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reclamations as $rec): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($rec['numero_reference']); ?></td>
                                <td><?php echo htmlspecialchars($rec['nom']); ?></td>
                                <td><?php echo htmlspecialchars($rec['ligne']); ?></td>
                                <td><?php echo htmlspecialchars($rec['categorie']); ?></td>
                                <td>
                                    <span class="priority-badge priority-<?php echo $rec['priorite']; ?>">
                                        <?php echo ucfirst($rec['priorite']); ?>
                                    </span>
                                </td>
                                <td>
                                    <select class="status-select" data-id="<?php echo $rec['id']; ?>">
                                        <option value="en-attente" <?php echo $rec['statut'] === 'en-attente' ? 'selected' : ''; ?>>En attente</option>
                                        <option value="en-cours" <?php echo $rec['statut'] === 'en-cours' ? 'selected' : ''; ?>>En cours</option>
                                        <option value="resolue" <?php echo $rec['statut'] === 'resolue' ? 'selected' : ''; ?>>Résolue</option>
                                    </select>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($rec['date_creation'])); ?></td>
                                <td>
								    <a href="view_complaint.php?id=<?php echo $rec['id']; ?>" class="action-btn btn-view"><i class="fas fa-eye"></i> Voir</a>
                                   
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Handle status change
        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', function() {
                const reclamationId = this.dataset.id;
                const newStatus = this.value;
                
                fetch('update_reclamation_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${reclamationId}&status=${newStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Statut mis à jour avec succès!');
                        location.reload();
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Erreur de connexion');
                });
            });
        });

        function viewReclamation(id) {
            // Simple alert with reclamation details (you can enhance this)
            alert('Affichage des détails de la réclamation #' + id + '\n\nCette fonctionnalité peut être étendue pour afficher une modal ou rediriger vers une page de détails.');
        }
    </script>
</body>
</html>