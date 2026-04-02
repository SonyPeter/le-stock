<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$host = $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? getenv('DB_HOST') ?? 'localhost';
$db   = $_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'] ?? getenv('DB_NAME') ?? 'le-stock';
$user = $_ENV['DB_USER'] ?? $_SERVER['DB_USER'] ?? getenv('DB_USER') ?? 'root';
$pass = $_ENV['DB_PASS'] ?? $_SERVER['DB_PASS'] ?? getenv('DB_PASS') ?? 'Sony-2003';
$charset = $_ENV['DB_CHARSET'] ?? $_SERVER['DB_CHARSET'] ?? getenv('DB_CHARSET') ?? 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// CORRECTION : On définit les options proprement sans appeler $pdo
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Pas de setAttribute ici !
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // La connexion est créée ICI en utilisant le tableau d'options
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // En cas d'erreur (mauvais mot de passe, base inexistante...)
    die("Erreur de connexion : " . $e->getMessage());
}
