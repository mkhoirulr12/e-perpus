<?php
define('BASE_URL', 'https://e-perpus-seven.vercel.app/');

class Database {

    private $host;
    private $db;
    private $user;
    private $pass;
    private $driver; // mysql or pgsql
    public $conn;

    public function __construct() {
        // Detect database configuration from environment variables (Vercel)
        // or use local defaults
        $this->host   = getenv('DB_HOST') ?: "localhost";
        $this->db     = getenv('DB_NAME') ?: "perpustakaan_sd";
        $this->user   = getenv('DB_USER') ?: "root";
        $this->pass   = getenv('DB_PASS') ?: "";
        $this->driver = getenv('DB_DRIVER') ?: "mysql";
    }

    public function connect() {

        $this->conn = null;

        try {
            // For Vercel Postgres, the DSN might be different, but typically it follows:
            // pgsql:host=xxx;port=5432;dbname=xxx
            
            $dsn = $this->driver . ":host=" . $this->host . ";dbname=" . $this->db;
            
            // Some cloud providers require specific options (like SSL)
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];

            $this->conn = new PDO($dsn, $this->user, $this->pass, $options);

        } catch(PDOException $e) {
            // In production, you might want to log this instead of echoing
            if (getenv('VERCEL')) {
                error_log("Connection Error: " . $e->getMessage());
                return null;
            }
            echo "Connection Error: " . $e->getMessage();
        }

        return $this->conn;
    }
}
