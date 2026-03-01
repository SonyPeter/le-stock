<?php
$host = 'localhost';          // Le serveur (souvent localhost)
$db   = 'le-stock';     // Le nom que tu as donné à ta base dans PHPMyAdmin
$user = 'root';               // Utilisateur par défaut sur XAMPP/WAMP
$pass = 'Sony-2003';                   // Mot de passe (vide par défaut sur XAMPP)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERR_MODE            => PDO::ERR_MODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // echo "Connexion réussie !"; // Décommente pour tester
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
