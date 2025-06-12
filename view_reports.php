<?php
require_once 'config.php';
requireAgentLogin();

$agent_id = $_SESSION['agent_id'];
$agent_username = $_SESSION['agent_username'];
$agent_nom = $_SESSION['agent_nom'];

// Fetch report data
try {
    $pdo = getConnection();
    
    // Monthly statistics
    $monthlyStats = [];
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(date_creation, '%Y-%m') as month,
            COUNT(*) as total,
            SUM(CASE WHEN statut = 'resolue' THEN 1 ELSE 0 END) as resolved,
            SUM(CASE WHEN statut = 'en-attente' THEN 1 ELSE 0 END) as pending
        FROM reclamations 
        WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(date_creation, '%Y-%m')
        ORDER BY month DESC
    ");
    $monthlyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Category statistics
    $categoryStats = [];
    $stmt = $pdo->query("
        SELECT 
            categorie,
            COUNT(*) as total,
            SUM(CASE WHEN statut = 'resolue' THEN 1 ELSE 0 END) as resolved
        FROM reclamations 
        GROUP BY categorie
        ORDER BY total DESC
    ");
    $categoryStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Priority statistics
    $priorityStats = [];
    $stmt = $pdo->query("
        SELECT 
            priorite,
            COUNT(*) as total,
            SUM(CASE WHEN statut = 'resolue' THEN 1 ELSE 0 END) as resolved
        FROM reclamations 
        GROUP BY priorite
        ORDER BY 
            CASE priorite 
                WHEN 'elevee' THEN 1 
                WHEN 'moyenne' THEN 2 
                WHEN 'faible' THEN 3 
            END
    ");
    $priorityStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Line statistics
    $lineStats = [];
    $stmt = $pdo->query("
        SELECT 
            ligne,
            COUNT(*) as total,
            SUM(CASE WHEN statut = 'resolue' THEN 1 ELSE 0 END) as resolved
        FROM reclamations 
        GROUP BY ligne
        ORDER BY total DESC
        LIMIT 10
    ");
    $lineStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Overall statistics
    $overallStats = [];
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_reclamations,
            SUM(CASE WHEN statut = 'en-attente' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN statut = 'en-cours' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN statut = 'resolue' THEN 1 ELSE 0 END) as resolved,
            SUM(CASE WHEN priorite = 'elevee' AND statut != 'resolue' THEN 1 ELSE 0 END) as high_priority_unresolved,
            ROUND(AVG(DATEDIFF(COALESCE(date_modification, NOW()), date_creation)), 1) as avg_resolution_days
        FROM reclamations
    ");
    $overallStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error fetching report data: " . $e->getMessage());
    $monthlyStats = $categoryStats = $priorityStats = $lineStats = [];
    $overallStats = ['total_reclamations' => 0, 'pending' => 0, 'in_progress' => 0, 'resolved' => 0, 'high_priority_unresolved' => 0, 'avg_resolution_days' => 0];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SNTF - Rapports et Statistiques</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 36px;
            margin-bottom: 15px;
        }

        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9em;
        }

        .report-section {
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

        .chart-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .chart-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
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

        .progress-bar {
            width: 100%;
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(45deg, #28a745, #20c997);
            transition: width 0.3s ease;
        }

        .percentage {
            font-weight: bold;
            color: #28a745;
        }

        .export-btn {
            background: linear-gradient(45deg, #17a2b8, #138496);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .export-btn:hover {
            transform: translateY(-2px);
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="logo">
                <div class="logo-icon">SNTF</div>
                <h1>Rapports et Statistiques</h1>
            </div>
            <div>
                <button class="export-btn" onclick="window.print()">
                    <i class="fas fa-print"></i> Imprimer
                </button>
                <a href="agent_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Retour</a>
                <a href="logout.php" class="logout-btn">Déconnexion <i class="fas fa-sign-out-alt"></i></a>
            </div>
        </header>

        <!-- Overall Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="color: #007bff;"><i class="fas fa-ticket-alt"></i></div>
                <div class="stat-value" style="color: #007bff;"><?php echo $overallStats['total_reclamations']; ?></div>
                <div class="stat-label">Total Réclamations</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color: #ffc107;"><i class="fas fa-hourglass-half"></i></div>
                <div class="stat-value" style="color: #ffc107;"><?php echo $overallStats['pending']; ?></div>
                <div class="stat-label">En Attente</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color: #17a2b8;"><i class="fas fa-cogs"></i></div>
                <div class="stat-value" style="color: #17a2b8;"><?php echo $overallStats['in_progress']; ?></div>
                <div class="stat-label">En Cours</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color: #28a745;"><i class="fas fa-check-circle"></i></div>
                <div class="stat-value" style="color: #28a745;"><?php echo $overallStats['resolved']; ?></div>
                <div class="stat-label">Résolues</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color: #dc3545;"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-value" style="color: #dc3545;"><?php echo $overallStats['high_priority_unresolved']; ?></div>
                <div class="stat-label">Urgentes Non Résolues</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color: #6f42c1;"><i class="fas fa-clock"></i></div>
                <div class="stat-value" style="color: #6f42c1;"><?php echo $overallStats['avg_resolution_days']; ?></div>
                <div class="stat-label">Jours Moy. Résolution</div>
            </div>
        </div>

        <!-- Monthly Trends -->
        <div class="report-section">
            <h3 class="section-title">
                <i class="fas fa-chart-line"></i>
                Tendances Mensuelles (6 derniers mois)
            </h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Mois</th>
                            <th>Total</th>
                            <th>Résolues</th>
                            <th>En Attente</th>
                            <th>Taux de Résolution</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($monthlyStats as $stat): ?>
                            <?php 
                                $resolutionRate = $stat['total'] > 0 ? round(($stat['resolved'] / $stat['total']) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><?php echo date('F Y', strtotime($stat['month'] . '-01')); ?></td>
                                <td><?php echo $stat['total']; ?></td>
                                <td><?php echo $stat['resolved']; ?></td>
                                <td><?php echo $stat['pending']; ?></td>
                                <td>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $resolutionRate; ?>%;"></div>
                                    </div>
                                    <span class="percentage"><?php echo $resolutionRate; ?>%</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Category Statistics -->
        <div class="report-section">
            <h3 class="section-title">
                <i class="fas fa-tags"></i>
                Statistiques par Catégorie
            </h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Catégorie</th>
                            <th>Total</th>
                            <th>Résolues</th>
                            <th>Taux de Résolution</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categoryStats as $stat): ?>
                            <?php 
                                $resolutionRate = $stat['total'] > 0 ? round(($stat['resolved'] / $stat['total']) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><?php echo ucfirst($stat['categorie']); ?></td>
                                <td><?php echo $stat['total']; ?></td>
                                <td><?php echo $stat['resolved']; ?></td>
                                <td>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $resolutionRate; ?>%;"></div>
                                    </div>
                                    <span class="percentage"><?php echo $resolutionRate; ?>%</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Priority and Line Statistics -->
        <div class="chart-container">
            <!-- Priority Statistics -->
            <div class="report-section">
                <h3 class="section-title">
                    <i class="fas fa-flag"></i>
                    Statistiques par Priorité
                </h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Priorité</th>
                                <th>Total</th>
                                <th>Résolues</th>
                                <th>Taux</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($priorityStats as $stat): ?>
                                <?php 
                                    $resolutionRate = $stat['total'] > 0 ? round(($stat['resolved'] / $stat['total']) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td><?php echo ucfirst($stat['priorite']); ?></td>
                                    <td><?php echo $stat['total']; ?></td>
                                    <td><?php echo $stat['resolved']; ?></td>
                                    <td class="percentage"><?php echo $resolutionRate; ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Line Statistics -->
            <div class="report-section">
                <h3 class="section-title">
                    <i class="fas fa-route"></i>
                    Top 10 Lignes
                </h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Ligne</th>
                                <th>Total</th>
                                <th>Résolues</th>
                                <th>Taux</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lineStats as $stat): ?>
                                <?php 
                                    $resolutionRate = $stat['total'] > 0 ? round(($stat['resolved'] / $stat['total']) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($stat['ligne']); ?></td>
                                    <td><?php echo $stat['total']; ?></td>
                                    <td><?php echo $stat['resolved']; ?></td>
                                    <td class="percentage"><?php echo $resolutionRate; ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Summary Report -->
        <div class="report-section">
            <h3 class="section-title">
                <i class="fas fa-file-alt"></i>
                Résumé Exécutif
            </h3>
            <div style="line-height: 1.6;">
                <p><strong>Rapport généré le:</strong> <?php echo date('d/m/Y à H:i'); ?></p>
                <br>
                <p><strong>Performance Globale:</strong></p>
                <ul style="margin-left: 20px; margin-top: 10px;">
                    <li>Total de <?php echo $overallStats['total_reclamations']; ?> réclamations enregistrées</li>
                    <li><?php echo $overallStats['resolved']; ?> réclamations résolues (<?php echo $overallStats['total_reclamations'] > 0 ? round(($overallStats['resolved'] / $overallStats['total_reclamations']) * 100, 1) : 0; ?>%)</li>
                    <li><?php echo $overallStats['pending']; ?> réclamations en attente de traitement</li>
                    <li><?php echo $overallStats['high_priority_unresolved']; ?> réclamations urgentes non résolues nécessitent une attention immédiate</li>
                    <li>Temps moyen de résolution: <?php echo $overallStats['avg_resolution_days']; ?> jours</li>
                </ul>
                <br>
                <p><strong>Recommandations:</strong></p>
                <ul style="margin-left: 20px; margin-top: 10px;">
                    <?php if ($overallStats['high_priority_unresolved'] > 0): ?>
                        <li style="color: #dc3545;">⚠️ Traiter en priorité les <?php echo $overallStats['high_priority_unresolved']; ?> réclamations urgentes</li>
                    <?php endif; ?>
                    <?php if ($overallStats['pending'] > $overallStats['resolved']): ?>
                        <li style="color: #ffc107;">📋 Augmenter la capacité de traitement pour réduire les réclamations en attente</li>
                    <?php endif; ?>
                    <li style="color: #17a2b8;">📊 Analyser les catégories les plus fréquentes pour des améliorations préventives</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Add some interactive features
        document.addEventListener('DOMContentLoaded', function() {
            // Animate progress bars
            const progressBars = document.querySelectorAll('.progress-fill');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 500);
            });
        });
    </script>
</body>
</html>