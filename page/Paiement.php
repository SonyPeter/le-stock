<?php
session_start();

// Inisyalize panier a si li pa egziste (menm kòd ak panier.php)
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

// Pran done yo nan sesyon an (menm jan ak panier.php)
$cartItems = $_SESSION['cart'];

// Kalkile total yo (menm fòmil ak panier.php)
$subtotal = array_reduce($cartItems, fn($acc, $item) => $acc + ($item['price'] * $item['quantity']), 0);
$shipping = $subtotal > 500 ? 0 : 50;
$appliedPromo = $_SESSION['applied_promo'] ?? null;
$discount = $appliedPromo === 'PROMO10' ? $subtotal * 0.1 : 0;
$tax = ($subtotal - $discount) * 0.05;
$total = $subtotal - $discount + $shipping + $tax;

// Kalkile total kantite pwodwi nan panye a (menm jan ak panier.php)
$totalItems = array_reduce($cartItems, fn($acc, $item) => $acc + $item['quantity'], 0);

// Jere soumisyon fòm la
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentMethod = $_POST['payment_method'] ?? 'card';
    $cardNumber = $_POST['card_number'] ?? '';
    $cardName = $_POST['card_name'] ?? '';
    $expiryDate = $_POST['expiry_date'] ?? '';
    $cvv = $_POST['cvv'] ?? '';
    
    // Validasyon senp
    if ($paymentMethod === 'card' && (empty($cardNumber) || empty($cardName) || empty($expiryDate) || empty($cvv))) {
        $error = "Tanpli ranpli tout chan yo pou kat la.";
    } else {
        // Nan yon vèsyon reyèl, ou ta pwosesse peman an isit la
        $success = "Peman an trete avèk siksè!";
        // Vide panier a apre peman siksè
        // unset($_SESSION['cart']);
        // header("Location: confirmation.php");
        // exit();
    }
}

// Fonksyon pou fòmate nimewo kat la
function formatCardNumber($value) {
    $numbers = preg_replace('/\s/', '', $value);
    $formatted = implode(' ', str_split($numbers, 4));
    return $formatted;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement - LE-STOCK</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="\le-stock\css\style.css">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .payment-method-btn {
            transition: all 0.3s ease;
        }
        
        .payment-method-btn.active {
            border-color: #2563eb;
            background-color: #eff6ff;
        }
        
        .payment-method-btn:hover:not(.active) {
            border-color: #d1d5db;
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
            background-color: #e5e7eb;
            color: #6b7280;
        }
        
        .card-input {
            transition: all 0.2s ease;
        }
        
        .card-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        /* Logo styles - Pi gwo e responsive */
        .logo-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .logo-img {
            height: 3.5rem;
            width: auto;
            max-width: 200px;
            object-fit: contain;
        }
        
        /* Checkout Button Style - Tankou Shein (Pi lisib) */
        .checkout-btn {
            background: linear-gradient(135deg, #1a1a1a 0%, #000000 100%);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            border-radius: 0.5rem;
            max-width: 300px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .checkout-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }
        
        .checkout-btn:active {
            transform: translateY(0);
        }
        
        .checkout-badge {
            background-color: #22c55e;
            color: white;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.2rem 0.4rem;
            border-radius: 0.25rem;
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
            margin-left: 0.4rem;
            white-space: nowrap;
            line-height: 1;
        }
        
        .checkout-main-text {
            font-weight: 700;
            letter-spacing: 0.025em;
            font-size: 0.95rem;
        }
        
        .checkout-subtext {
            font-size: 0.75rem;
            opacity: 0.9;
            margin-top: 0.15rem;
            line-height: 1.2;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Extra small devices */
        @media (max-width: 375px) {
            .logo-img {
                height: 2.5rem;
                max-width: 140px;
            }
            
            .checkout-btn {
                padding: 0.5rem 0.75rem;
                max-width: 260px;
            }
            
            .checkout-main-text {
                font-size: 0.85rem;
            }
            
            .checkout-badge {
                font-size: 0.65rem;
                padding: 0.15rem 0.3rem;
            }
            
            .checkout-subtext {
                font-size: 0.65rem;
            }
        }
        
        /* Small devices */
        @media (min-width: 376px) and (max-width: 640px) {
            .step-text {
                display: none;
            }
            
            .step-line {
                width: 2rem;
            }
            
            .payment-grid {
                grid-template-columns: 1fr;
            }
            
            .logo-img {
                height: 3rem;
                max-width: 160px;
            }
            
            .checkout-btn {
                padding: 0.6rem 1rem;
                max-width: 280px;
            }
            
            .checkout-main-text {
                font-size: 0.9rem;
            }
            
            .checkout-badge {
                font-size: 0.7rem;
                padding: 0.2rem 0.35rem;
            }
            
            .checkout-subtext {
                font-size: 0.7rem;
            }
        }
        
        /* Medium devices (tablets) */
        @media (min-width: 641px) and (max-width: 1024px) {
            .step-line {
                width: 4rem;
            }
            
            .logo-img {
                height: 3.25rem;
                max-width: 180px;
            }
            
            .checkout-btn {
                padding: 0.75rem 1.25rem;
                max-width: 300px;
            }
            
            .checkout-main-text {
                font-size: 1rem;
            }
            
            .checkout-badge {
                font-size: 0.75rem;
                padding: 0.2rem 0.4rem;
            }
            
            .checkout-subtext {
                font-size: 0.75rem;
            }
        }
        
        /* Large devices (desktops) */
        @media (min-width: 1025px) and (max-width: 1280px) {
            .step-line {
                width: 6rem;
            }
            
            .logo-img {
                height: 3.5rem;
                max-width: 200px;
            }
            
            .checkout-btn {
                padding: 0.75rem 1.25rem;
                max-width: 300px;
            }
            
            .checkout-main-text {
                font-size: 1rem;
            }
            
            .checkout-badge {
                font-size: 0.75rem;
                padding: 0.2rem 0.4rem;
            }
            
            .checkout-subtext {
                font-size: 0.75rem;
            }
        }
        
        /* Extra large devices */
        @media (min-width: 1281px) {
            .step-line {
                width: 6rem;
            }
            
            .logo-img {
                height: 4rem;
                max-width: 220px;
            }
            
            .checkout-btn {
                padding: 0.75rem 1.25rem;
                max-width: 300px;
            }
            
            .checkout-main-text {
                font-size: 1rem;
            }
            
            .checkout-badge {
                font-size: 0.75rem;
                padding: 0.2rem 0.4rem;
            }
            
            .checkout-subtext {
                font-size: 0.75rem;
            }
        }
        
        /* Header padding ajisteman */
        @media (max-width: 640px) {
            header .max-w-6xl {
                padding-top: 0.75rem;
                padding-bottom: 0.75rem;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 antialiased">

    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between">
            <!-- Logo Section -->
            <a href="index.php" class="logo-container">
                <img src="\le-stock\assets\img\le stock entreprise copy.png" 
                    alt="LE-STOCK" 
                    class="logo-img" 
                    onerror="this.style.display='none'; document.getElementById('logo-fallback').style.display='flex'; document.getElementById('logo-text').classList.remove('hidden');">
                
                <div id="logo-fallback" class="w-12 h-12 bg-gray-900 rounded-lg flex items-center justify-center flex-shrink-0" style="display: none;">
                    <i class="fas fa-shopping-bag text-white text-xl"></i>
                </div>
                
                <span id="logo-text" class="text-xl sm:text-2xl font-bold text-gray-900 tracking-tight hidden">LE-STOCK</span>
            </a>
            
            <!-- Security Badge -->
            <div class="flex items-center gap-2 text-sm text-gray-600">
                <i class="fas fa-lock text-green-600"></i>
                <span class="hidden sm:inline font-medium">Paiement sécurisé</span>
            </div>
        </div>
    </header>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-6 sm:py-8">
        
        <!-- Back Button -->
        <div class="mb-4 sm:mb-6">
            <a href="panier.php" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 transition-colors text-sm sm:text-base font-medium">
                <i class="fas fa-chevron-left text-sm"></i>
                <span>Retour au panier</span>
            </a>
        </div>

        <!-- Progress Steps -->
        <div class="mb-6 sm:mb-8 overflow-x-auto">
            <div class="flex items-center justify-center gap-2 sm:gap-4 min-w-max px-2">
                <!-- Step 1 -->
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 step-completed rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-check text-sm"></i>
                    </div>
                    <span class="step-text text-sm font-medium text-gray-900">Panier</span>
                </div>
                
                <div class="step-line h-0.5 bg-green-500"></div>
                
                <!-- Step 2 -->
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 step-active rounded-full flex items-center justify-center font-semibold text-sm flex-shrink-0">
                        2
                    </div>
                    <span class="step-text text-sm font-medium text-blue-600">Paiement</span>
                </div>
                
                <div class="step-line h-0.5 bg-gray-300"></div>
                
                <!-- Step 3 -->
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 step-pending rounded-full flex items-center justify-center font-semibold text-sm flex-shrink-0">
                        3
                    </div>
                    <span class="step-text text-sm font-medium text-gray-500">Confirmation</span>
                </div>
            </div>
        </div>

        <!-- Error/Success Messages -->
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3 text-red-700">
                <i class="fas fa-exclamation-circle text-lg"></i>
                <span class="font-medium"><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3 text-green-700">
                <i class="fas fa-check-circle text-lg"></i>
                <span class="font-medium"><?= htmlspecialchars($success) ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
            
            <!-- Payment Form Section -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Payment Methods -->
                <div class="bg-white border-2 border-gray-200 rounded-2xl shadow-lg overflow-hidden">
                    <div class="border-b bg-gray-50 px-4 sm:px-6 py-4">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-900 flex items-center gap-2">
                            <i class="fas fa-credit-card text-blue-600"></i>
                            Méthode de paiement
                        </h2>
                    </div>
                    
                    <div class="p-4 sm:p-6">
                        <form method="POST" action="" id="paymentForm">
                            
                            <!-- Payment Method Selection -->
                            <div class="payment-grid grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 mb-6">
                                
                                <!-- Card Option -->
                                <button type="button" onclick="setPaymentMethod('card')" 
                                    class="payment-method-btn <?= (!isset($_POST['payment_method']) || $_POST['payment_method'] === 'card') ? 'active' : '' ?> p-4 border-2 rounded-xl flex flex-col items-center gap-2 relative"
                                    id="btn-card">
                                    <i class="fas fa-credit-card text-2xl <?= (!isset($_POST['payment_method']) || $_POST['payment_method'] === 'card') ? 'text-blue-600' : 'text-gray-600' ?>"></i>
                                    <span class="font-medium text-sm sm:text-base <?= (!isset($_POST['payment_method']) || $_POST['payment_method'] === 'card') ? 'text-blue-600' : 'text-gray-900' ?>">
                                        Carte bancaire
                                    </span>
                                    <?php if (!isset($_POST['payment_method']) || $_POST['payment_method'] === 'card'): ?>
                                        <div class="absolute top-2 right-2 w-5 h-5 bg-blue-600 rounded-full flex items-center justify-center">
                                            <i class="fas fa-check text-white text-xs"></i>
                                        </div>
                                    <?php endif; ?>
                                </button>

                                <!-- PayPal Option -->
                                <button type="button" onclick="setPaymentMethod('paypal')" 
                                    class="payment-method-btn <?= (isset($_POST['payment_method']) && $_POST['payment_method'] === 'paypal') ? 'active' : '' ?> p-4 border-2 border-gray-200 rounded-xl flex flex-col items-center gap-2 relative hover:border-gray-300"
                                    id="btn-paypal">
                                    <i class="fas fa-wallet text-2xl <?= (isset($_POST['payment_method']) && $_POST['payment_method'] === 'paypal') ? 'text-blue-600' : 'text-gray-600' ?>"></i>
                                    <span class="font-medium text-sm sm:text-base <?= (isset($_POST['payment_method']) && $_POST['payment_method'] === 'paypal') ? 'text-blue-600' : 'text-gray-900' ?>">
                                        PayPal
                                    </span>
                                    <?php if (isset($_POST['payment_method']) && $_POST['payment_method'] === 'paypal'): ?>
                                        <div class="absolute top-2 right-2 w-5 h-5 bg-blue-600 rounded-full flex items-center justify-center">
                                            <i class="fas fa-check text-white text-xs"></i>
                                        </div>
                                    <?php endif; ?>
                                </button>

                                <!-- Wallet Option -->
                                <button type="button" onclick="setPaymentMethod('wallet')" 
                                    class="payment-method-btn <?= (isset($_POST['payment_method']) && $_POST['payment_method'] === 'wallet') ? 'active' : '' ?> p-4 border-2 border-gray-200 rounded-xl flex flex-col items-center gap-2 relative hover:border-gray-300"
                                    id="btn-wallet">
                                    <i class="fas fa-shield-alt text-2xl <?= (isset($_POST['payment_method']) && $_POST['payment_method'] === 'wallet') ? 'text-blue-600' : 'text-gray-600' ?>"></i>
                                    <span class="font-medium text-sm sm:text-base <?= (isset($_POST['payment_method']) && $_POST['payment_method'] === 'wallet') ? 'text-blue-600' : 'text-gray-900' ?>">
                                        Portefeuille
                                    </span>
                                    <?php if (isset($_POST['payment_method']) && $_POST['payment_method'] === 'wallet'): ?>
                                        <div class="absolute top-2 right-2 w-5 h-5 bg-blue-600 rounded-full flex items-center justify-center">
                                            <i class="fas fa-check text-white text-xs"></i>
                                        </div>
                                    <?php endif; ?>
                                </button>
                            </div>

                            <input type="hidden" name="payment_method" id="paymentMethod" value="<?= htmlspecialchars($_POST['payment_method'] ?? 'card') ?>">

                            <!-- Card Payment Form -->
                            <div id="card-form" class="<?= (isset($_POST['payment_method']) && $_POST['payment_method'] !== 'card') ? 'hidden' : '' ?> space-y-4">
                                
                                <!-- Card Number -->
                                <div class="space-y-2">
                                    <label for="card_number" class="block text-sm font-semibold text-gray-700">
                                        Numéro de carte
                                    </label>
                                    <div class="relative">
                                        <input type="text" id="card_number" name="card_number" 
                                            placeholder="1234 5678 9012 3456" maxlength="19"
                                            value="<?= htmlspecialchars($_POST['card_number'] ?? '') ?>"
                                            class="card-input w-full pl-4 pr-16 py-3 sm:py-3.5 bg-white border-2 border-gray-200 rounded-xl text-base font-medium text-gray-900 placeholder:text-gray-400 focus:outline-none"
                                            oninput="formatCardNumber(this)">
                                        <div class="absolute right-3 top-1/2 -translate-y-1/2 flex gap-1">
                                            <div class="w-8 h-5 bg-blue-600 rounded-sm opacity-80"></div>
                                            <div class="w-8 h-5 bg-red-600 rounded-sm opacity-80"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Name -->
                                <div class="space-y-2">
                                    <label for="card_name" class="block text-sm font-semibold text-gray-700">
                                        Nom sur la carte
                                    </label>
                                    <input type="text" id="card_name" name="card_name" 
                                        placeholder="Jean Dupont"
                                        value="<?= htmlspecialchars($_POST['card_name'] ?? '') ?>"
                                        class="card-input w-full px-4 py-3 sm:py-3.5 bg-white border-2 border-gray-200 rounded-xl text-base font-medium text-gray-900 placeholder:text-gray-400 focus:outline-none">
                                </div>

                                <!-- Expiry & CVV -->
                                <div class="grid grid-cols-2 gap-3 sm:gap-4">
                                    <div class="space-y-2">
                                        <label for="expiry_date" class="block text-sm font-semibold text-gray-700">
                                            Date d'expiration
                                        </label>
                                        <input type="text" id="expiry_date" name="expiry_date" 
                                            placeholder="MM/AA" maxlength="5"
                                            value="<?= htmlspecialchars($_POST['expiry_date'] ?? '') ?>"
                                            class="card-input w-full px-4 py-3 sm:py-3.5 bg-white border-2 border-gray-200 rounded-xl text-base font-medium text-gray-900 placeholder:text-gray-400 focus:outline-none"
                                            oninput="formatExpiryDate(this)">
                                    </div>
                                    <div class="space-y-2">
                                        <label for="cvv" class="block text-sm font-semibold text-gray-700">
                                            CVV
                                        </label>
                                        <input type="password" id="cvv" name="cvv" 
                                            placeholder="123" maxlength="3"
                                            value="<?= htmlspecialchars($_POST['cvv'] ?? '') ?>"
                                            class="card-input w-full px-4 py-3 sm:py-3.5 bg-white border-2 border-gray-200 rounded-xl text-base font-medium text-gray-900 placeholder:text-gray-400 focus:outline-none"
                                            oninput="this.value = this.value.replace(/\D/g, '')">
                                    </div>
                                </div>

                                <!-- Security Notice -->
                                <div class="flex items-center gap-3 p-3 sm:p-4 bg-blue-50 border border-blue-200 rounded-xl mt-4">
                                    <i class="fas fa-lock text-blue-600 text-lg"></i>
                                    <p class="text-sm text-blue-900 font-medium">
                                        Vos informations de paiement sont cryptées et sécurisées
                                    </p>
                                </div>
                            </div>

                            <!-- PayPal Form -->
                            <div id="paypal-form" class="<?= (!isset($_POST['payment_method']) || $_POST['payment_method'] !== 'paypal') ? 'hidden' : '' ?> py-8 text-center">
                                <i class="fas fa-wallet text-5xl sm:text-6xl text-blue-600 mb-4"></i>
                                <p class="text-gray-600 mb-6 text-base sm:text-lg">
                                    Vous serez redirigé vers PayPal pour finaliser votre paiement
                                </p>
                                <button type="button" class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white px-6 sm:px-8 py-3 sm:py-4 rounded-xl font-semibold text-base sm:text-lg transition-colors shadow-lg">
                                    Continuer avec PayPal
                                </button>
                            </div>

                            <!-- Wallet Form -->
                            <div id="wallet-form" class="<?= (!isset($_POST['payment_method']) || $_POST['payment_method'] !== 'wallet') ? 'hidden' : '' ?> py-8 text-center">
                                <i class="fas fa-shield-alt text-5xl sm:text-6xl text-blue-600 mb-4"></i>
                                <p class="text-gray-600 mb-6 text-base sm:text-lg">
                                    Connectez votre portefeuille électronique pour payer
                                </p>
                                <button type="button" class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white px-6 sm:px-8 py-3 sm:py-4 rounded-xl font-semibold text-base sm:text-lg transition-colors shadow-lg">
                                    Connecter le portefeuille
                                </button>
                            </div>

                            <!-- Billing Address -->
                            <div class="mt-8 pt-6 border-t border-gray-200">
                                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                                    <i class="fas fa-map-marker-alt text-blue-600"></i>
                                    Adresse de facturation
                                </h3>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                                    <div class="space-y-2">
                                        <label class="block text-sm font-semibold text-gray-700">Prénom</label>
                                        <input type="text" name="first_name" placeholder="Jean" 
                                            class="card-input w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl text-base focus:outline-none">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-sm font-semibold text-gray-700">Nom</label>
                                        <input type="text" name="last_name" placeholder="Dupont" 
                                            class="card-input w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl text-base focus:outline-none">
                                    </div>
                                </div>

                                <div class="space-y-2 mt-3 sm:mt-4">
                                    <label class="block text-sm font-semibold text-gray-700">Adresse</label>
                                    <input type="text" name="address" placeholder="123 Rue de la Paix" 
                                        class="card-input w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl text-base focus:outline-none">
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 mt-3 sm:mt-4">
                                    <div class="space-y-2">
                                        <label class="block text-sm font-semibold text-gray-700">Ville</label>
                                        <input type="text" name="city" placeholder="Paris" 
                                            class="card-input w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl text-base focus:outline-none">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-sm font-semibold text-gray-700">Code postal</label>
                                        <input type="text" name="zip_code" placeholder="75001" 
                                            class="card-input w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl text-base focus:outline-none">
                                    </div>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white border-2 border-gray-200 rounded-2xl shadow-lg sticky top-24 overflow-hidden">
                    <div class="border-b bg-gray-50 px-4 sm:px-6 py-4">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-900">Résumé de la commande</h2>
                    </div>
                    
                    <div class="p-4 sm:p-6">
                        <!-- Cart Items -->
                        <div class="space-y-4 mb-6 max-h-64 overflow-y-auto">
                            <?php foreach ($cartItems as $item): ?>
                                <div class="flex gap-3 sm:gap-4">
                                    <img src="<?= htmlspecialchars($item['image']) ?>" 
                                        alt="<?= htmlspecialchars($item['name']) ?>" 
                                        class="w-16 h-16 sm:w-20 sm:h-20 object-cover rounded-lg border border-gray-200 flex-shrink-0"
                                        onerror="this.src='/le-stock/assets/img/placeholder.jpg'">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-semibold text-sm text-gray-900 truncate"><?= htmlspecialchars($item['name']) ?></h3>
                                        <p class="text-xs sm:text-sm text-gray-500">Quantité: <?= $item['quantity'] ?></p>
                                        <p class="text-sm font-bold text-gray-900 mt-1">
                                            <?= number_format($item['price'] * $item['quantity'], 2) ?> MAD
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="border-t border-gray-200 my-4"></div>

                        <!-- Totals -->
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Sous-total</span>
                                <span class="font-semibold text-gray-900"><?= number_format($subtotal, 2) ?> MAD</span>
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
                                    <span class="font-semibold text-gray-900"><?= number_format($shipping, 2) ?> MAD</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Taxes (5%)</span>
                                <span class="font-semibold text-gray-900"><?= number_format($tax, 2) ?> MAD</span>
                            </div>

                            <div class="border-t border-gray-200 my-3"></div>

                            <div class="flex justify-between text-lg sm:text-xl font-bold">
                                <span class="text-gray-900">Total</span>
                                <span class="text-blue-600"><?= number_format($total, 2) ?> MAD</span>
                            </div>
                        </div>

                        <!-- Pay Button - Style Shein avec Badge (Pi lisib) -->
                        <button type="submit" form="paymentForm" class="checkout-btn block w-full mt-6 text-white transition-all shadow-lg">
                            <div class="flex items-center justify-center gap-2 py-2.5 px-3">
                                <span class="checkout-main-text">Passer la commande</span>
                                <span class="checkout-badge">
                                    <i class="fas fa-shopping-bag text-[10px]"></i>
                                    <?= $totalItems ?>
                                </span>
                            </div>
                            <?php if ($shipping === 0): ?>
                                <div class="checkout-subtext pb-1.5">
                                    <i class="fas fa-truck text-[10px] mr-1"></i>
                                    Livraison gratuite !
                                </div>
                            <?php endif; ?>
                        </button>

                        <!-- Security Badges -->
                        <div class="mt-6 space-y-2 sm:space-y-3">
                            <div class="flex items-center gap-2 text-xs sm:text-sm text-gray-600">
                                <i class="fas fa-shield-alt text-green-600"></i>
                                <span>Paiement 100% sécurisé</span>
                            </div>
                            <div class="flex items-center gap-2 text-xs sm:text-sm text-gray-600">
                                <i class="fas fa-lock text-green-600"></i>
                                <span>Données cryptées SSL</span>
                            </div>
                            <div class="flex items-center gap-2 text-xs sm:text-sm text-gray-600">
                                <i class="fas fa-check-circle text-green-600"></i>
                                <span>Garantie satisfait ou remboursé</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Banner -->
        <div class="mt-6 sm:mt-8 p-4 sm:p-6 bg-gradient-to-r from-green-50 to-blue-50 border-2 border-green-200 rounded-xl sm:rounded-2xl">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-shield-alt text-white text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 mb-1 text-base sm:text-lg">Paiement sécurisé par cryptage SSL</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">
                        Vos informations bancaires sont protégées par les plus hauts standards de sécurité internationaux.
                        Nous ne stockons jamais vos données de carte bancaire.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Payment method switching
        function setPaymentMethod(method) {
            document.getElementById('paymentMethod').value = method;
            
            document.querySelectorAll('.payment-method-btn').forEach(btn => {
                btn.classList.remove('active');
                btn.classList.add('border-gray-200');
                btn.classList.remove('border-blue-600', 'bg-blue-50');
                
                const icon = btn.querySelector('i');
                const text = btn.querySelector('span');
                const check = btn.querySelector('.absolute');
                
                if (icon) {
                    icon.classList.remove('text-blue-600');
                    icon.classList.add('text-gray-600');
                }
                if (text) {
                    text.classList.remove('text-blue-600');
                    text.classList.add('text-gray-900');
                }
                if (check) check.remove();
            });
            
            const activeBtn = document.getElementById('btn-' + method);
            activeBtn.classList.add('active');
            activeBtn.classList.remove('border-gray-200');
            activeBtn.classList.add('border-blue-600', 'bg-blue-50');
            
            const activeIcon = activeBtn.querySelector('i');
            const activeText = activeBtn.querySelector('span');
            
            if (activeIcon) {
                activeIcon.classList.remove('text-gray-600');
                activeIcon.classList.add('text-blue-600');
            }
            if (activeText) {
                activeText.classList.remove('text-gray-900');
                activeText.classList.add('text-blue-600');
            }
            
            const checkDiv = document.createElement('div');
            checkDiv.className = 'absolute top-2 right-2 w-5 h-5 bg-blue-600 rounded-full flex items-center justify-center';
            checkDiv.innerHTML = '<i class="fas fa-check text-white text-xs"></i>';
            activeBtn.appendChild(checkDiv);
            
            document.getElementById('card-form').classList.add('hidden');
            document.getElementById('paypal-form').classList.add('hidden');
            document.getElementById('wallet-form').classList.add('hidden');
            
            document.getElementById(method + '-form').classList.remove('hidden');
        }
        
        function formatCardNumber(input) {
            let value = input.value.replace(/\s/g, '').replace(/\D/g, '');
            let formatted = value.match(/.{1,4}/g)?.join(' ') || value;
            input.value = formatted;
        }
        
        function formatExpiryDate(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length >= 2) {
                input.value = value.slice(0, 2) + '/' + value.slice(2, 4);
            } else {
                input.value = value;
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const currentMethod = document.getElementById('paymentMethod').value || 'card';
            setPaymentMethod(currentMethod);
        });
    </script>
</body>
</html>