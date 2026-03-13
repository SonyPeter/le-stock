<?php
session_start();
require_once dirname(__DIR__, 2) . '/config/db.php';

$user_id = $_SESSION['user_id'] ?? 1;

// Wè sa ki nan baz done a
$stmt = $pdo->prepare("SELECT id, name, image FROM products WHERE id IN (SELECT product_id FROM panier WHERE user_id = ?)");
$stmt->execute([$user_id]);
$items = $stmt->fetchAll();

echo "<h2>Sa ki nan baz done a (kòlòn 'image'):</h2>";
foreach ($items as $item) {
    echo "ID: " . $item['id'] . "<br>";
    echo "Name: " . htmlspecialchars($item['name']) . "<br>";
    echo "Image raw: " . htmlspecialchars($item['image']) . "<br>";
    echo "Image with cleanImagePath: " . htmlspecialchars(cleanImagePath($item['image'])) . "<br>";

    // Tès si fichye a egziste
    $testPath = dirname(__DIR__) . '/uploads/requests/' . basename(str_replace('\\', '/', $item['image']));
    echo "Full server path: " . htmlspecialchars($testPath) . "<br>";
    echo "File exists: " . (file_exists($testPath) ? 'WI' : 'NON') . "<br>";
    echo "<hr>";
}

function cleanImagePath($fullPath)
{
    if (empty($fullPath)) {
        return '../assets/img/placeholder.png';
    }
    $path = str_replace('\\', '/', $fullPath);

    // Si gen deja uploads/requests/, jis pran sa ki apre page/
    if (strpos($path, 'uploads/requests/') !== false) {
        $pos = strpos($path, 'uploads/requests/');
        return '../' . substr($path, $pos);
    }

    // Si gen page/, retire l
    if (strpos($path, 'page/') !== false) {
        $pos = strpos($path, 'page/') + 5;
        return '../' . substr($path, $pos);
    }

    // Sinon jis pran non fichye a
    if (strpos($path, '/') === false) {
        return '../uploads/requests/' . $path;
    }

    $filename = basename($path);
    return '../uploads/requests/' . $filename;
}
