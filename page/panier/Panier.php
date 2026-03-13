<?php
session_start();

// Chemen absoli pou enkli fichye konfigirasyon (monte 2 nivo: panier/ -> pages/ -> rasin)
require_once dirname(__DIR__, 2) . '/config/db.php';

// Verifye si itilizatè a konekte
if (!isset($_SESSION['user_id'])) {
    // Redireksyon relatif soti nan pozisyon paj la (panier/panier.php -> ../login.php)
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Jere aksyon POST yo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'update_quantity':
            $itemId = intval($_POST['item_id']);
            $newQuantity = intval($_POST['quantity']);

            if ($newQuantity >= 1) {
                // Verifye stock disponib
                $stockCheck = $pdo->prepare("
                    SELECT p.stock_qty 
                    FROM products p 
                    JOIN panier cart ON p.id = cart.product_id 
                    WHERE cart.id = ? AND cart.user_id = ?
                ");
                $stockCheck->execute([$itemId, $user_id]);
                $stock = $stockCheck->fetchColumn();

                if ($stock !== false && $newQuantity <= $stock) {
                    $stmt = $pdo->prepare("UPDATE panier SET quantity = ? WHERE id = ? AND user_id = ?");
                    $stmt->execute([$newQuantity, $itemId, $user_id]);
                    $message = 'Kantite mete ajou!';
                    $messageType = 'success';
                } else {
                    $message = 'Kantite demann depase stock disponib!';
                    $messageType = 'error';
                }
            } else {
                // Si kantite < 1, retire atik la
                $stmt = $pdo->prepare("DELETE FROM panier WHERE id = ? AND user_id = ?");
                $stmt->execute([$itemId, $user_id]);
            }
            break;

        case 'remove_item':
            $itemId = intval($_POST['item_id']);
            $stmt = $pdo->prepare("DELETE FROM panier WHERE id = ? AND user_id = ?");
            $stmt->execute([$itemId, $user_id]);
            $message = 'Atik retire nan panier!';
            $messageType = 'success';
            break;

        case 'clear_cart':
            $stmt = $pdo->prepare("DELETE FROM panier WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $message = 'Panier vid!';
            $messageType = 'success';
            break;

        case 'apply_promo':
            $promoCode = strtoupper(trim($_POST['promo_code']));
            $validPromos = [
                'PROMO10' => 0.10,
                'PROMO20' => 0.20,
                'STOCK30' => 0.30,
            ];

            if (isset($validPromos[$promoCode])) {
                $_SESSION['promo_code'] = $promoCode;
                $_SESSION['promo_discount'] = $validPromos[$promoCode];
                $message = "Kòd '$promoCode' aplike! -" . ($validPromos[$promoCode] * 100) . "% rabè";
                $messageType = 'success';
            } else {
                unset($_SESSION['promo_code']);
                unset($_SESSION['promo_discount']);
                $message = 'Kòd promosyon envalide!';
                $messageType = 'error';
            }
            break;

        case 'remove_promo':
            unset($_SESSION['promo_code']);
            unset($_SESSION['promo_discount']);
            $message = 'Kòd promosyon retire!';
            $messageType = 'success';
            break;
    }

    // Redireksyon PRG (Post/Redirect/Get) pou anpeche re-soumisyon
    // Sèvi ak chemen absoli relatif pou tounen nan paj panier a
    header('Location: ' . $_SERVER['PHP_SELF'] . ($message ? '?msg=' . urlencode($message) . '&type=' . $messageType : ''));
    exit();
}

// Jere mesaj apre redireksyon
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $messageType = $_GET['type'] ?? 'info';
}

// Rekipere atik yo nan panier a
$cartItems = [];
$subtotal = 0;
$totalItems = 0;

try {
    $stmt = $pdo->prepare("
        SELECT 
            cart.id as cart_id,
            cart.quantity,
            p.id as product_id,
            p.name,
            p.price,
            p.price_promo,
            p.image,
            p.stock_qty,
            p.status,
            c.name as category_name
        FROM panier cart
        JOIN products p ON cart.product_id = p.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE cart.user_id = ?
        ORDER BY cart.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $cartItems = $stmt->fetchAll();

    foreach ($cartItems as $item) {
        $price = ($item['price_promo'] > 0 && $item['price_promo'] < $item['price'])
            ? $item['price_promo']
            : $item['price'];
        $subtotal += $price * $item['quantity'];
        $totalItems += $item['quantity'];
    }
} catch (PDOException $e) {
    error_log("Erè panier: " . $e->getMessage());
    $message = 'Erè nan chajman panier a';
    $messageType = 'error';
}

// Kalkile total yo
$promoDiscount = $_SESSION['promo_discount'] ?? 0;
$discountAmount = $subtotal * $promoDiscount;
$subtotalAfterDiscount = $subtotal - $discountAmount;
$taxRate = 0.05;
$taxAmount = $subtotalAfterDiscount * $taxRate;
$shipping = ($subtotal > 5000) ? 0 : 250;
$total = $subtotalAfterDiscount + $taxAmount + $shipping;

// Fonksyon èd
function getDisplayPrice($item)
{
    if ($item['price_promo'] > 0 && $item['price_promo'] < $item['price']) {
        return $item['price_promo'];
    }
    return $item['price'];
}

function isOnPromo($item)
{
    return $item['price_promo'] > 0 && $item['price_promo'] < $item['price'];
}

function getStockStatus($stockQty)
{
    if ($stockQty <= 0) return ['out', 'Epiize'];
    if ($stockQty <= 5) return ['low', 'Stock ba (' . $stockQty . ' disponib)'];
    return ['in', 'Disponib'];
}
?>

<!DOCTYPE html>
<html lang="ht">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panier - LE-STOCK</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
        }

        /* Header */
        .header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f172a;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo span {
            color: #3b82f6;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
        }

        .nav-links a {
            color: #64748b;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: #3b82f6;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .header-btn {
            position: relative;
            color: #64748b;
            text-decoration: none;
            font-size: 1.25rem;
            transition: color 0.2s;
        }

        .header-btn:hover {
            color: #3b82f6;
        }

        .header-btn .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ef4444;
            color: white;
            font-size: 0.625rem;
            font-weight: 700;
            padding: 0.125rem 0.375rem;
            border-radius: 9999px;
        }

        /* Progress Steps */
        .progress-steps {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .steps-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .step-number {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.875rem;
        }

        .step-active .step-number {
            background: #3b82f6;
            color: white;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .step-pending .step-number {
            background: #e2e8f0;
            color: #64748b;
        }

        .step-line {
            width: 4rem;
            height: 2px;
            background: #e2e8f0;
        }

        /* Main Content */
        .main-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem 4rem;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #64748b;
            font-size: 1.125rem;
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        /* Cart Layout */
        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 2rem;
        }

        @media (max-width: 1024px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }
        }

        /* Cart Items */
        .cart-items-section {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .cart-count {
            color: #64748b;
            font-size: 0.875rem;
        }

        .clear-cart-btn {
            background: none;
            border: none;
            color: #ef4444;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.2s;
        }

        .clear-cart-btn:hover {
            color: #dc2626;
        }

        .cart-item {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.5rem;
            display: flex;
            gap: 1.5rem;
            transition: all 0.3s;
        }

        .cart-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-color: #cbd5e1;
        }

        .item-image {
            width: 120px;
            height: 120px;
            border-radius: 0.75rem;
            object-fit: cover;
            background: #f1f5f9;
            flex-shrink: 0;
        }

        .item-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.75rem;
        }

        .item-info h3 {
            font-size: 1.125rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }

        .item-category {
            font-size: 0.875rem;
            color: #3b82f6;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .item-stock {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .in-stock {
            background: #dcfce7;
            color: #166534;
        }

        .low-stock {
            background: #fef3c7;
            color: #92400e;
        }

        .out-stock {
            background: #fee2e2;
            color: #991b1b;
        }

        .remove-btn {
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 1.25rem;
            transition: all 0.2s;
            padding: 0.5rem;
            border-radius: 0.5rem;
        }

        .remove-btn:hover {
            color: #ef4444;
            background: #fee2e2;
        }

        .item-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 1rem;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .qty-btn {
            width: 2.5rem;
            height: 2.5rem;
            border: none;
            background: #f8fafc;
            cursor: pointer;
            color: #64748b;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qty-btn:hover:not(:disabled) {
            background: #e2e8f0;
            color: #0f172a;
        }

        .qty-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .qty-value {
            width: 3rem;
            text-align: center;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .item-price {
            text-align: right;
        }

        .price-promo {
            font-size: 1.25rem;
            font-weight: 800;
            color: #ef4444;
        }

        .price-original {
            font-size: 0.875rem;
            color: #94a3b8;
            text-decoration: line-through;
            margin-right: 0.5rem;
        }

        .price-regular {
            font-size: 1.25rem;
            font-weight: 800;
            color: #0f172a;
        }

        .price-per-unit {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.25rem;
        }

        /* Empty Cart */
        .empty-cart {
            background: white;
            border: 2px dashed #e2e8f0;
            border-radius: 1.5rem;
            padding: 4rem 2rem;
            text-align: center;
        }

        .empty-cart-icon {
            font-size: 5rem;
            color: #cbd5e1;
            margin-bottom: 1.5rem;
        }

        .empty-cart-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }

        .empty-cart-text {
            color: #64748b;
            margin-bottom: 2rem;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #3b82f6;
            color: white;
            padding: 0.875rem 2rem;
            border-radius: 0.75rem;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        /* Sidebar Summary */
        .cart-sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .summary-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.5rem;
            position: sticky;
            top: 100px;
        }

        .summary-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.875rem;
        }

        .summary-row:last-of-type {
            border-bottom: none;
        }

        .summary-row.discount {
            color: #16a34a;
        }

        .summary-row.total {
            border-top: 2px solid #e2e8f0;
            margin-top: 0.5rem;
            padding-top: 1rem;
            font-size: 1.125rem;
            font-weight: 700;
            color: #0f172a;
        }

        .summary-row.total .amount {
            font-size: 1.5rem;
            color: #3b82f6;
        }

        /* Promo Code */
        .promo-section {
            margin: 1.5rem 0;
            padding: 1rem 0;
            border-top: 1px solid #f1f5f9;
            border-bottom: 1px solid #f1f5f9;
        }

        .promo-form {
            display: flex;
            gap: 0.5rem;
        }

        .promo-input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: border-color 0.2s;
            text-transform: uppercase;
        }

        .promo-input:focus {
            outline: none;
            border-color: #3b82f6;
        }

        .btn-promo {
            padding: 0.75rem 1.25rem;
            background: #f1f5f9;
            color: #475569;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-promo:hover {
            background: #e2e8f0;
        }

        .applied-promo {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #dcfce7;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-top: 0.75rem;
        }

        .applied-promo code {
            background: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-weight: 700;
            color: #166534;
        }

        .remove-promo {
            color: #ef4444;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Checkout Button */
        .checkout-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .checkout-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
        }

        .checkout-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .secure-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1rem;
            color: #64748b;
            font-size: 0.75rem;
        }

        .shipping-note {
            text-align: center;
            margin-top: 1rem;
            padding: 0.75rem;
            background: #f0fdf4;
            border-radius: 0.5rem;
            color: #166534;
            font-size: 0.875rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                padding: 1rem;
            }

            .main-content {
                padding: 0 1rem 2rem;
            }

            .cart-item {
                flex-direction: column;
                padding: 1rem;
            }

            .item-image {
                width: 100%;
                height: 200px;
            }

            .cart-layout {
                gap: 1rem;
            }

            .summary-card {
                position: static;
            }

            .item-footer {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .item-price {
                text-align: left;
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <!-- Logo: monte yon nivo pou ale nan akèy -->
            <a href="../accueil.php" class="logo">
                <i class="fas fa-cube" style="color: #3b82f6;"></i>
                LE<span>STOCK</span>
            </a>

            <nav class="nav-links">
                <a href="../accueil.php">Akèy</a>
                <a href="../products.php">Pwodwi</a>
                <a href="../categories.php">Kategori</a>
            </nav>

            <div class="header-actions">
                <!-- Chemen relatif korek pou favori -->
                <a href="../favoris.php" class="header-btn" title="Favori">
                    <i class="fas fa-heart"></i>
                </a>
                <!-- Panier (paj aktyèl) -->
                <a href="panier.php" class="header-btn" title="Panier">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($totalItems > 0): ?>
                        <span class="badge"><?php echo $totalItems; ?></span>
                    <?php endif; ?>
                </a>
                <!-- Chemen relatif korek pou pwofil -->
                <a href="../profile.php" class="header-btn" title="Pwofil">
                    <i class="fas fa-user"></i>
                </a>
            </div>
        </div>
    </header>

    <!-- Progress Steps -->
    <div class="progress-steps">
        <div class="steps-container">
            <div class="step step-active">
                <div class="step-number">1</div>
                <span>Panier</span>
            </div>
            <div class="step-line"></div>
            <div class="step step-pending">
                <div class="step-number">2</div>
                <span>Livrezon</span>
            </div>
            <div class="step-line"></div>
            <div class="step step-pending">
                <div class="step-number">3</div>
                <span>Peman</span>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Panier ou a</h1>
            <p class="page-subtitle">Jere atik ou yo anvan ou pase kòmand ou a</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas fa-<?php
                                    echo $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : 'info-circle');
                                    ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="cart-layout">
            <!-- Cart Items Section -->
            <div class="cart-items-section">
                <?php if (empty($cartItems)): ?>
                    <!-- Empty Cart -->
                    <div class="empty-cart">
                        <div class="empty-cart-icon">
                            <i class="fas fa-shopping-basket"></i>
                        </div>
                        <h2 class="empty-cart-title">Panier ou a vid</h2>
                        <p class="empty-cart-text">Sanble ou poko ajoute anyen nan panier ou a. Eksplore pwodwi nou yo epi kòmanse fè makèt ou a!</p>
                        <!-- Redireksyon korek pou ale nan akèy -->
                        <a href="../accueil.php" class="btn-primary">
                            <i class="fas fa-store"></i>
                            Kòmanse fè makèt
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Cart Header -->
                    <div class="cart-header">
                        <span class="cart-count">
                            <i class="fas fa-box" style="margin-right: 0.5rem;"></i>
                            <?php echo $totalItems; ?> atik nan panier ou
                        </span>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Èske ou sèten ou vle vid panier ou a?');">
                            <input type="hidden" name="action" value="clear_cart">
                            <button type="submit" class="clear-cart-btn">
                                <i class="fas fa-trash-alt"></i>
                                Vid panier
                            </button>
                        </form>
                    </div>

                    <!-- Cart Items -->
                    <?php foreach ($cartItems as $item):
                        $currentPrice = getDisplayPrice($item);
                        $itemTotal = $currentPrice * $item['quantity'];
                        $stockStatus = getStockStatus($item['stock_qty']);
                    ?>
                        <div class="cart-item">
                            <!-- Chemen imaj: monte 2 nivo (panier/ -> pages/ -> rasin) -> uploads/ -->
                            <img src="../../uploads/products/<?php echo htmlspecialchars($item['image'] ?? 'placeholder.png'); ?>"
                                alt="<?php echo htmlspecialchars($item['name']); ?>"
                                class="item-image"
                                onerror="this.src='../../assets/img/placeholder.png'">

                            <div class="item-details">
                                <div class="item-header">
                                    <div class="item-info">
                                        <span class="item-category"><?php echo htmlspecialchars($item['category_name'] ?? 'Kategori'); ?></span>
                                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                        <span class="item-stock <?php echo $stockStatus[0]; ?>-stock">
                                            <i class="fas fa-circle" style="font-size: 0.4rem; vertical-align: middle;"></i>
                                            <?php echo $stockStatus[1]; ?>
                                        </span>
                                    </div>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="remove_item">
                                        <input type="hidden" name="item_id" value="<?php echo $item['cart_id']; ?>">
                                        <button type="submit" class="remove-btn" title="Retire atik la">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>

                                <div class="item-footer">
                                    <form method="POST" class="quantity-controls">
                                        <input type="hidden" name="action" value="update_quantity">
                                        <input type="hidden" name="item_id" value="<?php echo $item['cart_id']; ?>">

                                        <button type="submit" name="quantity" value="<?php echo max(1, $item['quantity'] - 1); ?>"
                                            class="qty-btn"
                                            <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>
                                            title="Diminye kantite">
                                            <i class="fas fa-minus"></i>
                                        </button>

                                        <span class="qty-value"><?php echo $item['quantity']; ?></span>

                                        <button type="submit" name="quantity" value="<?php echo $item['quantity'] + 1; ?>"
                                            class="qty-btn"
                                            <?php echo $item['quantity'] >= $item['stock_qty'] ? 'disabled' : ''; ?>
                                            title="Ogmante kantite">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </form>

                                    <div class="item-price">
                                        <?php if (isOnPromo($item)): ?>
                                            <div>
                                                <span class="price-original"><?php echo number_format($item['price'], 2); ?> HTG</span>
                                                <span class="price-promo"><?php echo number_format($itemTotal, 2); ?> HTG</span>
                                            </div>
                                        <?php else: ?>
                                            <div class="price-regular"><?php echo number_format($itemTotal, 2); ?> HTG</div>
                                        <?php endif; ?>
                                        <div class="price-per-unit"><?php echo number_format($currentPrice, 2); ?> HTG / inite</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Order Summary Sidebar -->
            <?php if (!empty($cartItems)): ?>
                <div class="cart-sidebar">
                    <div class="summary-card">
                        <h2 class="summary-title">
                            <i class="fas fa-receipt"></i>
                            Rekapitilatif kòmand
                        </h2>

                        <div class="summary-row">
                            <span>Sou-total (<?php echo $totalItems; ?> atik)</span>
                            <span><?php echo number_format($subtotal, 2); ?> HTG</span>
                        </div>

                        <?php if ($discountAmount > 0): ?>
                            <div class="summary-row discount">
                                <span>
                                    <i class="fas fa-tag" style="margin-right: 0.25rem;"></i>
                                    Rabè (<?php echo $_SESSION['promo_code']; ?>)
                                </span>
                                <span>-<?php echo number_format($discountAmount, 2); ?> HTG</span>
                            </div>
                        <?php endif; ?>

                        <div class="summary-row">
                            <span>Tax (5%)</span>
                            <span><?php echo number_format($taxAmount, 2); ?> HTG</span>
                        </div>

                        <div class="summary-row">
                            <span>Livrezon</span>
                            <span><?php echo $shipping == 0 ? 'Gratis' : number_format($shipping, 2) . ' HTG'; ?></span>
                        </div>

                        <!-- Promo Code Section -->
                        <div class="promo-section">
                            <?php if (isset($_SESSION['promo_code'])): ?>
                                <div class="applied-promo">
                                    <div>
                                        <i class="fas fa-check-circle" style="color: #16a34a; margin-right: 0.5rem;"></i>
                                        Kòd <code><?php echo $_SESSION['promo_code']; ?></code> aplike
                                    </div>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="remove_promo">
                                        <button type="submit" class="remove-promo">Retire</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <form method="POST" class="promo-form">
                                    <input type="hidden" name="action" value="apply_promo">
                                    <input type="text" name="promo_code" class="promo-input"
                                        placeholder="Kòd rabè (egz: PROMO10)"
                                        maxlength="20">
                                    <button type="submit" class="btn-promo">Aplike</button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <div class="summary-row total">
                            <span>Total</span>
                            <span class="amount"><?php echo number_format($total, 2); ?> HTG</span>
                        </div>

                        <!-- Bouton peman: soumèt nan checkout.php nan menm dosye a -->
                        <form action="checkout.php" method="POST">
                            <button type="submit" class="checkout-btn">
                                <i class="fas fa-lock"></i>
                                Pase kòmand la
                            </button>
                        </form>

                        <div class="secure-badge">
                            <i class="fas fa-shield-alt"></i>
                            <span>Peman an sekirye ak chifreman SSL</span>
                        </div>

                        <?php if ($shipping > 0): ?>
                            <div class="shipping-note">
                                <i class="fas fa-truck" style="margin-right: 0.5rem;"></i>
                                Ajoute <?php echo number_format(5000 - $subtotal, 2); ?> HTG pou livrezon gratis!
                            </div>
                        <?php else: ?>
                            <div class="shipping-note" style="background: #dbeafe; color: #1e40af;">
                                <i class="fas fa-check-circle" style="margin-right: 0.5rem;"></i>
                                Ou benefisye livrezon gratis!
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Konfimasyon anvan retire atik
        document.querySelectorAll('.remove-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Èske ou sèten ou vle retire atik sa a nan panier ou a?')) {
                    e.preventDefault();
                }
            });
        });

        // Anpeche double soumisyon
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.7';
                }
            });
        });
    </script>

</body>

</html>