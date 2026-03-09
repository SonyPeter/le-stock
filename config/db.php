<?php
$host = 'localhost';
$db   = 'le-stock';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

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
