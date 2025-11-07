<?php
/**
 * ClubSphere - Enhanced Database Connection
 * Features: Connection pooling, error handling, performance monitoring
 * 
 * @version 2.0
 * @author ClubSphere Development Team
 */

class Database {
    private static $instance = null;
    private $pdo;
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset;
    private $options;
    
    // Connection statistics
    private $queryCount = 0;
    private $queryLog = [];
    private $connectionTime;
    
    private function __construct() {
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->dbname = $_ENV['DB_NAME'] ?? 'clubsphere';
        $this->username = $_ENV['DB_USER'] ?? 'root';
        $this->password = $_ENV['DB_PASS'] ?? '';
        $this->charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
        
        $this->options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_STATEMENT_CLASS => ['PDOStatement'],
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset} COLLATE utf8mb4_unicode_ci"
        ];
        
        $this->connectionTime = microtime(true);
        $this->connect();
    }
    
    /**
     * Get database instance (Singleton pattern)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Establish database connection
     */
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $this->pdo = new PDO($dsn, $this->username, $this->password, $this->options);
            
            // Set additional MySQL settings for better performance
            $this->pdo->exec("SET time_zone = '+00:00'");
            $this->pdo->exec("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
            
        } catch (PDOException $e) {
            $this->handleConnectionError($e);
        }
    }
    
    /**
     * Handle connection errors with user-friendly messages
     */
    private function handleConnectionError(PDOException $e) {
        $errorMessage = $e->getMessage();
        $errorCode = $e->getCode();
        
        // Log detailed error for debugging
        error_log("Database Connection Error [{$errorCode}]: {$errorMessage}");
        
        // Display user-friendly error page
        $this->showErrorPage($errorCode, $errorMessage);
    }
    
    /**
     * Show beautiful error page for database connection issues
     */
    private function showErrorPage($errorCode, $errorMessage) {
        $isDevelopment = ($_ENV['APP_ENV'] ?? 'production') === 'development';
        
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Error - ClubSphere</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at top right, #1f1c2c, #928dab);
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .error-container {
            text-align: center;
            max-width: 600px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: 0 0 30px rgba(255, 255, 255, 0.1);
            animation: fadeInUp 0.8s ease;
        }
        
        .error-icon {
            font-size: 4rem;
            color: #ff6b6b;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        
        .error-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 2rem;
            margin-bottom: 15px;
            background: linear-gradient(90deg, #ffcf70, #f3a683);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .error-message {
            font-size: 1.1rem;
            color: #e0e0e0;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .error-details {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            border-left: 4px solid #ff6b6b;
            text-align: left;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        
        .error-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .btn-primary {
            background: linear-gradient(90deg, #ffcf70, #f3a683);
            color: #000;
        }
        
        .btn-primary:hover {
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(255, 207, 112, 0.6);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        @media (max-width: 768px) {
            .error-container {
                margin: 20px;
                padding: 30px 20px;
            }
            
            .error-title {
                font-size: 1.5rem;
            }
            
            .error-actions {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-database"></i>
        </div>
        <h1 class="error-title">Database Connection Error</h1>
        <p class="error-message">
            We're experiencing technical difficulties connecting to our database. 
            This is usually temporary and our team has been notified.
        </p>
        
        
        
        if ($isDevelopment){
            $html .= <<<HTML
            <div class="error-details">
                <strong>Error Code:</strong> {$errorCode}<br>
                <strong>Error Message:</strong> {$errorMessage}<br>
                <strong>Time:</strong> {date('Y-m-d H:i:s')}<br>
                <strong>Environment:</strong> Development
            </div>
            HTML;
        }
        
        $html .= <<<HTML
        <div class="error-actions">
            <a href="javascript:location.reload();" class="btn btn-primary">
                <i class="fas fa-redo"></i> Try Again
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Go Home
            </a>
        </div>
        
        <p style="margin-top: 30px; font-size: 0.9rem; color: #bbb;">
            If this problem persists, please contact the system administrator.
        </p>
    </div>
</body>
</html>
HTML;
        
        echo $html;
        exit;
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection() {
        // Check if connection is still alive
        if (!$this->pdo) {
            $this->connect();
        }
        
        try {
            $this->pdo->query("SELECT 1");
        } catch (PDOException $e) {
            // Connection lost, reconnect
            $this->connect();
        }
        
        return $this->pdo;
    }
    
    /**
     * Execute a query with logging and error handling
     */
    public function query($sql, $params = []) {
        $startTime = microtime(true);
        
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            
            $executionTime = microtime(true) - $startTime;
            $this->queryCount++;
            
            // Log slow queries (>1 second)
            if ($executionTime > 1.0) {
                $this->queryLog[] = [
                    'sql' => $sql,
                    'params' => $params,
                    'execution_time' => $executionTime,
                    'timestamp' => date('Y-m-d H:i:s')
                ];
                
                error_log("Slow query detected: {$executionTime}s - {$sql}");
            }
            
            return $stmt;
            
        } catch (PDOException $e) {
            $this->handleQueryError($e, $sql, $params);
            return false;
        }
    }
    
    /**
     * Handle query errors
     */
    private function handleQueryError(PDOException $e, $sql, $params) {
        $errorInfo = $e->errorInfo;
        $errorCode = $errorInfo[1] ?? $e->getCode();
        
        // Log the error
        error_log("Database Query Error [{$errorCode}]: " . $e->getMessage());
        error_log("SQL: " . $sql);
        error_log("Params: " . json_encode($params));
        
        // Handle specific error codes
        switch ($errorCode) {
            case 1062: // Duplicate entry
                throw new DuplicateEntryException("Duplicate entry detected", 1062);
            case 1452: // Foreign key constraint fails
                throw new ForeignKeyException("Referenced record not found", 1452);
            case 1048: // Column cannot be null
                throw new ValidationException("Required field is missing", 1048);
            default:
                throw $e;
        }
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->getConnection()->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->getConnection()->rollBack();
    }
    
    /**
     * Get last inserted ID
     */
    public function lastInsertId() {
        return $this->getConnection()->lastInsertId();
    }
    
    /**
     * Get query statistics
     */
    public function getStats() {
        return [
            'query_count' => $this->queryCount,
            'connection_time' => microtime(true) - $this->connectionTime,
            'slow_queries' => array_filter($this->queryLog, function($q) {
                return $q['execution_time'] > 1.0;
            })
        ];
    }
    
    /**
     * Sanitize table/column names for dynamic queries
     */
    public function sanitizeIdentifier($identifier) {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $identifier);
    }
    
    /**
     * Check if table exists
     */
    public function tableExists($tableName) {
        $sql = "SHOW TABLES LIKE :table";
        $stmt = $this->query($sql, ['table' => $tableName]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Get table schema
     */
    public function getTableSchema($tableName) {
        $sql = "DESCRIBE " . $this->sanitizeIdentifier($tableName);
        return $this->query($sql)->fetchAll();
    }
    
    /**
     * Optimize table
     */
    public function optimizeTable($tableName) {
        $sql = "OPTIMIZE TABLE " . $this->sanitizeIdentifier($tableName);
        return $this->query($sql);
    }
    
    /**
     * Create backup point
     */
    public function createSavepoint($name) {
        $this->getConnection()->exec("SAVEPOINT {$name}");
    }
    
    /**
     * Rollback to savepoint
     */
    public function rollbackToSavepoint($name) {
        $this->getConnection()->exec("ROLLBACK TO SAVEPOINT {$name}");
    }
    
    /**
     * Release savepoint
     */
    public function releaseSavepoint($name) {
        $this->getConnection()->exec("RELEASE SAVEPOINT {$name}");
    }
}

// Custom Exception Classes
class DuplicateEntryException extends PDOException {}
class ForeignKeyException extends PDOException {}
class ValidationException extends PDOException {}

// Create database instance
$db = Database::getInstance();
$pdo = $db->getConnection();

// Helper function to get database instance
function getDB() {
    return Database::getInstance();
}

// Helper function to handle database operations with try-catch
function safeQuery($sql, $params = [], $errorMessage = 'Database operation failed') {
    try {
        $db = getDB();
        return $db->query($sql, $params);
    } catch (Exception $e) {
        error_log("Query failed: " . $e->getMessage());
        return false;
    }
}

// Check database connection on startup
try {
    $db = getDB();
    $stats = $db->getStats();
    
    // Log successful connection in development mode
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
        error_log("Database connected successfully. Connection time: " . number_format($stats['connection_time'], 4) . "s");
    }
} catch (Exception $e) {
    // Database connection failed - error page already shown
    exit;
}