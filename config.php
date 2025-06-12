<?php
/**
 * Configuration principale de l'application SNTF
 */

// Configuration de la session AVANT de la démarrer
if (session_status() === PHP_SESSION_NONE) {
    // Configuration des paramètres de session
    ini_set('session.cookie_lifetime', 3600); // 1 heure
    ini_set('session.gc_maxlifetime', 3600);
    ini_set('session.cookie_httponly', 1);
    session_set_cookie_params([
        'lifetime' => 3600,
        'path' => '/',
        'domain' => '',
        'secure' => false, // Mettre à true en HTTPS
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    // Démarrer la session
    session_start();
}

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'sntf_reclamations');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configuration de l'application
define('SITE_URL', 'http://localhost/GL/'); // Ajustez selon votre configuration
define('SITE_NAME', 'SNTF - Réclamations');

// Configuration de la sécurité
define('HASH_COST', 12);

/**
 * Connexion à la base de données
 */
function getConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Erreur de connexion à la base de données: " . $e->getMessage());
        die("Erreur de connexion à la base de données. Veuillez réessayer plus tard.");
    }
}

/**
 * Fonction de nettoyage des données d'entrée
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Génération d'un numéro de référence unique
 */
function generateReferenceNumber() {
    return 'REC-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
}

/**
 * Vérification de la connexion agent
 */
function isAgentLoggedIn() {
    return isset($_SESSION['agent_id']) && !empty($_SESSION['agent_id']);
}

/**
 * Redirection vers la page de connexion si non connecté
 */
function requireAgentLogin() {
    if (!isAgentLoggedIn()) {
        header('Location: agent_login.php');
        exit();
    }
}

/**
 * Formatage des dates en français
 */
function formatDateFr($date) {
    if (!$date) return 'Non définie';
    
    $months = [
        1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
        5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
        9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'
    ];
    
    $timestamp = is_string($date) ? strtotime($date) : $date;
    $day = date('j', $timestamp);
    $month = $months[(int)date('n', $timestamp)];
    $year = date('Y', $timestamp);
    
    return "$day $month $year";
}

/**
 * Formatage des dates et heures
 */
function formatDateTimeFr($datetime) {
    if (!$datetime) return 'Non définie';
    
    $timestamp = is_string($datetime) ? strtotime($datetime) : $datetime;
    return formatDateFr($timestamp) . ' à ' . date('H:i', $timestamp);
}

/**
 * Obtenir le libellé du statut
 */
function getStatutLabel($statut) {
    $labels = [
        'en-attente' => 'En attente',
        'en-cours' => 'En cours',
        'resolue' => 'Résolue'
    ];
    return $labels[$statut] ?? $statut;
}

/**
 * Obtenir la classe CSS du statut
 */
function getStatutClass($statut) {
    $classes = [
        'en-attente' => 'status-pending',
        'en-cours' => 'status-progress',
        'resolue' => 'status-resolved'
    ];
    return $classes[$statut] ?? 'status-pending';
}

/**
 * Obtenir le libellé de la priorité
 */
function getPrioriteLabel($priorite) {
    $labels = [
        'faible' => 'Faible',
        'moyenne' => 'Moyenne',
        'elevee' => 'Élevée'
    ];
    return $labels[$priorite] ?? $priorite;
}

/**
 * Obtenir la classe CSS de la priorité
 */
function getPrioriteClass($priorite) {
    $classes = [
        'faible' => 'priority-low',
        'moyenne' => 'priority-medium',
        'elevee' => 'priority-high'
    ];
    return $classes[$priorite] ?? 'priority-medium';
}

/**
 * Validation de l'email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validation du téléphone (format algérien)
 */
function isValidPhone($phone) {
    // Format algérien: 0X XX XX XX XX ou +213 X XX XX XX XX
    $pattern = '/^(0[5-7]\d{8}|\+213[5-7]\d{8})$/';
    return preg_match($pattern, str_replace(' ', '', $phone));
}

/**
 * Logs des actions
 */
function logAction($action, $details = '') {
    $logFile = 'logs/actions.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $user = isset($_SESSION['agent_username']) ? $_SESSION['agent_username'] : 'Anonyme';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    $logEntry = "[$timestamp] [$user] [$ip] $action - $details" . PHP_EOL;
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Protection CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérification du token CSRF
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Nettoyage des sessions expirées
 */
function cleanExpiredSessions() {
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > 3600) { // 1 heure
            session_destroy();
            header('Location: agent_login.php?expired=1');
            exit();
        }
    }
    $_SESSION['last_activity'] = time();
}

// Vérification automatique des sessions expirées pour les pages d'agents
if (basename($_SERVER['PHP_SELF']) !== 'agent_login.php' && strpos($_SERVER['PHP_SELF'], 'agent_') !== false) {
    cleanExpiredSessions();
}
?>