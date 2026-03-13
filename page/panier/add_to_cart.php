<?php
// Aktive tout rapò erè (retire sa nan pwodiksyon)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Asire w pa gen espas oswa karaktè anvan <?php
ob_start();

session_start();

// Tèt JSON dwe premye bagay
header('Content-Type: application/json');

try {
    // Verifye chemen db.php
    $db_path = dirname(__DIR__, 2) . '/config/db.php';
    if (!file_exists($db_path)) {
        throw new Exception("Fichye db.php pa jwenn nan: " . $db_path);
    }

    require_once $db_path;

    // Verifye koneksyon
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Ou dwe konekte anvan']);
        exit;
    }

    // Verifye POST
    if (!isset($_POST['product_id'])) {
        echo json_encode(['success' => false, 'message' => 'Pa gen ID pwodwi']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $product_id = intval($_POST['product_id']);
    $qty = isset($_POST['qty']) ? intval($_POST['qty']) : 1;

    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID pwodwi envalid']);
        exit;
    }

    // Verifye pwodwi a
    $stmt = $pdo->prepare("SELECT id, stock_qty, price, status FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Pwodwi sa pa egziste']);
        exit;
    }

    if ($product['status'] !== 'disponible') {
        echo json_encode(['success' => false, 'message' => 'Pwodwi sa pa disponib']);
        exit;
    }

    if ($product['stock_qty'] <= 0) {
        echo json_encode(['success' => false, 'message' => 'Stock epize']);
        exit;
    }

    // Verifye si deja nan panier
    $existing = $pdo->prepare("SELECT id, quantity FROM panier WHERE user_id = ? AND product_id = ?");
    $existing->execute([$user_id, $product_id]);
    $item = $existing->fetch();

    if ($item) {
        $newQty = $item['quantity'] + $qty;
        if ($newQty > $product['stock_qty']) {
            echo json_encode(['success' => false, 'message' => 'Stock insuffisant!']);
            exit;
        }
        $update = $pdo->prepare("UPDATE panier SET quantity = ? WHERE id = ?");
        $update->execute([$newQty, $item['id']]);
    } else {
        $insert = $pdo->prepare("INSERT INTO panier (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $insert->execute([$user_id, $product_id, $qty]);
    }

    echo json_encode(['success' => true, 'message' => 'Pwodwi ajoute nan panier']);
} catch (Exception $e) {
    ob_clean(); // Netwaye tout sòti anvan
    echo json_encode(['success' => false, 'message' => 'Erè: ' . $e->getMessage()]);
}
