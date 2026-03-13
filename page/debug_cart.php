<?php
// debug_cart.php - Mete sa nan menm katab ak accueil.php
echo "Chemen kouran: " . __DIR__ . "<br>";
echo "Fichye sa ye: " . __FILE__ . "<br><br>";

// Eseye jwenn db.php
$db_path = dirname(__DIR__) . '/config/db.php';
echo "Chemen db.php: " . $db_path . "<br>";
echo "Egziste? " . (file_exists($db_path) ? 'WI' : 'NON') . "<br><br>";

// Eseye jwenn add_to_cart.php
$cart_path = __DIR__ . '/panier/add_to_cart.php';
echo "Chemen add_to_cart.php: " . $cart_path . "<br>";
echo "Egziste? " . (file_exists($cart_path) ? 'WI' : 'NON') . "<br>";
