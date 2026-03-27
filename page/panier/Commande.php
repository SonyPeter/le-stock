<?php
session_start();

require_once dirname(__DIR__, 2) . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Jere aksyon anile kòmand
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'cancel_order') {
        $orderId = intval($_POST['order_id']);

        // Verifye ke kòmand lan pou itilizatè sa a epi li an kou (pending) toujou
        $checkStmt = $pdo->prepare("
            SELECT id, status 
            FROM orders 
            WHERE id = ? AND user_id = ?
        ");
        $checkStmt->execute([$orderId, $user_id]);
        $order = $checkStmt->fetch();

        if ($order && $order['status'] === 'pending') {
            // Anile kòmand lan - chanje status pou 'cancelled'
            $cancelStmt = $pdo->prepare("
                UPDATE orders 
                SET status = 'cancelled'
                WHERE id = ? AND user_id = ?
            ");
            $cancelStmt->execute([$orderId, $user_id]);

            // Remèt pwodwi yo nan stock la
            try {
                $itemsStmt = $pdo->prepare("
                    SELECT product_id, quantity 
                    FROM order_items 
                    WHERE order_id = ?
                ");
                $itemsStmt->execute([$orderId]);
                $items = $itemsStmt->fetchAll();

                foreach ($items as $item) {
                    $restoreStock = $pdo->prepare("
                        UPDATE products 
                        SET stock_qty = stock_qty + ? 
                        WHERE id = ?
                    ");
                    $restoreStock->execute([$item['quantity'], $item['product_id']]);
                }
            } catch (Exception $e) {
                error_log("Erè restore stock: " . $e->getMessage());
            }

            $message = 'Kòmand la anile avèk siksè!';
            $messageType = 'success';
        } else {
            $message = 'Ou pa ka anile kòmand sa a. Li deja valide, rejte oswa anile deja.';
            $messageType = 'error';
        }

        header('Location: ' . $_SERVER['PHP_SELF'] . ($message ? '?msg=' . urlencode($message) . '&type=' . $messageType : ''));
        exit();
    }
}

if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $messageType = $_GET['type'] ?? 'info';
}

// Pran tout kòmand itilizatè a - KOREJE POU RALE reject_reason
$orders = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            o.id,
            o.total_amount,
            o.subtotal,
            o.tax_amount,
            o.shipping_amount,
            o.discount_amount,
            o.promo_code,
            o.payment_method,
            o.payment_status,
            o.status,
            o.transaction_ref,
            o.stripe_payment_intent_id,
            o.created_at,
            o.reject_reason,
            COUNT(oi.id) as item_count
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erè kòmand: " . $e->getMessage());
    $message = 'Erè nan chajman kòmand yo: ' . $e->getMessage();
    $messageType = 'error';
}

// Klasifye kòmand yo selon status
$pendingOrders = [];    // pending - an kou
$validatedOrders = [];  // validated - valide pa admin
$rejectedOrders = [];   // rejected - rejte pa admin  
$cancelledOrders = [];  // cancelled - anile pa kliyan
$deliveredOrders = [];  // delivered - livre

foreach ($orders as $order) {
    switch ($order['status']) {
        case 'pending':
            $pendingOrders[] = $order;
            break;
        case 'validated':
        case 'approved':
        case 'processing':
            $validatedOrders[] = $order;
            break;
        case 'rejected':
        case 'refused':
            $rejectedOrders[] = $order;
            break;
        case 'cancelled':
            $cancelledOrders[] = $order;
            break;
        case 'delivered':
        case 'completed':
        case 'shipped':
            $deliveredOrders[] = $order;
            break;
        default:
            $pendingOrders[] = $order; // default mete l nan pending
    }
}

function formatPrice($price)
{
    return number_format($price, 2) . ' HTG';
}

function getStatusBadge($status)
{
    switch ($status) {
        case 'pending':
            return ['bg-yellow-100 text-yellow-800 border-yellow-200', 'An kou (ap tann)', 'fa-clock', 'text-yellow-500'];
        case 'validated':
        case 'approved':
        case 'processing':
            return ['bg-blue-100 text-blue-800 border-blue-200', 'Valide pa admin', 'fa-check-circle', 'text-blue-500'];
        case 'rejected':
        case 'refused':
            return ['bg-red-100 text-red-800 border-red-200', 'Rejte pa admin', 'fa-times-circle', 'text-red-500'];
        case 'cancelled':
            return ['bg-gray-100 text-gray-800 border-gray-200', 'Anile', 'fa-ban', 'text-gray-500'];
        case 'delivered':
        case 'completed':
        case 'shipped':
            return ['bg-green-100 text-green-800 border-green-200', 'Livre', 'fa-box', 'text-green-500'];
        default:
            return ['bg-slate-100 text-slate-800 border-slate-200', $status, 'fa-question', 'text-slate-500'];
    }
}

function getPaymentIcon($method)
{
    switch ($method) {
        case 'card':
            return 'fa-credit-card';
        case 'paypal':
            return 'fa-paypal';
        case 'mobile_wallet':
            return 'fa-mobile-alt';
        case 'cash':
            return 'fa-money-bill-wave';
        default:
            return 'fa-money-bill';
    }
}

function getStatusColor($status)
{
    switch ($status) {
        case 'pending':
            return 'bg-yellow-600';
        case 'validated':
        case 'approved':
        case 'processing':
            return 'bg-blue-600';
        case 'rejected':
        case 'refused':
            return 'bg-red-600';
        case 'cancelled':
            return 'bg-gray-600';
        case 'delivered':
        case 'completed':
        case 'shipped':
            return 'bg-green-600';
        default:
            return 'bg-slate-600';
    }
}
?>

<!DOCTYPE html>
<html lang="ht">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kòmand Mwen yo - LE STOCK</title>

    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="\le-stock\css\style.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .bg-glass {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .glass-dark {
            background: rgba(30, 41, 59, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
        }

        .logo-img {
            height: 50px;
            width: auto;
            max-width: 180px;
            object-fit: contain;
            filter: brightness(1.1);
        }

        @media (min-width: 640px) {
            .logo-img {
                height: 60px;
                max-width: 220px;
            }
        }

        @media (min-width: 768px) {
            .logo-img {
                height: 70px;
                max-width: 260px;
            }
        }

        @media (min-width: 1024px) {
            .logo-img {
                height: 80px;
                max-width: 300px;
            }
        }

        header .h-16 {
            height: 4.5rem;
        }

        @media (min-width: 640px) {
            header .h-16 {
                height: 5rem;
            }
        }

        @media (min-width: 768px) {
            header .h-16 {
                height: 5.5rem;
            }
        }

        .hamburger-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border-radius: 10px;
            border: 2px solid #60a5fa;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .hamburger-btn:hover {
            background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
            border-color: #93c5fd;
            transform: scale(1.05);
        }

        .hamburger-btn i {
            color: white;
            font-size: 1.25rem;
        }

        .mobile-menu {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        .mobile-menu.open {
            transform: translateX(0);
        }

        .mobile-nav-link {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            margin-bottom: 8px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 8px;
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #1e293b;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .mobile-nav-link:hover {
            background: #3b82f6;
            color: white;
            transform: translateX(5px);
        }

        .mobile-menu-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-bottom: 2px solid #3b82f6;
        }

        #mobile-menu {
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
        }

        .menu-title {
            color: white;
            font-size: 18px;
            font-weight: 700;
        }

        .order-tab {
            transition: all 0.3s ease;
            position: relative;
        }

        .order-tab.active {
            color: #3b82f6;
            font-weight: 600;
        }

        .order-tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 3px;
            background: #3b82f6;
            border-radius: 3px 3px 0 0;
        }

        .order-tab:hover:not(.active) {
            color: #60a5fa;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .order-card {
            animation: slideIn 0.4s ease forwards;
        }

        .order-card:nth-child(1) {
            animation-delay: 0.05s;
        }

        .order-card:nth-child(2) {
            animation-delay: 0.1s;
        }

        .order-card:nth-child(3) {
            animation-delay: 0.15s;
        }

        .order-card:nth-child(4) {
            animation-delay: 0.2s;
        }

        @keyframes pulse-red {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
            }

            50% {
                box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
            }
        }

        .cancel-btn:hover {
            animation: pulse-red 1.5s infinite;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }

        .empty-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .timeline {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            padding: 0 10px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 10px;
            right: 10px;
            height: 2px;
            background: #334155;
            transform: translateY(-50%);
            z-index: 0;
        }

        .timeline-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            position: relative;
            z-index: 1;
        }

        .timeline-dot {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            transition: all 0.3s ease;
        }

        .timeline-dot.completed {
            background: #22c55e;
            color: white;
        }

        .timeline-dot.active {
            background: #eab308;
            color: white;
            animation: pulse 2s infinite;
        }

        .timeline-dot.pending {
            background: #334155;
            color: #94a3b8;
        }

        .timeline-dot.rejected {
            background: #ef4444;
            color: white;
        }

        .timeline-dot.cancelled {
            background: #6b7280;
            color: white;
        }

        @keyframes pulse {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(234, 179, 8, 0.4);
            }

            50% {
                box-shadow: 0 0 0 8px rgba(234, 179, 8, 0);
            }
        }

        .timeline-label {
            font-size: 10px;
            color: #94a3b8;
            text-align: center;
            white-space: nowrap;
        }

        .timeline-label.active {
            color: #eab308;
            font-weight: 600;
        }

        .timeline-label.completed {
            color: #22c55e;
        }

        .hover-lift {
            transition: all 0.3s ease;
        }

        .hover-lift:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            animation: blink 2s infinite;
        }

        @keyframes blink {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        /* TI KARE REZON REJTE - STYLES NOUVO */
        .reject-reason-box {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border-left: 4px solid #ef4444;
            border-radius: 0.75rem;
            padding: 1rem;
            margin: 1rem 0;
            position: relative;
            animation: slideIn 0.5s ease;
        }

        .reject-reason-box::before {
            content: '\f071';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: -10px;
            left: 10px;
            background: #ef4444;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .reject-reason-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #991b1b;
            font-weight: 700;
            margin-bottom: 0.25rem;
            margin-left: 1.5rem;
        }

        .reject-reason-text {
            color: #7f1d1d;
            font-weight: 600;
            font-style: italic;
            margin-left: 1.5rem;
            line-height: 1.5;
        }
    </style>
</head>

<body class="min-h-screen">

    <!-- Background Image with Glass Effect -->
    <div class="fixed inset-0 z-0">
        <div class="absolute inset-0 bg-cover bg-center bg-fixed bg-no-repeat" style="background-image: url('/le-stock/assets/img/stock11.png');"></div>
        <div class="absolute inset-0 bg-glass"></div>
    </div>

    <!-- Header -->
    <header class="sticky top-0 z-50 bg-slate-900/95 backdrop-blur-md border-b border-blue-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">

                <!-- Logo -->
                <a href="../accueil.php" class="logo-container group">
                    <img src="\le-stock\assets\img\le stock entreprise copy2.png"
                        alt="LE STOCK Logo"
                        class="logo-img"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">

                    <div class="hidden w-12 h-12 sm:w-14 sm:h-14 bg-blue-600 rounded-lg items-center justify-center" id="logo-fallback">
                        <i class="fas fa-shopping-bag text-2xl sm:text-3xl text-white"></i>
                    </div>
                </a>

                <!-- Desktop Navigation -->
                <nav class="hidden md:flex items-center gap-8">
                    <a href="../../index.php" class="text-blue-300 hover:text-white text-sm font-medium uppercase tracking-wide transition-colors">Akèy</a>
                    <a href="../acceuil.php" class="text-blue-300 hover:text-white text-sm font-medium uppercase tracking-wide transition-colors">Pwodwi</a>
                    <a href="panier.php" class="text-blue-300 hover:text-white text-sm font-medium uppercase tracking-wide transition-colors">Panier</a>
                    <a href="commandes.php" class="text-blue-400 font-bold text-sm uppercase tracking-wide border-b-2 border-blue-400">Kòmand</a>
                </nav>

                <!-- Actions -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <a href="panier.php" class="p-2 text-blue-400 hover:text-white transition-colors relative">
                        <i class="fas fa-shopping-cart text-lg"></i>
                    </a>
                    <a href="../profile.php" class="hidden sm:block p-2 text-blue-300 hover:text-white transition-colors">
                        <i class="far fa-user text-lg"></i>
                    </a>

                    <button id="mobile-menu-btn" class="hamburger-btn md:hidden" aria-label="Ouvri menu a">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="mobile-menu fixed inset-y-0 left-0 w-64 md:hidden z-50">
            <div class="mobile-menu-header p-4 flex items-center justify-between">
                <span class="menu-title">Menu</span>
                <button id="close-menu-btn" class="hamburger-btn">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <nav class="p-4">
                <a href="../../index.php" class="mobile-nav-link">
                    <i class="fas fa-home"></i>
                    <span>Akèy</span>
                </a>
                <a href="../acceuil.php" class="mobile-nav-link">
                    <i class="fas fa-box"></i>
                    <span>Pwodwi</span>
                </a>
                <a href="panier.php" class="mobile-nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Panier</span>
                </a>
                <a href="commandes.php" class="mobile-nav-link bg-blue-600 text-white">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Kòmand Mwen yo</span>
                </a>
            </nav>
        </div>
        <div id="menu-overlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden"></div>
    </header>

    <!-- Main Content -->
    <main class="relative z-10 min-h-screen py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Page Header -->
            <div class="glass-dark rounded-2xl p-6 sm:p-8 mb-6 border border-blue-800/50">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white mb-2">
                            <i class="fas fa-clipboard-list text-blue-400 mr-3"></i>
                            Kòmand Mwen yo
                        </h1>
                        <p class="text-blue-200">Gade tout kòmand ou fè yo ak swiv pwogrè yo</p>
                    </div>
                    <a href="../acceuil.php" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-500 text-white px-6 py-3 rounded-xl transition-all font-semibold shadow-lg hover:shadow-blue-500/25">
                        <i class="fas fa-plus"></i>
                        Nouvo Kòmand
                    </a>
                </div>
            </div>

            <!-- Alerts -->
            <?php if (!empty($message)): ?>
                <div class="mb-6 rounded-xl p-4 flex items-center gap-3 shadow-lg <?php
                                                                                    echo $messageType === 'success' ? 'bg-green-100 text-green-900 border border-green-400' : 'bg-red-100 text-red-900 border border-red-400';
                                                                                    ?>">
                    <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle text-green-600' : 'fa-exclamation-circle text-red-600'; ?> text-xl"></i>
                    <span class="font-semibold"><?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>

            <?php if (empty($orders)): ?>
                <!-- Empty State - No Orders -->
                <div class="glass-card rounded-2xl p-8 sm:p-12 text-center shadow-xl">
                    <div class="empty-icon">
                        <i class="fas fa-shopping-bag text-4xl text-white"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Ou poko fè kòmand</h2>
                    <p class="text-gray-600 mb-8">Kòmanse fè makèt kounye a pou w ka wè kòmand ou yo isit la!</p>
                    <a href="../acceuil.php" class="inline-flex items-center gap-2 bg-blue-600 text-white px-8 py-4 rounded-xl hover:bg-blue-700 transition-colors font-bold text-lg shadow-lg">
                        <i class="fas fa-store mr-2"></i>
                        Ale nan Magazen an
                    </a>
                </div>
            <?php else: ?>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
                    <div class="glass-card rounded-xl p-3 border-l-4 border-yellow-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-600 font-medium">An Kou</p>
                                <p class="text-xl font-bold text-gray-900"><?php echo count($pendingOrders); ?></p>
                            </div>
                            <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-clock text-yellow-600"></i>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card rounded-xl p-3 border-l-4 border-blue-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-600 font-medium">Valide</p>
                                <p class="text-xl font-bold text-gray-900"><?php echo count($validatedOrders); ?></p>
                            </div>
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check-circle text-blue-600"></i>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card rounded-xl p-3 border-l-4 border-purple-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-600 font-medium">Livre</p>
                                <p class="text-xl font-bold text-gray-900"><?php echo count($deliveredOrders); ?></p>
                            </div>
                            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-box text-purple-600"></i>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card rounded-xl p-3 border-l-4 border-red-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-600 font-medium">Rejte</p>
                                <p class="text-xl font-bold text-gray-900"><?php echo count($rejectedOrders); ?></p>
                            </div>
                            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-times-circle text-red-600"></i>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card rounded-xl p-3 border-l-4 border-gray-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-600 font-medium">Anile</p>
                                <p class="text-xl font-bold text-gray-900"><?php echo count($cancelledOrders); ?></p>
                            </div>
                            <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-ban text-gray-600"></i>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card rounded-xl p-3 border-l-4 border-green-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-600 font-medium">Total</p>
                                <p class="text-xl font-bold text-gray-900"><?php echo count($orders); ?></p>
                            </div>
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-shopping-bag text-green-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="glass-card rounded-t-xl border-b border-gray-200 overflow-x-auto">
                    <div class="flex min-w-max">
                        <button onclick="switchTab('pending')" id="tab-pending" class="order-tab active px-5 py-4 text-sm font-medium text-gray-700 whitespace-nowrap flex items-center gap-2">
                            <i class="fas fa-clock text-yellow-500"></i>
                            An Kou
                            <?php if (count($pendingOrders) > 0): ?>
                                <span class="bg-yellow-500 text-white text-xs px-2 py-0.5 rounded-full"><?php echo count($pendingOrders); ?></span>
                            <?php endif; ?>
                        </button>
                        <button onclick="switchTab('validated')" id="tab-validated" class="order-tab px-5 py-4 text-sm font-medium text-gray-700 whitespace-nowrap flex items-center gap-2">
                            <i class="fas fa-check-circle text-blue-500"></i>
                            Valide
                            <?php if (count($validatedOrders) > 0): ?>
                                <span class="bg-blue-500 text-white text-xs px-2 py-0.5 rounded-full"><?php echo count($validatedOrders); ?></span>
                            <?php endif; ?>
                        </button>
                        <button onclick="switchTab('delivered')" id="tab-delivered" class="order-tab px-5 py-4 text-sm font-medium text-gray-700 whitespace-nowrap flex items-center gap-2">
                            <i class="fas fa-box text-purple-500"></i>
                            Livre
                            <?php if (count($deliveredOrders) > 0): ?>
                                <span class="bg-purple-500 text-white text-xs px-2 py-0.5 rounded-full"><?php echo count($deliveredOrders); ?></span>
                            <?php endif; ?>
                        </button>
                        <button onclick="switchTab('rejected')" id="tab-rejected" class="order-tab px-5 py-4 text-sm font-medium text-gray-700 whitespace-nowrap flex items-center gap-2">
                            <i class="fas fa-times-circle text-red-500"></i>
                            Rejte
                            <?php if (count($rejectedOrders) > 0): ?>
                                <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full"><?php echo count($rejectedOrders); ?></span>
                            <?php endif; ?>
                        </button>
                        <button onclick="switchTab('cancelled')" id="tab-cancelled" class="order-tab px-5 py-4 text-sm font-medium text-gray-700 whitespace-nowrap flex items-center gap-2">
                            <i class="fas fa-ban text-gray-500"></i>
                            Anile
                            <?php if (count($cancelledOrders) > 0): ?>
                                <span class="bg-gray-500 text-white text-xs px-2 py-0.5 rounded-full"><?php echo count($cancelledOrders); ?></span>
                            <?php endif; ?>
                        </button>
                        <button onclick="switchTab('all')" id="tab-all" class="order-tab px-5 py-4 text-sm font-medium text-gray-700 whitespace-nowrap flex items-center gap-2">
                            <i class="fas fa-list text-green-500"></i>
                            Tout
                        </button>
                    </div>
                </div>

                <!-- Orders Content -->
                <div class="glass-card rounded-b-xl p-4 sm:p-6 min-h-[400px]">

                    <!-- Pending Orders -->
                    <div id="content-pending" class="tab-content space-y-4">
                        <?php if (empty($pendingOrders)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-clock text-3xl text-white"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Pa gen kòmand an kou</h3>
                                <p class="text-gray-600 mb-4">Tout kòmand ou yo trete deja oswa ou poko fè kòmand.</p>
                                <a href="../acceuil.php" class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 font-semibold">
                                    <i class="fas fa-arrow-right"></i>
                                    Kòmanse fè makèt
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($pendingOrders as $order):
                                $statusBadge = getStatusBadge($order['status']);
                            ?>
                                <div class="order-card glass-dark rounded-xl p-4 sm:p-6 border border-yellow-600/30 hover-lift">
                                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-4">
                                        <div class="flex items-center gap-4">
                                            <div class="w-14 h-14 bg-yellow-600 rounded-xl flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-clock text-2xl text-white"></i>
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                                    <h3 class="text-lg font-bold text-white">Kòmand #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h3>
                                                    <span class="status-indicator <?php echo $statusBadge[0]; ?>">
                                                        <span class="pulse-dot bg-yellow-500"></span>
                                                        <?php echo $statusBadge[1]; ?>
                                                    </span>
                                                </div>
                                                <p class="text-yellow-300 text-sm">
                                                    <i class="far fa-calendar-alt mr-1"></i>
                                                    <?php echo date('d/m/Y à H:i', strtotime($order['created_at'])); ?>
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            <span class="text-2xl font-bold text-white"><?php echo formatPrice($order['total_amount']); ?></span>
                                        </div>
                                    </div>

                                    <!-- Timeline -->
                                    <div class="timeline mb-6">
                                        <div class="timeline-step">
                                            <div class="timeline-dot completed"><i class="fas fa-check text-xs"></i></div>
                                            <span class="timeline-label completed">Kreye</span>
                                        </div>
                                        <div class="timeline-step">
                                            <div class="timeline-dot active"><i class="fas fa-clock text-xs"></i></div>
                                            <span class="timeline-label active">Ap tann</span>
                                        </div>
                                        <div class="timeline-step">
                                            <div class="timeline-dot pending"><i class="fas fa-check-circle text-xs"></i></div>
                                            <span class="timeline-label">Valide</span>
                                        </div>
                                        <div class="timeline-step">
                                            <div class="timeline-dot pending"><i class="fas fa-box text-xs"></i></div>
                                            <span class="timeline-label">Livre</span>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4 p-4 bg-slate-800/50 rounded-lg">
                                        <div>
                                            <p class="text-xs text-yellow-300 mb-1">Pwodwi</p>
                                            <p class="text-white font-semibold"><?php echo $order['item_count']; ?> atik</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-yellow-300 mb-1">Peman</p>
                                            <p class="text-white font-semibold flex items-center gap-1">
                                                <i class="fas <?php echo getPaymentIcon($order['payment_method']); ?> text-sm"></i>
                                                <?php echo ucfirst($order['payment_method']); ?>
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-yellow-300 mb-1">Sou-total</p>
                                            <p class="text-white font-semibold"><?php echo formatPrice($order['subtotal']); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-yellow-300 mb-1">Livrezon</p>
                                            <p class="text-white font-semibold"><?php echo ($order['shipping_amount'] ?? 0) == 0 ? 'Gratis' : formatPrice($order['shipping_amount']); ?></p>
                                        </div>
                                    </div>

                                    <div class="flex flex-col sm:flex-row gap-3 justify-end">
                                        <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-yellow-600/20 hover:bg-yellow-600/30 text-yellow-400 border border-yellow-500/30 transition-colors">
                                            <i class="fas fa-eye"></i>
                                            Detay
                                        </a>
                                        <form method="POST" class="inline" onsubmit="return confirm('Èske ou sèten ou vle anile kòmand sa a?');">
                                            <input type="hidden" name="action" value="cancel_order">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <button type="submit" class="cancel-btn w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-red-600/20 hover:bg-red-600 text-red-400 hover:text-white border border-red-500/30 hover:border-red-500 transition-all">
                                                <i class="fas fa-times"></i>
                                                Anile Kòmand
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Validated Orders -->
                    <div id="content-validated" class="tab-content hidden space-y-4">
                        <?php if (empty($validatedOrders)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-check-circle text-3xl text-white"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Pa gen kòmand valide</h3>
                                <p class="text-gray-600">Kòmand ou yo poko valide pa administrasyon an.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($validatedOrders as $order):
                                $statusBadge = getStatusBadge($order['status']);
                            ?>
                                <div class="order-card glass-dark rounded-xl p-4 sm:p-6 border border-blue-600/30 hover-lift">
                                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-4">
                                        <div class="flex items-center gap-4">
                                            <div class="w-14 h-14 bg-blue-600 rounded-xl flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-check-circle text-2xl text-white"></i>
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                                    <h3 class="text-lg font-bold text-white">Kòmand #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h3>
                                                    <span class="status-indicator <?php echo $statusBadge[0]; ?>">
                                                        <i class="fas fa-shield-alt text-xs"></i>
                                                        <?php echo $statusBadge[1]; ?>
                                                    </span>
                                                </div>
                                                <p class="text-blue-300 text-sm">
                                                    <i class="far fa-calendar-check mr-1"></i>
                                                    <?php echo date('d/m/Y à H:i', strtotime($order['created_at'])); ?>
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            <span class="text-2xl font-bold text-white"><?php echo formatPrice($order['total_amount']); ?></span>
                                        </div>
                                    </div>

                                    <!-- Timeline -->
                                    <div class="timeline mb-6">
                                        <div class="timeline-step">
                                            <div class="timeline-dot completed"><i class="fas fa-check text-xs"></i></div>
                                            <span class="timeline-label completed">Kreye</span>
                                        </div>
                                        <div class="timeline-step">
                                            <div class="timeline-dot completed"><i class="fas fa-check text-xs"></i></div>
                                            <span class="timeline-label completed">Tann</span>
                                        </div>
                                        <div class="timeline-step">
                                            <div class="timeline-dot completed"><i class="fas fa-check-circle text-xs"></i></div>
                                            <span class="timeline-label completed">Valide</span>
                                        </div>
                                        <div class="timeline-step">
                                            <div class="timeline-dot pending"><i class="fas fa-box text-xs"></i></div>
                                            <span class="timeline-label">Livre</span>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4 p-4 bg-slate-800/50 rounded-lg">
                                        <div>
                                            <p class="text-xs text-blue-300 mb-1">Pwodwi</p>
                                            <p class="text-white font-semibold"><?php echo $order['item_count']; ?> atik</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-blue-300 mb-1">Peman</p>
                                            <p class="text-white font-semibold flex items-center gap-1">
                                                <i class="fas <?php echo getPaymentIcon($order['payment_method']); ?> text-sm"></i>
                                                <?php echo ucfirst($order['payment_method']); ?>
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-blue-300 mb-1">Total</p>
                                            <p class="text-white font-semibold"><?php echo formatPrice($order['total_amount']); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-blue-300 mb-1">Referans</p>
                                            <p class="text-white font-semibold text-xs truncate" title="<?php echo $order['transaction_ref'] ?? 'N/A'; ?>">
                                                <?php echo !empty($order['transaction_ref']) ? substr($order['transaction_ref'], -10) : 'N/A'; ?>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex gap-3 justify-end">
                                        <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600/20 hover:bg-blue-600/30 text-blue-400 border border-blue-500/30 transition-colors">
                                            <i class="fas fa-eye"></i>
                                            Wè Detay
                                        </a>
                                        <button disabled class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-700 text-gray-400 cursor-not-allowed border border-gray-600" title="Kòmand valide pa ka anile">
                                            <i class="fas fa-lock"></i>
                                            Valide pa Admin
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Delivered Orders -->
                    <div id="content-delivered" class="tab-content hidden space-y-4">
                        <?php if (empty($deliveredOrders)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-box text-3xl text-white"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Pa gen kòmand livre</h3>
                                <p class="text-gray-600">Kòmand ou yo poko livre.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($deliveredOrders as $order):
                                $statusBadge = getStatusBadge($order['status']);
                            ?>
                                <div class="order-card glass-dark rounded-xl p-4 sm:p-6 border border-green-600/30 hover-lift">
                                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-4">
                                        <div class="flex items-center gap-4">
                                            <div class="w-14 h-14 bg-green-600 rounded-xl flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-box text-2xl text-white"></i>
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                                    <h3 class="text-lg font-bold text-white">Kòmand #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h3>
                                                    <span class="status-indicator <?php echo $statusBadge[0]; ?>">
                                                        <i class="fas fa-check-double text-xs"></i>
                                                        <?php echo $statusBadge[1]; ?>
                                                    </span>
                                                </div>
                                                <p class="text-green-300 text-sm">
                                                    <i class="far fa-calendar-check mr-1"></i>
                                                    <?php echo date('d/m/Y à H:i', strtotime($order['created_at'])); ?>
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            <span class="text-2xl font-bold text-white"><?php echo formatPrice($order['total_amount']); ?></span>
                                        </div>
                                    </div>

                                    <!-- Timeline -->
                                    <div class="timeline mb-6">
                                        <div class="timeline-step">
                                            <div class="timeline-dot completed"><i class="fas fa-check text-xs"></i></div>
                                            <span class="timeline-label completed">Kreye</span>
                                        </div>
                                        <div class="timeline-step">
                                            <div class="timeline-dot completed"><i class="fas fa-check text-xs"></i></div>
                                            <span class="timeline-label completed">Valide</span>
                                        </div>
                                        <div class="timeline-step">
                                            <div class="timeline-dot completed"><i class="fas fa-truck text-xs"></i></div>
                                            <span class="timeline-label completed">Livre</span>
                                        </div>
                                        <div class="timeline-step">
                                            <div class="timeline-dot completed"><i class="fas fa-check-double text-xs"></i></div>
                                            <span class="timeline-label completed">Fini</span>
                                        </div>
                                    </div>

                                    <div class="flex gap-3 justify-end">
                                        <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-green-600/20 hover:bg-green-600/30 text-green-400 border border-green-500/30 transition-colors">
                                            <i class="fas fa-eye"></i>
                                            Wè Detay
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Rejected Orders - KOREJE POU AFICHE REZON AN -->
                    <div id="content-rejected" class="tab-content hidden space-y-4">
                        <?php if (empty($rejectedOrders)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-times-circle text-3xl text-white"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Pa gen kòmand rejte</h3>
                                <p class="text-gray-600">Okenn nan kòmand ou yo pa rejte.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($rejectedOrders as $order):
                                $statusBadge = getStatusBadge($order['status']);
                            ?>
                                <div class="order-card glass-dark rounded-xl p-4 sm:p-6 border border-red-600/30 opacity-80">
                                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-4">
                                        <div class="flex items-center gap-4">
                                            <div class="w-14 h-14 bg-red-600 rounded-xl flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-times-circle text-2xl text-white"></i>
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                                    <h3 class="text-lg font-bold text-white line-through">Kòmand #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h3>
                                                    <span class="status-indicator <?php echo $statusBadge[0]; ?>">
                                                        <i class="fas fa-times text-xs"></i>
                                                        <?php echo $statusBadge[1]; ?>
                                                    </span>
                                                </div>
                                                <p class="text-red-300 text-sm">
                                                    <i class="far fa-calendar-times mr-1"></i>
                                                    <?php echo date('d/m/Y à H:i', strtotime($order['created_at'])); ?>
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            <span class="text-2xl font-bold text-gray-400 line-through"><?php echo formatPrice($order['total_amount']); ?></span>
                                        </div>
                                    </div>

                                    <!-- TI KARE REZON REJTE - KOREJE -->
                                    <?php if (!empty($order['reject_reason'])): ?>
                                        <div class="reject-reason-box">
                                            <div class="reject-reason-label">Rezon rejte:</div>
                                            <div class="reject-reason-text"><?php echo nl2br(htmlspecialchars($order['reject_reason'])); ?></div>
                                        </div>
                                    <?php else: ?>
                                        <!-- Si pa gen rezon, montre yon mesaj default -->
                                        <div class="reject-reason-box">
                                            <div class="reject-reason-label">Rezon rejte:</div>
                                            <div class="reject-reason-text">Pa gen rezon spesifye. Kontakte administrasyon an pou plis enfòmasyon.</div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="flex gap-3 justify-end mt-4">
                                        <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-600/20 hover:bg-red-600/30 text-red-400 border border-red-500/30 transition-colors">
                                            <i class="fas fa-eye"></i>
                                            Wè Detay
                                        </a>
                                        <a href="../acceuil.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-white transition-colors">
                                            <i class="fas fa-redo"></i>
                                            Refè Kòmand
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Cancelled Orders -->
                    <div id="content-cancelled" class="tab-content hidden space-y-4">
                        <?php if (empty($cancelledOrders)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-ban text-3xl text-white"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Pa gen kòmand anile</h3>
                                <p class="text-gray-600">Ou poko anile okenn kòmand.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($cancelledOrders as $order):
                                $statusBadge = getStatusBadge($order['status']);
                            ?>
                                <div class="order-card glass-dark rounded-xl p-4 sm:p-6 border border-gray-600/30 opacity-60">
                                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-4">
                                        <div class="flex items-center gap-4">
                                            <div class="w-14 h-14 bg-gray-600 rounded-xl flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-ban text-2xl text-white"></i>
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                                    <h3 class="text-lg font-bold text-white line-through">Kòmand #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h3>
                                                    <span class="status-indicator <?php echo $statusBadge[0]; ?>">
                                                        <i class="fas fa-user-slash text-xs"></i>
                                                        <?php echo $statusBadge[1]; ?>
                                                    </span>
                                                </div>
                                                <p class="text-gray-300 text-sm">
                                                    <i class="far fa-calendar-times mr-1"></i>
                                                    <?php echo date('d/m/Y à H:i', strtotime($order['created_at'])); ?>
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            <span class="text-2xl font-bold text-gray-500 line-through"><?php echo formatPrice($order['total_amount']); ?></span>
                                        </div>
                                    </div>

                                    <div class="flex gap-3 justify-end">
                                        <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-700 hover:bg-gray-600 text-gray-300 border border-gray-600 transition-colors">
                                            <i class="fas fa-eye"></i>
                                            Wè Detay
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- All Orders -->
                    <div id="content-all" class="tab-content hidden space-y-4">
                        <?php if (empty($orders)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-shopping-bag text-3xl text-white"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Ou poko fè kòmand</h3>
                                <p class="text-gray-600 mb-4">Kòmanse fè makèt kounye a!</p>
                                <a href="../acceuil.php" class="inline-flex items-center gap-2 bg-blue-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-store"></i>
                                    Ale nan Magazen an
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($orders as $order):
                                $statusBadge = getStatusBadge($order['status']);
                                $statusColor = getStatusColor($order['status']);
                                $isCancelled = $order['status'] === 'cancelled';
                                $isRejected = in_array($order['status'], ['rejected', 'refused']);
                                $canCancel = $order['status'] === 'pending';
                            ?>
                                <div class="order-card glass-dark rounded-xl p-4 sm:p-6 border <?php echo $isCancelled ? 'border-gray-600/30 opacity-60' : ($isRejected ? 'border-red-600/30 opacity-80' : 'border-' . str_replace('bg-', '', $statusColor) . '-600/30'); ?> hover-lift">
                                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-4">
                                        <div class="flex items-center gap-4">
                                            <div class="w-14 h-14 <?php echo $statusColor; ?> rounded-xl flex items-center justify-center flex-shrink-0">
                                                <i class="fas <?php echo $statusBadge[2]; ?> text-2xl text-white"></i>
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                                    <h3 class="text-lg font-bold text-white <?php echo ($isCancelled || $isRejected) ? 'line-through' : ''; ?>">Kòmand #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h3>
                                                    <span class="status-indicator <?php echo $statusBadge[0]; ?>">
                                                        <?php if ($order['status'] === 'pending'): ?>
                                                            <span class="pulse-dot <?php echo $statusBadge[3]; ?>"></span>
                                                        <?php else: ?>
                                                            <i class="fas <?php echo $statusBadge[2]; ?> text-xs"></i>
                                                        <?php endif; ?>
                                                        <?php echo $statusBadge[1]; ?>
                                                    </span>
                                                </div>
                                                <p class="<?php echo $statusBadge[3]; ?> text-sm">
                                                    <i class="far fa-calendar<?php echo ($isCancelled || $isRejected) ? '-times' : '-alt'; ?> mr-1"></i>
                                                    <?php echo date('d/m/Y à H:i', strtotime($order['created_at'])); ?>
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            <span class="text-2xl font-bold <?php echo ($isCancelled || $isRejected) ? 'text-gray-400 line-through' : 'text-white'; ?>"><?php echo formatPrice($order['total_amount']); ?></span>
                                        </div>
                                    </div>

                                    <!-- Montre rezon an nan tab Tout si kòmand la rejte -->
                                    <?php if ($isRejected): ?>
                                        <?php if (!empty($order['reject_reason'])): ?>
                                            <div class="reject-reason-box mb-4">
                                                <div class="reject-reason-label">Rezon rejte:</div>
                                                <div class="reject-reason-text"><?php echo nl2br(htmlspecialchars($order['reject_reason'])); ?></div>
                                            </div>
                                        <?php else: ?>
                                            <div class="reject-reason-box mb-4">
                                                <div class="reject-reason-label">Rezon rejte:</div>
                                                <div class="reject-reason-text">Pa gen rezon spesifye</div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4 p-4 <?php echo ($isCancelled || $isRejected) ? 'bg-slate-800/30' : 'bg-slate-800/50'; ?> rounded-lg">
                                        <div>
                                            <p class="text-xs <?php echo $statusBadge[3]; ?> mb-1">Pwodwi</p>
                                            <p class="<?php echo ($isCancelled || $isRejected) ? 'text-gray-400' : 'text-white'; ?> font-semibold"><?php echo $order['item_count']; ?> atik</p>
                                        </div>
                                        <div>
                                            <p class="text-xs <?php echo $statusBadge[3]; ?> mb-1">Peman</p>
                                            <p class="<?php echo ($isCancelled || $isRejected) ? 'text-gray-400' : 'text-white'; ?> font-semibold flex items-center gap-1">
                                                <i class="fas <?php echo getPaymentIcon($order['payment_method']); ?> text-sm"></i>
                                                <?php echo ucfirst($order['payment_method']); ?>
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-xs <?php echo $statusBadge[3]; ?> mb-1">Sou-total</p>
                                            <p class="<?php echo ($isCancelled || $isRejected) ? 'text-gray-400' : 'text-white'; ?> font-semibold"><?php echo formatPrice($order['subtotal']); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-xs <?php echo $statusBadge[3]; ?> mb-1">Livrezon</p>
                                            <p class="<?php echo ($isCancelled || $isRejected) ? 'text-gray-400' : 'text-white'; ?> font-semibold"><?php echo ($order['shipping_amount'] ?? 0) == 0 ? 'Gratis' : formatPrice($order['shipping_amount']); ?></p>
                                        </div>
                                    </div>

                                    <div class="flex flex-col sm:flex-row gap-3 justify-end">
                                        <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg <?php echo ($isCancelled || $isRejected) ? 'bg-slate-700 hover:bg-slate-600 text-gray-300 border-slate-600' : 'bg-' . str_replace('bg-', '', $statusColor) . '-600/20 hover:bg-' . str_replace('bg-', '', $statusColor) . '-600/30 text-' . str_replace('bg-', '', $statusColor) . '-400 border-' . str_replace('bg-', '', $statusColor) . '-500/30'; ?> border transition-colors">
                                            <i class="fas fa-eye"></i>
                                            Detay
                                        </a>

                                        <?php if ($canCancel): ?>
                                            <form method="POST" class="inline" onsubmit="return confirm('Èske ou sèten ou vle anile kòmand sa a?');">
                                                <input type="hidden" name="action" value="cancel_order">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <button type="submit" class="cancel-btn w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-red-600/20 hover:bg-red-600 text-red-400 hover:text-white border border-red-500/30 hover:border-red-500 transition-all">
                                                    <i class="fas fa-times"></i>
                                                    Anile
                                                </button>
                                            </form>
                                        <?php elseif ($isRejected): ?>
                                            <a href="../acceuil.php" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-white transition-colors">
                                                <i class="fas fa-redo"></i>
                                                Refè
                                            </a>
                                        <?php else: ?>
                                            <button disabled class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-gray-700 text-gray-400 cursor-not-allowed border border-gray-600">
                                                <i class="fas fa-lock"></i>
                                                <?php echo $isCancelled ? 'Anile' : 'Fini'; ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <div class="relative z-10 bg-slate-900/95 backdrop-blur-md mt-10">
        <footer class="border-t border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
                <div class="text-center">
                    <p class="text-slate-500 text-sm">
                        &copy; 2024 LE STOCK. Tout dwa rezève.
                    </p>
                </div>
            </div>
        </footer>
    </div>

    <script>
        // Mobile menu
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const closeMenuBtn = document.getElementById('close-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const menuOverlay = document.getElementById('menu-overlay');

        function openMenu() {
            mobileMenu.classList.add('open');
            menuOverlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeMenu() {
            mobileMenu.classList.remove('open');
            menuOverlay.classList.add('hidden');
            document.body.style.overflow = '';
        }

        mobileMenuBtn.addEventListener('click', openMenu);
        closeMenuBtn.addEventListener('click', closeMenu);
        menuOverlay.addEventListener('click', closeMenu);

        // Tab switching
        function switchTab(tabName) {
            // Hide all contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });

            // Remove active class from all tabs
            document.querySelectorAll('.order-tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected content
            document.getElementById('content-' + tabName).classList.remove('hidden');

            // Add active class to selected tab
            document.getElementById('tab-' + tabName).classList.add('active');

            // Add animation to cards
            const cards = document.querySelectorAll('#content-' + tabName + ' .order-card');
            cards.forEach((card, index) => {
                card.style.animation = 'none';
                setTimeout(() => {
                    card.style.animation = `slideIn 0.4s ease forwards`;
                    card.style.animationDelay = `${index * 0.05}s`;
                }, 10);
            });
        }

        // Initialize with pending tab
        document.addEventListener('DOMContentLoaded', function() {
            switchTab('pending');
        });
    </script>
</body>

</html>