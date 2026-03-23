<?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';

// Rekipere pwodwi an promosyon
$promotionProducts = [];
$categories = ['All'];

try {
    $stmt = $pdo->query("
        SELECT DISTINCT c.id, c.name 
        FROM categories c
        JOIN products p ON p.category_id = c.id
        WHERE p.price_promo IS NOT NULL 
        AND p.price_promo > 0 
        AND p.price_promo < p.price
        AND p.status = 'disponible'
        ORDER BY c.name ASC
    ");
    $dbCategories = $stmt->fetchAll();
    
    foreach ($dbCategories as $cat) {
        $categories[] = $cat['name'];
    }

    $stmt = $pdo->query("
        SELECT p.*, c.name as category_name 
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.price_promo IS NOT NULL 
        AND p.price_promo > 0 
        AND p.price_promo < p.price
        AND p.status = 'disponible'
        ORDER BY p.id DESC
    ");
    $promotionProducts = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $promotionProducts = [];
    $categories = ['All', 'Electronics', 'Fashion', 'Home', 'Sports'];
}

function getDiscountPercent($price, $price_promo) {
    return round((1 - $price_promo / $price) * 100);
}

function getSavings($price, $price_promo) {
    return $price - $price_promo;
}

$selectedCategory = isset($_GET['category']) ? $_GET['category'] : 'All';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'featured';

$filteredProducts = $promotionProducts;
if ($selectedCategory !== 'All') {
    $filteredProducts = array_filter($promotionProducts, function($p) use ($selectedCategory) {
        return $p['category_name'] === $selectedCategory;
    });
}

switch ($sortBy) {
    case 'price-low':
        usort($filteredProducts, function($a, $b) {
            return $a['price_promo'] - $b['price_promo'];
        });
        break;
    case 'price-high':
        usort($filteredProducts, function($a, $b) {
            return $b['price_promo'] - $a['price_promo'];
        });
        break;
    case 'discount':
        usort($filteredProducts, function($a, $b) {
            $discountA = getDiscountPercent($a['price'], $a['price_promo']);
            $discountB = getDiscountPercent($b['price'], $b['price_promo']);
            return $discountB - $discountA;
        });
        break;
}

$totalProducts = count($promotionProducts);
$totalSavings = array_reduce($promotionProducts, function($sum, $p) {
    return $sum + getSavings($p['price'], $p['price_promo']);
}, 0);
?>

<!DOCTYPE html>
<html lang="ht">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promosyon - LE-STOCK</title>
    
    <!-- Sèlman fichè CSS lokal ou a -->
    <link rel="stylesheet" href="\le-stock\css\style.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Style pou koulè bleu yo sèlman -->
    <style>
        /* Koulè bleu gradient pou Hero Section */
        .hero-gradient {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #06b6d4 100%);
        }
        
        /* Glassmorphism efè */
        .glass {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        /* Gradient bouton */
        .btn-gradient {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }
        
        /* Gradient badge */
        .badge-gradient {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        
        /* Background decoration blur */
        .bg-primary-500\/30 {
            background-color: rgba(59, 130, 246, 0.3);
        }
        
        .bg-cyan-400\/20 {
            background-color: rgba(34, 211, 238, 0.2);
        }
        
        /* Top banner gradient */
        .bg-gradient-to-r.from-primary-500.to-primary-600 {
            background: linear-gradient(to right, #3b82f6, #2563eb);
        }
        
        /* Text koulè */
        .text-primary-500 {
            color: #3b82f6;
        }
        
        .text-yellow-300 {
            color: #fde047;
        }
        
        .text-green-400 {
            color: #4ade80;
        }
        
        .text-red-500 {
            color: #ef4444;
        }
        
        /* Background koulè */
        .bg-dark-900 {
            background-color: #0f172a;
        }
        
        .bg-dark-800 {
            background-color: #1e293b;
        }
        
        .bg-dark-700 {
            background-color: #334155;
        }
        
        .bg-dark-600 {
            background-color: #475569;
        }
        
        .bg-slate-950 {
            background-color: #020617;
        }
        
        /* Border koulè */
        .border-dark-700 {
            border-color: #334155;
        }
        
        .border-dark-800 {
            border-color: #1e293b;
        }
        
        /* Text slate */
        .text-slate-200 {
            color: #e2e8f0;
        }
        
        .text-slate-400 {
            color: #94a3b8;
        }
        
        .text-slate-500 {
            color: #64748b;
        }
        
        .text-slate-100 {
            color: #f1f5f9;
        }
        
        /* Hover efè */
        .hover\:text-primary-500:hover {
            color: #3b82f6;
        }
        
        .hover\:border-primary-500:hover {
            border-color: #3b82f6;
        }
        
        .hover\:bg-primary-500:hover {
            background-color: #3b82f6;
        }
        
        /* Animation */
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .animate-slide-in {
            animation: slideIn 0.3s ease;
        }
        
        /* Mobile Menu Styles */
        .mobile-menu {
            display: none;
            position: fixed;
            top: 90px;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #0f172a;
            z-index: 40;
            padding: 2rem;
            overflow-y: auto;
        }
        
        .mobile-menu.active {
            display: block;
        }
        
        .mobile-menu a {
            display: block;
            padding: 1rem;
            border-bottom: 1px solid #334155;
            font-size: 1.1rem;
        }
        
        .mobile-menu-toggle.active {
            background-color: #3b82f6;
            border-color: #3b82f6;
            color: white;
        }
        
        @media (min-width: 1024px) {
            .mobile-menu-toggle {
                display: none;
            }
        }
    </style>
    
</head>
<body class="bg-dark-900 text-slate-200 font-sans antialiased">

    <!-- Top Banner -->
    <div id="topBanner" class="bg-gradient-to-r from-primary-500 to-primary-600 text-white py-2.5 text-sm">
        <div class="max-w-7xl mx-auto px-4 flex justify-between items-center">
            <div class="flex items-center gap-6">
                <span><i class="fas fa-phone-alt mr-1.5"></i> (406) 555-0120</span>
                <span>Sign up and <strong>GET 25% OFF</strong> for your first order. <a href="#" class="underline hover:no-underline">Sign up now</a></span>
            </div>
            <button onclick="closeTopBanner()" class="w-7 h-7 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center transition-colors">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
    </div>

    <!-- Header -->
    <header class="bg-dark-800 border-b border-dark-700 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 h-[90px] flex justify-between items-center">
            
            <!-- ESPAS POU LOGO OU AN -->
            <div class="h-full flex items-center">
                <img src="\le-stock\assets\img\le stock entreprise copy2.png" 
                     alt="Logo" 
                     class="h-[70px] max-w-[280px] object-contain hover:scale-[1.02] transition-transform"
                     onerror="this.style.display='none'">
            </div>

            <!-- Navigation -->
            <nav class="hidden lg:flex gap-8">
                <a href="accueil.php" class="text-slate-400 hover:text-primary-500 font-medium transition-colors">Home</a>
                <a href="boutique.php" class="text-slate-400 hover:text-primary-500 font-medium transition-colors">Shop</a>
                <a href="femmes.php" class="text-slate-400 hover:text-primary-500 font-medium transition-colors">Women</a>
                <a href="hommes.php" class="text-slate-400 hover:text-primary-500 font-medium transition-colors">Men</a>
                <a href="accessoires.php" class="text-slate-400 hover:text-primary-500 font-medium transition-colors">Accessories</a>
                <a href="promotions.php" class="text-primary-500 font-semibold relative after:content-[''] after:absolute after:-bottom-2 after:left-0 after:right-0 after:h-0.5 after:bg-primary-500 after:rounded-full">Promotions</a>
                <a href="contact.php" class="text-slate-400 hover:text-primary-500 font-medium transition-colors">Contact Us</a>
            </nav>

            <!-- Actions -->
            <div class="flex gap-3">
                <a href="recherche.php" class="w-11 h-11 rounded-xl bg-dark-900 border border-dark-700 text-slate-400 hover:bg-primary-500 hover:border-primary-500 hover:text-white flex items-center justify-center transition-all">
                    <i class="fas fa-search"></i>
                </a>
                <a href="favoris.php" class="w-11 h-11 rounded-xl bg-dark-900 border border-dark-700 text-slate-400 hover:bg-primary-500 hover:border-primary-500 hover:text-white flex items-center justify-center transition-all">
                    <i class="fas fa-heart"></i>
                </a>
                <a href="panier.php" class="w-11 h-11 rounded-xl bg-dark-900 border border-dark-700 text-slate-400 hover:bg-primary-500 hover:border-primary-500 hover:text-white flex items-center justify-center transition-all">
                    <i class="fas fa-shopping-cart"></i>
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="profil.php" class="w-11 h-11 rounded-xl bg-dark-900 border border-dark-700 text-slate-400 hover:bg-primary-500 hover:border-primary-500 hover:text-white flex items-center justify-center transition-all">
                        <i class="fas fa-user"></i>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="w-11 h-11 rounded-xl bg-dark-900 border border-dark-700 text-slate-400 hover:bg-primary-500 hover:border-primary-500 hover:text-white flex items-center justify-center transition-all">
                        <i class="fas fa-user"></i>
                    </a>
                <?php endif; ?>
                
                <!-- Mobile Menu Toggle Button -->
                <button onclick="toggleMobileMenu()" class="mobile-menu-toggle w-11 h-11 rounded-xl bg-dark-900 border border-dark-700 text-slate-400 hover:bg-primary-500 hover:border-primary-500 hover:text-white flex items-center justify-center transition-all lg:hidden">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile Menu -->
    <div id="mobileMenu" class="mobile-menu lg:hidden">
        <a href="accueil.php" class="text-slate-400 hover:text-primary-500 font-medium transition-colors">Home</a>
        <a href="boutique.php" class="text-slate-400 hover:text-primary-500 font-medium transition-colors">Shop</a>
        <a href="femmes.php" class="text-slate-400 hover:text-primary-500 font-medium transition-colors">Women</a>
        <a href="hommes.php" class="text-slate-400 hover:text-primary-500 font-medium transition-colors">Men</a>
        <a href="accessoires.php" class="text-slate-400 hover:text-primary-500 font-medium transition-colors">Accessories</a>
        <a href="promotions.php" class="text-primary-500 font-semibold">Promotions</a>
        <a href="contact.php" class="text-slate-400 hover:text-primary-500 font-medium transition-colors">Contact Us</a>
    </div>

    <!-- Hero Section -->
    <section class="hero-gradient text-white py-12 relative overflow-hidden">
        <!-- Background decoration -->
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute top-[20%] left-[20%] w-64 h-64 bg-primary-500/30 rounded-full blur-3xl"></div>
            <div class="absolute bottom-[20%] right-[20%] w-48 h-48 bg-cyan-400/20 rounded-full blur-3xl"></div>
        </div>

        <div class="max-w-3xl mx-auto px-4 text-center relative z-10">
            <!-- Flash Badge -->
            <div class="inline-flex items-center gap-2 glass px-4 py-2 rounded-full mb-5 text-xs font-semibold uppercase tracking-wider">
                <i class="fas fa-bolt text-yellow-300"></i>
                <span>Flash Sale</span>
            </div>

            <h1 class="text-4xl md:text-5xl font-extrabold mb-4 drop-shadow-lg">MEGA PROMOTION</h1>
            <p class="text-lg md:text-xl mb-2 opacity-95">Save up to <span class="text-yellow-300 font-bold">30% OFF</span> on selected items</p>
            <p class="text-sm md:text-base opacity-85 mb-8">Limited time offer - Hurry up before it's too late!</p>

            <!-- Countdown -->
            <div class="flex justify-center gap-4 mb-10">
                <div class="glass rounded-2xl px-5 py-4 min-w-[80px] shadow-xl">
                    <span id="days" class="text-3xl font-extrabold block tabular-nums">04</span>
                    <span class="text-xs uppercase opacity-70 tracking-wider mt-1 block">Days</span>
                </div>
                <div class="glass rounded-2xl px-5 py-4 min-w-[80px] shadow-xl">
                    <span id="hours" class="text-3xl font-extrabold block tabular-nums">23</span>
                    <span class="text-xs uppercase opacity-70 tracking-wider mt-1 block">Hours</span>
                </div>
                <div class="glass rounded-2xl px-5 py-4 min-w-[80px] shadow-xl">
                    <span id="minutes" class="text-3xl font-extrabold block tabular-nums">57</span>
                    <span class="text-xs uppercase opacity-70 tracking-wider mt-1 block">Minutes</span>
                </div>
                <div class="glass rounded-2xl px-5 py-4 min-w-[80px] shadow-xl">
                    <span id="seconds" class="text-3xl font-extrabold block tabular-nums">48</span>
                    <span class="text-xs uppercase opacity-70 tracking-wider mt-1 block">Seconds</span>
                </div>
            </div>

            <!-- Stats -->
            <div class="flex justify-center gap-5 flex-wrap">
                <div class="glass rounded-2xl px-7 py-5 flex items-center gap-3.5 shadow-xl">
                    <div class="w-11 h-11 bg-primary-500/30 rounded-xl flex items-center justify-center text-primary-400 text-lg">
                        <i class="fas fa-tag"></i>
                    </div>
                    <div class="text-left">
                        <div class="text-2xl font-extrabold"><?= $totalProducts ?></div>
                        <div class="text-xs opacity-70 capitalize">Products on Sale</div>
                    </div>
                </div>
                <div class="glass rounded-2xl px-7 py-5 flex items-center gap-3.5 shadow-xl">
                    <div class="w-11 h-11 bg-primary-500/30 rounded-xl flex items-center justify-center text-primary-400 text-lg">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="text-left">
                        <div class="text-2xl font-extrabold">30%</div>
                        <div class="text-xs opacity-70 capitalize">Maximum Discount</div>
                    </div>
                </div>
                <div class="glass rounded-2xl px-7 py-5 flex items-center gap-3.5 shadow-xl">
                    <div class="w-11 h-11 bg-primary-500/30 rounded-xl flex items-center justify-center text-primary-400 text-lg">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="text-left">
                        <div class="text-2xl font-extrabold"><?= number_format($totalSavings, 0) ?></div>
                        <div class="text-xs opacity-70 capitalize">Total Savings</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-10">
        
        <!-- Filters -->
        <div class="bg-dark-800 border border-dark-700 rounded-2xl p-6 mb-8">
            <div class="flex flex-col lg:flex-row justify-between gap-6">
                <div class="flex-1">
                    <label class="block text-xs font-semibold uppercase text-slate-500 tracking-wider mb-3">Categories</label>
                    <div class="flex flex-wrap gap-2.5">
                        <?php foreach ($categories as $category): ?>
                            <a href="?category=<?= urlencode($category) ?>&sort=<?= $sortBy ?>" 
                               class="px-4 py-2 rounded-full text-sm font-medium transition-all border <?= $selectedCategory === $category ? 'bg-primary-500 text-white border-primary-500' : 'bg-dark-900 text-slate-400 border-dark-700 hover:border-primary-500 hover:text-primary-500' ?>">
                                <?= htmlspecialchars($category) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="lg:w-56">
                    <label class="block text-xs font-semibold uppercase text-slate-500 tracking-wider mb-3">Sort By</label>
                    <form method="get" class="m-0">
                        <input type="hidden" name="category" value="<?= htmlspecialchars($selectedCategory) ?>">
                        <select name="sort" onchange="this.form.submit()" class="w-full px-3.5 py-2.5 border border-dark-700 rounded-xl text-sm bg-dark-900 text-slate-200 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 cursor-pointer">
                            <option value="featured" <?= $sortBy === 'featured' ? 'selected' : '' ?>>Featured</option>
                            <option value="price-low" <?= $sortBy === 'price-low' ? 'selected' : '' ?>>Price: Low to High</option>
                            <option value="price-high" <?= $sortBy === 'price-high' ? 'selected' : '' ?>>Price: High to Low</option>
                            <option value="discount" <?= $sortBy === 'discount' ? 'selected' : '' ?>>Highest Discount</option>
                        </select>
                    </form>
                </div>
            </div>

            <div class="mt-5 pt-5 border-t border-dark-700 text-sm text-slate-500">
                Showing <strong class="text-slate-200 font-semibold"><?= count($filteredProducts) ?></strong> of <strong class="text-slate-200 font-semibold"><?= $totalProducts ?></strong> products
            </div>
        </div>

        <!-- Products Grid -->
        <?php if (count($filteredProducts) > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-12">
                <?php foreach ($filteredProducts as $product): 
                    $discount = getDiscountPercent($product['price'], $product['price_promo']);
                    $savings = getSavings($product['price'], $product['price_promo']);
                    $isLowStock = ($product['stock_qty'] ?? 0) < 5;
                ?>
                    <div class="bg-dark-800 border border-dark-700 rounded-2xl overflow-hidden hover:-translate-y-2 hover:border-primary-500 transition-all duration-300 group shadow-lg hover:shadow-2xl hover:shadow-black/40">
                        <div class="h-56 relative overflow-hidden bg-dark-900">
                            <img src="../uploads/products/<?= htmlspecialchars($product['image'] ?? 'placeholder.jpg') ?>" 
                                 alt="<?= htmlspecialchars($product['name']) ?>"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                 onerror="this.src='../assets/img/placeholder.png'">
                            
                            <!-- Discount Badge -->
                            <span class="absolute top-3 left-3 badge-gradient text-white px-3 py-1.5 rounded-full text-xs font-bold shadow-lg shadow-red-500/30">-<?= $discount ?>%</span>
                            
                            <!-- Actions -->
                            <div class="absolute top-3 right-3 flex flex-col gap-2 opacity-0 translate-x-2.5 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300">
                                <button onclick="addToFavorites(<?= $product['id'] ?>)" class="w-9 h-9 rounded-xl bg-dark-800 border border-dark-700 text-slate-400 hover:bg-primary-500 hover:border-primary-500 hover:text-white flex items-center justify-center transition-all" title="Add to favorites">
                                    <i class="fas fa-heart text-sm"></i>
                                </button>
                                <button onclick="quickView(<?= $product['id'] ?>)" class="w-9 h-9 rounded-xl bg-dark-800 border border-dark-700 text-slate-400 hover:bg-primary-500 hover:border-primary-500 hover:text-white flex items-center justify-center transition-all" title="Quick view">
                                    <i class="fas fa-eye text-sm"></i>
                                </button>
                            </div>
                        </div>

                        <div class="p-5">
                            <div class="flex items-center gap-1.5 text-xs text-primary-500 font-semibold uppercase tracking-wider mb-2">
                                <i class="fas fa-tag"></i>
                                <?= htmlspecialchars($product['category_name']) ?>
                            </div>
                            
                            <h3 class="text-base font-semibold text-slate-100 mb-3 line-clamp-2 h-12"><?= htmlspecialchars($product['name']) ?></h3>
                            
                            <div class="flex items-center gap-3 mb-3">
                                <span class="text-xl font-bold text-primary-500"><?= number_format($product['price_promo'], 2) ?> HTG</span>
                                <span class="text-sm text-slate-500 line-through"><?= number_format($product['price'], 2) ?> HTG</span>
                            </div>

                            <div class="inline-flex items-center gap-1.5 bg-green-500/15 text-green-400 px-3 py-1.5 rounded-full text-xs font-semibold mb-4 border border-green-500/20">
                                <i class="fas fa-piggy-bank"></i>
                                You save <?= number_format($savings, 2) ?> HTG
                            </div>

                            <div class="flex justify-between items-center pt-4 border-t border-dark-700">
                                <span class="text-sm text-slate-500 flex items-center gap-1.5 <?= $isLowStock ? 'text-red-500 font-semibold' : '' ?>">
                                    <i class="fas fa-box"></i>
                                    <?= $product['stock_qty'] ?? 0 ?> left
                                </span>
                                <button onclick="addToCart(<?= $product['id'] ?>)" class="btn-gradient text-white px-4 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 hover:scale-105 hover:shadow-lg hover:shadow-primary-500/40 transition-all">
                                    <i class="fas fa-cart-plus"></i> Add
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-20 text-slate-500 bg-dark-800 border border-dark-700 rounded-3xl">
                <i class="fas fa-search text-6xl text-primary-500/50 mb-6"></i>
                <h2 class="text-2xl font-bold text-slate-100 mb-2">No products found</h2>
                <p class="text-base mb-6">Try changing your filters or check back later for new promotions.</p>
                <a href="promotions.php" class="btn-gradient text-white px-6 py-3 rounded-xl font-semibold inline-flex items-center gap-2 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-primary-500/40 transition-all">
                    <i class="fas fa-undo"></i> Reset Filters
                </a>
            </div>
        <?php endif; ?>

        <!-- CTA Section -->
        <div class="bg-gradient-to-br from-dark-800 to-dark-700 border border-dark-700 rounded-3xl p-12 text-center relative overflow-hidden mb-12">
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-primary-500 to-cyan-400"></div>
            <h2 class="text-3xl font-bold text-slate-100 mb-3">Don't Miss Out on These Amazing Deals!</h2>
            <p class="text-base text-slate-400 mb-7">Subscribe to our newsletter and get exclusive offers</p>
            <form onsubmit="event.preventDefault(); subscribeNewsletter(this);" class="flex flex-col sm:flex-row gap-3 max-w-md mx-auto">
                <input type="email" placeholder="Enter your email" required class="flex-1 px-5 py-3.5 border border-dark-700 rounded-xl text-base bg-dark-900 text-slate-200 focus:outline-none focus:border-primary-500 placeholder-slate-500">
                <button type="submit" class="btn-gradient text-white px-8 py-3.5 rounded-xl font-semibold hover:-translate-y-0.5 hover:shadow-lg hover:shadow-primary-500/40 transition-all whitespace-nowrap">
                    Subscribe Now
                </button>
            </form>
        </div>

        <!-- Features -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-dark-800 border border-dark-700 rounded-2xl p-7 flex items-start gap-4 hover:border-primary-500 transition-colors">
                <div class="w-13 h-13 bg-primary-500/15 rounded-xl flex items-center justify-center text-primary-500 text-2xl flex-shrink-0">
                    <i class="fas fa-shipping-fast"></i>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-slate-100 mb-1">Free Shipping</h3>
                    <p class="text-sm text-slate-500">Free shipping for order above $180</p>
                </div>
            </div>
            <div class="bg-dark-800 border border-dark-700 rounded-2xl p-7 flex items-start gap-4 hover:border-primary-500 transition-colors">
                <div class="w-13 h-13 bg-primary-500/15 rounded-xl flex items-center justify-center text-primary-500 text-2xl flex-shrink-0">
                    <i class="fas fa-credit-card"></i>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-slate-100 mb-1">Flexible Payment</h3>
                    <p class="text-sm text-slate-500">Multiple secure payment options</p>
                </div>
            </div>
            <div class="bg-dark-800 border border-dark-700 rounded-2xl p-7 flex items-start gap-4 hover:border-primary-500 transition-colors">
                <div class="w-13 h-13 bg-primary-500/15 rounded-xl flex items-center justify-center text-primary-500 text-2xl flex-shrink-0">
                    <i class="fas fa-headset"></i>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-slate-100 mb-1">24x7 Support</h3>
                    <p class="text-sm text-slate-500">We support online all days</p>
                </div>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="bg-slate-950 border-t border-dark-800 mt-16 pt-14 pb-6">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 mb-10">
                
                <!-- Logo & About -->
                <div>
                    <div class="mb-5">
                        <img src="../assets/img/logo.png" alt="Logo" class="h-20 max-w-[320px] object-contain" onerror="this.style.display='none'">
                    </div>
                    <p class="text-sm text-slate-500 leading-relaxed mb-5">Your one-stop destination for premium products at unbeatable prices. Quality meets affordability.</p>
                    <div class="flex gap-3">
                        <a href="#" class="w-11 h-11 bg-dark-800 border border-dark-700 rounded-xl flex items-center justify-center text-slate-400 hover:bg-primary-500 hover:border-primary-500 hover:text-white transition-all">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="w-11 h-11 bg-dark-800 border border-dark-700 rounded-xl flex items-center justify-center text-slate-400 hover:bg-primary-500 hover:border-primary-500 hover:text-white transition-all">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="w-11 h-11 bg-dark-800 border border-dark-700 rounded-xl flex items-center justify-center text-slate-400 hover:bg-primary-500 hover:border-primary-500 hover:text-white transition-all">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="w-11 h-11 bg-dark-800 border border-dark-700 rounded-xl flex items-center justify-center text-slate-400 hover:bg-primary-500 hover:border-primary-500 hover:text-white transition-all">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>

                <!-- Company -->
                <div>
                    <h4 class="text-sm font-bold uppercase tracking-wider text-slate-100 mb-5">Company</h4>
                    <a href="#" class="block text-sm text-slate-500 hover:text-primary-500 mb-2.5 transition-colors">About Us</a>
                    <a href="#" class="block text-sm text-slate-500 hover:text-primary-500 mb-2.5 transition-colors">Our Story</a>
                    <a href="#" class="block text-sm text-slate-500 hover:text-primary-500 mb-2.5 transition-colors">Careers</a>
                    <a href="#" class="block text-sm text-slate-500 hover:text-primary-500 transition-colors">Press</a>
                </div>

                <!-- Customer Service -->
                <div>
                    <h4 class="text-sm font-bold uppercase tracking-wider text-slate-100 mb-5">Customer Service</h4>
                    <a href="#" class="block text-sm text-slate-500 hover:text-primary-500 mb-2.5 transition-colors">Help Center</a>
                    <a href="#" class="block text-sm text-slate-500 hover:text-primary-500 mb-2.5 transition-colors">Track Order</a>
                    <a href="#" class="block text-sm text-slate-500 hover:text-primary-500 mb-2.5 transition-colors">Returns</a>
                    <a href="#" class="block text-sm text-slate-500 hover:text-primary-500 transition-colors">Contact Us</a>
                </div>

                <!-- Contact Info -->
                <div>
                    <h4 class="text-sm font-bold uppercase tracking-wider text-slate-100 mb-5">Contact Info</h4>
                    <a href="tel:+0123-456-789" class="block text-sm text-slate-500 hover:text-primary-500 mb-2.5 transition-colors">
                        <i class="fas fa-phone mr-2"></i> +0123-456-789
                    </a>
                    <a href="mailto:support@lestock.com" class="block text-sm text-slate-500 hover:text-primary-500 mb-2.5 transition-colors">
                        <i class="fas fa-envelope mr-2"></i> support@lestock.com
                    </a>
                    <p class="text-sm text-slate-500 mt-3 leading-relaxed">
                        <i class="fas fa-map-marker-alt mr-2"></i>
                        8502 Preston Rd, Inglewood<br>
                        Maine 98380, USA
                    </p>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="pt-6 border-t border-dark-800 flex flex-col sm:flex-row justify-between items-center gap-4 text-sm text-slate-500">
                <p>&copy; <?= date('Y') ?> LE-STOCK. All rights reserved.</p>
                <div class="flex gap-5">
                    <button class="bg-dark-800 border border-dark-700 px-4 py-2 rounded-lg text-slate-400 hover:border-primary-500 hover:text-primary-500 flex items-center gap-2 transition-all">
                        <i class="fas fa-globe"></i> English <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <button class="bg-dark-800 border border-dark-700 px-4 py-2 rounded-lg text-slate-400 hover:border-primary-500 hover:text-primary-500 flex items-center gap-2 transition-all">
                        <i class="fas fa-dollar-sign"></i> USD <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                </div>
            </div>
        </div>
    </footer>

    <!-- Quick View Modal -->
    <div id="quickViewModal" onclick="if(event.target === this) closeQuickView()" class="hidden fixed inset-0 bg-slate-950/80 z-50 flex items-center justify-center p-6 backdrop-blur-sm">
        <div class="bg-dark-800 border border-dark-700 rounded-3xl max-w-4xl w-full max-h-[90vh] overflow-y-auto relative">
            <button onclick="closeQuickView()" class="absolute top-5 right-5 w-11 h-11 rounded-xl bg-dark-900 border border-dark-700 text-slate-400 hover:bg-red-500 hover:border-red-500 hover:text-white flex items-center justify-center transition-all z-10">
                <i class="fas fa-times"></i>
            </button>
            <div id="quickViewContent" class="p-8"></div>
        </div>
    </div>

    <!-- Admin Edit Button -->
    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        <button onclick="openEditModal()" title="Edit Countdown" class="fixed bottom-6 right-6 w-14 h-14 btn-gradient text-white rounded-full flex items-center justify-center text-xl shadow-lg shadow-primary-500/40 hover:scale-110 transition-transform z-50">
            <i class="fas fa-cog"></i>
        </button>

        <!-- Edit Modal -->
        <div id="editModal" onclick="if(event.target === this) closeEditModal()" class="hidden fixed inset-0 bg-slate-950/90 z-[60] flex items-center justify-center p-6 backdrop-blur-sm">
            <div class="bg-dark-800 border border-dark-700 rounded-3xl max-w-lg w-full p-8">
                <h3 class="text-xl font-bold text-slate-100 mb-6 flex items-center gap-2.5">
                    <i class="fas fa-clock text-primary-500"></i>
                    Edit Countdown
                </h3>
                
                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-2">End Date</label>
                        <input type="datetime-local" id="countdownEndDate" class="w-full px-4 py-3 border border-dark-700 rounded-xl bg-dark-900 text-slate-200 focus:outline-none focus:border-primary-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-2">Promotion Title</label>
                        <input type="text" id="promoTitle" value="MEGA PROMOTION" class="w-full px-4 py-3 border border-dark-700 rounded-xl bg-dark-900 text-slate-200 focus:outline-none focus:border-primary-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-2">Discount Text</label>
                        <input type="text" id="promoDiscount" value="30% OFF" class="w-full px-4 py-3 border border-dark-700 rounded-xl bg-dark-900 text-slate-200 focus:outline-none focus:border-primary-500">
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button onclick="closeEditModal()" class="flex-1 bg-dark-700 text-slate-400 py-3.5 rounded-xl font-semibold hover:bg-dark-600 transition-colors">Cancel</button>
                    <button onclick="saveCountdown()" class="flex-1 btn-gradient text-white py-3.5 rounded-xl font-semibold hover:shadow-lg hover:shadow-primary-500/40 transition-all">Save Changes</button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script>
        // Countdown Timer
        let countdownEndDate = localStorage.getItem('countdownEndDate') 
            ? new Date(localStorage.getItem('countdownEndDate'))
            : new Date(Date.now() + 5 * 24 * 60 * 60 * 1000);

        function updateCountdown() {
            const now = new Date();
            const diff = countdownEndDate - now;
            
            if (diff > 0) {
                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);
                
                document.getElementById('days').textContent = String(days).padStart(2, '0');
                document.getElementById('hours').textContent = String(hours).padStart(2, '0');
                document.getElementById('minutes').textContent = String(minutes).padStart(2, '0');
                document.getElementById('seconds').textContent = String(seconds).padStart(2, '0');
            } else {
                countdownEndDate = new Date(Date.now() + 5 * 24 * 60 * 60 * 1000);
                localStorage.setItem('countdownEndDate', countdownEndDate.toISOString());
            }
        }
        
        setInterval(updateCountdown, 1000);
        updateCountdown();

        // Mobile Menu Toggle
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            menu.classList.toggle('active');
            toggle.classList.toggle('active');
            
            // Change icon
            const icon = toggle.querySelector('i');
            if (menu.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        }

        // Admin Functions
        function openEditModal() {
            document.getElementById('editModal').classList.remove('hidden');
            const dateStr = countdownEndDate.toISOString().slice(0, 16);
            document.getElementById('countdownEndDate').value = dateStr;
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function saveCountdown() {
            const newDate = document.getElementById('countdownEndDate').value;
            const newTitle = document.getElementById('promoTitle').value;
            const newDiscount = document.getElementById('promoDiscount').value;
            
            if (newDate) {
                countdownEndDate = new Date(newDate);
                localStorage.setItem('countdownEndDate', countdownEndDate.toISOString());
            }
            
            document.querySelector('.hero-gradient h1').textContent = newTitle;
            document.querySelector('.hero-gradient .text-yellow-300').textContent = newDiscount;
            
            showNotification('Countdown updated successfully!', 'success');
            closeEditModal();
            updateCountdown();
        }

        // Add to Cart
        function addToCart(productId) {
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'product_id=' + productId + '&qty=1'
            })
            .then(r => r.json())
            .then(data => {
                showNotification(data.success ? 'Product added to cart!' : (data.message || 'Error'), data.success ? 'success' : 'error');
            })
            .catch(() => showNotification('Network error', 'error'));
        }

        // Add to Favorites
        function addToFavorites(productId) {
            fetch('add_to_favorites.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'product_id=' + productId
            })
            .then(r => r.json())
            .then(data => {
                showNotification(data.success ? 'Added to favorites!' : (data.message || 'Please login first'), data.success ? 'success' : 'error');
            });
        }

        // Quick View
        function quickView(productId) {
            const modal = document.getElementById('quickViewModal');
            const content = document.getElementById('quickViewContent');
            
            content.innerHTML = '<div class="text-center py-16"><i class="fas fa-spinner fa-spin text-4xl text-primary-500"></i></div>';
            modal.classList.remove('hidden');
            
            fetch('get_product.php?id=' + productId)
                .then(r => r.json())
                .then(data => {
                    const discount = Math.round((1 - data.price_promo / data.price) * 100);
                    const savings = data.price - data.price_promo;
                    
                    content.innerHTML = `
                        <div class="grid md:grid-cols-2 gap-8">
                            <div>
                                <img src="../uploads/products/${data.image}" 
                                     alt="${data.name}" 
                                     class="w-full rounded-2xl object-cover"
                                     onerror="this.src='../assets/img/placeholder.png'">
                            </div>
                            <div>
                                <span class="inline-block badge-gradient text-white px-4 py-2 rounded-full text-sm font-bold mb-4">-${discount}% OFF</span>
                                <h2 class="text-2xl font-bold text-slate-100 mb-4">${data.name}</h2>
                                <p class="text-slate-400 mb-6 leading-relaxed">${data.description || 'No description available.'}</p>
                                
                                <div class="mb-5">
                                    <span class="text-3xl font-extrabold text-primary-500">${new Intl.NumberFormat().format(data.price_promo)} HTG</span>
                                    <span class="line-through text-slate-500 ml-4 text-lg">${new Intl.NumberFormat().format(data.price)} HTG</span>
                                </div>
                                
                                <div class="inline-flex items-center gap-2 bg-green-500/15 text-green-400 px-4 py-3 rounded-xl mb-6 border border-green-500/20">
                                    <i class="fas fa-piggy-bank"></i>
                                    <span class="font-semibold">You save ${new Intl.NumberFormat().format(savings)} HTG</span>
                                </div>
                                
                                <div class="flex gap-3">
                                    <button onclick="addToCart(${data.id}); closeQuickView();" class="flex-1 btn-gradient text-white py-4 rounded-xl font-semibold flex items-center justify-center gap-2 hover:shadow-lg hover:shadow-primary-500/40 transition-all">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                    <button onclick="addToFavorites(${data.id})" class="w-14 h-14 rounded-xl border border-dark-700 bg-dark-900 flex items-center justify-center hover:border-red-500 transition-colors">
                                        <i class="fas fa-heart text-red-500 text-xl"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                })
                .catch(() => {
                    content.innerHTML = '<div class="text-center text-red-500 py-16"><i class="fas fa-exclamation-circle text-4xl mb-4"></i><br>Error loading product</div>';
                });
        }

        function closeQuickView() {
            document.getElementById('quickViewModal').classList.add('hidden');
        }

        // Newsletter
        function subscribeNewsletter(form) {
            const email = form.querySelector('input[type="email"]').value;
            showNotification('Thank you for subscribing with: ' + email, 'success');
            form.reset();
        }

        // Notification
        function showNotification(message, type) {
            const existing = document.querySelector('.notification');
            if (existing) existing.remove();
            
            const notif = document.createElement('div');
            notif.className = `fixed top-24 right-6 px-6 py-4 rounded-xl font-semibold text-sm z-[100] animate-slide-in flex items-center gap-2.5 shadow-2xl ${type === 'success' ? 'bg-dark-800 border border-green-500 text-green-400' : 'bg-dark-800 border border-red-500 text-red-400'}`;
            notif.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i> ${message}`;
            document.body.appendChild(notif);
            
            setTimeout(() => {
                notif.style.opacity = '0';
                notif.style.transform = 'translateX(100%)';
                setTimeout(() => notif.remove(), 300);
            }, 3000);
        }

        // Close top banner
        function closeTopBanner() {
            document.getElementById('topBanner').style.display = 'none';
            localStorage.setItem('topBannerClosed', 'true');
        }

        if (localStorage.getItem('topBannerClosed') === 'true') {
            document.getElementById('topBanner').style.display = 'none';
        }
    </script>

</body>
</html>