<?php
// Include the configuration file for database connection and session management
require_once 'config.php';
// Ensure that only authenticated agents can access this page
requireAgentLogin();

// Get the complaint ID from the URL parameter
// Sanitize the input to prevent SQL injection and other vulnerabilities
$reclamation_id = isset($_GET['id']) ? sanitizeInput($_GET['id']) : 0;

$reclamation = null; // Initialize reclamation variable

try {
    // Establish a database connection
    $pdo = getConnection();

    // Prepare a SQL query to fetch all details of a specific complaint by its ID
    $query = "SELECT * FROM reclamations WHERE id = ?";
    $stmt = $pdo->prepare($query);
    // Bind the ID parameter to the prepared statement
    $stmt->execute([$reclamation_id]);
    // Fetch the complaint details as an associative array
    $reclamation = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Log any database connection or query errors
    error_log("Error fetching reclamation details: " . $e->getMessage());
    // Optionally, display a user-friendly error message
    // You might want to redirect to an error page or manage_reclamations.php
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de la Réclamation</title>
    <style>
        /* General body styling */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        /* Container for the complaint details card */
        .card-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 800px;
            animation: fadeIn 0.5s ease-out;
        }

        /* Animation for card appearance */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Header styling for the card */
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }

        .card-header h1 {
            color: #1e3c72;
            font-size: 2em;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Styling for the back button */
        .back-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex; /* Use flex to align icon and text */
            align-items: center;
            gap: 8px; /* Space between icon and text */
        }

        .back-btn:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        /* Styling for detail rows */
        .detail-row {
            display: flex;
            flex-wrap: wrap; /* Allow items to wrap on smaller screens */
            margin-bottom: 15px;
            border-bottom: 1px dashed #f1f3f5; /* Subtle separator */
            padding-bottom: 10px;
        }

        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .detail-label {
            flex: 0 0 150px; /* Fixed width for labels */
            font-weight: 600;
            color: #2a5298;
            padding-right: 20px;
        }

        .detail-value {
            flex: 1; /* Take remaining space */
            color: #555;
        }

        /* Specific styling for description field */
        .detail-row.description-row .detail-value {
            white-space: pre-wrap; /* Preserve whitespace and breaks */
            max-height: 200px; /* Limit height for long descriptions */
            overflow-y: auto; /* Add scroll for overflow */
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        /* Badges for status and priority */
        .status-badge, .priority-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
            text-transform: capitalize;
            display: inline-block;
            margin-top: 5px; /* Adjust for better alignment */
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

        /* Message for no complaint found */
        .no-reclamation {
            text-align: center;
            padding: 50px;
            color: #dc3545;
            font-size: 1.2em;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 10px;
            margin-top: 20px;
        }

        .no-reclamation i {
            font-size: 3em;
            margin-bottom: 15px;
            color: #dc3545;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .detail-label {
                flex-basis: 100%; /* Labels take full width on small screens */
                margin-bottom: 5px;
            }
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .back-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="card-container">
        <div class="card-header">
            <h1><i class="fas fa-info-circle"></i> Détails de la Réclamation</h1>
            <a href="manage_reclamations.php" class="back-btn"><i class="fas fa-arrow-left"></i> Retour aux Réclamations</a>
        </div>

        <?php if ($reclamation): ?>
            <!-- Display complaint details if found -->
            <div class="detail-row">
                <div class="detail-label">Numéro de Référence:</div>
                <div class="detail-value"><?php echo htmlspecialchars($reclamation['numero_reference']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Nom du plaignant:</div>
                <div class="detail-value"><?php echo htmlspecialchars($reclamation['nom']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Email:</div>
                <div class="detail-value"><?php echo htmlspecialchars($reclamation['email']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Téléphone:</div>
                <div class="detail-value"><?php echo htmlspecialchars($reclamation['telephone'] ?: 'N/A'); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Ligne:</div>
                <div class="detail-value"><?php echo htmlspecialchars($reclamation['ligne']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Catégorie:</div>
                <div class="detail-value"><?php echo htmlspecialchars($reclamation['categorie']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Priorité:</div>
                <div class="detail-value">
                    <span class="priority-badge priority-<?php echo $reclamation['priorite']; ?>">
                        <?php echo ucfirst($reclamation['priorite']); ?>
                    </span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Statut:</div>
                <div class="detail-value">
                    <span class="status-badge status-<?php echo $reclamation['statut']; ?>">
                        <?php echo ucfirst($reclamation['statut']); ?>
                    </span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Date de l'incident:</div>
                <div class="detail-value"><?php echo date('d/m/Y', strtotime($reclamation['date_incident'])); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Date de création:</div>
                <div class="detail-value"><?php echo date('d/m/Y H:i', strtotime($reclamation['date_creation'])); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Dernière modification:</div>
                <div class="detail-value"><?php echo date('d/m/Y H:i', strtotime($reclamation['date_modification'])); ?></div>
            </div>
            <div class="detail-row description-row">
                <div class="detail-label">Description:</div>
                <div class="detail-value"><?php echo htmlspecialchars($reclamation['description']); ?></div>
            </div>
        <?php else: ?>
            <!-- Message if no complaint is found or ID is invalid -->
            <div class="no-reclamation">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Réclamation non trouvée ou ID invalide.</p>
                <p>Veuillez vérifier l'ID de la réclamation et réessayer.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
