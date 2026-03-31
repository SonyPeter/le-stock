<?php
// paiement.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__, 2) . '/config/db.php';
require_once dirname(__DIR__, 2) . '/config/stripe.php';
require_once dirname(__DIR__, 2) . '/includes/notifications.php';

// Apre ou anrejistre kòmand nan baz done a...
 $order_id = $_SESSION['last_order_id'] ?? 'N/A';
 $prenom = $_SESSION['user_prenom'] ?? 'Kliyan';
 $nom = $_SESSION['user_nom'] ?? '';
 $email = $_SESSION['user_email'] ?? '';
 $total = $_SESSION['cart_total'] ?? 0;
 $items = $_SESSION['cart_items'] ?? [];

// Apre sa ou rele fonksyon an
notifyNewOrder([
    'order_id' => $order_id,
    'customer_name' => $prenom . ' ' . $nom,
    'customer_email' => $email,
    'total_amount' => $total,
    'items' => $items
]);

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

 $user_id = $_SESSION['user_id'];
 $message = '';
 $messageType = '';

// Pran pwodwi yo nan panier a
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
    $message = 'Erè nan chajman panier a';
    $messageType = 'error';
}

if (empty($cartItems)) {
    header('Location: panier.php');
    exit();
}

// Kalkile total yo
 $promoDiscount = $_SESSION['promo_discount'] ?? 0;
 $discountAmount = $subtotal * $promoDiscount;
 $subtotalAfterDiscount = $subtotal - $discountAmount;
 $taxRate = 0.05;
 $taxAmount = $subtotalAfterDiscount * $taxRate;
 $shipping = ($subtotal > 5000) ? 0 : 250;
 $total = $subtotalAfterDiscount + $taxAmount + $shipping;

// Jere soumisyon fòm peman an
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentMethod = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : '';

    if (empty($paymentMethod)) {
        $paymentMethod = 'card';
    }

    // Pran done adrès livrezon
    $deliveryAddress = trim($_POST['delivery_address'] ?? '');
    $deliveryCity = trim($_POST['delivery_city'] ?? '');
    $deliveryPhone = trim($_POST['delivery_phone'] ?? '');
    $deliveryNotes = trim($_POST['delivery_notes'] ?? '');

    // Konbine adrès a
    $fullAddress = $deliveryAddress;
    if (!empty($deliveryCity)) {
        $fullAddress .= ', ' . $deliveryCity;
    }
    if (!empty($deliveryNotes)) {
        $fullAddress .= ' | ' . $deliveryNotes;
    }

    switch ($paymentMethod) {
        case 'card':
            if (isset($_POST['stripe_processed']) && $_POST['stripe_processed'] === '1') {
                $paymentIntentId = $_POST['stripe_payment_intent_id'] ?? '';

                if (empty($paymentIntentId)) {
                    $message = 'ID peman an manke. Tanpri eseye ankò.';
                    $messageType = 'error';
                    break;
                }

                try {
                    if (!class_exists('\Stripe\Stripe')) {
                        throw new Exception('Klas Stripe pa disponib.');
                    }

                    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
                    $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);

                    if ($paymentIntent->status === 'succeeded') {
                        try {
                            $orderStmt = $pdo->prepare("
                                INSERT INTO orders SET
                                    user_id = ?,
                                    total_amount = ?,
                                    subtotal = ?,
                                    tax_amount = ?,
                                    shipping_amount = ?,
                                    discount_amount = ?,
                                    promo_code = ?,
                                    payment_method = 'card',
                                    payment_status = 'paid',
                                    stripe_payment_intent_id = ?,
                                    delivery_address = ?,
                                    delivery_city = ?,
                                    delivery_phone = ?,
                                    delivery_notes = ?,
                                    status = 'pending',
                                    created_at = NOW()
                            ");
                            $orderStmt->execute([
                                $user_id,
                                $total,
                                $subtotal,
                                $taxAmount,
                                $shipping,
                                $discountAmount,
                                $_SESSION['promo_code'] ?? null,
                                $paymentIntentId,
                                $fullAddress,
                                $deliveryCity,
                                $deliveryPhone,
                                $deliveryNotes
                            ]);
                            $orderId = $pdo->lastInsertId();

                            foreach ($cartItems as $item) {
                                $price = ($item['price_promo'] > 0 && $item['price_promo'] < $item['price'])
                                    ? $item['price_promo'] : $item['price'];
                                $itemTotal = $price * $item['quantity'];

                                $itemStmt = $pdo->prepare("INSERT INTO order_items SET order_id = ?, product_id = ?, quantity = ?, unit_price = ?, total_price = ?");
                                $itemStmt->execute([$orderId, $item['product_id'], $item['quantity'], $price, $itemTotal]);

                                $updateStock = $pdo->prepare("UPDATE products SET stock_qty = stock_qty - ? WHERE id = ?");
                                $updateStock->execute([$item['quantity'], $item['product_id']]);
                            }

                            $clearCart = $pdo->prepare("DELETE FROM panier WHERE user_id = ?");
                            $clearCart->execute([$user_id]);

                            unset($_SESSION['promo_code'], $_SESSION['promo_discount']);
                            $_SESSION['last_order_id'] = $orderId;

                            header('Location: commande.php?order_id=' . $orderId);
                            exit();
                        } catch (PDOException $e) {
                            $message = 'Erè nan anrejistreman kòmand lan: ' . $e->getMessage();
                            $messageType = 'error';
                        }
                    } else {
                        $message = 'Peman an pa fini. Status: ' . $paymentIntent->status;
                        $messageType = 'error';
                    }
                } catch (\Stripe\Exception\ApiErrorException $e) {
                    $message = 'Erè nan komunikasyon ak Stripe: ' . $e->getMessage();
                    $messageType = 'error';
                } catch (Exception $e) {
                    $message = 'Yon erè finn pase: ' . $e->getMessage();
                    $messageType = 'error';
                }
            } else {
                $message = 'Tanpri ranpli tout chan yo kat la anvan ou soumèt.';
                $messageType = 'error';
            }
            break;

        case 'paypal':
            $message = 'Ou pral redirekte sou PayPal...';
            $messageType = 'success';
            break;

        case 'mobile_wallet':
            $walletType = $_POST['wallet_type'] ?? '';
            $fullName = $_POST['wallet_full_name'] ?? '';
            $email = $_POST['wallet_email'] ?? '';
            $senderPhone = $_POST['wallet_sender_phone'] ?? '';
            $transactionId = $_POST['wallet_transaction_id'] ?? '';

            if (empty($fullName) || empty($email) || empty($senderPhone) || empty($transactionId)) {
                $message = 'Tanpri ranpli tout chan yo (Non, Imèl, Telefòn, ID Tranzaksyon).';
                $messageType = 'error';
                break;
            }

            if (empty($deliveryAddress)) {
                $message = 'Tanpri antre adrès livrezon ou.';
                $messageType = 'error';
                break;
            }

            $receiptPath = '';
            if (isset($_FILES['wallet_receipt']) && $_FILES['wallet_receipt']['error'] === 0) {
                $uploadDir = dirname(__DIR__, 2) . '/uploads/payments/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $fileExt = pathinfo($_FILES['wallet_receipt']['name'], PATHINFO_EXTENSION);
                $fileName = 'receipt_' . time() . '_' . uniqid() . '.' . $fileExt;
                $uploadFile = $uploadDir . $fileName;

                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
                $maxSize = 5 * 1024 * 1024; // 5MB

                if (!in_array($_FILES['wallet_receipt']['type'], $allowedTypes)) {
                    $message = 'Tip fichye a pa valide. Sèvi ak JPG, PNG, GIF oswa PDF.';
                    $messageType = 'error';
                    break;
                }

                if ($_FILES['wallet_receipt']['size'] > $maxSize) {
                    $message = 'Fichye a twò gwo. Maksimòm 5MB.';
                    $messageType = 'error';
                    break;
                }

                if (move_uploaded_file($_FILES['wallet_receipt']['tmp_name'], $uploadFile)) {
                    $receiptPath = 'uploads/payments/' . $fileName;
                } else {
                    $message = 'Erè nan telechajman fichye a. Tanpri eseye ankò.';
                    $messageType = 'error';
                    break;
                }
            } else {
                $message = 'Tanpri ajoute yon prev peman (foto oswa PDF).';
                $messageType = 'error';
                break;
            }

            try {
                $pdo->beginTransaction();

                // KOREKSYON FINALE : Utilisation de INSERT SET
                $orderStmt = $pdo->prepare("
                    INSERT INTO orders SET
                        user_id = ?,
                        total_amount = ?,
                        subtotal = ?,
                        tax_amount = ?,
                        shipping_amount = ?,
                        discount_amount = ?,
                        promo_code = ?,
                        payment_method = 'mobile_wallet',
                        payment_status = 'pending_verification',
                        wallet_type = ?,
                        wallet_full_name = ?,
                        wallet_email = ?,
                        wallet_sender_phone = ?,
                        wallet_transaction_id = ?,
                        wallet_receipt_path = ?,
                        delivery_address = ?,
                        delivery_city = ?,
                        delivery_phone = ?,
                        delivery_notes = ?,
                        status = 'pending',
                        created_at = NOW()
                ");
                $orderStmt->execute([
                    $user_id,
                    $total,
                    $subtotal,
                    $taxAmount,
                    $shipping,
                    $discountAmount,
                    $_SESSION['promo_code'] ?? null,
                    $walletType,
                    $fullName,
                    $email,
                    $senderPhone,
                    $transactionId,
                    $receiptPath,
                    $fullAddress,
                    $deliveryCity,
                    $deliveryPhone,
                    $deliveryNotes
                ]);
                
                $orderId = $pdo->lastInsertId();

                foreach ($cartItems as $item) {
                    $price = ($item['price_promo'] > 0 && $item['price_promo'] < $item['price']) ? $item['price_promo'] : $item['price'];
                    
                    $itemStmt = $pdo->prepare("INSERT INTO order_items SET order_id = ?, product_id = ?, quantity = ?, unit_price = ?, total_price = ?");
                    $itemStmt->execute([$orderId, $item['product_id'], $item['quantity'], $price, $price * $item['quantity']]);

                    $updateStock = $pdo->prepare("UPDATE products SET stock_qty = stock_qty - ? WHERE id = ?");
                    $updateStock->execute([$item['quantity'], $item['product_id']]);
                }

                $clearCart = $pdo->prepare("DELETE FROM panier WHERE user_id = ?");
                $clearCart->execute([$user_id]);
                
                unset($_SESSION['promo_code'], $_SESSION['promo_discount']);
                $_SESSION['last_order_id'] = $orderId;
                
                $pdo->commit();

                header('Location: commande.php?success=1&order_id=' . $orderId);
                exit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                $message = 'Erè nan anrejistreman kòmand lan: ' . $e->getMessage();
                $messageType = 'error';
            }
            break;

        default:
            $message = 'Metòd peman pa valide.';
            $messageType = 'error';
    }
}

function formatPrice($price)
{
    return number_format($price, 2) . ' HTG';
}

function getDisplayPrice($item)
{
    return ($item['price_promo'] > 0 && $item['price_promo'] < $item['price']) ? $item['price_promo'] : $item['price'];
}
?>

<!DOCTYPE html>
<html lang="ht">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement - LE STOCK</title>
    <link rel="stylesheet" href="\le-stock\css\style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
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

        .payment-method-btn {
            transition: all 0.3s ease;
            border: 2px solid rgba(255, 255, 255, 0.1);
        }

        .payment-method-btn.active {
            border-color: #3b82f6;
            background: rgba(59, 130, 246, 0.2);
        }

        .payment-method-btn:hover:not(.active) {
            border-color: rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.05);
        }

        .card-input {
            transition: all 0.2s ease;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
        }

        .card-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
            outline: none;
        }

        .card-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .wallet-info-box {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            border: 1px solid rgba(59, 130, 246, 0.5);
        }

        .wallet-steps {
            background: rgba(15, 23, 42, 0.8);
            border-left: 4px solid #3b82f6;
        }

        .step-number {
            background: #3b82f6;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            flex-shrink: 0;
        }

        .file-upload-area {
            border: 2px dashed rgba(59, 130, 246, 0.5);
            background: rgba(15, 23, 42, 0.6);
            transition: all 0.3s ease;
        }

        .file-upload-area:hover {
            border-color: #3b82f6;
            background: rgba(59, 130, 246, 0.1);
        }

        .file-upload-area.has-file {
            border-color: #22c55e;
            background: rgba(34, 197, 94, 0.1);
        }

        .checkout-btn {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transition: all 0.3s ease;
        }

        .checkout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.4);
        }

        .checkout-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .step-active {
            background-color: #2563eb;
            color: white;
        }

        .step-completed {
            background-color: #22c55e;
            color: white;
        }

        .step-pending {
            background-color: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.5);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hidden-form {
            display: none;
        }

        .visible-form {
            display: block;
            animation: fadeIn 0.3s ease forwards;
        }

        .StripeElement {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 0.75rem;
            padding: 12px 16px;
        }

        .StripeElement--focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        .StripeElement--invalid {
            border-color: #ef4444;
        }

        .address-input {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            transition: all 0.2s ease;
        }

        .address-input:focus {
            border-color: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.2);
            outline: none;
        }

        .address-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .section-divider {
            border: none;
            height: 2px;
            background: linear-gradient(to right, transparent, rgba(59, 130, 246, 0.4), transparent);
            margin: 2rem 0;
        }
    </style>

    <script src="https://js.stripe.com/v3/"></script>
</head>

<body class="min-h-screen">
    <div class="fixed inset-0 z-0">
        <div class="absolute inset-0 bg-cover bg-center bg-fixed bg-no-repeat" style="background-image: url('/le-stock/assets/img/stock11.png');"></div>
        <div class="absolute inset-0 bg-glass"></div>
    </div>

    <header class="sticky top-0 z-50 bg-slate-900/95 backdrop-blur-md border-b border-blue-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="../accueil.php" class="logo-container group">
                    <img src="\le-stock\assets\img\le stock entreprise copy2.png" alt="LE STOCK Logo" class="logo-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="hidden w-12 h-12 sm:w-14 sm:h-14 bg-blue-600 rounded-lg items-center justify-center" id="logo-fallback">
                        <i class="fas fa-shopping-bag text-2xl sm:text-3xl text-white"></i>
                    </div>
                </a>
                <nav class="hidden md:flex items-center gap-8">
                    <a href="../accueil.php" class="text-blue-300 hover:text-white text-sm font-medium uppercase tracking-wide transition-colors">Akèy</a>
                    <a href="../products.php" class="text-blue-300 hover:text-white text-sm font-medium uppercase tracking-wide transition-colors">Pwodwi</a>
                    <a href="../categories.php" class="text-blue-300 hover:text-white text-sm font-medium uppercase tracking-wide transition-colors">Kategori</a>
                </nav>
                <div class="flex items-center gap-2 sm:gap-3">
                    <a href="panier.php" class="p-2 text-blue-400 hover:text-white transition-colors relative">
                        <i class="fas fa-shopping-cart text-lg"></i>
                        <?php if ($totalItems > 0): ?>
                            <span class="absolute -top-1 -right-1 w-5 h-5 bg-blue-500 text-white text-xs font-bold rounded-full flex items-center justify-center"><?php echo $totalItems; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="../profile.php" class="hidden sm:block p-2 text-blue-300 hover:text-white transition-colors"><i class="far fa-user text-lg"></i></a>
                    <button id="mobile-menu-btn" class="hamburger-btn md:hidden" aria-label="Ouvri menu a"><i class="fas fa-bars"></i></button>
                </div>
            </div>
        </div>
        <div id="mobile-menu" class="mobile-menu fixed inset-y-0 left-0 w-64 md:hidden z-50">
            <div class="mobile-menu-header p-4 flex items-center justify-between">
                <span class="menu-title">Menu</span>
                <button id="close-menu-btn" class="hamburger-btn"><i class="fas fa-times"></i></button>
            </div>
            <nav class="p-4">
                <a href="../accueil.php" class="mobile-nav-link"><i class="fas fa-home"></i><span>Akèy</span></a>
                <a href="../products.php" class="mobile-nav-link"><i class="fas fa-box"></i><span>Pwodwi</span></a>
                <a href="panier.php" class="mobile-nav-link"><i class="fas fa-shopping-cart"></i><span>Panier</span></a>
            </nav>
        </div>
        <div id="menu-overlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden"></div>
    </header>

    <div class="bg-slate-900/90 backdrop-blur-sm border-b border-blue-800 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 sm:py-4">
            <div class="flex items-center justify-center gap-2 sm:gap-4">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 sm:w-8 sm:h-8 step-completed rounded-full flex items-center justify-center text-xs sm:text-sm font-bold"><i class="fas fa-check text-xs"></i></div>
                    <span class="text-xs sm:text-sm font-medium text-green-400">Panier</span>
                </div>
                <div class="w-8 sm:w-16 h-0.5 bg-green-500"></div>
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 sm:w-8 sm:h-8 step-active rounded-full flex items-center justify-center text-xs sm:text-sm font-bold">2</div>
                    <span class="text-xs sm:text-sm font-medium text-white">Paiement</span>
                </div>
                <div class="w-8 sm:w-16 h-0.5 bg-slate-600"></div>
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 sm:w-8 sm:h-8 step-pending rounded-full flex items-center justify-center text-xs sm:text-sm font-bold border border-slate-600">3</div>
                    <span class="text-xs sm:text-sm font-medium text-slate-400">Konfimasyon</span>
                </div>
            </div>
        </div>
    </div>

    <main class="relative z-10 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">

            <div class="mb-4 sm:mb-6">
                <a href="panier.php" class="inline-flex items-center gap-2 text-blue-300 hover:text-white transition-colors text-sm sm:text-base font-medium">
                    <i class="fas fa-arrow-left text-sm"></i>
                    <span>Retounen nan panier</span>
                </a>
            </div>

            <?php if ($message): ?>
                <div class="mb-4 sm:mb-6 rounded-lg p-3 sm:p-4 flex items-center gap-3 shadow-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-900 border border-green-400' : 'bg-red-100 text-red-900 border border-red-400'; ?>">
                    <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle text-green-600' : 'fa-exclamation-circle text-red-600'; ?> text-xl"></i>
                    <span class="font-semibold text-sm sm:text-base"><?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 sm:gap-6">
                <div class="lg:col-span-2 space-y-5">

                    <!-- FORMULAIRE UNIQUE -->
                    <div class="glass-dark rounded-xl shadow-lg overflow-hidden border border-blue-800/50">
                        <div class="border-b border-slate-700 bg-slate-900/80 px-4 sm:px-6 py-4">
                            <h2 class="text-lg sm:text-xl font-bold text-white flex items-center gap-2">
                                <i class="fas fa-credit-card text-blue-400"></i>
                                Paj Peman
                            </h2>
                        </div>

                        <div class="p-4 sm:p-6">

                            <form method="POST" action="" id="paymentForm" enctype="multipart/form-data">
                                <input type="hidden" name="payment_method" id="paymentMethod" value="card">

                                <!-- SEKSYON ADRES LIVEREZON -->
                                <div class="mb-6">
                                    <h3 class="text-lg font-bold text-white flex items-center gap-2 mb-1">
                                        <i class="fas fa-map-marker-alt text-green-400"></i>
                                        Adrès Livrezon
                                    </h3>
                                    <p class="text-sm text-slate-400 mb-4">Kote nou dwa voye kòmand lan?</p>

                                    <div class="space-y-4 p-4 sm:p-5 rounded-xl bg-slate-800/50 border border-green-800/30">
                                        <div class="space-y-2">
                                            <label for="delivery_address" class="block text-sm font-semibold text-green-200">
                                                <i class="fas fa-home mr-1"></i> Adrès Konplè <span class="text-red-400">*</span>
                                            </label>
                                            <textarea name="delivery_address" id="delivery_address" rows="2" required
                                                class="address-input w-full px-4 py-3 rounded-xl text-base font-medium placeholder:text-slate-500 resize-none"
                                                placeholder="Egz: Rue 15, Numewo 42, Site Canapé Vert"></textarea>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div class="space-y-2">
                                                <label for="delivery_city" class="block text-sm font-semibold text-green-200">
                                                    <i class="fas fa-city mr-1"></i> Vil / Komin <span class="text-red-400">*</span>
                                                </label>
                                                <input type="text" name="delivery_city" id="delivery_city" required
                                                    class="address-input w-full px-4 py-3 rounded-xl text-base font-medium placeholder:text-slate-500"
                                                    placeholder="Egz: Port-au-Prince">
                                            </div>
                                            <div class="space-y-2">
                                                <label for="delivery_phone" class="block text-sm font-semibold text-green-200">
                                                    <i class="fas fa-phone mr-1"></i> Telefòn pou Livrezon <span class="text-red-400">*</span>
                                                </label>
                                                <input type="tel" name="delivery_phone" id="delivery_phone" required
                                                    class="address-input w-full px-4 py-3 rounded-xl text-base font-medium placeholder:text-slate-500"
                                                    placeholder="Egz: 509 37 12 3456">
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <label for="delivery_notes" class="block text-sm font-semibold text-green-200">
                                                <i class="fas fa-sticky-note mr-1"></i> Nòt Adisyonèl <span class="text-slate-500">(opsyonèl)</span>
                                            </label>
                                            <textarea name="delivery_notes" id="delivery_notes" rows="2"
                                                class="address-input w-full px-4 py-3 rounded-xl text-base font-medium placeholder:text-slate-500 resize-none"
                                                placeholder="Egz: Kay la gen pòt ble, sonje pou w sonje avan ou rive..."></textarea>
                                        </div>
                                    </div>
                                </div>

                                <hr class="section-divider">

                                <!-- SEKSYON METÒD PEMAN -->
                                <div>
                                    <h3 class="text-lg font-bold text-white flex items-center gap-2 mb-4">
                                        <i class="fas fa-wallet text-blue-400"></i>
                                        Chwazi Metòd Peman
                                    </h3>

                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 mb-6">
                                        <button type="button" onclick="setPaymentMethod('card')" class="payment-method-btn active p-4 rounded-xl flex flex-col items-center gap-2 relative bg-slate-800/50 text-white" id="btn-card">
                                            <i class="fas fa-credit-card text-2xl text-blue-400"></i>
                                            <span class="font-medium text-sm sm:text-base">Kat Labank</span>
                                            <div class="absolute top-2 right-2 w-5 h-5 bg-blue-600 rounded-full flex items-center justify-center check-icon"><i class="fas fa-check text-white text-xs"></i></div>
                                        </button>
                                        <button type="button" onclick="setPaymentMethod('paypal')" class="payment-method-btn p-4 rounded-xl flex flex-col items-center gap-2 relative bg-slate-800/50 text-white" id="btn-paypal">
                                            <i class="fab fa-paypal text-2xl text-blue-400"></i>
                                            <span class="font-medium text-sm sm:text-base">PayPal</span>
                                        </button>
                                        <button type="button" onclick="setPaymentMethod('mobile_wallet')" class="payment-method-btn p-4 rounded-xl flex flex-col items-center gap-2 relative bg-slate-800/50 text-white" id="btn-mobile_wallet">
                                            <i class="fas fa-mobile-alt text-2xl text-blue-400"></i>
                                            <span class="font-medium text-sm sm:text-base">NatCash / MonCash</span>
                                        </button>
                                    </div>

                                    <!-- CARD FORM -->
                                    <div id="card-form" class="visible-form space-y-4">
                                        <div class="space-y-2">
                                            <label class="block text-sm font-semibold text-blue-200">Nimewo Kat</label>
                                            <div id="card-element" class="card-input w-full px-4 py-3 rounded-xl"></div>
                                            <div id="card-errors" class="text-red-400 text-sm mt-1"></div>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="block text-sm font-semibold text-blue-200">Non sou Kat la</label>
                                            <input type="text" id="card-name" name="card_name" placeholder="JEAN DUPONT" class="card-input w-full px-4 py-3 rounded-xl text-base font-medium placeholder:text-slate-500 uppercase">
                                        </div>
                                        <div class="flex items-center gap-3 p-3 sm:p-4 bg-blue-900/30 border border-blue-500/30 rounded-xl mt-4">
                                            <i class="fas fa-lock text-blue-400 text-lg"></i>
                                            <p class="text-sm text-blue-200 font-medium">Enfòmasyon peman ou yo chifre e sekirize pa Stripe.</p>
                                        </div>
                                    </div>

                                    <!-- PAYPAL FORM -->
                                    <div id="paypal-form" class="hidden-form py-8 text-center">
                                        <div class="w-20 h-20 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-4"><i class="fab fa-paypal text-4xl text-white"></i></div>
                                        <h3 class="text-xl font-bold text-white mb-2">Peye ak PayPal</h3>
                                        <p class="text-blue-200 mb-6">Ou pral redirekte sou sit PayPal la.</p>
                                        <div class="bg-slate-800/50 p-4 rounded-lg inline-block">
                                            <p class="text-sm text-slate-400">Total pou peye:</p>
                                            <p class="text-2xl font-bold text-white"><?php echo formatPrice($total); ?></p>
                                        </div>
                                    </div>

                                    <!-- MOBILE WALLET FORM -->
                                    <div id="mobile_wallet-form" class="hidden-form space-y-5">
                                        <div class="space-y-2">
                                            <label class="block text-sm font-semibold text-blue-200">Chwazi Sèvis Mobil</label>
                                            <div class="grid grid-cols-2 gap-3">
                                                <label class="cursor-pointer">
                                                    <input type="radio" name="wallet_type" value="natcash" class="peer hidden" checked>
                                                    <div class="p-4 rounded-xl border-2 border-slate-600 bg-slate-800/50 peer-checked:border-green-500 peer-checked:bg-green-900/30 transition-all text-center">
                                                        <i class="fas fa-money-bill-wave text-2xl text-green-400 mb-2"></i>
                                                        <p class="font-bold text-white">NatCash</p>
                                                        <p class="text-xs text-slate-400">Natcom</p>
                                                    </div>
                                                </label>
                                                <label class="cursor-pointer">
                                                    <input type="radio" name="wallet_type" value="moncash" class="peer hidden">
                                                    <div class="p-4 rounded-xl border-2 border-slate-600 bg-slate-800/50 peer-checked:border-orange-500 peer-checked:bg-orange-900/30 transition-all text-center">
                                                        <i class="fas fa-mobile-alt text-2xl text-orange-400 mb-2"></i>
                                                        <p class="font-bold text-white">MonCash</p>
                                                        <p class="text-xs text-slate-400">Digicel</p>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="wallet-info-box rounded-xl p-4 sm:p-5">
                                            <div class="flex items-center gap-3 mb-3">
                                                <i class="fas fa-info-circle text-blue-300 text-xl"></i>
                                                <h4 class="font-bold text-white">Enfòmasyon Kont Nou An</h4>
                                            </div>
                                            <div class="space-y-2 text-sm">
                                                <div class="flex justify-between items-center py-2 border-b border-blue-400/30">
                                                    <span class="text-blue-200">Non:</span>
                                                    <span class="font-bold text-white">LE STOCK S.A</span>
                                                </div>
                                                <div class="flex justify-between items-center py-2 border-b border-blue-400/30">
                                                    <span class="text-blue-200">Nimewo NatCash:</span>
                                                    <span class="font-bold text-white text-lg select-all">509 37 45 1234</span>
                                                </div>
                                                <div class="flex justify-between items-center py-2">
                                                    <span class="text-blue-200">Nimewo MonCash:</span>
                                                    <span class="font-bold text-white text-lg select-all">509 31 22 5678</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="wallet-steps rounded-xl p-4 sm:p-5">
                                            <h4 class="font-bold text-white mb-4 flex items-center gap-2">
                                                <i class="fas fa-list-ol text-blue-400"></i> Etap pou swiv yo:
                                            </h4>
                                            <div class="space-y-3">
                                                <div class="flex items-start gap-3">
                                                    <div class="step-number">1</div>
                                                    <p class="text-sm text-blue-200">Fè yon transfè sòl nan yon nan nimewo ki anro a.</p>
                                                </div>
                                                <div class="flex items-start gap-3">
                                                    <div class="step-number">2</div>
                                                    <p class="text-sm text-blue-200">Asire ou ke kantite a koresponn ak total kòmand lan (<strong class="text-white"><?php echo formatPrice($total); ?></strong>).</p>
                                                </div>
                                                <div class="flex items-start gap-3">
                                                    <div class="step-number">3</div>
                                                    <p class="text-sm text-blue-200">Kenbe nimewo tranzaksyon an.</p>
                                                </div>
                                                <div class="flex items-start gap-3">
                                                    <div class="step-number">4</div>
                                                    <p class="text-sm text-blue-200">Ranpli fòm ki anba a epi voye prev peman an.</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                                            <div class="space-y-2">
                                                <label class="block text-sm font-semibold text-blue-200">Non Konplè <span class="text-red-400">*</span></label>
                                                <input type="text" name="wallet_full_name" id="wallet_full_name" placeholder="Jean Dupont" class="card-input w-full px-4 py-3 rounded-xl text-base font-medium placeholder:text-slate-500">
                                            </div>
                                            <div class="space-y-2">
                                                <label class="block text-sm font-semibold text-blue-200">Imèl <span class="text-red-400">*</span></label>
                                                <input type="email" name="wallet_email" id="wallet_email" placeholder="jean@example.com" class="card-input w-full px-4 py-3 rounded-xl text-base font-medium placeholder:text-slate-500">
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <label class="block text-sm font-semibold text-blue-200">Nimewo Telefòn ou (ki fè transfè a) <span class="text-red-400">*</span></label>
                                            <input type="tel" name="wallet_sender_phone" id="wallet_sender_phone" placeholder="509 37 12 3456" class="card-input w-full px-4 py-3 rounded-xl text-base font-medium placeholder:text-slate-500">
                                        </div>

                                        <div class="space-y-2">
                                            <label class="block text-sm font-semibold text-blue-200">ID Tranzaksyon <span class="text-red-400">*</span></label>
                                            <input type="text" name="wallet_transaction_id" id="wallet_transaction_id" placeholder="Ex: TX123456789" class="card-input w-full px-4 py-3 rounded-xl text-base font-medium placeholder:text-slate-500">
                                            <p class="text-xs text-slate-400 mt-1">Ou jwenn nimewo sa a nan mesaj konfimasyon transfè a.</p>
                                        </div>

                                        <div class="space-y-2">
                                            <label class="block text-sm font-semibold text-blue-200">Prev Peman (Foto) <span class="text-red-400">*</span></label>
                                            <div class="file-upload-area rounded-xl p-6 text-center cursor-pointer" onclick="document.getElementById('wallet_receipt').click()">
                                                <input type="file" id="wallet_receipt" name="wallet_receipt" accept="image/*,.pdf" class="hidden" onchange="handleFileSelect(this)">
                                                <i class="fas fa-cloud-upload-alt text-3xl text-blue-400 mb-2" id="upload-icon"></i>
                                                <p class="text-sm text-blue-200 mb-1" id="upload-text">Klike pou chwazi fichye oswa glise l isit la</p>
                                                <p class="text-xs text-slate-500" id="upload-hint">JPG, PNG, GIF oswa PDF (max 5MB)</p>
                                                <p class="text-sm text-green-400 font-medium hidden" id="file-name"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </form>

                        </div>
                    </div>
                    <!-- FIN FORMULAIRE UNIQUE -->

                </div>

                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <div class="glass-dark rounded-xl shadow-xl p-5 sm:p-6 sticky top-20 border border-blue-800/50">
                        <h2 class="text-white text-lg sm:text-xl font-bold mb-5 flex items-center gap-2"><i class="fas fa-receipt text-blue-400"></i> Rekapitilatif kòmand</h2>
                        <div class="space-y-4 mb-6 max-h-64 overflow-y-auto pr-2">
                            <?php foreach ($cartItems as $item): $price = getDisplayPrice($item); ?>
                                <div class="flex gap-3 sm:gap-4 bg-slate-800/50 p-2 rounded-lg">
                                    <img src="../../uploads/products/<?php echo htmlspecialchars($item['image'] ?? 'placeholder.png'); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-16 h-16 sm:w-20 sm:h-20 object-cover rounded-lg bg-slate-700 flex-shrink-0" onerror="this.src='../../assets/img/placeholder.png'">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-semibold text-sm text-white truncate"><?php echo htmlspecialchars($item['name']); ?></h3>
                                        <p class="text-xs text-blue-300">Kantite: <?php echo $item['quantity']; ?></p>
                                        <p class="text-sm font-bold text-blue-400 mt-1"><?php echo formatPrice($price * $item['quantity']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="border-t border-slate-700 my-4"></div>
                        <div class="space-y-3 text-sm sm:text-base mb-5">
                            <div class="flex justify-between text-blue-200"><span>Sou-total (<?php echo $totalItems; ?> atik)</span><span class="text-white font-semibold"><?php echo formatPrice($subtotal); ?></span></div>
                            <?php if ($discountAmount > 0): ?>
                                <div class="flex justify-between text-green-400 font-medium"><span class="flex items-center gap-1"><i class="fas fa-tag text-xs"></i> Rabè</span><span>-<?php echo formatPrice($discountAmount); ?></span></div>
                            <?php endif; ?>
                            <div class="flex justify-between text-blue-200"><span>Tax (5%)</span><span class="text-white"><?php echo formatPrice($taxAmount); ?></span></div>
                            <div class="flex justify-between text-blue-200"><span>Livrezon</span><span class="<?php echo $shipping == 0 ? 'text-green-400 font-medium' : 'text-white'; ?>"><?php echo $shipping == 0 ? 'Gratis' : formatPrice($shipping); ?></span></div>
                            <div class="border-t border-slate-700 my-3"></div>
                            <div class="flex justify-between text-lg sm:text-xl font-bold"><span class="text-white">Total</span><span class="text-blue-400"><?php echo formatPrice($total); ?></span></div>
                        </div>
                        <button type="button" id="submitBtn" class="checkout-btn w-full py-4 rounded-xl font-bold text-white flex items-center justify-center gap-2 text-base sm:text-lg shadow-lg">
                            <i class="fas fa-lock"></i>
                            <span>Konfime Peman an</span>
                        </button>
                        <div class="mt-6 space-y-2 sm:space-y-3">
                            <div class="flex items-center gap-2 text-xs sm:text-sm text-slate-400"><i class="fas fa-shield-alt text-green-500"></i><span>Peman 100% sekir</span></div>
                            <div class="flex items-center gap-2 text-xs sm:text-sm text-slate-400"><i class="fas fa-lock text-green-500"></i><span>Chifreman SSL 256-bit</span></div>
                            <div class="flex items-center gap-2 text-xs sm:text-sm text-slate-400"><i class="fas fa-check-circle text-green-500"></i><span>Garanti satisfè oswa rembouse</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div class="relative z-10 bg-slate-900/95 backdrop-blur-md mt-10">
        <footer class="border-t border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
                <div class="text-center">
                    <p class="text-slate-500 text-sm">&copy; 2024 LE STOCK. Tout dwa rezève.</p>
                </div>
            </div>
        </footer>
    </div>

    <script>
        let stripe, elements, cardElement;

        try {
            const stripeKey = '<?php echo STRIPE_PUBLISHABLE_KEY; ?>';
            if (!stripeKey || stripeKey === '') {
                console.error("Erreur: Clé Stripe manquante");
                document.getElementById('card-errors').textContent = "Erreur de configuration du système de paiement.";
            } else {
                stripe = Stripe(stripeKey);
                elements = stripe.elements();
                cardElement = elements.create('card', {
                    style: {
                        base: {
                            color: '#ffffff',
                            fontFamily: 'Inter, sans-serif',
                            fontSize: '16px',
                            '::placeholder': {
                                color: 'rgba(255, 255, 255, 0.5)'
                            }
                        },
                        invalid: {
                            color: '#ef4444'
                        }
                    }
                });
                cardElement.mount('#card-element');
                cardElement.on('change', function(event) {
                    document.getElementById('card-errors').textContent = event.error ? event.error.message : '';
                });
            }
        } catch (e) {
            console.error("Erreur d'initialisation Stripe:", e);
            document.getElementById('card-errors').textContent = "Erreur de chargement du module de paiement.";
        }

        // Mobile menu
        document.getElementById('mobile-menu-btn').addEventListener('click', () => {
            document.getElementById('mobile-menu').classList.add('open');
            document.getElementById('menu-overlay').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        });
        document.getElementById('close-menu-btn').addEventListener('click', () => {
            document.getElementById('mobile-menu').classList.remove('open');
            document.getElementById('menu-overlay').classList.add('hidden');
            document.body.style.overflow = '';
        });
        document.getElementById('menu-overlay').addEventListener('click', () => {
            document.getElementById('mobile-menu').classList.remove('open');
            document.getElementById('menu-overlay').classList.add('hidden');
            document.body.style.overflow = '';
        });

        function setPaymentMethod(method) {
            document.getElementById('paymentMethod').value = method;

            document.querySelectorAll('.payment-method-btn').forEach(btn => {
                btn.classList.remove('active');
                const check = btn.querySelector('.check-icon');
                if (check) check.remove();
            });

            document.getElementById('card-form').className = method === 'card' ? 'visible-form space-y-4' : 'hidden-form space-y-4';
            document.getElementById('paypal-form').className = method === 'paypal' ? 'visible-form py-8 text-center' : 'hidden-form py-8 text-center';
            document.getElementById('mobile_wallet-form').className = method === 'mobile_wallet' ? 'visible-form space-y-5' : 'hidden-form space-y-5';

            const activeBtn = document.getElementById('btn-' + method);
            activeBtn.classList.add('active');
            const checkDiv = document.createElement('div');
            checkDiv.className = 'absolute top-2 right-2 w-5 h-5 bg-blue-600 rounded-full flex items-center justify-center check-icon';
            checkDiv.innerHTML = '<i class="fas fa-check text-white text-xs"></i>';
            activeBtn.appendChild(checkDiv);
        }

        function handleFileSelect(input) {
            const file = input.files[0];
            if (file) {
                input.parentElement.classList.add('has-file');
                document.getElementById('upload-icon').className = 'fas fa-check-circle text-3xl text-green-400 mb-2';
                document.getElementById('upload-text').textContent = 'Fichye chwazi avèk siksè!';
                document.getElementById('upload-hint').classList.add('hidden');
                const fileName = document.getElementById('file-name');
                fileName.textContent = file.name;
                fileName.classList.remove('hidden');
            }
        }

        // SOUMISSION DU FORMULAIRE
        document.getElementById('submitBtn').addEventListener('click', async function(e) {
            const paymentMethod = document.getElementById('paymentMethod').value;

            // 1) Valide adrès livrezon
            const deliveryAddress = document.getElementById('delivery_address').value.trim();
            const deliveryCity = document.getElementById('delivery_city').value.trim();
            const deliveryPhone = document.getElementById('delivery_phone').value.trim();

            if (!deliveryAddress || !deliveryCity || !deliveryPhone) {
                alert('Tanpri ranpli adrès livrezon ou (Adrès, Vil, Telefòn).');
                document.getElementById('delivery_address').scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                setTimeout(() => {
                    document.getElementById('delivery_address').focus();
                }, 500);
                return;
            }

            // 2) Si se pa kat, soumèt fòm nan dirèkteman
            if (paymentMethod !== 'card') {
                if (paymentMethod === 'mobile_wallet') {
                    const fullName = document.getElementById('wallet_full_name').value.trim();
                    const email = document.getElementById('wallet_email').value.trim();
                    const senderPhone = document.getElementById('wallet_sender_phone').value.trim();
                    const transactionId = document.getElementById('wallet_transaction_id').value.trim();
                    const receipt = document.getElementById('wallet_receipt').files[0];

                    if (!fullName || !email || !senderPhone || !transactionId) {
                        alert('Tanpri ranpli tout chan yo (Non, Imèl, Telefòn, ID Tranzaksyon).');
                        return;
                    }
                    if (!receipt) {
                        alert('Tanpri ajoute yon prev peman (foto oswa PDF).');
                        return;
                    }
                }
                document.getElementById('paymentForm').submit();
                return;
            }

            // 3) Si se kat, traite ak Stripe
            e.preventDefault();

            const cardName = document.getElementById('card-name').value.trim();
            if (!cardName) {
                document.getElementById('card-errors').textContent = 'Tanpri antre non sou kat la.';
                return;
            }
            if (!stripe || !cardElement) {
                document.getElementById('card-errors').textContent = 'Sistèm peman pa disponib.';
                return;
            }

            const originalText = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Chajman...';

            try {
                const { error: stripeError } = await stripe.createPaymentMethod({
                    type: 'card',
                    card: cardElement,
                    billing_details: {
                        name: cardName
                    }
                });

                if (stripeError) {
                    document.getElementById('card-errors').textContent = stripeError.message;
                    this.disabled = false;
                    this.innerHTML = originalText;
                    return;
                }

                const response = await fetch('create-payment-intent.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        amount: <?php echo $total * 100; ?>,
                        currency: 'htg'
                    })
                });

                if (!response.ok) throw new Error('Erè serveur: ' + response.status);

                const data = await response.json();
                if (data.error) throw new Error(data.error);

                const { error, paymentIntent } = await stripe.confirmCardPayment(data.clientSecret, {
                    payment_method: {
                        card: cardElement,
                        billing_details: {
                            name: cardName
                        }
                    }
                });

                if (error) {
                    document.getElementById('card-errors').textContent = error.message;
                    this.disabled = false;
                    this.innerHTML = originalText;
                } else if (paymentIntent.status === 'succeeded') {
                    const hidPI = document.createElement('input');
                    hidPI.type = 'hidden';
                    hidPI.name = 'stripe_payment_intent_id';
                    hidPI.value = paymentIntent.id;
                    document.getElementById('paymentForm').appendChild(hidPI);

                    const hidSP = document.createElement('input');
                    hidSP.type = 'hidden';
                    hidSP.name = 'stripe_processed';
                    hidSP.value = '1';
                    document.getElementById('paymentForm').appendChild(hidSP);

                    document.getElementById('paymentForm').submit();
                } else {
                    throw new Error('Status inatandi: ' + paymentIntent.status);
                }
            } catch (err) {
                document.getElementById('card-errors').textContent = err.message || 'Yon erè finn pase.';
                this.disabled = false;
                this.innerHTML = originalText;
            }
        });
    </script>
</body>

</html>