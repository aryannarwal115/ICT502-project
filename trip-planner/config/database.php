<?php
/**
 * Database connection configuration.
 * Update these values for your local XAMPP / MySQL setup.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'trip_planner');
define('DB_USER', 'root');
define('DB_PASS', ''); // set your MySQL root password if any
define('DB_CHARSET', 'utf8mb4');

/**
 * BASE_URL: the URL path prefix the app is installed under.
 * Works whether the project sits at the web root (http://localhost/)
 * or in a subfolder (http://localhost/trip-planner/) — no manual
 * configuration or Apache VirtualHost needed.
 */
if (!defined('BASE_URL')) {
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/\\')) : '';
    $projectRoot = str_replace('\\', '/', rtrim(dirname(__DIR__), '/\\')); // .../trip-planner
    $base = '';
    if ($docRoot !== '' && strpos($projectRoot, $docRoot) === 0) {
        $base = substr($projectRoot, strlen($docRoot));
    }
    define('BASE_URL', rtrim($base, '/'));
}

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            die('A server error occurred. Please try again later.');
        }
    }
    return $pdo;
}
