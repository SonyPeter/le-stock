<?php
session_start();
require_once dirname(__DIR__, 2) . '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM panier WHERE user_id = ?");
$stmt->execute([$user_id]);
$result = $stmt->fetch();

echo json_encode(['count' => intval($result['total'] ?? 0)]);
