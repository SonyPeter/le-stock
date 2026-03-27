<?php
session_start();

// Aktive affichage erè pou devlopman (efase sa a an pwodiksyon)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__, 2) . '/config/db.php';
require_once dirname(__DIR__, 2) . '/config/stripe.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Pran pwodwi yo nan panier a (BIZUEN anvan POST trete a)
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
    error_log("Erè panier: " . $e->getMessage());
    $message = 'Erè nan chajman panier a';
    $messageType = 'error';
}

// Si panier a vid, retounen nan paj panier a
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
    $paymentMethod = $_POST['payment_method'] ?? '';

    error_log("=== DEBUG PAIMENt ===");
    error_log("Payment method: " . $paymentMethod);
    error_log("POST data: " . print_r($_POST, true));

    switch ($paymentMethod) {
        case 'card':
            // ETAP 4: Trete peman Stripe a
            if (isset($_POST['stripe_processed']) && $_POST['stripe_processed'] === '1') {
                $paymentIntentId = $_POST['stripe_payment_intent_id'] ?? '';

                error_log("Stripe processed OK");
                error_log("PaymentIntent ID: " . $paymentIntentId);

                if (empty($paymentIntentId)) {
                    $message = 'ID peman an manke. Tanpri eseye ankò.';
                    $messageType = 'error';
                    error_log("ERREUR: PaymentIntent ID vid");
                    break;
                }

                try {
                    // Verifye si klas Stripe egziste
                    if (!class_exists('\Stripe\Stripe')) {
                        throw new Exception('Klas Stripe pa disponib. Verifye ke libreri a chaje korekteman nan config/stripe.php');
                    }

                    // Verifye peman an ak Stripe
                    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
                    error_log("Ap rele Stripe API...");

                    $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
                    error_log("PaymentIntent rekipere - Status: " . $paymentIntent->status);

                    if ($paymentIntent->status === 'succeeded') {
                        // Peman siksè! Anrejistre kòmand lan nan baz done a
                        try {
                            // 1. Kreye kòmand nan tab orders
                            $orderStmt = $pdo->prepare("
                                INSERT INTO orders (user_id, total_amount, subtotal, tax_amount, 
                                                  shipping_amount, discount_amount, promo_code, 
                                                  payment_method, payment_status, stripe_payment_intent_id, 
                                                  status, created_at)
                                VALUES (?, ?, ?, ?, ?, ?, ?, 'card', 'paid', ?, 'pending', NOW())
                            ");

                            $orderStmt->execute([
                                $user_id,
                                $total,
                                $subtotal,
                                $taxAmount,
                                $shipping,
                                $discountAmount,
                                $_SESSION['promo_code'] ?? null,
                                $paymentIntentId
                            ]);

                            $orderId = $pdo->lastInsertId();
                            error_log("Order kreye avèk ID: " . $orderId);

                            // 2. Anrejistre pwodwi yo nan order_items
                            foreach ($cartItems as $item) {
                                $price = ($item['price_promo'] > 0 && $item['price_promo'] < $item['price'])
                                    ? $item['price_promo']
                                    : $item['price'];
                                $itemTotal = $price * $item['quantity'];

                                $itemStmt = $pdo->prepare("
                                    INSERT INTO order_items (order_id, product_id, quantity, 
                                                          unit_price, total_price)
                                    VALUES (?, ?, ?, ?, ?)
                                ");
                                $itemStmt->execute([
                                    $orderId,
                                    $item['product_id'],
                                    $item['quantity'],
                                    $price,
                                    $itemTotal
                                ]);

                                // 3. Mete ajou stock la
                                $updateStock = $pdo->prepare("
                                    UPDATE products 
                                    SET stock_qty = stock_qty - ? 
                                    WHERE id = ?
                                ");
                                $updateStock->execute([$item['quantity'], $item['product_id']]);
                            }

                            // 4. Vide panier a
                            $clearCart = $pdo->prepare("DELETE FROM panier WHERE user_id = ?");
                            $clearCart->execute([$user_id]);

                            // 5. Efase kòd promosyon an nan sesyon an
                            unset($_SESSION['promo_code']);
                            unset($_SESSION['promo_discount']);

                            // 6. Sove ID kòmand lan nan sesyon pou paj konfimasyon an
                            $_SESSION['last_order_id'] = $orderId;

                            $message = 'Peman an trete avèk siksè! Mèsi pou achte ou a.';
                            $messageType = 'success';

                            // Redireksyon nan paj konfimasyon
                            header('Location: commande.php?order_id=' . $orderId);
                            exit();
                        } catch (PDOException $e) {
                            error_log("Database Erè: " . $e->getMessage());
                            throw new Exception('Erè nan anrejistreman kòmand lan: ' . $e->getMessage());
                        }
                    } else {
                        $message = 'Peman an pa fini. Status: ' . $paymentIntent->status;
                        $messageType = 'error';
                        error_log("PaymentIntent pa succeeded. Status: " . $paymentIntent->status);
                    }
                } catch (\Stripe\Exception\ApiErrorException $e) {
                    error_log("Stripe API Erè: " . $e->getMessage());
                    $message = 'Erè nan komunikasyon ak Stripe: ' . $e->getMessage();
                    $messageType = 'error';
                } catch (Exception $e) {
                    error_log("General Erè: " . $e->getMessage());
                    $message = 'Yon erè finn pase: ' . $e->getMessage();
                    $messageType = 'error';
                }
            } else {
                // Si pa gen stripe_processed
                $message = 'Erè nan trete peman an. Stripe pa trete peman an korekteman.';
                $messageType = 'error';
                error_log("ERREUR: stripe_processed pa egziste oswa pa egal a 1");
                error_log("stripe_processed value: " . (isset($_POST['stripe_processed']) ? $_POST['stripe_processed'] : 'pa egziste'));
            }
            break;

        case 'paypal':
            $message = 'Ou pral redirekte sou PayPal...';
            $messageType = 'success';
            break;

        case 'mobile_wallet':
            // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
            // NOUVO KÒD POU NATCASH/MONCASH - KREYE KÒMAND NAN BAZ DONE A
            // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>

            // Peman pa NatCash/MonCash
            $walletType = $_POST['wallet_type'] ?? '';
            $fullName = $_POST['wallet_full_name'] ?? '';
            $email = $_POST['wallet_email'] ?? '';
            $senderPhone = $_POST['wallet_sender_phone'] ?? '';
            $transactionId = $_POST['wallet_transaction_id'] ?? '';

            // Verifye si tout chan obligatwa yo ranpli
            if (empty($fullName) || empty($email) || empty($senderPhone) || empty($transactionId)) {
                $message = 'Tanpri ranpli tout chan yo (Non, Imèl, Telefòn, ID Tranzaksyon).';
                $messageType = 'error';
                break;
            }

            // Jere upload prev peman an
            $receiptPath = '';
            if (isset($_FILES['wallet_receipt']) && $_FILES['wallet_receipt']['error'] === 0) {
                $uploadDir = '../../uploads/payments/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $fileName = time() . '_' . basename($_FILES['wallet_receipt']['name']);
                $uploadFile = $uploadDir . $fileName;

                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
                if (in_array($_FILES['wallet_receipt']['type'], $allowedTypes)) {
                    if (move_uploaded_file($_FILES['wallet_receipt']['tmp_name'], $uploadFile)) {
                        $receiptPath = $uploadFile;
                    } else {
                        $message = 'Erè nan telechajman fichye a.';
                        $messageType = 'error';
                        break;
                    }
                } else {
                    $message = 'Tip fichye a pa valide. Sèlman JPG, PNG, GIF, oswa PDF.';
                    $messageType = 'error';
                    break;
                }
            } else {
                $message = 'Tanpri ajoute yon prev peman (foto oswa PDF).';
                $messageType = 'error';
                break;
            }

            // TOUT BYEN, KOUNYE A KREYE KÒMAND LAN
            try {
                // Kòmanse tranzaksyon
                $pdo->beginTransaction();

                // 1. Kreye kòmand nan tab orders avèk tout enfòmasyon mobile wallet
                $orderStmt = $pdo->prepare("
                    INSERT INTO orders (
                        user_id, total_amount, subtotal, tax_amount, 
                        shipping_amount, discount_amount, promo_code, 
                        payment_method, payment_status, 
                        wallet_type, wallet_full_name, wallet_email, 
                        wallet_sender_phone, wallet_transaction_id, wallet_receipt_path,
                        status, created_at
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, 
                        'mobile_wallet', 'pending_verification',
                        ?, ?, ?, ?, ?, ?,
                        'pending', NOW()
                    )
                ");

                $orderStmt->execute([
                    $user_id,
                    $total,
                    $subtotal,
                    $taxAmount,
                    $shipping,
                    $discountAmount,
                    $_SESSION['promo_code'] ?? null,
                    // Mobile wallet info
                    $walletType,
                    $fullName,
                    $email,
                    $senderPhone,
                    $transactionId,
                    $receiptPath
                ]);

                $orderId = $pdo->lastInsertId();
                error_log("Mobile Wallet Order kreye avèk ID: " . $orderId);

                // 2. Anrejistre pwodwi yo nan order_items
                foreach ($cartItems as $item) {
                    $price = ($item['price_promo'] > 0 && $item['price_promo'] < $item['price'])
                        ? $item['price_promo']
                        : $item['price'];
                    $itemTotal = $price * $item['quantity'];

                    $itemStmt = $pdo->prepare("
                        INSERT INTO order_items (order_id, product_id, quantity, 
                                              unit_price, total_price)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $itemStmt->execute([
                        $orderId,
                        $item['product_id'],
                        $item['quantity'],
                        $price,
                        $itemTotal
                    ]);

                    // 3. Mete ajou stock la (rezève pwodwi yo)
                    $updateStock = $pdo->prepare("
                        UPDATE products 
                        SET stock_qty = stock_qty - ? 
                        WHERE id = ?
                    ");
                    $updateStock->execute([$item['quantity'], $item['product_id']]);
                }

                // 4. Vide panier a
                $clearCart = $pdo->prepare("DELETE FROM panier WHERE user_id = ?");
                $clearCart->execute([$user_id]);

                // 5. Efase kòd promosyon an nan sesyon an
                unset($_SESSION['promo_code']);
                unset($_SESSION['promo_discount']);

                // 6. Sove ID kòmand lan nan sesyon
                $_SESSION['last_order_id'] = $orderId;

                // Konfime tranzaksyon an
                $pdo->commit();

                $message = 'Kòmand anrejistre! Nap verifye peman ou a. Ou pral resevwa yon konfimasyon imèl.';
                $messageType = 'success';

                // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
                // REDIREKSYON NAN PAJ KÒMAND YO (PA CONFIRMATION.PHP PASKE PEMAN AN Poko KONFIME)
                // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
                header('Location: commande.php?success=1&order_id=' . $orderId);
                exit();
            } catch (PDOException $e) {
                // Anile tranzaksyon an si gen erè
                $pdo->rollBack();
                error_log("Mobile Wallet Database Erè: " . $e->getMessage());
                $message = 'Erè nan anrejistreman kòmand lan: ' . $e->getMessage();
                $messageType = 'error';
            }
            break;
    }
}

function formatPrice($price)
{
    return number_format($price, 2) . ' HTG';
}

function getDisplayPrice($item)
{
    if ($item['price_promo'] > 0 && $item['price_promo'] < $item['price']) {
        return $item['price_promo'];
    }
    return $item['price'];
}
?>

<!DOCTYPE html>
<html lang="ht">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement - LE STOCK</title>

    <!-- Tailwind CSS CLI -->
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

        .checkout-btn:active {
            transform: translateY(0);
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

        .animate-fade-in {
            animation: fadeIn 0.3s ease forwards;
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
    </style>

    <!-- Stripe.js -->
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        const stripe = Stripe('<?php echo STRIPE_PUBLISHABLE_KEY; ?>');
        const elements = stripe.elements();
    </script>

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
                    <a href="../accueil.php" class="text-blue-300 hover:text-white text-sm font-medium uppercase tracking-wide transition-colors">Akèy</a>
                    <a href="../products.php" class="text-blue-300 hover:text-white text-sm font-medium uppercase tracking-wide transition-colors">Pwodwi</a>
                    <a href="../categories.php" class="text-blue-300 hover:text-white text-sm font-medium uppercase tracking-wide transition-colors">Kategori</a>
                    <a href="#" class="text-blue-300 hover:text-white text-sm font-medium uppercase tracking-wide transition-colors">Pwomosyon</a>
                </nav>

                <!-- Actions -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <button class="hidden sm:block p-2 text-blue-300 hover:text-white transition-colors">
                        <i class="fas fa-search text-lg"></i>
                    </button>
                    <a href="panier.php" class="p-2 text-blue-400 hover:text-white transition-colors relative">
                        <i class="fas fa-shopping-cart text-lg"></i>
                        <?php if ($totalItems > 0): ?>
                            <span class="absolute -top-1 -right-1 w-5 h-5 bg-blue-500 text-white text-xs font-bold rounded-full flex items-center justify-center">
                                <?php echo $totalItems; ?>
                            </span>
                        <?php endif; ?>
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
                <a href="../accueil.php" class="mobile-nav-link">
                    <i class="fas fa-home"></i>
                    <span>Akèy</span>
                </a>
                <a href="../products.php" class="mobile-nav-link">
                    <i class="fas fa-box"></i>
                    <span>Pwodwi</span>
                </a>
                <a href="../categories.php" class="mobile-nav-link">
                    <i class="fas fa-tags"></i>
                    <span>Kategori</span>
                </a>
                <a href="panier.php" class="mobile-nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Panier</span>
                </a>
            </nav>
        </div>
        <div id="menu-overlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden"></div>
    </header>

    <!-- Progress Steps -->
    <div class="bg-slate-900/90 backdrop-blur-sm border-b border-blue-800 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 sm:py-4">
            <div class="flex items-center justify-center">
                <div class="flex items-center gap-2 sm:gap-4">
                    <!-- Step 1: Completed -->
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 sm:w-8 sm:h-8 step-completed rounded-full flex items-center justify-center text-xs sm:text-sm font-bold">
                            <i class="fas fa-check text-xs"></i>
                        </div>
                        <span class="text-xs sm:text-sm font-medium text-green-400">Panier</span>
                    </div>

                    <div class="w-8 sm:w-16 h-0.5 bg-green-500"></div>

                    <!-- Step 2: Active -->
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 sm:w-8 sm:h-8 step-active rounded-full flex items-center justify-center text-xs sm:text-sm font-bold">2</div>
                        <span class="text-xs sm:text-sm font-medium text-white">Paiement</span>
                    </div>

                    <div class="w-8 sm:w-16 h-0.5 bg-slate-600"></div>

                    <!-- Step 3: Pending -->
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 sm:w-8 sm:h-8 step-pending rounded-full flex items-center justify-center text-xs sm:text-sm font-bold border border-slate-600">3</div>
                        <span class="text-xs sm:text-sm font-medium text-slate-400">Konfimasyon</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="relative z-10 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">

            <!-- Back Button -->
            <div class="mb-4 sm:mb-6">
                <a href="panier.php" class="inline-flex items-center gap-2 text-blue-300 hover:text-white transition-colors text-sm sm:text-base font-medium">
                    <i class="fas fa-arrow-left text-sm"></i>
                    <span>Retounen nan panier</span>
                </a>
            </div>

            <!-- Alerts -->
            <?php if ($message): ?>
                <div class="mb-4 sm:mb-6 rounded-lg p-3 sm:p-4 flex items-center gap-3 shadow-lg <?php
                                                                                                    echo $messageType === 'success' ? 'bg-green-100 text-green-900 border border-green-400' : 'bg-red-100 text-red-900 border border-red-400';
                                                                                                    ?>">
                    <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle text-green-600' : 'fa-exclamation-circle text-red-600'; ?> text-xl"></i>
                    <span class="font-semibold text-sm sm:text-base"><?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 sm:gap-6">

                <!-- Payment Form Section -->
                <div class="lg:col-span-2 space-y-5">

                    <!-- Payment Methods -->
                    <div class="glass-dark rounded-xl shadow-lg overflow-hidden border border-blue-800/50">
                        <div class="border-b border-slate-700 bg-slate-900/80 px-4 sm:px-6 py-4">
                            <h2 class="text-lg sm:text-xl font-bold text-white flex items-center gap-2">
                                <i class="fas fa-credit-card text-blue-400"></i>
                                Chwazi metòd peman
                            </h2>
                        </div>

                        <div class="p-4 sm:p-6">
                            <form method="POST" action="" id="paymentForm" enctype="multipart/form-data">

                                <!-- Payment Method Selection -->
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 mb-6">

                                    <!-- Card Option -->
                                    <button type="button" onclick="setPaymentMethod('card')"
                                        class="payment-method-btn active p-4 rounded-xl flex flex-col items-center gap-2 relative bg-slate-800/50 text-white"
                                        id="btn-card">
                                        <i class="fas fa-credit-card text-2xl text-blue-400"></i>
                                        <span class="font-medium text-sm sm:text-base">Kat Labank</span>
                                        <div class="absolute top-2 right-2 w-5 h-5 bg-blue-600 rounded-full flex items-center justify-center check-icon">
                                            <i class="fas fa-check text-white text-xs"></i>
                                        </div>
                                    </button>

                                    <!-- PayPal Option -->
                                    <button type="button" onclick="setPaymentMethod('paypal')"
                                        class="payment-method-btn p-4 rounded-xl flex flex-col items-center gap-2 relative bg-slate-800/50 text-white"
                                        id="btn-paypal">
                                        <i class="fab fa-paypal text-2xl text-blue-400"></i>
                                        <span class="font-medium text-sm sm:text-base">PayPal</span>
                                    </button>

                                    <!-- Mobile Wallet Option (NatCash/MonCash) -->
                                    <button type="button" onclick="setPaymentMethod('mobile_wallet')"
                                        class="payment-method-btn p-4 rounded-xl flex flex-col items-center gap-2 relative bg-slate-800/50 text-white"
                                        id="btn-mobile_wallet">
                                        <i class="fas fa-mobile-alt text-2xl text-blue-400"></i>
                                        <span class="font-medium text-sm sm:text-base">NatCash / MonCash</span>
                                    </button>
                                </div>

                                <input type="hidden" name="payment_method" id="paymentMethod" value="card">

                                <!-- Card Payment Form - Stripe -->
                                <div id="card-form" class="visible-form space-y-4">

                                    <!-- Kote Stripe pral mete eleman yo -->
                                    <div class="space-y-2">
                                        <label class="block text-sm font-semibold text-blue-200">Nimewo Kat</label>
                                        <div id="card-element" class="card-input w-full px-4 py-3 rounded-xl">
                                            <!-- Stripe pral ajoute chan kat la isit la -->
                                        </div>
                                        <!-- Pou erè yo -->
                                        <div id="card-errors" class="text-red-400 text-sm mt-1"></div>
                                    </div>

                                    <!-- Non sou kat la -->
                                    <div class="space-y-2">
                                        <label class="block text-sm font-semibold text-blue-200">Non sou Kat la</label>
                                        <input type="text" id="card-name" name="card_name"
                                            placeholder="JEAN DUPONT"
                                            class="card-input w-full px-4 py-3 rounded-xl text-base font-medium placeholder:text-slate-500 uppercase">
                                    </div>

                                    <!-- Sekirite -->
                                    <div class="flex items-center gap-3 p-3 sm:p-4 bg-blue-900/30 border border-blue-500/30 rounded-xl mt-4">
                                        <i class="fas fa-lock text-blue-400 text-lg"></i>
                                        <p class="text-sm text-blue-200 font-medium">
                                            Enfòmasyon peman ou yo chifre e sekirize pa Stripe.
                                        </p>
                                    </div>
                                </div>

                                <!-- PayPal Form -->
                                <div id="paypal-form" class="hidden-form py-8 text-center">
                                    <div class="w-20 h-20 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i class="fab fa-paypal text-4xl text-white"></i>
                                    </div>
                                    <h3 class="text-xl font-bold text-white mb-2">Peye ak PayPal</h3>
                                    <p class="text-blue-200 mb-6">
                                        Ou pral redirekte sou sit PayPal la pou finalize peman an.
                                    </p>
                                    <div class="bg-slate-800/50 p-4 rounded-lg inline-block">
                                        <p class="text-sm text-slate-400">Total pou peye:</p>
                                        <p class="text-2xl font-bold text-white"><?php echo formatPrice($total); ?></p>
                                    </div>
                                </div>

                                <!-- Mobile Wallet Form (NatCash/MonCash) -->
                                <div id="mobile_wallet-form" class="hidden-form space-y-5">

                                    <!-- Wallet Type Selection -->
                                    <div class="space-y-2">
                                        <label class="block text-sm font-semibold text-blue-200">Chwazi Sèvis Mobil</label>
                                        <div class="grid grid-cols-2 gap-3">
                                            <label class="cursor-pointer">
                                                <input type="radio" name="wallet_type" value="natcash" class="peer hidden" checked>
                                                <div class="p-4 rounded-xl border-2 border-slate-600 bg-slate-800/50 peer-checked:border-blue-500 peer-checked:bg-blue-900/30 transition-all text-center">
                                                    <i class="fas fa-money-bill-wave text-2xl text-green-400 mb-2"></i>
                                                    <p class="font-bold text-white">NatCash</p>
                                                    <p class="text-xs text-slate-400">Digicel</p>
                                                </div>
                                            </label>
                                            <label class="cursor-pointer">
                                                <input type="radio" name="wallet_type" value="moncash" class="peer hidden">
                                                <div class="p-4 rounded-xl border-2 border-slate-600 bg-slate-800/50 peer-checked:border-blue-500 peer-checked:bg-blue-900/30 transition-all text-center">
                                                    <i class="fas fa-mobile-alt text-2xl text-blue-400 mb-2"></i>
                                                    <p class="font-bold text-white">MonCash</p>
                                                    <p class="text-xs text-slate-400">Digicel</p>
                                                </div>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Platform Account Info -->
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

                                    <!-- Steps to Follow -->
                                    <div class="wallet-steps rounded-xl p-4 sm:p-5">
                                        <h4 class="font-bold text-white mb-4 flex items-center gap-2">
                                            <i class="fas fa-list-ol text-blue-400"></i>
                                            Etap pou swiv yo:
                                        </h4>
                                        <div class="space-y-3">
                                            <div class="flex items-start gap-3">
                                                <div class="step-number">1</div>
                                                <p class="text-sm text-blue-200">Fè yon transfè sòl nan yon nan nimewo ki anwo a.</p>
                                            </div>
                                            <div class="flex items-start gap-3">
                                                <div class="step-number">2</div>
                                                <p class="text-sm text-blue-200">Asire ou ke kantite a koresponn ak total kòmand lan (<strong class="text-white"><?php echo formatPrice($total); ?></strong>).</p>
                                            </div>
                                            <div class="flex items-start gap-3">
                                                <div class="step-number">3</div>
                                                <p class="text-sm text-blue-200">Kenbe nimewo tranzaksyon an (ID Transfè a).</p>
                                            </div>
                                            <div class="flex items-start gap-3">
                                                <div class="step-number">4</div>
                                                <p class="text-sm text-blue-200">Ranpli fòm ki anba a ak enfòmasyon yo epi voye prev peman an.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- User Info Form -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                                        <div class="space-y-2">
                                            <label class="block text-sm font-semibold text-blue-200">Non Konplè <span class="text-red-400">*</span></label>
                                            <input type="text" name="wallet_full_name" id="wallet_full_name"
                                                placeholder="Jean Dupont" required
                                                class="card-input w-full px-4 py-3 rounded-xl text-base font-medium placeholder:text-slate-500">
                                        </div>
                                        <div class="space-y-2">
                                            <label class="block text-sm font-semibold text-blue-200">Imèl <span class="text-red-400">*</span></label>
                                            <input type="email" name="wallet_email" id="wallet_email"
                                                placeholder="jean@example.com" required
                                                class="card-input w-full px-4 py-3 rounded-xl text-base font-medium placeholder:text-slate-500">
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-semibold text-blue-200">Nimewo Telefòn ou (ki fè transfè a) <span class="text-red-400">*</span></label>
                                        <input type="tel" name="wallet_sender_phone" id="wallet_sender_phone"
                                            placeholder="509 37 12 3456" required
                                            class="card-input w-full px-4 py-3 rounded-xl text-base font-medium placeholder:text-slate-500">
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-semibold text-blue-200">ID Tranzaksyon / Nimewo Referans <span class="text-red-400">*</span></label>
                                        <input type="text" name="wallet_transaction_id" id="wallet_transaction_id"
                                            placeholder="Ex: TX123456789" required
                                            class="card-input w-full px-4 py-3 rounded-xl text-base font-medium placeholder:text-slate-500">
                                        <p class="text-xs text-slate-400">Ou jwenn nimewo sa a nan mesaj konfimasyon transfè a.</p>
                                    </div>

                                    <!-- File Upload -->
                                    <div class="space-y-2">
                                        <label class="block text-sm font-semibold text-blue-200">Prev Peman (Foto) <span class="text-red-400">*</span></label>
                                        <div class="file-upload-area rounded-xl p-6 text-center cursor-pointer" onclick="document.getElementById('wallet_receipt').click()">
                                            <input type="file" id="wallet_receipt" name="wallet_receipt" accept="image/*,.pdf" class="hidden" onchange="handleFileSelect(this)" required>
                                            <i class="fas fa-cloud-upload-alt text-3xl text-blue-400 mb-2" id="upload-icon"></i>
                                            <p class="text-sm text-blue-200 mb-1" id="upload-text">Klike pou chwazi fichye oswa glise l isit la</p>
                                            <p class="text-xs text-slate-500" id="upload-hint">JPG, PNG, GIF oswa PDF (max 5MB)</p>
                                            <p class="text-sm text-green-400 font-medium hidden" id="file-name"></p>
                                        </div>
                                    </div>

                                </div>

                            </form>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <div class="glass-dark rounded-xl shadow-xl p-5 sm:p-6 sticky top-20 border border-blue-800/50">
                        <h2 class="text-white text-lg sm:text-xl font-bold mb-5 flex items-center gap-2">
                            <i class="fas fa-receipt text-blue-400"></i>
                            Rekapitilatif kòmand
                        </h2>

                        <!-- Cart Items -->
                        <div class="space-y-4 mb-6 max-h-64 overflow-y-auto pr-2">
                            <?php foreach ($cartItems as $item):
                                $price = getDisplayPrice($item);
                            ?>
                                <div class="flex gap-3 sm:gap-4 bg-slate-800/50 p-2 rounded-lg">
                                    <img src="../../uploads/products/<?php echo htmlspecialchars($item['image'] ?? 'placeholder.png'); ?>"
                                        alt="<?php echo htmlspecialchars($item['name']); ?>"
                                        class="w-16 h-16 sm:w-20 sm:h-20 object-cover rounded-lg bg-slate-700 flex-shrink-0"
                                        onerror="this.src='../../assets/img/placeholder.png'">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-semibold text-sm text-white truncate"><?php echo htmlspecialchars($item['name']); ?></h3>
                                        <p class="text-xs text-blue-300">Kantite: <?php echo $item['quantity']; ?></p>
                                        <p class="text-sm font-bold text-blue-400 mt-1">
                                            <?php echo formatPrice($price * $item['quantity']); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="border-t border-slate-700 my-4"></div>

                        <!-- Totals -->
                        <div class="space-y-3 text-sm sm:text-base mb-5">
                            <div class="flex justify-between text-blue-200">
                                <span>Sou-total (<?php echo $totalItems; ?> atik)</span>
                                <span class="text-white font-semibold"><?php echo formatPrice($subtotal); ?></span>
                            </div>

                            <?php if ($discountAmount > 0): ?>
                                <div class="flex justify-between text-green-400 font-medium">
                                    <span class="flex items-center gap-1">
                                        <i class="fas fa-tag text-xs"></i>
                                        Rabè (<?php echo $_SESSION['promo_code']; ?>)
                                    </span>
                                    <span>-<?php echo formatPrice($discountAmount); ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="flex justify-between text-blue-200">
                                <span>Tax (5%)</span>
                                <span class="text-white"><?php echo formatPrice($taxAmount); ?></span>
                            </div>

                            <div class="flex justify-between text-blue-200">
                                <span>Livrezon</span>
                                <span class="<?php echo $shipping == 0 ? 'text-green-400 font-medium' : 'text-white'; ?>">
                                    <?php echo $shipping == 0 ? 'Gratis' : formatPrice($shipping); ?>
                                </span>
                            </div>

                            <div class="border-t border-slate-700 my-3"></div>

                            <div class="flex justify-between text-lg sm:text-xl font-bold">
                                <span class="text-white">Total</span>
                                <span class="text-blue-400"><?php echo formatPrice($total); ?></span>
                            </div>
                        </div>

                        <!-- Pay Button -->
                        <button type="submit" form="paymentForm" id="submitBtn" class="checkout-btn w-full py-4 rounded-xl font-bold text-white flex items-center justify-center gap-2 text-base sm:text-lg shadow-lg">
                            <i class="fas fa-lock"></i>
                            <span>Konfime Peman an</span>
                        </button>

                        <!-- Security Badges -->
                        <div class="mt-6 space-y-2 sm:space-y-3">
                            <div class="flex items-center gap-2 text-xs sm:text-sm text-slate-400">
                                <i class="fas fa-shield-alt text-green-500"></i>
                                <span>Peman 100% sekir</span>
                            </div>
                            <div class="flex items-center gap-2 text-xs sm:text-sm text-slate-400">
                                <i class="fas fa-lock text-green-500"></i>
                                <span>Chifreman SSL 256-bit</span>
                            </div>
                            <div class="flex items-center gap-2 text-xs sm:text-sm text-slate-400">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span>Garanti satisfè oswa rembouse</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Banner -->
            <div class="mt-6 sm:mt-8 p-4 sm:p-6 bg-gradient-to-r from-blue-900/50 to-green-900/30 border-2 border-blue-500/30 rounded-xl sm:rounded-2xl glass-dark">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                    <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-shield-alt text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-white mb-1 text-base sm:text-lg">Peman Sekir pa SSL</h3>
                        <p class="text-sm text-blue-200 leading-relaxed">
                            Enfòmasyon peman ou yo pwoteje pa pwotokòl sekirite ki pi avanse yo.
                            Nou pa janm estoke enfòmasyon kat kredi ou.
                        </p>
                    </div>
                </div>
            </div>
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

        // Payment method switching
        function setPaymentMethod(method) {
            document.getElementById('paymentMethod').value = method;

            // Reset all buttons
            document.querySelectorAll('.payment-method-btn').forEach(btn => {
                btn.classList.remove('active');
                const check = btn.querySelector('.check-icon');
                if (check) check.remove();
            });

            // Hide all forms
            document.getElementById('card-form').classList.remove('visible-form');
            document.getElementById('card-form').classList.add('hidden-form');
            document.getElementById('paypal-form').classList.remove('visible-form');
            document.getElementById('paypal-form').classList.add('hidden-form');
            document.getElementById('mobile_wallet-form').classList.remove('visible-form');
            document.getElementById('mobile_wallet-form').classList.add('hidden-form');

            // Activate selected button
            const activeBtn = document.getElementById('btn-' + method);
            activeBtn.classList.add('active');

            const checkDiv = document.createElement('div');
            checkDiv.className = 'absolute top-2 right-2 w-5 h-5 bg-blue-600 rounded-full flex items-center justify-center check-icon';
            checkDiv.innerHTML = '<i class="fas fa-check text-white text-xs"></i>';
            activeBtn.appendChild(checkDiv);

            // Show selected form
            const selectedForm = document.getElementById(method + '-form');
            selectedForm.classList.remove('hidden-form');
            selectedForm.classList.add('visible-form');
        }

        // File upload handling
        function handleFileSelect(input) {
            const file = input.files[0];
            const uploadArea = input.parentElement;
            const icon = document.getElementById('upload-icon');
            const text = document.getElementById('upload-text');
            const hint = document.getElementById('upload-hint');
            const fileName = document.getElementById('file-name');

            if (file) {
                uploadArea.classList.add('has-file');
                icon.className = 'fas fa-check-circle text-3xl text-green-400 mb-2';
                text.textContent = 'Fichye chwazi avèk siksè!';
                hint.classList.add('hidden');
                fileName.textContent = file.name;
                fileName.classList.remove('hidden');
            }
        }

        // Kreye eleman kat Stripe a
        const cardElement = elements.create('card', {
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

        // Monte eleman an
        cardElement.mount('#card-element');

        // Jere erè yo
        cardElement.on('change', function(event) {
            const displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });

        // Jere soumisyon fòm nan
        const paymentForm = document.getElementById('paymentForm');
        const submitBtn = document.getElementById('submitBtn');

        paymentForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Verifye si se kat ki chwazi
            const paymentMethod = document.getElementById('paymentMethod').value;
            if (paymentMethod !== 'card') {
                // Pou NatCash/MonCash, verifye si tout chan yo ranpli
                if (paymentMethod === 'mobile_wallet') {
                    const fullName = document.getElementById('wallet_full_name').value.trim();
                    const email = document.getElementById('wallet_email').value.trim();
                    const phone = document.getElementById('wallet_sender_phone').value.trim();
                    const transId = document.getElementById('wallet_transaction_id').value.trim();
                    const receipt = document.getElementById('wallet_receipt').files[0];

                    if (!fullName || !email || !phone || !transId || !receipt) {
                        alert('Tanpri ranpli tout chan yo epi ajoute yon prev peman.');
                        return;
                    }
                }

                paymentForm.submit(); // Soumèt nòmalman pou lòt metòd
                return;
            }

            // Verifye si non an ranpli
            const cardName = document.getElementById('card-name').value.trim();
            if (!cardName) {
                document.getElementById('card-errors').textContent = 'Tanpri antre non sou kat la.';
                return;
            }

            // Bouton chajman
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Chajman...';

            try {
                console.log('Kreye PaymentIntent...');

                // Kreye yon PaymentIntent sou serveur a
                const response = await fetch('create-payment-intent.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        amount: <?php echo $total * 100; ?>,
                        currency: 'htg'
                    })
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error('Erè serveur: ' + errorText);
                }

                const data = await response.json();
                console.log('PaymentIntent reponn:', data);

                if (data.error) {
                    throw new Error(data.error);
                }

                const {
                    clientSecret
                } = data;

                // Konfime peman an ak Stripe
                console.log('Konfime peman an...');
                const {
                    error,
                    paymentIntent
                } = await stripe.confirmCardPayment(clientSecret, {
                    payment_method: {
                        card: cardElement,
                        billing_details: {
                            name: cardName
                        }
                    }
                });

                if (error) {
                    // Montre erè a
                    console.error('Stripe erè:', error);
                    document.getElementById('card-errors').textContent = error.message;
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                } else {
                    console.log('PaymentIntent status:', paymentIntent.status);

                    if (paymentIntent.status === 'succeeded') {
                        // Peman siksè! Soumèt fòm nan pou anrejistre kòmand lan
                        console.log('Peman siksè! ID:', paymentIntent.id);

                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'stripe_payment_intent_id';
                        hiddenInput.value = paymentIntent.id;
                        paymentForm.appendChild(hiddenInput);

                        const stripeInput = document.createElement('input');
                        stripeInput.type = 'hidden';
                        stripeInput.name = 'stripe_processed';
                        stripeInput.value = '1';
                        paymentForm.appendChild(stripeInput);

                        paymentForm.submit();
                    } else {
                        throw new Error('Status inatandi: ' + paymentIntent.status);
                    }
                }
            } catch (err) {
                console.error('Erè:', err);
                document.getElementById('card-errors').textContent = err.message || 'Yon erè finn pase. Tanpri eseye ankò.';
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    </script>
</body>

</html>