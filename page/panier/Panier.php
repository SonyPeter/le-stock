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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'update_quantity':
            $itemId = intval($_POST['item_id']);
            $newQuantity = intval($_POST['quantity']);

            if ($newQuantity >= 1) {
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

    header('Location: ' . $_SERVER['PHP_SELF'] . ($message ? '?msg=' . urlencode($message) . '&type=' . $messageType : ''));
    exit();
}

if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $messageType = $_GET['type'] ?? 'info';
}

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

$recommendedProducts = [];
try {
    $cartProductIds = array_column($cartItems, 'product_id');
    $placeholders = !empty($cartProductIds) ? implode(',', array_fill(0, count($cartProductIds), '?')) : '0';
    
    $sql = "
        SELECT 
            p.id,
            p.name,
            p.price,
            p.price_promo,
            p.image,
            c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status = 'active' 
        AND p.stock_qty > 0
        " . (!empty($cartProductIds) ? "AND p.id NOT IN ($placeholders)" : "") . "
        ORDER BY p.created_at DESC
        LIMIT 4
    ";
    
    $stmt = $pdo->prepare($sql);
    $params = !empty($cartProductIds) ? $cartProductIds : [];
    $stmt->execute($params);
    $recommendedProducts = $stmt->fetchAll();
    
    if (count($recommendedProducts) < 4) {
        $limit = 4 - count($recommendedProducts);
        $existingIds = array_column($recommendedProducts, 'id');
        $allIds = array_merge($existingIds, $cartProductIds);
        $placeholders2 = !empty($allIds) ? implode(',', array_fill(0, count($allIds), '?')) : '0';
        
        $sql2 = "
            SELECT 
                p.id,
                p.name,
                p.price,
                p.price_promo,
                p.image,
                c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status = 'active' 
            AND p.stock_qty > 0
            " . (!empty($allIds) ? "AND p.id NOT IN ($placeholders2)" : "") . "
            ORDER BY RAND()
            LIMIT $limit
        ";
        
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute(!empty($allIds) ? $allIds : []);
        $additionalProducts = $stmt2->fetchAll();
        $recommendedProducts = array_merge($recommendedProducts, $additionalProducts);
    }
} catch (PDOException $e) {
    error_log("Erè rekòmandasyon: " . $e->getMessage());
}

$promoDiscount = $_SESSION['promo_discount'] ?? 0;
$discountAmount = $subtotal * $promoDiscount;
$subtotalAfterDiscount = $subtotal - $discountAmount;
$taxRate = 0.05;
$taxAmount = $subtotalAfterDiscount * $taxRate;
$shipping = ($subtotal > 5000) ? 0 : 250;
$total = $subtotalAfterDiscount + $taxAmount + $shipping;

function getDisplayPrice($item) {
    if ($item['price_promo'] > 0 && $item['price_promo'] < $item['price']) {
        return $item['price_promo'];
    }
    return $item['price'];
}

function isOnPromo($item) {
    return $item['price_promo'] > 0 && $item['price_promo'] < $item['price'];
}

function getStockStatus($stockQty) {
    if ($stockQty <= 0) return ['out', 'Epiize'];
    if ($stockQty <= 5) return ['low', 'Stock ba (' . $stockQty . ' disponib)'];
    return ['in', 'Disponib'];
}

function formatPrice($price) {
    return number_format($price, 2) . ' HTG';
}
?>

<!DOCTYPE html>
<html lang="ht">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panier - LE STOCK</title>
    
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
        
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .hover-lift {
            transition: all 0.3s ease;
        }
        
        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .mobile-menu {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        
        .mobile-menu.open {
            transform: translateX(0);
        }
        
        @keyframes bounce-cart {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-25%); }
        }
        
        .animate-bounce-cart {
            animation: bounce-cart 1s infinite;
        }
        
        /* Glass Morphism Background */
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

        /* Logo image styles - PI GWO E LISIB */
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

        /* Header height adjustment for bigger logo */
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

        /* HAMBURGER BUTTON STYLES - PI LISIB AK BACKGROUND */
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
            box-shadow: 0 6px 8px -1px rgba(0, 0, 0, 0.4), 0 4px 6px -1px rgba(0, 0, 0, 0.3);
        }

        .hamburger-btn:active {
            transform: scale(0.95);
        }

        .hamburger-btn i {
            color: white;
            font-size: 1.25rem;
            filter: drop-shadow(0 1px 2px rgba(0,0,0,0.3));
        }

        /* Responsive hamburger sizes */
        @media (max-width: 380px) {
            .hamburger-btn {
                width: 40px;
                height: 40px;
            }
            .hamburger-btn i {
                font-size: 1.1rem;
            }
        }

        @media (min-width: 640px) {
            .hamburger-btn {
                width: 48px;
                height: 48px;
            }
            .hamburger-btn i {
                font-size: 1.4rem;
            }
        }

        /* MOBILE MENU NAVIGATION STYLES - BACKGROUND BLANC POU TÈKS YO */
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .mobile-nav-link:hover {
            background: #3b82f6;
            color: white;
            transform: translateX(5px);
            border-color: #3b82f6;
        }

        .mobile-nav-link i {
            width: 24px;
            margin-right: 12px;
            color: #3b82f6;
            font-size: 16px;
        }

        .mobile-nav-link:hover i {
            color: white;
        }

        /* Mobile menu header background */
        .mobile-menu-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-bottom: 2px solid #3b82f6;
        }

        /* Mobile menu container background */
        #mobile-menu {
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
        }

        /* Menu title styling */
        .menu-title {
            color: white;
            font-size: 18px;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
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
                
                <!-- Logo Image - PI GWO E LISIB -->
                <a href="accueil.php" class="logo-container group">
                    <img src="\le-stock\assets\img\le stock entreprise copy2.png" 
                         alt="LE STOCK Logo" 
                         class="logo-img"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    
                    <!-- Fallback si imaj la pa ka chaje -->
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
                    <button class="hidden sm:block p-2 text-blue-300 hover:text-white transition-colors">
                        <i class="far fa-heart text-lg"></i>
                    </button>
                    <a href="panier.php" class="p-2 text-blue-400 hover:text-white transition-colors relative">
                        <i class="fas fa-shopping-cart text-lg"></i>
                        <?php if ($totalItems > 0): ?>
                            <span class="absolute -top-1 -right-1 w-5 h-5 bg-blue-500 text-white text-xs font-bold rounded-full flex items-center justify-center animate-bounce-cart">
                                <?php echo $totalItems; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <a href="../profile.php" class="hidden sm:block p-2 text-blue-300 hover:text-white transition-colors">
                        <i class="far fa-user text-lg"></i>
                    </a>
                    
                    <!-- HAMBURGER BUTTON - NOUVO STYLES -->
                    <button id="mobile-menu-btn" class="hamburger-btn md:hidden" aria-label="Ouvri menu a">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile Menu - NOUVO STYLES AK BACKGROUND BLANC -->
        <div id="mobile-menu" class="mobile-menu fixed inset-y-0 left-0 w-64 md:hidden z-50">
            <!-- Header -->
            <div class="mobile-menu-header p-4 flex items-center justify-between">
                <span class="menu-title">Menu</span>
                <button id="close-menu-btn" class="hamburger-btn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Navigation Links -->
            <nav class="p-4">
                <a href="../acceuil.php" class="mobile-nav-link">
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
                <a href="#" class="mobile-nav-link">
                    <i class="fas fa-percent"></i>
                    <span>Pwomosyon</span>
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
                    <!-- Step 1: Active -->
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 sm:w-8 sm:h-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs sm:text-sm font-bold">1</div>
                        <span class="text-xs sm:text-sm font-medium text-white">Panier</span>
                    </div>
                    
                    <!-- Line -->
                    <div class="w-8 sm:w-16 h-0.5 bg-blue-500"></div>
                    
                    <!-- Step 2 -->
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 sm:w-8 sm:h-8 bg-slate-700 text-slate-400 rounded-full flex items-center justify-center text-xs sm:text-sm font-bold border border-slate-600">2</div>
                        <span class="text-xs sm:text-sm font-medium text-slate-400">Livrezon</span>
                    </div>
                    
                    <!-- Line -->
                    <div class="w-8 sm:w-16 h-0.5 bg-slate-600"></div>
                    
                    <!-- Step 3 -->
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 sm:w-8 sm:h-8 bg-slate-700 text-slate-400 rounded-full flex items-center justify-center text-xs sm:text-sm font-bold border border-slate-600">3</div>
                        <span class="text-xs sm:text-sm font-medium text-slate-400">Peman</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="relative z-10 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
            
            <!-- Alerts -->
            <?php if ($message): ?>
                <div class="mb-4 sm:mb-6 rounded-lg p-3 sm:p-4 flex items-center gap-3 shadow-lg <?php 
                    echo $messageType === 'success' ? 'bg-green-100 text-green-900 border border-green-400' : 
                         ($messageType === 'error' ? 'bg-red-100 text-red-900 border border-red-400' : 
                         'bg-blue-100 text-blue-900 border border-blue-400'); 
                ?>">
                    <i class="fas <?php 
                        echo $messageType === 'success' ? 'fa-check-circle text-green-600' : 
                             ($messageType === 'error' ? 'fa-exclamation-circle text-red-600' : 
                             'fa-info-circle text-blue-600'); 
                    ?> text-xl"></i>
                    <span class="font-semibold text-sm sm:text-base"><?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>

            <?php if (empty($cartItems)): ?>
                <!-- Empty State -->
                <div class="max-w-md mx-auto pb-10">
                    <div class="glass-card rounded-2xl p-8 sm:p-12 text-center shadow-xl">
                        <div class="w-24 h-24 sm:w-32 sm:h-32 bg-gradient-to-br from-blue-900 to-blue-600 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-shopping-basket text-4xl sm:text-5xl text-white"></i>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900 mb-4">Panier ou a vid</h1>
                        <p class="text-gray-600 mb-8">Sanble ou poko ajoute anyen nan panier ou a.</p>
                        <a href="../accueil.php" class="inline-flex items-center gap-2 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-semibold">
                            <i class="fas fa-store"></i>
                            Kòmanse fè Makèt
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Cart Header -->
                <div class="glass-dark rounded-xl p-5 sm:p-6 mb-5 sm:mb-6 shadow-lg">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
                        <div>
                            <h1 class="text-xl sm:text-2xl font-bold text-white mb-1">Panier ou a</h1>
                            <p class="text-blue-400 font-medium text-sm sm:text-base">
                                <i class="fas fa-box mr-2"></i>
                                <?php echo $totalItems; ?> atik nan panier ou
                            </p>
                        </div>
                        <a href="../products.php" class="inline-flex items-center gap-2 text-blue-400 hover:text-white font-medium transition-all bg-blue-600/20 hover:bg-blue-600/40 px-4 py-2.5 rounded-lg border border-blue-500/50 hover:border-blue-400 w-fit text-sm sm:text-base group">
                            <i class="fas fa-arrow-left group-hover:-translate-x-1 transition-transform"></i>
                            <span>Kontinye achte</span>
                        </a>
                    </div>
                </div>

                <!-- Cart Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 sm:gap-6">
                    
                    <!-- Cart Items -->
                    <div class="lg:col-span-2 space-y-4 sm:space-y-5">
                        
                        <!-- Clear Cart -->
                        <div class="flex justify-end">
                            <form method="POST" onsubmit="return confirm('Èske ou sèten ou vle vid panier ou a?');">
                                <input type="hidden" name="action" value="clear_cart">
                                <button type="submit" class="text-red-600 hover:text-red-700 font-medium text-xs sm:text-sm flex items-center gap-2 px-3 py-2 rounded-lg bg-red-50 hover:bg-red-100 transition-colors border border-red-200 glass-card">
                                    <i class="fas fa-trash-alt"></i>
                                    Vid panier
                                </button>
                            </form>
                        </div>

                        <!-- Items -->
                        <?php foreach ($cartItems as $item):
                            $currentPrice = getDisplayPrice($item);
                            $itemTotal = $currentPrice * $item['quantity'];
                            $stockStatus = getStockStatus($item['stock_qty']);
                        ?>
                            <div class="glass-card rounded-xl p-4 sm:p-5 shadow-md hover-lift">
                                <div class="flex gap-4 sm:gap-5">
                                    
                                    <!-- Image -->
                                    <div class="relative flex-shrink-0">
                                        <img src="../../uploads/products/<?php echo htmlspecialchars($item['image'] ?? 'placeholder.png'); ?>"
                                            alt="<?php echo htmlspecialchars($item['name']); ?>"
                                            class="w-24 h-24 sm:w-28 sm:h-28 object-cover rounded-lg bg-gray-100"
                                            onerror="this.src='../../assets/img/placeholder.png'">
                                        <?php if (isOnPromo($item)): ?>
                                            <span class="absolute -top-2 -left-2 bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded">
                                                -<?php echo round((1 - $item['price_promo']/$item['price']) * 100); ?>%
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Details -->
                                    <div class="flex-1 flex flex-col justify-between min-w-0 py-1">
                                        <div class="flex justify-between items-start gap-3">
                                            <div class="min-w-0 flex-1">
                                                <span class="text-blue-600 text-xs sm:text-sm font-bold uppercase tracking-wider">
                                                    <?php echo htmlspecialchars($item['category_name'] ?? 'Kategori'); ?>
                                                </span>
                                                <h3 class="text-base sm:text-lg font-bold text-gray-900 mt-1 line-clamp-1">
                                                    <?php echo htmlspecialchars($item['name']); ?>
                                                </h3>
                                                <span class="inline-flex items-center gap-1.5 mt-2 text-xs sm:text-sm font-medium <?php 
                                                    echo $stockStatus[0] === 'in' ? 'text-green-600' : 
                                                         ($stockStatus[0] === 'low' ? 'text-yellow-600' : 
                                                         'text-red-600'); 
                                                ?>">
                                                    <span class="w-2 h-2 rounded-full <?php 
                                                        echo $stockStatus[0] === 'in' ? 'bg-green-500' : 
                                                             ($stockStatus[0] === 'low' ? 'bg-yellow-500' : 
                                                             'bg-red-500'); 
                                                    ?>"></span>
                                                    <?php echo $stockStatus[1]; ?>
                                                </span>
                                            </div>
                                            
                                            <form method="POST" class="flex-shrink-0">
                                                <input type="hidden" name="action" value="remove_item">
                                                <input type="hidden" name="item_id" value="<?php echo $item['cart_id']; ?>">
                                                <button type="submit" onclick="return confirm('Èske ou sèten?')" 
                                                    class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors">
                                                    <i class="fas fa-trash-alt text-base"></i>
                                                </button>
                                            </form>
                                        </div>

                                        <div class="flex items-center justify-between mt-3">
                                            <!-- Quantity -->
                                            <form method="POST" class="flex items-center gap-2">
                                                <input type="hidden" name="action" value="update_quantity">
                                                <input type="hidden" name="item_id" value="<?php echo $item['cart_id']; ?>">
                                                
                                                <button type="submit" name="quantity" value="<?php echo max(1, $item['quantity'] - 1); ?>"
                                                    class="w-8 h-8 sm:w-10 sm:h-10 rounded bg-gray-100 hover:bg-gray-200 text-gray-600 flex items-center justify-center transition-colors disabled:opacity-50"
                                                    <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-minus text-xs sm:text-sm"></i>
                                                </button>
                                                
                                                <span class="w-10 sm:w-12 text-center font-semibold text-gray-900 text-base sm:text-lg">
                                                    <?php echo $item['quantity']; ?>
                                                </span>
                                                
                                                <button type="submit" name="quantity" value="<?php echo $item['quantity'] + 1; ?>"
                                                    class="w-8 h-8 sm:w-10 sm:h-10 rounded bg-gray-100 hover:bg-gray-200 text-gray-600 flex items-center justify-center transition-colors disabled:opacity-50"
                                                    <?php echo $item['quantity'] >= $item['stock_qty'] ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-plus text-xs sm:text-sm"></i>
                                                </button>
                                            </form>

                                            <!-- Price -->
                                            <div class="text-right">
                                                <?php if (isOnPromo($item)): ?>
                                                    <div class="flex flex-col items-end">
                                                        <span class="text-xs sm:text-sm text-gray-400 line-through">
                                                            <?php echo formatPrice($item['price']); ?>
                                                        </span>
                                                        <span class="text-base sm:text-lg font-bold text-red-600">
                                                            <?php echo formatPrice($itemTotal); ?>
                                                        </span>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-base sm:text-lg font-bold text-gray-900">
                                                        <?php echo formatPrice($itemTotal); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <p class="text-xs sm:text-sm text-gray-500">
                                                    <?php echo formatPrice($currentPrice); ?> / inite
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Order Summary -->
                    <div class="lg:col-span-1">
                        <div class="glass-dark rounded-xl shadow-xl p-5 sm:p-6 sticky top-20 border border-blue-800/50">
                            <h2 class="text-white text-lg sm:text-xl font-bold mb-5 flex items-center gap-2">
                                <i class="fas fa-receipt text-blue-400"></i>
                                Rekapitilatif kòmand
                            </h2>

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
                            </div>

                            <!-- Promo Code -->
                            <div class="py-4 border-t border-b border-slate-700 mb-5">
                                <?php if (isset($_SESSION['promo_code'])): ?>
                                    <div class="bg-slate-900/80 rounded-lg p-3 flex items-center justify-between border border-green-600/50">
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-check-circle text-green-500 text-base"></i>
                                            <div>
                                                <span class="text-xs text-green-400 block">Kòd aplike</span>
                                                <code class="text-green-300 font-bold text-sm sm:text-base"><?php echo $_SESSION['promo_code']; ?></code>
                                            </div>
                                        </div>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="remove_promo">
                                            <button type="submit" class="text-red-400 hover:text-red-300 text-xs sm:text-sm font-medium">
                                                Retire
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <form method="POST" class="flex gap-2">
                                        <input type="hidden" name="action" value="apply_promo">
                                        <input type="text" name="promo_code" 
                                            placeholder="KÒD RABÈ"
                                            class="flex-1 px-3 py-2.5 rounded-lg text-xs sm:text-sm uppercase bg-slate-900/80 border border-slate-600 text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 font-medium">
                                        <button type="submit" class="px-4 py-2.5 rounded-lg text-xs sm:text-sm font-semibold bg-blue-600 text-white hover:bg-blue-500 transition-colors">
                                            Aplike
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>

                            <!-- Total -->
                            <div class="flex justify-between items-center pt-2 mb-5">
                                <span class="text-white font-semibold text-base sm:text-lg">Total</span>
                                <span class="text-2xl sm:text-3xl font-bold text-white"><?php echo formatPrice($total); ?></span>
                            </div>

                            <!-- Checkout -->
                            <form action="checkout.php" method="POST">
                                <button type="submit" class="w-full py-4 rounded-lg font-semibold bg-blue-600 text-white hover:bg-blue-500 transition-colors shadow-lg flex items-center justify-center gap-2 text-base sm:text-lg">
                                    <i class="fas fa-lock"></i>
                                    Pase kòmand la
                                </button>
                            </form>

                            <div class="mt-4 flex items-center justify-center gap-2 text-xs sm:text-sm text-slate-400">
                                <i class="fas fa-shield-alt text-green-500"></i>
                                <span>Peman an sekirye ak chifreman SSL</span>
                            </div>

                            <!-- Shipping Progress -->
                            <?php if ($shipping > 0): ?>
                                <div class="mt-4 bg-slate-900/80 rounded-lg p-3 border border-blue-800/50">
                                    <p class="text-xs sm:text-sm text-blue-300 mb-2">
                                        <i class="fas fa-truck mr-1"></i>
                                        Ajoute <span class="font-semibold text-white"><?php echo formatPrice(5000 - $subtotal); ?></span> pou livrezon gratis!
                                    </p>
                                    <div class="w-full bg-slate-700 rounded-full h-2">
                                        <div class="bg-blue-500 h-2 rounded-full" style="width: <?php echo min(100, ($subtotal/5000)*100); ?>%"></div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="mt-4 bg-green-900/30 rounded-lg p-3 border border-green-600/50 text-center">
                                    <p class="text-xs sm:text-sm text-green-400 font-medium">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Ou benefisye livrezon gratis!
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Recommendations -->
            <?php if (!empty($recommendedProducts)): ?>
                <div class="mt-10 sm:mt-12 pt-8">
                    <h2 class="text-xl sm:text-2xl font-bold text-white mb-6 drop-shadow-lg">Ou ta kapab renmen sa yo tou</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-5">
                        <?php foreach ($recommendedProducts as $product): 
                            $isPromo = $product['price_promo'] > 0 && $product['price_promo'] < $product['price'];
                            $displayPrice = $isPromo ? $product['price_promo'] : $product['price'];
                        ?>
                            <a href="../product-detail.php?id=<?php echo $product['id']; ?>" class="glass-card rounded-xl p-3 sm:p-4 shadow-md block hover:shadow-xl transition-shadow">
                                <div class="aspect-square bg-gray-100 rounded-lg mb-3 relative overflow-hidden">
                                    <img src="../../uploads/products/<?php echo htmlspecialchars($product['image'] ?? 'placeholder.png'); ?>" 
                                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                                        class="w-full h-full object-cover"
                                        onerror="this.src='../../assets/img/placeholder.png'">
                                    <?php if ($isPromo): ?>
                                        <span class="absolute top-2 left-2 bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded">
                                            -<?php echo round((1 - $product['price_promo']/$product['price']) * 100); ?>%
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="text-sm sm:text-base font-semibold text-gray-900 mb-2 line-clamp-2 hover:text-blue-600 transition-colors">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </h3>
                                <div class="flex items-center gap-2">
                                    <?php if ($isPromo): ?>
                                        <span class="text-xs text-gray-400 line-through"><?php echo formatPrice($product['price']); ?></span>
                                        <span class="text-blue-600 font-bold text-sm sm:text-base"><?php echo formatPrice($displayPrice); ?></span>
                                    <?php else: ?>
                                        <span class="text-blue-600 font-bold text-sm sm:text-base"><?php echo formatPrice($displayPrice); ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Features & Footer -->
    <div class="relative z-10 bg-slate-900/95 backdrop-blur-md">
        <!-- Features -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                
                <!-- Free Shipping -->
                <div class="glass-card rounded-xl p-5 flex items-center gap-4 shadow-lg">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-cube text-blue-600 text-xl sm:text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-base sm:text-lg">Free Shipping</h3>
                        <p class="text-gray-600 text-sm mt-1">Free shipping for order above $180</p>
                    </div>
                </div>

                <!-- Flexible Payment -->
                <div class="glass-card rounded-xl p-5 flex items-center gap-4 shadow-lg">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-credit-card text-blue-600 text-xl sm:text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-base sm:text-lg">Flexible Payment</h3>
                        <p class="text-gray-600 text-sm mt-1">Multiple secure payment options</p>
                    </div>
                </div>

                <!-- 24×7 Support -->
                <div class="glass-card rounded-xl p-5 flex items-center gap-4 shadow-lg sm:col-span-2 lg:col-span-1">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-headset text-blue-600 text-xl sm:text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-base sm:text-lg">24×7 Support</h3>
                        <p class="text-gray-600 text-sm mt-1">We support online all days</p>
                    </div>
                </div>

            </div>
        </div>

        <!-- Footer -->
        <footer class="border-t border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8">
                    
                    <div class="space-y-4">
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-600 rounded-full flex items-center justify-center">
                                <span class="text-white font-bold text-lg sm:text-xl">L</span>
                            </div>
                            <span class="text-xl sm:text-2xl font-bold text-white">LE-STOCK.</span>
                        </div>
                        <p class="text-slate-400 text-sm leading-relaxed">
                            Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
                        </p>
                        <div class="flex items-center gap-3">
                            <a href="#" class="w-9 h-9 sm:w-10 sm:h-10 bg-blue-800 hover:bg-blue-600 rounded-full flex items-center justify-center transition-colors">
                                <i class="fab fa-facebook-f text-white text-sm"></i>
                            </a>
                            <a href="#" class="w-9 h-9 sm:w-10 sm:h-10 bg-blue-800 hover:bg-blue-600 rounded-full flex items-center justify-center transition-colors">
                                <i class="fab fa-instagram text-white text-sm"></i>
                            </a>
                            <a href="#" class="w-9 h-9 sm:w-10 sm:h-10 bg-blue-800 hover:bg-blue-600 rounded-full flex items-center justify-center transition-colors">
                                <i class="fab fa-youtube text-white text-sm"></i>
                            </a>
                            <a href="#" class="w-9 h-9 sm:w-10 sm:h-10 bg-blue-800 hover:bg-blue-600 rounded-full flex items-center justify-center transition-colors">
                                <i class="fab fa-twitter text-white text-sm"></i>
                            </a>
                        </div>
                    </div>

                    <div>
                        <h4 class="font-bold text-white mb-4 text-base sm:text-lg">Company</h4>
                        <ul class="space-y-3">
                            <li><a href="#" class="text-slate-400 hover:text-blue-400 text-sm transition-colors">About Us</a></li>
                            <li><a href="#" class="text-slate-400 hover:text-blue-400 text-sm transition-colors">Blog</a></li>
                            <li><a href="#" class="text-slate-400 hover:text-blue-400 text-sm transition-colors">Contact Us</a></li>
                            <li><a href="#" class="text-slate-400 hover:text-blue-400 text-sm transition-colors">Career</a></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="font-bold text-white mb-4 text-base sm:text-lg">Customer Services</h4>
                        <ul class="space-y-3">
                            <li><a href="#" class="text-slate-400 hover:text-blue-400 text-sm transition-colors">My Account</a></li>
                            <li><a href="#" class="text-slate-400 hover:text-blue-400 text-sm transition-colors">Track Your Order</a></li>
                            <li><a href="#" class="text-slate-400 hover:text-blue-400 text-sm transition-colors">Return</a></li>
                            <li><a href="#" class="text-slate-400 hover:text-blue-400 text-sm transition-colors">FAQS</a></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="font-bold text-white mb-4 text-base sm:text-lg">Contact Info</h4>
                        <ul class="space-y-3 text-sm text-slate-400">
                            <li class="flex items-center gap-2">
                                <i class="fas fa-phone text-blue-500 text-sm"></i>
                                +0123-456-789
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fas fa-envelope text-blue-500 text-sm"></i>
                                example@gmail.com
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-map-marker-alt text-blue-500 text-sm mt-1"></i>
                                <span>8502 Preston Rd.<br>Inglewood, Maine 98380</span>
                            </li>
                        </ul>
                    </div>

                </div>

                <div class="border-t border-slate-800 mt-8 pt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-slate-500 text-sm text-center sm:text-left">
                        Copyright © 2024 Clothing Website Design. All Rights Reserved.
                    </p>
                    <div class="flex items-center gap-3">
                        <select class="bg-slate-800 text-slate-300 text-sm rounded-lg px-3 py-2 border-none focus:ring-0 cursor-pointer hover:bg-slate-700 transition-colors">
                            <option value="en">English</option>
                            <option value="fr">Français</option>
                            <option value="ht">Kreyòl</option>
                        </select>
                        <select class="bg-slate-800 text-slate-300 text-sm rounded-lg px-3 py-2 border-none focus:ring-0 cursor-pointer hover:bg-slate-700 transition-colors">
                            <option value="usd">USD</option>
                            <option value="htg">HTG</option>
                            <option value="eur">EUR</option>
                        </select>
                    </div>
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
        
        // Form submission
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.classList.contains('qty-btn')) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Chajman...';
                }
            });
        });
    </script>
</body>

</html>