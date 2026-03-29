<?php
// ATANSYON: Pa gen espas oswa liy vid anvan <?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';

// Sekirite: Sèlman Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$msg = "";
$error = "";

// ==================== AKSYON YO ====================

// 1. VALIDE KÒMAND
if (isset($_POST['validate_order'])) {
    $order_id = intval($_POST['order_id']);

    try {
        $check = $pdo->prepare("SELECT id, status FROM orders WHERE id = ? AND status = 'pending'");
        $check->execute([$order_id]);

        if ($check->rowCount() > 0) {
            $pdo->prepare("UPDATE orders SET status = 'validated', validated_at = NOW(), validated_by = ? WHERE id = ?")
                ->execute([$_SESSION['user_id'], $order_id]);
            $msg = "Kòmand #$order_id valide ak siksè!";
        } else {
            $error = "Kòmand sa a pa disponib pou valide!";
        }
    } catch (PDOException $e) {
        $error = "Erè: " . $e->getMessage();
    }
}

// 2. REJTE KÒMAND
if (isset($_POST['reject_order'])) {
    $order_id = intval($_POST['order_id']);
    $reject_reason = trim(htmlspecialchars($_POST['reject_reason'] ?? ''));

    try {
        $check = $pdo->prepare("SELECT id, status FROM orders WHERE id = ? AND status = 'pending'");
        $check->execute([$order_id]);

        if ($check->rowCount() > 0) {
            $pdo->prepare("UPDATE orders SET status = 'rejected', rejected_at = NOW(), rejected_by = ?, reject_reason = ? WHERE id = ?")
                ->execute([$_SESSION['user_id'], $reject_reason, $order_id]);

            // Remèt pwodwi yo nan stock la
            $itemsStmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $itemsStmt->execute([$order_id]);
            $items = $itemsStmt->fetchAll();

            foreach ($items as $item) {
                $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ? WHERE id = ?")
                    ->execute([$item['quantity'], $item['product_id']]);
            }

            $msg = "Kòmand #$order_id rejte. Pwodwi yo remèt nan stock.";
        } else {
            $error = "Kòmand sa a pa disponib pou rejte!";
        }
    } catch (PDOException $e) {
        $error = "Erè: " . $e->getMessage();
    }
}

// 3. MAKE KÒMAND KÒM LIVRE
if (isset($_POST['mark_delivered'])) {
    $order_id = intval($_POST['order_id']);

    try {
        $check = $pdo->prepare("SELECT id, status FROM orders WHERE id = ? AND status = 'validated'");
        $check->execute([$order_id]);

        if ($check->rowCount() > 0) {
            $pdo->prepare("UPDATE orders SET status = 'delivered', delivered_at = NOW(), delivered_by = ? WHERE id = ?")
                ->execute([$_SESSION['user_id'], $order_id]);
            $msg = "Kòmand #$order_id make kòm livre!";
        } else {
            $error = "Kòmand sa a dwe valide anvan li livre!";
        }
    } catch (PDOException $e) {
        $error = "Erè: " . $e->getMessage();
    }
}

// 4. ANILE KÒMAND (pa admin)
if (isset($_POST['admin_cancel_order'])) {
    $order_id = intval($_POST['order_id']);

    try {
        $pdo->prepare("UPDATE orders SET status = 'cancelled', cancelled_at = NOW(), cancelled_by = ? WHERE id = ? AND status IN ('pending', 'validated')")
            ->execute([$_SESSION['user_id'], $order_id]);

        // Remèt pwodwi yo nan stock la
        $itemsStmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $itemsStmt->execute([$order_id]);
        $items = $itemsStmt->fetchAll();

        foreach ($items as $item) {
            $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ? WHERE id = ?")
                ->execute([$item['quantity'], $item['product_id']]);
        }

        $msg = "Kòmand #$order_id anile!";
    } catch (PDOException $e) {
        $error = "Erè: " . $e->getMessage();
    }
}

// ==================== REKIPERE DONE KÒMAND YO ====================

$pendingOrders = [];
$validatedOrders = [];
$deliveredOrders = [];
$rejectedOrders = [];
$cancelledOrders = [];
$allOrders = [];

// ============ KOREKSYON: Fonksyon pou koreje chemen imaj la ============
function getImageUrl($receiptPath)
{
    if (empty($receiptPath)) return '';

    // Si chemen an kòmanse ak http oswa https, retounen li jan li ye a
    if (strpos($receiptPath, 'http') === 0) {
        return $receiptPath;
    }

    // Si chemen an gen ../../, ranplase l ak chemen absoli relatif nan root sit la
    if (strpos($receiptPath, '../../') === 0) {
        return str_replace('../../', '/le-stock/', $receiptPath);
    }

    // Si chemen an gen ../, ranplase l
    if (strpos($receiptPath, '../') === 0) {
        return str_replace('../', '/le-stock/', $receiptPath);
    }

    // Si chemen an kòmanse ak uploads/, ajoute /le-stock/ devan l
    if (strpos($receiptPath, 'uploads/') === 0) {
        return '/le-stock/' . $receiptPath;
    }

    // Si chemen an kòmanse ak /, retounen li jan li ye a
    if (strpos($receiptPath, '/') === 0) {
        return $receiptPath;
    }

    // Pou tout lòt ka, ajoute /le-stock/uploads/payments/
    return '/le-stock/uploads/payments/' . basename($receiptPath);
}

try {
    // ============ KOREKSYON: Requète ki pran tout kolon ki nesesè yo ============
    $stmt = $pdo->query("
        SELECT 
            o.*,
            u.prenom,
            u.nom,
            u.email,
            u.telephone,
            COUNT(oi.id) as item_count,
            GROUP_CONCAT(
                CONCAT(p.name, ' (x', oi.quantity, ')') 
                SEPARATOR '||'
            ) as items_summary
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");

    $allOrders = $stmt->fetchAll();

    foreach ($allOrders as $order) {
        switch ($order['status']) {
            case 'pending':
                $pendingOrders[] = $order;
                break;
            case 'validated':
            case 'approved':
            case 'processing':
                $validatedOrders[] = $order;
                break;
            case 'delivered':
            case 'completed':
            case 'shipped':
                $deliveredOrders[] = $order;
                break;
            case 'rejected':
            case 'refused':
                $rejectedOrders[] = $order;
                break;
            case 'cancelled':
                $cancelledOrders[] = $order;
                break;
        }
    }
} catch (PDOException $e) {
    $error = "Erè nan chajman kòmand yo: " . $e->getMessage();
}

// Fonksyon èd
function getStatusBadge($status)
{
    switch ($status) {
        case 'pending':
            return ['bg-yellow-100 text-yellow-800 border-yellow-200', 'An kou (ap tann)', 'fa-clock', 'text-yellow-500'];
        case 'validated':
        case 'approved':
        case 'processing':
            return ['bg-blue-100 text-blue-800 border-blue-200', 'Valide', 'fa-check-circle', 'text-blue-500'];
        case 'delivered':
        case 'completed':
        case 'shipped':
            return ['bg-green-100 text-green-800 border-green-200', 'Livre', 'fa-box', 'text-green-500'];
        case 'rejected':
        case 'refused':
            return ['bg-red-100 text-red-800 border-red-200', 'Rejte', 'fa-times-circle', 'text-red-500'];
        case 'cancelled':
            return ['bg-gray-100 text-gray-800 border-gray-200', 'Anile', 'fa-ban', 'text-gray-500'];
        default:
            return ['bg-slate-100 text-slate-800 border-slate-200', $status, 'fa-question', 'text-slate-500'];
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
        case 'delivered':
        case 'completed':
        case 'shipped':
            return 'bg-green-600';
        case 'rejected':
        case 'refused':
            return 'bg-red-600';
        case 'cancelled':
            return 'bg-gray-600';
        default:
            return 'bg-slate-600';
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
        case 'moncash':
        case 'natcash':
            return 'fa-mobile-alt';
        case 'cash':
            return 'fa-money-bill-wave';
        default:
            return 'fa-money-bill';
    }
}

function formatPrice($price)
{
    return number_format($price, 2) . ' HTG';
}

function getPaymentMethodName($method)
{
    switch ($method) {
        case 'moncash':
            return 'MonCash';
        case 'natcash':
            return 'NatCash';
        case 'mobile_wallet':
            return 'Mobile Wallet';
        case 'card':
            return 'Kat Kredi';
        case 'paypal':
            return 'PayPal';
        case 'cash':
            return 'Lajan Kach';
        default:
            return ucfirst($method);
    }
}
?>

<!DOCTYPE html>
<html lang="ht">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Jesyon Kòmand yo | LE-STOCK Admin</title>
    <style>
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
            background: #e2e8f0;
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
            background: #cbd5e1;
            color: #64748b;
        }

        .timeline-dot.rejected {
            background: #ef4444;
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

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 100;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 1.5rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalSlide 0.3s ease;
        }

        @keyframes modalSlide {
            from {
                opacity: 0;
                transform: scale(0.9);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* ============ KOREKSYON: Styles enfòmasyon peman ============ */
        .payment-info-box {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-left: 4px solid #3b82f6;
            border-radius: 0.75rem;
            padding: 1rem;
            margin: 1rem 0;
        }

        .payment-info-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #1e40af;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .payment-info-value {
            color: #1e3a8a;
            font-weight: 600;
            font-size: 1rem;
        }

        .payment-proof-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 0.5rem;
            border: 2px solid #3b82f6;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .payment-proof-image:hover {
            transform: scale(1.02);
        }

        .moncash-badge {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .natcash-badge {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .wallet-badge {
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* ============ KOREKSYON: Styles adrès livrezon ============ */
        .delivery-info-box {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            border-left: 4px solid #22c55e;
            border-radius: 0.75rem;
            padding: 1rem;
            margin: 1rem 0;
        }

        .delivery-info-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #166534;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .delivery-info-value {
            color: #14532d;
            font-weight: 600;
            font-size: 1rem;
        }
    </style>
</head>

<body class="bg-slate-100 min-h-screen">

    <!-- Header ak Bouton Retounen -->
    <div class="bg-slate-900 text-white p-4 shadow-lg">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <a href="admin_dashboard.php" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg font-bold transition-all">
                <i class="fas fa-arrow-left"></i>
                Retounen nan Dashboard
            </a>
            <div class="text-right">
                <h1 class="text-xl font-black uppercase tracking-tight">LE STOCK <span class="text-blue-400">ADMIN</span></h1>
                <p class="text-xs text-slate-400">Jesyon Kòmand yo</p>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto p-6">

        <!-- Alerts -->
        <?php if ($msg): ?>
            <div class="bg-green-600 text-white p-4 rounded-2xl mb-6 shadow-lg uppercase text-xs text-center flex items-center justify-center gap-2">
                <i class="fas fa-check-circle"></i> <?= $msg ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-600 text-white p-4 rounded-2xl mb-6 shadow-lg uppercase text-xs text-center flex items-center justify-center gap-2">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="bg-slate-900 rounded-2xl p-6 sm:p-8 mb-6 border border-blue-800/50">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-black text-white mb-2 uppercase border-l-4 border-blue-500 pl-4">
                        <i class="fas fa-clipboard-list text-blue-400 mr-3"></i>
                        Jesyon Kòmand yo
                    </h1>
                    <p class="text-blue-200">Valide, rejte oswa make kòmand kòm livre</p>
                </div>
                <div class="flex gap-3">
                    <div class="bg-slate-800 rounded-xl p-4 text-center">
                        <p class="text-xs text-slate-400 uppercase">An kou</p>
                        <p class="text-2xl font-black text-yellow-500"><?= count($pendingOrders) ?></p>
                    </div>
                    <div class="bg-slate-800 rounded-xl p-4 text-center">
                        <p class="text-xs text-slate-400 uppercase">Valide</p>
                        <p class="text-2xl font-black text-blue-500"><?= count($validatedOrders) ?></p>
                    </div>
                    <div class="bg-slate-800 rounded-xl p-4 text-center">
                        <p class="text-xs text-slate-400 uppercase">Livre</p>
                        <p class="text-2xl font-black text-green-500"><?= count($deliveredOrders) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($allOrders)): ?>
            <div class="bg-white p-10 rounded-3xl text-center text-slate-400 shadow-lg">
                <i class="fas fa-shopping-bag text-6xl mb-4 text-slate-200"></i>
                <p class="text-xl">Pa gen kòmand nan sistèm nan</p>
            </div>
        <?php else: ?>

            <!-- Tabs -->
            <div class="bg-white rounded-t-xl border-b border-gray-200 overflow-x-auto shadow-sm">
                <div class="flex min-w-max">
                    <button onclick="switchTab('pending')" id="tab-pending" class="order-tab active px-5 py-4 text-sm font-medium text-gray-700 whitespace-nowrap flex items-center gap-2">
                        <i class="fas fa-clock text-yellow-500"></i> An kou
                        <?php if (count($pendingOrders) > 0): ?><span class="bg-yellow-500 text-white text-xs px-2 py-0.5 rounded-full"><?= count($pendingOrders) ?></span><?php endif; ?>
                    </button>
                    <button onclick="switchTab('validated')" id="tab-validated" class="order-tab px-5 py-4 text-sm font-medium text-gray-700 whitespace-nowrap flex items-center gap-2">
                        <i class="fas fa-check-circle text-blue-500"></i> Valide
                        <?php if (count($validatedOrders) > 0): ?><span class="bg-blue-500 text-white text-xs px-2 py-0.5 rounded-full"><?= count($validatedOrders) ?></span><?php endif; ?>
                    </button>
                    <button onclick="switchTab('delivered')" id="tab-delivered" class="order-tab px-5 py-4 text-sm font-medium text-gray-700 whitespace-nowrap flex items-center gap-2">
                        <i class="fas fa-box text-green-500"></i> Livre
                        <?php if (count($deliveredOrders) > 0): ?><span class="bg-green-500 text-white text-xs px-2 py-0.5 rounded-full"><?= count($deliveredOrders) ?></span><?php endif; ?>
                    </button>
                    <button onclick="switchTab('rejected')" id="tab-rejected" class="order-tab px-5 py-4 text-sm font-medium text-gray-700 whitespace-nowrap flex items-center gap-2">
                        <i class="fas fa-times-circle text-red-500"></i> Rejte
                        <?php if (count($rejectedOrders) > 0): ?><span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full"><?= count($rejectedOrders) ?></span><?php endif; ?>
                    </button>
                    <button onclick="switchTab('cancelled')" id="tab-cancelled" class="order-tab px-5 py-4 text-sm font-medium text-gray-700 whitespace-nowrap flex items-center gap-2">
                        <i class="fas fa-ban text-gray-500"></i> Anile
                        <?php if (count($cancelledOrders) > 0): ?><span class="bg-gray-500 text-white text-xs px-2 py-0.5 rounded-full"><?= count($cancelledOrders) ?></span><?php endif; ?>
                    </button>
                    <button onclick="switchTab('all')" id="tab-all" class="order-tab px-5 py-4 text-sm font-medium text-gray-700 whitespace-nowrap flex items-center gap-2">
                        <i class="fas fa-list text-slate-500"></i> Tout
                    </button>
                </div>
            </div>

            <!-- Orders Content -->
            <div class="bg-white rounded-b-xl p-6 min-h-[400px] shadow-sm">

                <!-- Pending Orders -->
                <div id="content-pending" class="tab-content space-y-4">
                    <?php if (empty($pendingOrders)): ?>
                        <div class="text-center py-10">
                            <i class="fas fa-clock text-5xl mb-4 text-yellow-200"></i>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Pa gen kòmand an kou</h3>
                            <p class="text-gray-600">Tout kòmand yo trete deja.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($pendingOrders as $order):
                            $statusBadge = getStatusBadge($order['status']);
                            $items = $order['items_summary'] ? explode('||', $order['items_summary']) : [];
                            // ============ KOREKSYON: Detekte wallet korèkteman ============
                            $isWallet = in_array($order['payment_method'], ['mobile_wallet', 'moncash', 'natcash']);
                            $walletType = $order['wallet_type'] ?? $order['payment_method'];
                            // ============ KOREKSYON: Chenen imaj ============
                            $receiptPath = $order['wallet_receipt_path'] ?? '';
                            $receiptUrl = getImageUrl($receiptPath);
                        ?>
                            <div class="order-card bg-white rounded-xl p-6 border-2 border-yellow-200 hover-lift shadow-sm">
                                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-14 h-14 bg-yellow-500 rounded-xl flex items-center justify-center flex-shrink-0">
                                            <i class="fas fa-clock text-2xl text-white"></i>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                                <h3 class="text-lg font-bold text-gray-900">Kòmand #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></h3>
                                                <span class="status-indicator <?= $statusBadge[0] ?>">
                                                    <span class="pulse-dot bg-yellow-500"></span>
                                                    <?= $statusBadge[1] ?>
                                                </span>
                                                <?php if ($walletType === 'moncash'): ?>
                                                    <span class="moncash-badge"><i class="fas fa-mobile-alt"></i> MonCash</span>
                                                <?php elseif ($walletType === 'natcash'): ?>
                                                    <span class="natcash-badge"><i class="fas fa-mobile-alt"></i> NatCash</span>
                                                <?php elseif ($isWallet): ?>
                                                    <span class="wallet-badge"><i class="fas fa-wallet"></i> Wallet</span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-gray-500 text-sm">
                                                <i class="far fa-calendar-alt mr-1"></i>
                                                <?= date('d/m/Y à H:i', strtotime($order['created_at'])) ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-black text-gray-900"><?= formatPrice($order['total_amount']) ?></p>
                                        <p class="text-sm text-gray-500"><?= $order['item_count'] ?> atik</p>
                                    </div>
                                </div>

                                <!-- Timeline -->
                                <div class="timeline mb-6">
                                    <div class="timeline-step">
                                        <div class="timeline-dot completed"><i class="fas fa-check text-xs"></i></div>
                                        <span class="text-xs text-gray-600">Kreye</span>
                                    </div>
                                    <div class="timeline-step">
                                        <div class="timeline-dot active"><i class="fas fa-clock text-xs"></i></div>
                                        <span class="text-xs text-yellow-600 font-semibold">Ap tann</span>
                                    </div>
                                    <div class="timeline-step">
                                        <div class="timeline-dot pending"><i class="fas fa-check-circle text-xs"></i></div>
                                        <span class="text-xs text-gray-400">Valide</span>
                                    </div>
                                    <div class="timeline-step">
                                        <div class="timeline-dot pending"><i class="fas fa-box text-xs"></i></div>
                                        <span class="text-xs text-gray-400">Livre</span>
                                    </div>
                                </div>

                                <!-- ============ KOREKSYON: ENFÒMASYON PEMAN MOBILE WALLET ============ -->
                                <?php if ($isWallet): ?>
                                    <div class="payment-info-box mb-4">
                                        <div class="flex items-center gap-2 mb-3">
                                            <i class="fas fa-mobile-alt text-blue-600 text-xl"></i>
                                            <h4 class="font-bold text-blue-900">Enfòmasyon Peman <?= getPaymentMethodName($walletType) ?></h4>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                            <?php if (!empty($order['wallet_full_name'])): ?>
                                                <div>
                                                    <p class="payment-info-label"><i class="fas fa-user mr-1"></i> Non Konplè</p>
                                                    <p class="payment-info-value"><?= htmlspecialchars($order['wallet_full_name']) ?></p>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($order['wallet_email'])): ?>
                                                <div>
                                                    <p class="payment-info-label"><i class="fas fa-envelope mr-1"></i> Imèl</p>
                                                    <p class="payment-info-value"><?= htmlspecialchars($order['wallet_email']) ?></p>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($order['wallet_sender_phone'])): ?>
                                                <div>
                                                    <p class="payment-info-label"><i class="fas fa-phone mr-1"></i> Telefòn (ki fè transfè a)</p>
                                                    <p class="payment-info-value"><?= htmlspecialchars($order['wallet_sender_phone']) ?></p>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($order['wallet_transaction_id'])): ?>
                                                <div>
                                                    <p class="payment-info-label"><i class="fas fa-hashtag mr-1"></i> ID Transaksyon</p>
                                                    <p class="payment-info-value"><?= htmlspecialchars($order['wallet_transaction_id']) ?></p>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($walletType)): ?>
                                                <div>
                                                    <p class="payment-info-label"><i class="fas fa-wallet mr-1"></i> Sèvis</p>
                                                    <p class="payment-info-value"><?= htmlspecialchars(ucfirst($walletType)) ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- ============ KOREKSYON: Foto prev peman ============ -->
                                        <?php if (!empty($receiptUrl)): ?>
                                            <div class="mt-4">
                                                <p class="payment-info-label mb-2"><i class="fas fa-image mr-1"></i> Foto Prev Peman:</p>
                                                <img src="<?= htmlspecialchars($receiptUrl) ?>"
                                                    alt="Prev Peman"
                                                    class="payment-proof-image"
                                                    onclick="openImageModal(this.src)"
                                                    onerror="handleImageError(this, '<?= htmlspecialchars($receiptUrl) ?>')">
                                                <p class="text-xs text-blue-600 mt-2"><i class="fas fa-hand-pointer mr-1"></i>Klike sou imaj la pou agrandi l</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="bg-yellow-100 border border-yellow-400 rounded-lg p-3 mt-4">
                                                <p class="text-yellow-800 text-sm"><i class="fas fa-exclamation-triangle mr-2"></i>Pa gen foto prev peman!</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- ============ KOREKSYON: ADRESÈ LIVREZON ============ -->
                                <?php if (!empty($order['delivery_address'])): ?>
                                    <div class="delivery-info-box mb-4">
                                        <div class="flex items-center gap-2 mb-3">
                                            <i class="fas fa-map-marker-alt text-green-600 text-xl"></i>
                                            <h4 class="font-bold text-green-900">Adrès Livrezon</h4>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="md:col-span-2">
                                                <p class="delivery-info-label"><i class="fas fa-home mr-1"></i> Adrès</p>
                                                <p class="delivery-info-value"><?= htmlspecialchars($order['delivery_address']) ?></p>
                                            </div>
                                            <?php if (!empty($order['delivery_city'])): ?>
                                                <div>
                                                    <p class="delivery-info-label"><i class="fas fa-city mr-1"></i> Vil / Komin</p>
                                                    <p class="delivery-info-value"><?= htmlspecialchars($order['delivery_city']) ?></p>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($order['delivery_phone'])): ?>
                                                <div>
                                                    <p class="delivery-info-label"><i class="fas fa-phone mr-1"></i> Telefòn Livrezon</p>
                                                    <p class="delivery-info-value"><?= htmlspecialchars($order['delivery_phone']) ?></p>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($order['delivery_notes'])): ?>
                                                <div class="md:col-span-2">
                                                    <p class="delivery-info-label"><i class="fas fa-sticky-note mr-1"></i> Nòt</p>
                                                    <p class="delivery-info-value text-sm"><?= htmlspecialchars($order['delivery_notes']) ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="bg-orange-50 border-l-4 border-orange-400 p-3 mb-4 rounded-r-lg">
                                        <p class="text-orange-800 text-sm"><i class="fas fa-exclamation-triangle mr-2"></i>Pa gen adrès livrezon pou kòmand sa a!</p>
                                    </div>
                                <?php endif; ?>

                                <!-- Client Info -->
                                <div class="bg-slate-50 rounded-xl p-4 mb-4">
                                    <h4 class="font-bold text-sm text-gray-700 mb-2 uppercase">
                                        <i class="fas fa-user mr-2 text-blue-500"></i>Klian:
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                        <div>
                                            <span class="text-gray-500">Non:</span>
                                            <p class="font-semibold"><?= htmlspecialchars($order['prenom'] . ' ' . $order['nom']) ?></p>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Email:</span>
                                            <p class="font-semibold"><?= htmlspecialchars($order['email']) ?></p>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Telefòn:</span>
                                            <p class="font-semibold"><?= htmlspecialchars($order['telephone'] ?? 'N/A') ?></p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Items Summary -->
                                <div class="bg-slate-50 rounded-xl p-4 mb-4">
                                    <h4 class="font-bold text-sm text-gray-700 mb-2 uppercase">
                                        <i class="fas fa-box mr-2 text-blue-500"></i>Pwodwi yo:
                                    </h4>
                                    <ul class="space-y-2 text-sm">
                                        <?php foreach ($items as $item): ?>
                                            <li class="flex items-center gap-2">
                                                <i class="fas fa-chevron-right text-xs text-blue-500"></i>
                                                <?= htmlspecialchars($item) ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>

                                <!-- Payment Info -->
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 p-4 bg-slate-50 rounded-xl text-sm">
                                    <div>
                                        <p class="text-xs text-gray-500 mb-1">Peman</p>
                                        <p class="font-semibold flex items-center gap-1">
                                            <i class="fas <?= getPaymentIcon($order['payment_method']) ?> text-blue-500"></i>
                                            <?= getPaymentMethodName($isWallet ? $walletType : $order['payment_method']) ?>
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 mb-1">Sou-total</p>
                                        <p class="font-semibold"><?= formatPrice($order['subtotal']) ?></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 mb-1">Livrezon</p>
                                        <p class="font-semibold"><?= ($order['shipping_amount'] ?? 0) == 0 ? 'Gratis' : formatPrice($order['shipping_amount']) ?></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 mb-1">Referans</p>
                                        <p class="font-semibold text-xs truncate" title="<?= $order['wallet_transaction_id'] ?? 'N/A' ?>">
                                            <?= !empty($order['wallet_transaction_id']) ? substr($order['wallet_transaction_id'], -12) : 'N/A' ?>
                                        </p>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex flex-col sm:flex-row gap-3 justify-end">
                                    <button onclick="openRejectModal(<?= $order['id'] ?>)" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-red-100 text-red-700 hover:bg-red-200 font-bold transition-all">
                                        <i class="fas fa-times"></i> Rejte
                                    </button>
                                    <form method="POST" class="inline" onsubmit="return confirm('Èske ou sèten ou vle valide kòmand sa a?');">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <button type="submit" name="validate_order" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-green-600 text-white hover:bg-green-700 font-bold transition-all shadow-lg hover:shadow-green-500/25">
                                            <i class="fas fa-check"></i> Valide Kòmand
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
                        <div class="text-center py-10">
                            <i class="fas fa-check-circle text-5xl mb-4 text-blue-200"></i>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Pa gen kòmand valide</h3>
                            <p class="text-gray-600">Kòmand yo ap tann valide w.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($validatedOrders as $order):
                            $statusBadge = getStatusBadge($order['status']);
                            $items = $order['items_summary'] ? explode('||', $order['items_summary']) : [];
                            $isWallet = in_array($order['payment_method'], ['mobile_wallet', 'moncash', 'natcash']);
                            $walletType = $order['wallet_type'] ?? $order['payment_method'];
                        ?>
                            <div class="order-card bg-white rounded-xl p-6 border-2 border-blue-200 hover-lift shadow-sm">
                                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-14 h-14 bg-blue-500 rounded-xl flex items-center justify-center flex-shrink-0">
                                            <i class="fas fa-check-circle text-2xl text-white"></i>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                                <h3 class="text-lg font-bold text-gray-900">Kòmand #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></h3>
                                                <span class="status-indicator <?= $statusBadge[0] ?>"><i class="fas fa-shield-alt text-xs"></i> <?= $statusBadge[1] ?></span>
                                                <?php if ($walletType === 'moncash'): ?><span class="moncash-badge"><i class="fas fa-mobile-alt"></i> MonCash</span><?php elseif ($walletType === 'natcash'): ?><span class="natcash-badge"><i class="fas fa-mobile-alt"></i> NatCash</span><?php elseif ($isWallet): ?><span class="wallet-badge"><i class="fas fa-wallet"></i> Wallet</span><?php endif; ?>
                                            </div>
                                            <p class="text-gray-500 text-sm"><i class="far fa-calendar-check mr-1"></i><?= date('d/m/Y à H:i', strtotime($order['created_at'])) ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-black text-gray-900"><?= formatPrice($order['total_amount']) ?></p>
                                        <p class="text-sm text-gray-500"><?= $order['item_count'] ?> atik</p>
                                    </div>
                                </div>
                                <div class="timeline mb-6">
                                    <div class="timeline-step">
                                        <div class="timeline-dot completed"><i class="fas fa-check text-xs"></i></div><span class="text-xs text-green-600">Kreye</span>
                                    </div>
                                    <div class="timeline-step">
                                        <div class="timeline-dot completed"><i class="fas fa-check text-xs"></i></div><span class="text-xs text-green-600">Tann</span>
                                    </div>
                                    <div class="timeline-step">
                                        <div class="timeline-dot completed"><i class="fas fa-check-circle text-xs"></i></div><span class="text-xs text-green-600 font-semibold">Valide</span>
                                    </div>
                                    <div class="timeline-step">
                                        <div class="timeline-dot pending"><i class="fas fa-box text-xs"></i></div><span class="text-xs text-gray-400">Livre</span>
                                    </div>
                                </div>

                                <!-- ============ ADRESÈ LIVREZON POU VALIDATED ============ -->
                                <?php if (!empty($order['delivery_address'])): ?>
                                    <div class="delivery-info-box mb-4">
                                        <div class="flex items-center gap-2 mb-2">
                                            <i class="fas fa-map-marker-alt text-green-600"></i>
                                            <h4 class="font-bold text-green-900 text-sm">Adrès Livrezon</h4>
                                        </div>
                                        <p class="delivery-info-value text-sm"><?= htmlspecialchars($order['delivery_address']) ?></p>
                                        <?php if (!empty($order['delivery_phone'])): ?>
                                            <p class="text-sm text-green-700 mt-1"><i class="fas fa-phone mr-1"></i> <?= htmlspecialchars($order['delivery_phone']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Wallet Info pou validated -->
                                <?php if ($isWallet): ?>
                                    <div class="payment-info-box mb-4">
                                        <div class="grid grid-cols-2 gap-3 text-sm">
                                            <?php if (!empty($order['wallet_full_name'])): ?><div><span class="payment-info-label">Non</span>
                                                    <p class="payment-info-value text-sm"><?= htmlspecialchars($order['wallet_full_name']) ?></p>
                                                </div><?php endif; ?>
                                            <?php if (!empty($order['wallet_sender_phone'])): ?><div><span class="payment-info-label">Telefòn</span>
                                                    <p class="payment-info-value text-sm"><?= htmlspecialchars($order['wallet_sender_phone']) ?></p>
                                                </div><?php endif; ?>
                                            <?php if (!empty($order['wallet_transaction_id'])): ?><div><span class="payment-info-label">ID Transaksyon</span>
                                                    <p class="payment-info-value text-sm"><?= htmlspecialchars($order['wallet_transaction_id']) ?></p>
                                                </div><?php endif; ?>
                                        </div>
                                        <?php if (!empty($order['wallet_receipt_path'])): ?>
                                            <div class="mt-3">
                                                <img src="<?= getImageUrl($order['wallet_receipt_path']) ?>" alt="Prev" class="payment-proof-image" style="max-height: 200px;" onclick="openImageModal(this.src)" onerror="this.style.display='none'">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="bg-slate-50 rounded-xl p-4 mb-4">
                                    <h4 class="font-bold text-sm text-gray-700 mb-2 uppercase"><i class="fas fa-user mr-2 text-blue-500"></i>Klian:</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                        <div><span class="text-gray-500">Non:</span>
                                            <p class="font-semibold"><?= htmlspecialchars($order['prenom'] . ' ' . $order['nom']) ?></p>
                                        </div>
                                        <div><span class="text-gray-500">Email:</span>
                                            <p class="font-semibold"><?= htmlspecialchars($order['email']) ?></p>
                                        </div>
                                        <div><span class="text-gray-500">Telefòn:</span>
                                            <p class="font-semibold"><?= htmlspecialchars($order['telephone'] ?? 'N/A') ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col sm:flex-row gap-3 justify-end">
                                    <form method="POST" class="inline" onsubmit="return confirm('Èske ou sèten ou vle anile kòmand sa a?');">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <button type="submit" name="admin_cancel_order" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-gray-100 text-gray-700 hover:bg-gray-200 font-bold transition-all"><i class="fas fa-ban"></i> Anile</button>
                                    </form>
                                    <form method="POST" class="inline" onsubmit="return confirm('Èske ou sèten ou vle make kòmand sa a kòm livre?');">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <button type="submit" name="mark_delivered" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-green-600 text-white hover:bg-green-700 font-bold transition-all shadow-lg hover:shadow-green-500/25"><i class="fas fa-box"></i> Make kòm Livre</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Delivered Orders -->
                <div id="content-delivered" class="tab-content hidden space-y-4">
                    <?php if (empty($deliveredOrders)): ?>
                        <div class="text-center py-10"><i class="fas fa-box text-5xl mb-4 text-green-200"></i>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Pa gen kòmand livre</h3>
                            <p class="text-gray-600">Kòmand yo poko livre.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($deliveredOrders as $order):
                            $statusBadge = getStatusBadge($order['status']);
                        ?>
                            <div class="order-card bg-white rounded-xl p-6 border-2 border-green-200 opacity-80 shadow-sm">
                                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-14 h-14 bg-green-500 rounded-xl flex items-center justify-center flex-shrink-0"><i class="fas fa-box text-2xl text-white"></i></div>
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                                <h3 class="text-lg font-bold text-gray-900">Kòmand #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></h3>
                                                <span class="status-indicator <?= $statusBadge[0] ?>"><i class="fas fa-check-double text-xs"></i> <?= $statusBadge[1] ?></span>
                                            </div>
                                            <p class="text-gray-500 text-sm"><i class="far fa-calendar-check mr-1"></i><?= date('d/m/Y à H:i', strtotime($order['created_at'])) ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-black text-gray-900"><?= formatPrice($order['total_amount']) ?></p>
                                        <p class="text-sm text-gray-500"><?= $order['item_count'] ?> atik</p>
                                    </div>
                                </div>
                                <?php if (!empty($order['delivery_address'])): ?>
                                    <div class="delivery-info-box mb-4">
                                        <p class="delivery-info-value text-sm"><i class="fas fa-map-marker-alt mr-2"></i><?= htmlspecialchars($order['delivery_address']) ?></p>
                                    </div>
                                <?php endif; ?>
                                <div class="bg-slate-50 rounded-xl p-4">
                                    <h4 class="font-bold text-sm text-gray-700 mb-2 uppercase"><i class="fas fa-user mr-2 text-blue-500"></i>Klian:</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                        <div><span class="text-gray-500">Non:</span>
                                            <p class="font-semibold"><?= htmlspecialchars($order['prenom'] . ' ' . $order['nom']) ?></p>
                                        </div>
                                        <div><span class="text-gray-500">Email:</span>
                                            <p class="font-semibold"><?= htmlspecialchars($order['email']) ?></p>
                                        </div>
                                        <div><span class="text-gray-500">Telefòn:</span>
                                            <p class="font-semibold"><?= htmlspecialchars($order['telephone'] ?? 'N/A') ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Rejected Orders -->
                <div id="content-rejected" class="tab-content hidden space-y-4">
                    <?php if (empty($rejectedOrders)): ?>
                        <div class="text-center py-10"><i class="fas fa-times-circle text-5xl mb-4 text-red-200"></i>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Pa gen kòmand rejte</h3>
                            <p class="text-gray-600">Okenn kòmand pa rejte.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($rejectedOrders as $order):
                            $statusBadge = getStatusBadge($order['status']);
                        ?>
                            <div class="order-card bg-white rounded-xl p-6 border-2 border-red-200 opacity-80 shadow-sm">
                                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-14 h-14 bg-red-500 rounded-xl flex items-center justify-center flex-shrink-0"><i class="fas fa-times-circle text-2xl text-white"></i></div>
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                                <h3 class="text-lg font-bold text-gray-900 line-through">Kòmand #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></h3>
                                                <span class="status-indicator <?= $statusBadge[0] ?>"><i class="fas fa-times text-xs"></i> <?= $statusBadge[1] ?></span>
                                            </div>
                                            <p class="text-gray-500 text-sm"><i class="far fa-calendar-times mr-1"></i><?= date('d/m/Y à H:i', strtotime($order['created_at'])) ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-black text-gray-400 line-through"><?= formatPrice($order['total_amount']) ?></p>
                                        <p class="text-sm text-gray-500"><?= $order['item_count'] ?> atik</p>
                                    </div>
                                </div>
                                <?php if (!empty($order['reject_reason'])): ?>
                                    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4">
                                        <p class="text-sm text-red-700"><i class="fas fa-exclamation-triangle mr-2"></i><strong>Rezon rejte:</strong> <?= htmlspecialchars($order['reject_reason']) ?></p>
                                    </div>
                                <?php endif; ?>
                                <div class="bg-slate-50 rounded-xl p-4">
                                    <h4 class="font-bold text-sm text-gray-700 mb-2 uppercase"><i class="fas fa-user mr-2 text-blue-500"></i>Klian:</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                        <div><span class="text-gray-500">Non:</span>
                                            <p class="font-semibold"><?= htmlspecialchars($order['prenom'] . ' ' . $order['nom']) ?></p>
                                        </div>
                                        <div><span class="text-gray-500">Email:</span>
                                            <p class="font-semibold"><?= htmlspecialchars($order['email']) ?></p>
                                        </div>
                                        <div><span class="text-gray-500">Telefòn:</span>
                                            <p class="font-semibold"><?= htmlspecialchars($order['telephone'] ?? 'N/A') ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Cancelled Orders -->
                <div id="content-cancelled" class="tab-content hidden space-y-4">
                    <?php if (empty($cancelledOrders)): ?>
                        <div class="text-center py-10"><i class="fas fa-ban text-5xl mb-4 text-gray-200"></i>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Pa gen kòmand anile</h3>
                            <p class="text-gray-600">Okenn kòmand pa anile.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($cancelledOrders as $order):
                            $statusBadge = getStatusBadge($order['status']);
                        ?>
                            <div class="order-card bg-white rounded-xl p-6 border-2 border-gray-200 opacity-60 shadow-sm">
                                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-14 h-14 bg-gray-500 rounded-xl flex items-center justify-center flex-shrink-0"><i class="fas fa-ban text-2xl text-white"></i></div>
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                                <h3 class="text-lg font-bold text-gray-900 line-through">Kòmand #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></h3>
                                                <span class="status-indicator <?= $statusBadge[0] ?>"><i class="fas fa-user-slash text-xs"></i> <?= $statusBadge[1] ?></span>
                                            </div>
                                            <p class="text-gray-500 text-sm"><i class="far fa-calendar-times mr-1"></i><?= date('d/m/Y à H:i', strtotime($order['created_at'])) ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-black text-gray-400 line-through"><?= formatPrice($order['total_amount']) ?></p>
                                        <p class="text-sm text-gray-500"><?= $order['item_count'] ?> atik</p>
                                    </div>
                                </div>
                                <div class="bg-slate-50 rounded-xl p-4">
                                    <h4 class="font-bold text-sm text-gray-700 mb-2 uppercase"><i class="fas fa-user mr-2 text-blue-500"></i>Klian:</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                        <div><span class="text-gray-500">Non:</span>
                                            <p class="font-semibold"><?= htmlspecialchars($order['prenom'] . ' ' . $order['nom']) ?></p>
                                        </div>
                                        <div><span class="text-gray-500">Email:</span>
                                            <p class="font-semibold"><?= htmlspecialchars($order['email']) ?></p>
                                        </div>
                                        <div><span class="text-gray-500">Telefòn:</span>
                                            <p class="font-semibold"><?= htmlspecialchars($order['telephone'] ?? 'N/A') ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- All Orders -->
                <div id="content-all" class="tab-content hidden space-y-4">
                    <?php foreach ($allOrders as $order):
                        $statusBadge = getStatusBadge($order['status']);
                        $statusColor = getStatusColor($order['status']);
                        $isCancelled = $order['status'] === 'cancelled';
                        $isRejected = in_array($order['status'], ['rejected', 'refused']);
                        $isWallet = in_array($order['payment_method'], ['mobile_wallet', 'moncash', 'natcash']);
                        $walletType = $order['wallet_type'] ?? $order['payment_method'];
                    ?>
                        <div class="order-card bg-white rounded-xl p-6 border-2 <?= $isCancelled ? 'border-gray-200 opacity-60' : ($isRejected ? 'border-red-200 opacity-80' : 'border-slate-200') ?> hover-lift shadow-sm">
                            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-14 h-14 <?= $statusColor ?> rounded-xl flex items-center justify-center flex-shrink-0"><i class="fas <?= $statusBadge[2] ?> text-2xl text-white"></i></div>
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap mb-1">
                                            <h3 class="text-lg font-bold text-gray-900 <?= ($isCancelled || $isRejected) ? 'line-through' : '' ?>">Kòmand #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></h3>
                                            <span class="status-indicator <?= $statusBadge[0] ?>">
                                                <?php if ($order['status'] === 'pending'): ?><span class="pulse-dot <?= $statusBadge[3] ?>"></span><?php else: ?><i class="fas <?= $statusBadge[2] ?> text-xs"></i><?php endif; ?>
                                                <?= $statusBadge[1] ?>
                                            </span>
                                            <?php if ($walletType === 'moncash'): ?><span class="moncash-badge"><i class="fas fa-mobile-alt"></i> MonCash</span><?php elseif ($walletType === 'natcash'): ?><span class="natcash-badge"><i class="fas fa-mobile-alt"></i> NatCash</span><?php elseif ($isWallet): ?><span class="wallet-badge"><i class="fas fa-wallet"></i> Wallet</span><?php endif; ?>
                                        </div>
                                        <p class="text-gray-500 text-sm"><i class="far fa-calendar<?= ($isCancelled || $isRejected) ? '-times' : '-alt' ?> mr-1"></i><?= date('d/m/Y à H:i', strtotime($order['created_at'])) ?></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-black <?= ($isCancelled || $isRejected) ? 'text-gray-400 line-through' : 'text-gray-900' ?>"><?= formatPrice($order['total_amount']) ?></p>
                                    <p class="text-sm text-gray-500"><?= $order['item_count'] ?> atik</p>
                                </div>
                            </div>
                            <?php if (!empty($order['delivery_address'])): ?>
                                <div class="delivery-info-box mb-4">
                                    <p class="delivery-info-value text-sm"><i class="fas fa-map-marker-alt mr-2"></i><?= htmlspecialchars($order['delivery_address']) ?></p>
                                </div>
                            <?php endif; ?>
                            <div class="bg-slate-50 rounded-xl p-4">
                                <h4 class="font-bold text-sm text-gray-700 mb-2 uppercase"><i class="fas fa-user mr-2 text-blue-500"></i>Klian:</h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                    <div><span class="text-gray-500">Non:</span>
                                        <p class="font-semibold"><?= htmlspecialchars($order['prenom'] . ' ' . $order['nom']) ?></p>
                                    </div>
                                    <div><span class="text-gray-500">Email:</span>
                                        <p class="font-semibold"><?= htmlspecialchars($order['email']) ?></p>
                                    </div>
                                    <div><span class="text-gray-500">Telefòn:</span>
                                        <p class="font-semibold"><?= htmlspecialchars($order['telephone'] ?? 'N/A') ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
        <?php endif; ?>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-black uppercase text-gray-900">Rejte Kòmand</h3>
                <button onclick="closeRejectModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-xl"></i></button>
            </div>
            <form method="POST" id="rejectForm">
                <input type="hidden" name="order_id" id="rejectOrderId">
                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2 uppercase">Rezon rejte (opsyonèl)</label>
                    <textarea name="reject_reason" rows="4" class="w-full p-4 bg-slate-50 rounded-xl outline-none ring-1 ring-slate-200 focus:ring-red-500" placeholder="Antre rezon ki fè ou rejte kòmand sa a..."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeRejectModal()" class="flex-1 py-3 bg-gray-200 text-gray-700 rounded-xl font-bold hover:bg-gray-300 transition-all">Anile</button>
                    <button type="submit" name="reject_order" class="flex-1 py-3 bg-red-600 text-white rounded-xl font-bold hover:bg-red-700 transition-all"><i class="fas fa-times mr-2"></i>Konfime Rejte</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="modal" style="z-index: 200;">
        <div class="modal-content p-2" style="max-width: 90%; max-height: 90%;">
            <div class="flex justify-between items-center mb-2">
                <h3 class="text-lg font-bold text-gray-900">Prev Peman</h3>
                <button onclick="closeImageModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-xl"></i></button>
            </div>
            <img id="modalImage" src="" alt="Prev Peman" style="max-width: 100%; max-height: 80vh; border-radius: 0.5rem;">
        </div>
    </div>

    <script>
        // Tab switching
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(content => content.classList.add('hidden'));
            document.querySelectorAll('.order-tab').forEach(tab => tab.classList.remove('active'));
            document.getElementById('content-' + tabName).classList.remove('hidden');
            document.getElementById('tab-' + tabName).classList.add('active');
            const cards = document.querySelectorAll('#content-' + tabName + ' .order-card');
            cards.forEach((card, index) => {
                card.style.animation = 'none';
                setTimeout(() => {
                    card.style.animation = `slideIn 0.4s ease forwards`;
                    card.style.animationDelay = `${index * 0.05}s`;
                }, 10);
            });
        }

        function openRejectModal(orderId) {
            document.getElementById('rejectOrderId').value = orderId;
            document.getElementById('rejectModal').classList.add('active');
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.remove('active');
        }

        function openImageModal(src) {
            document.getElementById('modalImage').src = src;
            document.getElementById('imageModal').classList.add('active');
        }

        function closeImageModal() {
            document.getElementById('imageModal').classList.remove('active');
        }

        // ============ KOREKSYON: Fonksyon pou montre mesaj erè si imaj pa chaje ============
        function handleImageError(img, path) {
            img.style.display = 'none';
            const errorDiv = document.createElement('div');
            errorDiv.className = 'bg-red-100 border border-red-400 rounded-lg p-3 mt-2';
            errorDiv.innerHTML = `<p class="text-red-800 text-sm"><i class="fas fa-exclamation-triangle mr-2"></i>Pa ka chaje imaj la.</p><p class="text-xs text-red-600 mt-1">Chemen: ${path}</p>`;
            img.parentNode.appendChild(errorDiv);
        }

        document.getElementById('rejectModal').addEventListener('click', function(e) {
            if (e.target === this) closeRejectModal();
        });
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) closeImageModal();
        });

        document.addEventListener('DOMContentLoaded', function() {
            switchTab('pending');
        });

        setTimeout(function() {
            const alerts = document.querySelectorAll('.bg-green-600, .bg-red-600');
            alerts.forEach(alert => {
                if (alert.classList.contains('text-white') && alert.classList.contains('p-4')) {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s';
                    setTimeout(() => alert.remove(), 500);
                }
            });
        }, 5000);
    </script>
</body>

</html>