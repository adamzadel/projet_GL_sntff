<?php
// --- PHP LOGIC TO HANDLE FORM SUBMISSION ---

// This block of code will only run when the user submits the form (sends a POST request).
$complaint = null;
$error_message = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- DATABASE CONFIGURATION ---
    // !! IMPORTANT !! Update these with your database credentials.
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "sntf_reclamations";

    // Create and check the database connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        $error_message = "Erreur de connexion à la base de données.";
    } else {
        // Get the reference number from the form
        $reference = isset($_POST['reference']) ? trim($_POST['reference']) : '';

        if (empty($reference)) {
            $error_message = "Veuillez fournir un numéro de référence.";
        } else {
            // Prepare and execute the SQL query safely
            $stmt = $conn->prepare("SELECT numero_reference, nom, statut, date_creation FROM reclamations WHERE numero_reference = ?");
            if ($stmt) {
                $stmt->bind_param("s", $reference);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    // If a complaint is found, store its data in the $complaint variable
                    $complaint = $result->fetch_assoc();
                } else {
                    $error_message = "Aucune réclamation trouvée avec cette référence.";
                }
                $stmt->close();
            } else {
                $error_message = "Erreur de préparation de la requête.";
            }
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SNTF - Suivi de Réclamation</title>
    <style>
        /* The styles remain the same for a consistent look */
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
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 600px;
            width: 100%;
            padding: 20px;
        }
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 10px;
        }
        .logo-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(45deg, #1e3c72, #2a5298);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 24px;
        }
        .main-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
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
            width: 100%;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }
        #result-container {
            margin-top: 30px;
            padding: 20px;
            border-radius: 10px;
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            color: white;
            text-transform: capitalize;
        }
        .status-en-attente { background-color: #ffc107; }
        .status-en-cours { background-color: #17a2b8; }
        .status-resolue { background-color: #28a745; }
        .result-item { margin-bottom: 10px; }
        .result-item strong { color: #1e3c72; }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <div class="logo-icon">SNTF</div>
                <div>
                    <h1>Système de Réclamations</h1>
                    <p style="color: #666; margin-top: 5px;">Société Nationale des Transports Ferroviaires</p>
                </div>
            </div>
        </div>

        <div class="main-content">
            <h2 style="margin-bottom: 25px; color: #1e3c72; text-align: center;">👨‍💻 Suivre une Réclamation</h2>
            
            <!-- The form now submits to this same page -->
            <form action="suivre_reclamation.php" method="POST">
                <div class="form-group">
                    <label for="reference">Numéro de référence *</label>
                    <input type="text" id="reference" name="reference" placeholder="Ex: REC-2024-001" required value="<?= htmlspecialchars($_POST['reference'] ?? '') ?>">
                </div>
                <button type="submit" class="btn">🔍 Vérifier le statut</button>
            </form>
			
            <!-- PHP logic to display the error message if it exists -->
            <?php if ($error_message): ?>
                <div class="message error" style="margin-top: 20px;">
                    ❌ <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <!-- PHP logic to display the result if a complaint was found -->
            <?php if ($complaint): ?>
                <div id="result-container">
                    <h3 style="margin-bottom: 15px; color: #1e3c72;">Détails de la réclamation</h3>
                    <div class="result-item"><strong>Référence:</strong> <span><?= htmlspecialchars($complaint['numero_reference']) ?></span></div>
                    <div class="result-item"><strong>Nom:</strong> <span><?= htmlspecialchars($complaint['nom']) ?></span></div>
                    <div class="result-item"><strong>Date:</strong> <span><?= htmlspecialchars(date('d/m/Y', strtotime($complaint['date_creation']))) ?></span></div>
                    <div class="result-item">
                        <strong>Statut:</strong> 
                        <?php
                            $status = htmlspecialchars($complaint['statut']);
                            $status_class = "status-" . str_replace('_', '-', $status);
                        ?>
                        <span class="status-badge <?= $status_class ?>"><?= str_replace('-', ' ', $status) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <br>
			<a href="passenger_page.html" class="btn" style="background: transparent; border: 2px solid #1e3c72; color: #1e3c72;">↩️ Soumettre une nouvelle réclamation</a>
        </div>
    </div>
    <!-- The JavaScript fetch logic is no longer needed as PHP handles the result display on page load -->
</body>
</html>
