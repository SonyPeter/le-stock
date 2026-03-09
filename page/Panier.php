<?php
session_start();

// Inisyalize panier a si li pa egziste
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [
        [
            'id' => 1,
            'name' => "Robe d'été élégante",
            'image' => 'https://images.unsplash.com/photo-1579664531470-ac357f8f8e2b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxmYXNoaW9uJTIwY2xvdGhlcyUyMHByb2R1Y3R8ZW58MXx8fHwxNzcyNjM1NDU3fDA&ixlib=rb-4.1.0&q=80&w=1080',
            'price' => 950,
            'quantity' => 2,
            'inStock' => true
        ],
        [
            'id' => 2,
            'name' => 'Vase décoratif moderne',
            'image' => 'https://images.unsplash.com/photo-1645743712272-1aef8753a06a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxob21lJTIwZGVjb3IlMjBwcm9kdWN0fGVufDF8fHx8MTc3MjczOTc2M3ww&ixlib=rb-4.1.0&q=80&w=1080',
            'price' => 900,
            'quantity' => 1,
            'inStock' => true
        ],
        [
            'id' => 3,
            'name' => 'Écouteurs sans fil premium',
            'image' => 'https://images.unsplash.com/photo-1758979792186-32a5da91f24d?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxlbGVjdHJvbmljcyUyMGdhZGdldCUyMHByb2R1Y3R8ZW58MXx8fHwxNzcyNjM1NDU4fDA&ixlib=rb-4.1.0&q=80&w=1080',
            'price' => 3200,
            'quantity' => 1,
            'inStock' => true
        ]
    ];
}

// Pwodwi rekòmande yo
$recommendedProducts = [
    [
        'id' => 10,
        'name' => 'Sac à main élégant',
        'image' => 'https://images.unsplash.com/photo-1584917865442-de89df76afd3?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxsdXh1cnklMjBiYWd8ZW58MXx8fHwxNzQxNTYzNDU3fDA&ixlib=rb-4.1.0&q=80&w=1080',
        'price' => 4500
    ],
    [
        'id' => 11,
        'name' => 'Montre de luxe',
        'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxsdXh1cnklMjB3YXRjaHxlbnwxfHx8fDE3NDE1NjM0NTd8MA&ixlib=rb-4.1.0&q=80&w=1080',
        'price' => 8500
    ],
    [
        'id' => 12,
        'name' => 'Lunettes de soleil',
        'image' => 'https://images.unsplash.com/photo-1572635196237-14b3f281503f?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxzdW5nbGFzc2VzfGVufDF8fHx8MTc0MTU2MzQ1N3ww&ixlib=rb-4.1.0&q=80&w=1080',
        'price' => 1200
    ]
];

// Jere aksyon yo
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_quantity':
            $itemId = intval($_POST['item_id']);
            $newQuantity = intval($_POST['quantity']);
            
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['id'] === $itemId && $newQuantity >= 1) {
                    $item['quantity'] = $newQuantity;
                    break;
                }
            }
            break;
            
        case 'remove_item':
            $itemId = intval($_POST['item_id']);
            $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], fn($item) => $item['id'] !== $itemId));
            break;
            
        case 'add_to_cart':
            $productId = intval($_POST['product_id']);
            $product = array_values(array_filter($recommendedProducts, fn($p) => $p['id'] === $productId))[0] ?? null;
            
            if ($product) {
                $existingKey = array_search($productId, array_column($_SESSION['cart'], 'id'));
                
                if ($existingKey !== false) {
                    $_SESSION['cart'][$existingKey]['quantity']++;
                } else {
                    $_SESSION['cart'][] = [
                        'id' => $product['id'],
                        'name' => $product['name'],
                        'image' => $product['image'],
                        'price' => $product['price'],
                        'quantity' => 1,
                        'inStock' => true
                    ];
                }
            }
            header('Location: panier.php');
            exit;
            
        case 'apply_promo':
            $promoCode = strtoupper(trim($_POST['promo_code']));
            if ($promoCode === 'PROMO10') {
                $_SESSION['applied_promo'] = 'PROMO10';
                $message = 'Code promo appliqué: -10%';
                $messageType = 'success';
            } else {
                $message = 'Code promo invalide';
                $messageType = 'error';
            }
            break;
    }
    
    // Redireksyon pou evite resoumisyon fòm la
    if ($action !== 'add_to_cart') {
        header('Location: panier.php');
        exit;
    }
}

// Kalkile total yo
$cartItems = $_SESSION['cart'];
$subtotal = array_reduce($cartItems, fn($acc, $item) => $acc + ($item['price'] * $item['quantity']), 0);
$shipping = $subtotal > 500 ? 0 : 50;
$appliedPromo = $_SESSION['applied_promo'] ?? null;
$discount = $appliedPromo === 'PROMO10' ? $subtotal * 0.1 : 0;
$tax = ($subtotal - $discount) * 0.05;
$total = $subtotal - $discount + $shipping + $tax;

// Kalkile kantite total atik yo
$totalItems = array_reduce($cartItems, fn($acc, $item) => $acc + $item['quantity'], 0);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panier - LE-STOCK</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/le-stock/css/style.css">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .cart-item {
            transition: all 0.3s ease;
        }
        
        .cart-item:hover {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .quantity-btn {
            transition: all 0.2s ease;
        }
        
        .quantity-btn:hover:not(:disabled) {
            background-color: #f3f4f6;
        }
        
        .quantity-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .product-image {
            transition: transform 0.3s ease;
        }
        
        .product-card:hover .product-image {
            transform: scale(1.1);
        }
        
        .step-active {
            background-color: #2563eb;
            color: white;
        }
        
        .step-pending {
            background-color: #e5e7eb;
            color: #6b7280;
        }
        
        /* Logo styles - Pi gwo e responsive */
        .logo-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .logo-img {
            height: 3.5rem; /* 56px - pi gwo default */
            width: auto;
            max-width: 200px;
            object-fit: contain;
        }
        
        /* Checkout Button Style - Tankou Shein */
        .checkout-btn {
            background: linear-gradient(135deg, #1a1a1a 0%, #000000 100%);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .checkout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }
        
        .checkout-btn:active {
            transform: translateY(0);
        }
        
        .checkout-badge {
            background-color: #22c55e;
            color: white;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            margin-left: 0.5rem;
            white-space: nowrap;
        }
        
        .checkout-main-text {
            font-weight: 700;
            letter-spacing: 0.025em;
        }
        
        .checkout-subtext {
            font-size: 0.75rem;
            opacity: 0.9;
            margin-top: 0.25rem;
        }
        
        /* Extra small devices */
        @media (max-width: 375px) {
            .logo-img {
                height: 2.5rem; /* 40px */
                max-width: 140px;
            }
            
            .checkout-btn {
                padding: 0.75rem 1rem;
            }
            
            .checkout-main-text {
                font-size: 0.875rem;
            }
            
            .checkout-badge {
                font-size: 0.625rem;
                padding: 0.125rem 0.375rem;
            }
        }
        
        /* Small devices */
        @media (min-width: 376px) and (max-width: 640px) {
            .cart-item-image {
                width: 80px;
                height: 80px;
            }
            
            .step-text {
                display: none;
            }
            
            .step-line {
                width: 2rem;
            }
            
            .logo-img {
                height: 3rem; /* 48px */
                max-width: 160px;
            }
            
            .checkout-btn {
                padding: 0.875rem 1.25rem;
            }
            
            .checkout-main-text {
                font-size: 1rem;
            }
        }
        
        /* Medium devices (tablets) */
        @media (min-width: 641px) and (max-width: 1024px) {
            .step-line {
                width: 4rem;
            }
            
            .logo-img {
                height: 3.25rem; /* 52px */
                max-width: 180px;
            }
            
            .checkout-btn {
                padding: 1rem 1.5rem;
            }
            
            .checkout-main-text {
                font-size: 1.125rem;
            }
        }
        
        /* Large devices (desktops) */
        @media (min-width: 1025px) and (max-width: 1280px) {
            .step-line {
                width: 6rem;
            }
            
            .logo-img {
                height: 3.5rem; /* 56px */
                max-width: 200px;
            }
            
            .checkout-btn {
                padding: 1.125rem 1.75rem;
            }
            
            .checkout-main-text {
                font-size: 1.25rem;
            }
        }
        
        /* Extra large devices */
        @media (min-width: 1281px) {
            .step-line {
                width: 6rem;
            }
            
            .logo-img {
                height: 4rem; /* 64px - pi gwo sou gwo ekran */
                max-width: 220px;
            }
            
            .checkout-btn {
                padding: 1.25rem 2rem;
            }
            
            .checkout-main-text {
                font-size: 1.25rem;
            }
        }
        
        /* Header padding ajisteman */
        @media (max-width: 640px) {
            header .max-w-7xl {
                padding-top: 0.75rem;
                padding-bottom: 0.75rem;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 antialiased">

    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between">
            <!-- Logo Section -->
            <a href="index.php" class="logo-container">
                <!-- Logo ou a -->
                <img src="\le-stock\assets\img\le stock entreprise copy.png" 
                    alt="LE-STOCK" 
                    class="logo-img" 
                    onerror="this.style.display='none'; document.getElementById('logo-fallback').style.display='flex'; document.getElementById('logo-text').classList.remove('hidden');">
                
                <!-- Fallback icon si logo pa ka chaje -->
                <div id="logo-fallback" class="w-12 h-12 bg-gray-900 rounded-lg flex items-center justify-center flex-shrink-0" style="display: none;">
                    <i class="fas fa-shopping-bag text-white text-xl"></i>
                </div>
                
                <!-- Tèks fallback -->
                <span id="logo-text" class="text-xl sm:text-2xl font-bold text-gray-900 tracking-tight hidden">LE-STOCK</span>
            </a>
            
            <!-- Cart Icon -->
            <div class="flex items-center gap-2">
                <a href="panier.php" class="relative p-2 hover:bg-gray-100 rounded-lg transition-colors">
                    <i class="fas fa-shopping-cart text-gray-700 text-xl sm:text-2xl"></i>
                    <?php if (count($cartItems) > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-blue-600 text-white text-xs font-bold rounded-full w-5 h-5 sm:w-6 sm:h-6 flex items-center justify-center">
                            <?= count($cartItems) ?>
                        </span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6 sm:py-8">
        
        <!-- Progress Steps -->
        <div class="mb-6 sm:mb-8 overflow-x-auto">
            <div class="flex items-center justify-center gap-2 sm:gap-4 min-w-max px-2">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 step-active rounded-full flex items-center justify-center font-semibold text-sm flex-shrink-0">
                        1
                    </div>
                    <span class="step-text text-sm font-medium text-blue-600">Panier</span>
                </div>
                <div class="step-line h-0.5 bg-gray-300"></div>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 step-pending rounded-full flex items-center justify-center font-semibold text-sm flex-shrink-0">
                        2
                    </div>
                    <span class="step-text text-sm font-medium text-gray-500">Paiement</span>
                </div>
                <div class="step-line h-0.5 bg-gray-300"></div>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 step-pending rounded-full flex items-center justify-center font-semibold text-sm flex-shrink-0">
                        3
                    </div>
                    <span class="step-text text-sm font-medium text-gray-500">Confirmation</span>
                </div>
            </div>
        </div>

        <!-- Page Title -->
        <div class="mb-6">
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">Votre panier</h2>
            <p class="text-gray-600 text-sm sm:text-base"><?= count($cartItems) ?> article<?= count($cartItems) > 1 ? 's' : '' ?> dans votre panier</p>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-xl <?= $messageType === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700' ?> flex items-center gap-3">
                <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> text-lg"></i>
                <span class="font-medium"><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <?php if (empty($cartItems)): ?>
            <!-- Empty Cart -->
            <div class="bg-white border-2 border-gray-200 rounded-2xl shadow-lg p-8 sm:p-12 text-center">
                <i class="fas fa-shopping-cart text-5xl sm:text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2">Votre panier est vide</h3>
                <p class="text-gray-600 mb-6 text-sm sm:text-base">Ajoutez des articles pour commencer vos achats</p>
                <a href="boutique.php" class="inline-flex items-center px-6 sm:px-8 py-3 sm:py-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-colors text-base sm:text-lg shadow-lg">
                    Continuer vos achats
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
                <!-- Cart Items -->
                <div class="lg:col-span-2 space-y-4">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="cart-item bg-white border-2 border-gray-200 rounded-2xl shadow-lg overflow-hidden">
                            <div class="p-4 sm:p-6">
                                <div class="flex gap-4 sm:gap-6">
                                    <!-- Product Image -->
                                    <div class="flex-shrink-0">
                                        <img src="<?= htmlspecialchars($item['image']) ?>" 
                                            alt="<?= htmlspecialchars($item['name']) ?>" 
                                            class="cart-item-image w-20 h-20 sm:w-32 sm:h-32 object-cover rounded-lg border border-gray-200"
                                            onerror="this.src='/le-stock/assets/img/placeholder.jpg'">
                                    </div>

                                    <!-- Product Details -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex justify-between items-start mb-2 sm:mb-3">
                                            <div class="min-w-0 pr-2">
                                                <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-1 truncate"><?= htmlspecialchars($item['name']) ?></h3>
                                                <?php if ($item['inStock']): ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700 border border-green-300">
                                                        <i class="fas fa-check text-xs"></i>
                                                        En stock
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700 border border-red-300">
                                                        Rupture de stock
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <form method="POST" action="" class="flex-shrink-0">
                                                <input type="hidden" name="action" value="remove_item">
                                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                                <button type="submit" class="text-gray-400 hover:text-red-500 transition-colors p-1">
                                                    <i class="fas fa-trash-alt text-lg"></i>
                                                </button>
                                            </form>
                                        </div>

                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 mt-3 sm:mt-0">
                                            <!-- Quantity Controls -->
                                            <div class="flex items-center gap-2 sm:gap-3">
                                                <span class="text-xs sm:text-sm text-gray-600">Quantité:</span>
                                                <div class="flex items-center border-2 border-gray-300 rounded-lg">
                                                    <form method="POST" action="" class="inline">
                                                        <input type="hidden" name="action" value="update_quantity">
                                                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                                        <input type="hidden" name="quantity" value="<?= $item['quantity'] - 1 ?>">
                                                        <button type="submit" class="quantity-btn p-2 sm:p-3" <?= $item['quantity'] <= 1 ? 'disabled' : '' ?>>
                                                            <i class="fas fa-minus text-xs sm:text-sm"></i>
                                                        </button>
                                                    </form>
                                                    <span class="px-3 sm:px-4 font-semibold text-gray-900 text-sm sm:text-base min-w-[2rem] text-center"><?= $item['quantity'] ?></span>
                                                    <form method="POST" action="" class="inline">
                                                        <input type="hidden" name="action" value="update_quantity">
                                                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                                        <input type="hidden" name="quantity" value="<?= $item['quantity'] + 1 ?>">
                                                        <button type="submit" class="quantity-btn p-2 sm:p-3">
                                                            <i class="fas fa-plus text-xs sm:text-sm"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>

                                            <!-- Price -->
                                            <div class="text-left sm:text-right">
                                                <p class="text-xs sm:text-sm text-gray-500 mb-1"><?= number_format($item['price'], 2) ?> MAD × <?= $item['quantity'] ?></p>
                                                <p class="text-xl sm:text-2xl font-bold text-gray-900"><?= number_format($item['price'] * $item['quantity'], 2) ?> MAD</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Continue Shopping Button -->
                    <a href="boutique.php" class="block w-full text-center px-6 py-4 border-2 border-gray-300 rounded-xl font-medium text-gray-700 hover:bg-gray-50 transition-colors text-base sm:text-lg">
                        Continuer vos achats
                    </a>
                </div>

                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white border-2 border-gray-200 rounded-2xl shadow-lg sticky top-24 overflow-hidden">
                        <div class="border-b bg-gray-50 px-4 sm:px-6 py-4">
                            <h3 class="text-lg sm:text-xl font-bold text-gray-900">Résumé de la commande</h3>
                        </div>
                        <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
                            <!-- Promo Code -->
                            <div>
                                <label class="text-sm font-semibold text-gray-700 mb-2 block">Code promo</label>
                                <form method="POST" action="" class="flex gap-2">
                                    <input type="hidden" name="action" value="apply_promo">
                                    <input type="text" name="promo_code" placeholder="Entrez le code" 
                                        value="<?= htmlspecialchars($_POST['promo_code'] ?? '') ?>"
                                        class="flex-1 px-3 sm:px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:outline-none text-sm sm:text-base uppercase"
                                        <?= $appliedPromo ? 'disabled' : '' ?>>
                                    <button type="submit" 
                                        class="px-3 sm:px-4 py-2.5 border-2 border-gray-300 rounded-lg font-medium text-sm hover:bg-gray-50 transition-colors disabled:opacity-50"
                                        <?= $appliedPromo ? 'disabled' : '' ?>>
                                        <?php if ($appliedPromo): ?>
                                            <i class="fas fa-check text-green-600"></i>
                                        <?php else: ?>
                                            Appliquer
                                        <?php endif; ?>
                                    </button>
                                </form>
                                <?php if ($appliedPromo): ?>
                                    <p class="text-sm text-green-600 mt-2 flex items-center gap-1">
                                        <i class="fas fa-percent text-xs"></i>
                                        Code promo appliqué: -10%
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div class="border-t border-gray-200"></div>

                            <!-- Price Breakdown -->
                            <div class="space-y-2 sm:space-y-3">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Sous-total</span>
                                    <span class="font-medium text-gray-900"><?= number_format($subtotal, 2) ?> MAD</span>
                                </div>

                                <?php if ($discount > 0): ?>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Réduction (-10%)</span>
                                        <span class="font-medium text-green-600">-<?= number_format($discount, 2) ?> MAD</span>
                                    </div>
                                <?php endif; ?>

                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Livraison</span>
                                    <?php if ($shipping === 0): ?>
                                        <span class="font-medium text-green-600">GRATUITE</span>
                                    <?php else: ?>
                                        <span class="font-medium text-gray-900"><?= number_format($shipping, 2) ?> MAD</span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($shipping > 0): ?>
                                    <p class="text-xs text-gray-500 flex items-center gap-1">
                                        <i class="fas fa-gift text-xs"></i>
                                        Livraison gratuite dès 500 MAD d'achat
                                    </p>
                                <?php endif; ?>

                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Taxes (5%)</span>
                                    <span class="font-medium text-gray-900"><?= number_format($tax, 2) ?> MAD</span>
                                </div>

                                <div class="border-t border-gray-200 my-3"></div>

                                <div class="flex justify-between text-lg sm:text-xl font-bold">
                                    <span class="text-gray-900">Total</span>
                                    <span class="text-blue-600"><?= number_format($total, 2) ?> MAD</span>
                                </div>
                            </div>

                            <!-- Checkout Button - Style Shein -->
                            <a href="paiement.php" class="checkout-btn block w-full text-center text-white rounded-xl transition-all shadow-lg">
                                <div class="flex items-center justify-center gap-2 flex-wrap">
                                    <span class="checkout-main-text">Passer la commande</span>
                                    <span class="checkout-badge">
                                        <i class="fas fa-shopping-bag text-xs"></i>
                                        <?= $totalItems ?>
                                    </span>
                                </div>
                                <?php if ($shipping === 0): ?>
                                    <div class="checkout-subtext">
                                        <i class="fas fa-truck text-xs mr-1"></i>
                                        Livraison gratuite !
                                    </div>
                                <?php endif; ?>
                            </a>

                            <!-- Security Info -->
                            <div class="space-y-2 pt-4 border-t">
                                <div class="flex items-center gap-2 text-xs sm:text-sm text-gray-600">
                                    <i class="fas fa-lock text-green-600"></i>
                                    <span>Paiement sécurisé SSL</span>
                                </div>
                                <div class="flex items-center gap-2 text-xs sm:text-sm text-gray-600">
                                    <i class="fas fa-check-circle text-green-600"></i>
                                    <span>Garantie satisfait ou remboursé</span>
                                </div>
                                <div class="flex items-center gap-2 text-xs sm:text-sm text-gray-600">
                                    <i class="fas fa-tag text-green-600"></i>
                                    <span>Meilleurs prix garantis</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Recommended Products -->
        <?php if (!empty($cartItems)): ?>
            <div class="mt-10 sm:mt-12">
                <h3 class="text-xl sm:text-2xl font-bold text-gray-900 mb-4 sm:mb-6">Vous aimerez aussi</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                    <?php foreach ($recommendedProducts as $product): ?>
                        <div class="product-card bg-white border-2 border-gray-200 rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
                            <div class="relative aspect-square overflow-hidden">
                                <img src="<?= htmlspecialchars($product['image']) ?>" 
                                    alt="<?= htmlspecialchars($product['name']) ?>" 
                                    class="product-image w-full h-full object-cover"
                                    onerror="this.src='/le-stock/assets/img/placeholder.jpg'">
                            </div>
                            <div class="p-4">
                                <h4 class="font-semibold text-gray-900 mb-2 text-sm sm:text-base truncate"><?= htmlspecialchars($product['name']) ?></h4>
                                <p class="text-lg sm:text-xl font-bold text-blue-600 mb-4"><?= number_format($product['price'], 2) ?> MAD</p>
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="add_to_cart">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <button type="submit" class="w-full bg-gray-900 hover:bg-gray-800 text-white py-2.5 sm:py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2 text-sm sm:text-base">
                                        <i class="fas fa-plus text-sm"></i>
                                        Ajouter au panier
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Montre tèks fallback si logo pa ka chaje
        document.addEventListener('DOMContentLoaded', function() {
            const logoImg = document.querySelector('.logo-img');
            const logoFallback = document.getElementById('logo-fallback');
            const logoText = document.getElementById('logo-text');
            
            if (logoImg && logoImg.style.display === 'none') {
                if (logoFallback) logoFallback.style.display = 'flex';
                if (logoText) logoText.classList.remove('hidden');
            }
        });
    </script>
</body>
</html>