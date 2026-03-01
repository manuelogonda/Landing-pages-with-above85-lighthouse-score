<?php
/**
 * LUMINÉ BEAUTY SHOP - Database & Configuration
 * M-Pesa Safaricom API Integration
 */

// ==========================================
// DATABASE CONFIGURATION
// ==========================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'luminebeauty');
define('DB_PORT', 3306);

// ==========================================
// M-PESA CONFIGURATION (Safaricom)
// ==========================================
// Get your credentials from: https://developer.safaricom.co.ke/
define('MPESA_CONSUMER_KEY', 'YOUR_CONSUMER_KEY_HERE');
define('MPESA_CONSUMER_SECRET', 'YOUR_CONSUMER_SECRET_HERE');
define('MPESA_BUSINESS_SHORTCODE', 'YOUR_SHORTCODE_HERE'); // e.g., 174379
define('MPESA_PASSKEY', 'bfb279f9aa9bdbcf158e97dd71a467cd'); // Default test passkey
define('MPESA_ENVIRONMENT', 'sandbox'); // Change to 'production' for live
define('MPESA_CALLBACK_URL', 'https://yoursite.com/backend/mpesa_callback.php');

// ==========================================
// BUSINESS CONFIGURATION
// ==========================================
define('BUSINESS_NAME', 'Luminé Beauty Shop');
define('BUSINESS_PHONE', '254712000003');
define('BUSINESS_EMAIL', 'bookings@luminebeauty.ke');
define('BUSINESS_CURRENCY', 'KES');

// ==========================================
// SYSTEM CONFIGURATION
// ==========================================
define('APP_DEBUG', true); // Set to false in production
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_SALT', 'luminebeauty_salt_key_change_in_production');

// ==========================================
// TIMEZONE
// ==========================================
date_default_timezone_set('Africa/Nairobi');

// ==========================================
// ERROR REPORTING
// ==========================================
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ==========================================
// PDO DATABASE CONNECTION CLASS
// ==========================================
class DatabaseConnection {
    private $connection;
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $user = DB_USER;
    private $password = DB_PASSWORD;
    private $port = DB_PORT;

    public function connect() {
        $this->connection = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            $this->connection = new PDO(
                $dsn,
                $this->user,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => false
                ]
            );

            return $this->connection;
        } catch (PDOException $e) {
            if (APP_DEBUG) {
                error_log("Database Connection Error: " . $e->getMessage());
            }
            return false;
        }
    }

    public function getConnection() {
        return $this->connection;
    }

    public function closeConnection() {
        $this->connection = null;
    }
}

// ==========================================
// HELPER FUNCTIONS
// ==========================================

/**
 * Get database connection
 */
function getDB() {
    static $db = null;
    if ($db === null) {
        $dbConn = new DatabaseConnection();
        $db = $dbConn->connect();
    }
    return $db;
}

/**
 * Send JSON response
 */
function sendJSON($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * Sanitize input (with PDO, prepared statements handle this, but filter anyway)
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate phone number (Kenya format)
 */
function validatePhone($phone) {
    // Kenya phone format: 254XXXXXXXXX
    return preg_match('/^254[0-9]{9}$/', $phone);
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate date
 */
function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Generate booking ID
 */
function generateBookingID() {
    return 'LUM-' . strtoupper(uniqid(bin2hex(random_bytes(3))));
}

/**
 * Hash password
 */
function hashPassword($password) {
    return hash_hmac('sha256', $password, PASSWORD_SALT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return hashPassword($password) === $hash;
}

/**
 * Log action
 */
function logAction($type, $message, $data = []) {
    if (APP_DEBUG) {
        $logFile = __DIR__ . '/logs/activity.log';
        if (!is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $type: $message";
        if (!empty($data)) {
            $logEntry .= " | " . json_encode($data);
        }
        error_log($logEntry . "\n", 3, $logFile);
    }
}

// ==========================================
// SESSION SECURITY
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', !APP_DEBUG); // Only HTTPS in production
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

?>
