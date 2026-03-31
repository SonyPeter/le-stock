<?php
session_start();
// Korige chemen pou jwenn baz done a
require_once dirname(__DIR__) . '/config/db.php';

// KORIJE: Chemen relatif pou itilize nan HTML - depi nan pages/ ale nan uploads/
function getImageFullPath($imageName)
{
    return '../uploads/hot_deals/' . $imageName;
}

function imageExists($imageName)
{
    $basePath = dirname(__DIR__) . '/uploads/hot_deals/';
    $fullPath = $basePath . $imageName;
    return file_exists($fullPath) && is_file($fullPath);
}

function getProductForDeal($deal, $pdo)
{
    $stmt = $pdo->prepare("SELECT id FROM products WHERE name LIKE ? LIMIT 1");
    $stmt->execute(['%' . $deal['titre'] . '%']);
    $product = $stmt->fetch();
    if ($product) {
        return $product['id'];
    }
    return null;
}

 $product_id_from_url = $_GET['product_id'] ?? null;

 $cart_message = '';
 $cart_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $deal_id = intval($_POST['deal_id']);
    $quantity = intval($_POST['quantity'] ?? 1);

    try {
        $stmt = $pdo->prepare("SELECT * FROM hot_deals WHERE id = ? AND en_stock = 1 AND (date_fin IS NULL OR date_fin > NOW())");
        $stmt->execute([$deal_id]);
        $deal = $stmt->fetch();

        if (!$deal) {
            $cart_message = 'Deal sa a pa disponib ankò!';
        } else {
            $stmt = $pdo->prepare("SELECT id, stock_qty FROM products WHERE name = ? LIMIT 1");
            $stmt->execute([$deal['titre']]);
            $product = $stmt->fetch();

            $product_id = null;

            if ($product) {
                $product_id = $product['id'];
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO products (name, description, price, price_promo, stock_qty, status, category_id, created_at) 
                    VALUES (?, ?, ?, ?, ?, 'active', 1, NOW())
                ");
                $stmt->execute([
                    $deal['titre'],
                    $deal['description'],
                    $deal['prix_original'],
                    $deal['prix_deal'],
                    $deal['quantite_limite'] ?? 100
                ]);
                $product_id = $pdo->lastInsertId();

                if (!empty($deal['images'])) {
                    $first_image = $deal['images'][0];
                    $stmt = $pdo->prepare("UPDATE products SET image = ? WHERE id = ?");
                    $stmt->execute([$first_image['image_name'], $product_id]);
                }
            }

            $stmt = $pdo->prepare("SELECT id, quantity FROM panier WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            $existing = $stmt->fetch();

            if ($existing) {
                $new_qty = $existing['quantity'] + $quantity;
                $stmt = $pdo->prepare("UPDATE panier SET quantity = ? WHERE id = ?");
                $stmt->execute([$new_qty, $existing['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO panier (user_id, product_id, quantity, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$user_id, $product_id, $quantity]);
            }

            $cart_success = true;
            $cart_message = 'Deal la ajoute nan panye ou a!';
        }
    } catch (PDOException $e) {
        error_log("Erè ajoute nan panier: " . $e->getMessage());
        $cart_message = 'Erè nan ajoute deal la. Eseye ankò.';
    }
}

 $hot_deals = [];
try {
    $stmt = $pdo->query("SELECT * FROM hot_deals ORDER BY created_at DESC");

    while ($deal = $stmt->fetch()) {
        $img_stmt = $pdo->prepare("SELECT * FROM hot_deal_images WHERE deal_id = ? ORDER BY is_primary DESC, ordre ASC");
        $img_stmt->execute([$deal['id']]);
        $images = $img_stmt->fetchAll();

        $validImages = [];
        foreach ($images as $img) {
            $img['full_path'] = getImageFullPath($img['image_name']);
            $img['exists'] = imageExists($img['image_name']);
            $validImages[] = $img;
        }

        $deal['images'] = $validImages;
        $hot_deals[] = $deal;
    }
} catch (PDOException $e) {
    $hot_deals = [];
}

 $showDebug = false;
?>
<!DOCTYPE html>
<html lang="ht">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hot Deals | LE-STOCK</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }

        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #dbeafe; }
        ::-webkit-scrollbar-thumb { background: #2563eb; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #3b82f6; }

        /* ===== HEADER ===== */
        .main-header {
            background: #2563eb;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
        }
        .main-header .nav-link {
            color: #93c5fd;
            position: relative;
            transition: color 0.3s;
        }
        .main-header .nav-link:hover { color: #fff; }
        .main-header .nav-link::after {
            content: '';
            position: absolute;
            bottom: -4px; left: 0;
            width: 0; height: 2px;
            background: #fff;
            transition: width 0.3s;
        }
        .main-header .nav-link:hover::after { width: 100%; }
        .main-header .icon-btn { color: #bfdbfe; transition: all 0.3s; }
        .main-header .icon-btn:hover { color: #fff; background: rgba(255,255,255,0.15); }
        .cart-badge { background: #1d4ed8; color: #fff; font-weight: 700; }

        /* ===== LOGO ===== */
        .logo-image { max-height: 55px; transition: transform 0.3s; }
        .logo-image:hover { transform: scale(1.05); }
        @media (max-width: 640px) { .logo-image { max-height: 40px; } }
        @media (min-width: 1024px) { .logo-image { max-height: 70px; } }

        /* ===== FOOTER ===== */
        .main-footer {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 50%, #2563eb 100%);
            color: #bfdbfe; padding: 3.5rem 0 1.5rem; border-top: 3px solid #2563eb;
        }
        .footer-logo-icon {
            width: 46px; height: 46px; background: linear-gradient(135deg, #3b82f6, #60a5fa);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 800; font-size: 1.3rem; box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .footer-logo-text { color: #fff; font-weight: 800; font-size: 1.35rem; }
        .main-footer h4 { color: #fff; font-weight: 700; font-size: 1.05rem; margin-bottom: 1.25rem; position: relative; padding-bottom: 0.6rem; }
        .main-footer h4::after { content: ''; position: absolute; bottom: 0; left: 0; width: 35px; height: 3px; background: linear-gradient(90deg, #60a5fa, #2563eb); border-radius: 2px; }
        .main-footer p, .footer-desc { color: #93c5fd; line-height: 1.7; font-size: 0.9rem; }
        .footer-links a { color: #93c5fd; text-decoration: none; font-size: 0.9rem; transition: all 0.3s; display: inline-block; padding: 0.2rem 0; }
        .footer-links a:hover { color: #fff; transform: translateX(4px); }
        .contact-item { color: #93c5fd; display: flex; align-items: flex-start; gap: 0.75rem; margin-bottom: 0.85rem; font-size: 0.9rem; }
        .contact-item i { color: #60a5fa; margin-top: 0.2rem; }
        .social-btn {
            width: 40px; height: 40px; background: rgba(96,165,250,0.12); border: 2px solid rgba(96,165,250,0.25);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            color: #fff; text-decoration: none; transition: all 0.3s; font-size: 1rem;
        }
        .social-btn:hover { background: #2563eb; border-color: #60a5fa; transform: translateY(-3px); box-shadow: 0 4px 12px rgba(37,99,235,0.4); }
        .footer-bottom { border-top: 1px solid rgba(96,165,250,0.15); margin-top: 2.5rem; padding-top: 1.5rem; display: flex; flex-direction: column; gap: 1rem; }
        @media (min-width: 640px) { .footer-bottom { flex-direction: row; justify-content: space-between; align-items: center; } }
        .footer-copy { color: #60a5fa; font-size: 0.85rem; }
        .footer-lang {
            background: rgba(96,165,250,0.12); color: #bfdbfe; border: 1px solid rgba(96,165,250,0.25);
            padding: 0.4rem 0.85rem; border-radius: 0.375rem; font-size: 0.8rem; cursor: pointer;
            display: flex; align-items: center; gap: 0.4rem; transition: all 0.3s;
        }
        .footer-lang:hover { background: rgba(96,165,250,0.22); color: #fff; }

        /* ===== FEATURES ===== */
        .features-section {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            padding: 3rem 0; border-bottom: 1px solid rgba(96,165,250,0.15);
        }
        .feature-card {
            background: #fff; border-radius: 1rem; padding: 1.5rem;
            display: flex; align-items: center; gap: 1rem;
            transition: all 0.3s; border: 2px solid transparent;
        }
        .feature-card:hover { transform: translateY(-4px); box-shadow: 0 15px 30px rgba(0,0,0,0.15); border-color: #2563eb; }
        .feature-icon {
            width: 56px; height: 56px; background: linear-gradient(135deg, #3b82f6, #2563eb);
            border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(37,99,235,0.3);
        }
        .feature-icon i { color: #fff; font-size: 1.4rem; }
        .feature-card h3 { color: #1e3a8a; font-weight: 700; font-size: 1.05rem; margin-bottom: 0.15rem; }
        .feature-card p { color: #6b7280; font-size: 0.875rem; }

        /* ===== HOT DEALS PAGE ===== */
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
        }

        .deal-card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            transition: all 0.3s ease;
        }

        .deal-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 35px 60px -15px rgba(0, 0, 0, 0.3);
        }

        .image-slider {
            position: relative;
            height: 320px;
            overflow: hidden;
            background: #f1f5f9;
        }

        .slides-container {
            display: flex;
            height: 100%;
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .slide {
            min-width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
            color: #64748b;
        }

        .image-placeholder i {
            font-size: 48px;
            margin-bottom: 12px;
        }

        .slider-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #374151;
            transition: all 0.2s;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            z-index: 10;
        }

        .slider-btn:hover {
            background: white;
            transform: translateY(-50%) scale(1.1);
        }

        .slider-btn.prev { left: 16px; }
        .slider-btn.next { right: 16px; }

        .slider-dots {
            position: absolute;
            bottom: 16px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
            z-index: 10;
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s;
        }

        .dot.active {
            background: white;
            transform: scale(1.2);
        }

        .discount-badge {
            position: absolute;
            top: 16px;
            left: 16px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 900;
            font-size: 14px;
            z-index: 10;
        }

        .stock-badge {
            position: absolute;
            top: 16px;
            right: 16px;
            background: #10b981;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            z-index: 10;
        }

        .stock-badge.out { background: #ef4444; }

        .price-container {
            display: flex;
            align-items: baseline;
            gap: 12px;
            margin: 16px 0;
        }

        .deal-price {
            font-size: 32px;
            font-weight: 900;
            color: #ea580c;
        }

        .original-price {
            font-size: 18px;
            color: #9ca3af;
            text-decoration: line-through;
        }

        .savings {
            background: #fef3c7;
            color: #92400e;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            display: inline-block;
            margin-bottom: 12px;
        }

        .countdown-box {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
            padding: 16px;
            border-radius: 16px;
            margin-top: 16px;
        }

        .countdown-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            opacity: 0.8;
            margin-bottom: 8px;
        }

        .countdown-time {
            font-size: 20px;
            font-weight: 900;
            font-family: 'Courier New', monospace;
        }

        .countdown-time.expired { color: #f87171; }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: white;
        }

        .empty-state i {
            font-size: 80px;
            margin-bottom: 24px;
            opacity: 0.5;
        }

        .page-header {
            text-align: center;
            padding: 60px 20px 40px;
            color: white;
        }

        .page-header h1 {
            font-size: 48px;
            font-weight: 900;
            margin-bottom: 16px;
            text-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .page-header p {
            font-size: 18px;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        @keyframes flame {
            0%, 100% { transform: scale(1) rotate(-2deg); }
            50% { transform: scale(1.1) rotate(2deg); }
        }

        .fire-icon {
            display: inline-block;
            animation: flame 1s ease-in-out infinite;
            color: #fbbf24;
        }

        .debug-panel {
            background: #fef3c7;
            border: 2px solid #f59e0b;
            padding: 20px;
            margin: 20px auto;
            max-width: 800px;
            border-radius: 12px;
            color: #92400e;
        }

        .cart-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            z-index: 1000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .cart-notification.show { transform: translateX(0); }

        .cart-notification.success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .cart-notification.error { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        .cart-notification a { color: white; text-decoration: underline; margin-left: 8px; }

        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            justify-content: center;
        }

        .qty-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 2px solid #e5e7eb;
            background: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .qty-btn:hover { border-color: #3b82f6; color: #3b82f6; }

        .qty-input {
            width: 60px;
            text-align: center;
            font-weight: 700;
            font-size: 18px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 6px;
        }

        /* ===== NOTIFICATION ===== */
        @keyframes slideInNotif {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .notif {
            position: fixed; top: 6rem; right: 2rem; padding: 1rem 1.5rem; border-radius: 0.75rem;
            font-weight: 600; z-index: 10000; box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            animation: slideInNotif 0.3s ease; font-size: 0.9rem;
        }
        .notif-success { background: #ecfdf5; color: #065f46; border: 1px solid #6ee7b7; }
        .notif-error { background: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }

        @keyframes badgeBounce { 0%,100% { transform: scale(1); } 50% { transform: scale(1.3); } }
        .badge-bounce { animation: badgeBounce 0.5s ease; }

        @media (max-width: 768px) {
            .page-header h1 { font-size: 32px; }
            .deal-price { font-size: 24px; }
            .image-slider { height: 250px; }
        }

        @media (min-width: 768px) {
            .features-grid { grid-template-columns: repeat(3, 1fr) !important; }
        }
        @media (min-width: 640px) {
            .footer-grid { grid-template-columns: repeat(2, 1fr) !important; }
        }
        @media (min-width: 1024px) {
            .footer-grid { grid-template-columns: repeat(4, 1fr) !important; }
            .lg-nav { display: flex !important; }
        }
    </style>
</head>

<body style="margin:0;">

    <!-- ===== HEADER ===== -->
    <header class="main-header" style="position:relative; z-index:100;">
        <div style="max-width:80rem; margin:0 auto; padding:0 1rem;">
            <div style="display:flex; align-items:center; justify-content:space-between; height:4.5rem;">
                <!-- Logo -->
                <a href="accueil.php" style="display:flex; align-items:center; text-decoration:none; flex-shrink:0;">
                    <img src="\le-stock\assets\img\le stock entreprise copy2.png" alt="LE-STOCK" class="logo-image"
                         style="filter:brightness(0) invert(1);"
                         onerror="this.src='https://via.placeholder.com/180x60/ffffff/2563eb?text=LE-STOCK'; this.style.filter='none'; this.style.background='#1d4ed8'; this.style.padding='8px'; this.style.borderRadius='8px';">
                </a>

                <!-- Nav Desktop -->
                <nav style="display:none; align-items:center; gap:2rem;" class="lg-nav">
                    <a href="../index.php" class="nav-link" style="text-decoration:none; font-size:0.9rem; font-weight:500;">Accueil</a>
                    <a href="promotion.php" class="nav-link" style="text-decoration:none; font-size:0.9rem; font-weight:500;">Promotions</a>
                    <a href="Affiliation" class="nav-link" style="text-decoration:none; font-size:0.9rem; font-weight:500;">Affiliations</a>
                </nav>

                <!-- Icons -->
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <a href="panier/Panier.php" class="icon-btn" style="padding:0.5rem; border-radius:50%; text-decoration:none; position:relative;" title="Panier">
                        <i class="fas fa-shopping-cart" style="font-size:1.15rem;"></i>
                        <span id="cart-badge" class="cart-badge" style="position:absolute; top:-2px; right:-2px; font-size:0.7rem; padding:0.1rem 0.4rem; border-radius:9999px;">0</span>
                    </a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="profile.php" class="icon-btn" style="padding:0.5rem; border-radius:50%; text-decoration:none;" title="Profil">
                            <i class="fas fa-user" style="font-size:1.15rem;"></i>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="icon-btn" style="padding:0.5rem; border-radius:50%; text-decoration:none;" title="Connexion">
                            <i class="fas fa-sign-in-alt" style="font-size:1.15rem;"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- ===== NOTIFICATION PANYE ===== -->
    <?php if ($cart_message): ?>
        <div id="cart-notification" class="cart-notification <?php echo $cart_success ? 'success' : 'error'; ?> show">
            <i class="fas <?php echo $cart_success ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>" style="margin-right:8px;"></i>
            <?php echo htmlspecialchars($cart_message); ?>
            <?php if ($cart_success): ?>
                <a href="panier/Panier.php"><i class="fas fa-shopping-cart"></i> Wè Panye a</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ===== PAGE HEADER ===== -->
    <div class="page-header">
        <h1>
            <span class="fire-icon"><i class="fas fa-fire"></i></span>
            Hot Deals
            <span class="fire-icon"><i class="fas fa-fire"></i></span>
        </h1>
        <p>Pwomosyon cho ki disponib kounye a. Profite anvan yo ekspire!</p>
    </div>

    <?php if ($showDebug): ?>
        <div class="debug-panel">
            <h3><i class="fas fa-bug"></i> DEBUG</h3>
            <p>Total Deals: <?= count($hot_deals) ?></p>
            <p>Base Path: <?= dirname(__DIR__) . '/uploads/hot_deals/' ?></p>
            <?php foreach ($hot_deals as $deal): ?>
                <hr style="margin:10px 0;">
                <p><strong><?= htmlspecialchars($deal['titre']) ?></strong> - <?= count($deal['images']) ?> imaj</p>
                <?php foreach ($deal['images'] as $img): ?>
                    <p><?= htmlspecialchars($img['image_name']) ?> → <?= $img['exists'] ? '✓ Jwenn' : '✗ Pa jwenn' ?> (Path: <?= htmlspecialchars($img['full_path']) ?>)</p>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ===== DEALS CONTAINER ===== -->
    <div class="container mx-auto px-4 pb-16 max-w-7xl">
        <?php if (empty($hot_deals)): ?>
            <div class="empty-state">
                <i class="fas fa-fire-extinguisher"></i>
                <h2 class="text-2xl font-bold mb-4">Pa gen Hot Deals pou kounye a</h2>
                <p>Tounen pita pou wè nouvo pwomosyon yo!</p>
                <a href="accueil.php" class="inline-block mt-8 px-8 py-3 bg-white text-purple-600 rounded-full font-bold hover:bg-gray-100 transition">
                    <i class="fas fa-arrow-left" style="margin-right:8px;"></i> Retounen nan akèy
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($hot_deals as $deal):
                    $discount = round((($deal['prix_original'] - $deal['prix_deal']) / $deal['prix_original']) * 100);
                    $savings = $deal['prix_original'] - $deal['prix_deal'];
                    $image_count = count($deal['images']);
                    $expired = $deal['date_fin'] && strtotime($deal['date_fin']) < time();
                    $out_of_stock = !$deal['en_stock'] || ($deal['quantite_limite'] !== null && $deal['quantite_limite'] <= 0);
                ?>
                    <div class="deal-card <?= $expired ? 'opacity-60' : '' ?>" id="deal-<?= $deal['id'] ?>">
                        <div class="image-slider" id="slider-<?= $deal['id'] ?>">
                            <div class="discount-badge">-<?= $discount ?>%</div>

                            <?php if ($out_of_stock): ?>
                                <div class="stock-badge out">Epuize</div>
                            <?php else: ?>
                                <div class="stock-badge">An Stock</div>
                            <?php endif; ?>

                            <?php if ($expired): ?>
                                <div style="position:absolute;inset:0;background:rgba(0,0,0,0.6);display:flex;align-items:center;justify-content:center;z-index:20;">
                                    <span style="background:#ef4444;color:#fff;padding:0.75rem 1.5rem;border-radius:9999px;font-weight:bold;font-size:1.25rem;transform:rotate(-12deg);">EXPIRE</span>
                                </div>
                            <?php endif; ?>

                            <div class="slides-container">
                                <?php if ($image_count === 0): ?>
                                    <div class="slide">
                                        <div class="image-placeholder">
                                            <i class="fas fa-image"></i>
                                            <span>Pa gen imaj</span>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($deal['images'] as $img): ?>
                                        <div class="slide">
                                            <?php if ($img['exists']): ?>
                                                <img src="<?= htmlspecialchars($img['full_path']) ?>"
                                                    alt="<?= htmlspecialchars($deal['titre']) ?>"
                                                    loading="lazy"
                                                    onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'image-placeholder\'><i class=\'fas fa-exclamation-triangle\'></i>Erè chajman</div>'">
                                            <?php else: ?>
                                                <div class="image-placeholder">
                                                    <i class="fas fa-question-circle"></i>
                                                    <span>Imaj pa jwenn</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <?php if ($image_count > 1): ?>
                                <button class="slider-btn prev" onclick="moveSlide(<?= $deal['id'] ?>, -1)">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <button class="slider-btn next" onclick="moveSlide(<?= $deal['id'] ?>, 1)">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                                <div class="slider-dots">
                                    <?php for ($i = 0; $i < $image_count; $i++): ?>
                                        <span class="dot <?= $i === 0 ? 'active' : '' ?>" onclick="goToSlide(<?= $deal['id'] ?>, <?= $i ?>)"></span>
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div style="padding:1.5rem;">
                            <h3 style="font-size:1.25rem;font-weight:bold;color:#111827;margin-bottom:0.5rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;"><?= htmlspecialchars($deal['titre']) ?></h3>
                            <p style="color:#6b7280;font-size:0.875rem;margin-bottom:1rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;"><?= htmlspecialchars($deal['description']) ?></p>

                            <div class="savings">
                                <i class="fas fa-piggy-bank" style="margin-right:4px;"></i> Ou ekonomize <?= number_format($savings) ?> HTG
                            </div>

                            <div class="price-container">
                                <span class="deal-price"><?= number_format($deal['prix_deal']) ?> HTG</span>
                                <span class="original-price"><?= number_format($deal['prix_original']) ?> HTG</span>
                            </div>

                            <?php if ($deal['date_fin']): ?>
                                <div class="countdown-box" data-end="<?= $deal['date_fin'] ?>">
                                    <div class="countdown-label"><i class="fas fa-clock" style="margin-right:4px;"></i> Ekspire nan</div>
                                    <div class="countdown-time" id="countdown-<?= $deal['id'] ?>">Calculating...</div>
                                </div>
                            <?php endif; ?>

                            <?php if (!$expired && !$out_of_stock): ?>
                                <form method="POST" action="" style="margin-top:1.5rem;">
                                    <input type="hidden" name="deal_id" value="<?= $deal['id'] ?>">
                                    <input type="hidden" name="add_to_cart" value="1">

                                    <div class="quantity-selector">
                                        <button type="button" class="qty-btn" onclick="changeQty(<?= $deal['id'] ?>, -1)">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" name="quantity" id="qty-<?= $deal['id'] ?>" value="1" min="1"
                                            max="<?= $deal['quantite_limite'] ?? 10 ?>" class="qty-input" readonly>
                                        <button type="button" class="qty-btn" onclick="changeQty(<?= $deal['id'] ?>, 1)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>

                                    <button type="submit" style="width:100%;padding:1rem;background:linear-gradient(to right,#f97316,#dc2626);color:#fff;border:none;border-radius:0.75rem;font-weight:bold;font-size:1.125rem;cursor:pointer;transition:all 0.2s;display:flex;align-items:center;justify-content:center;gap:0.5rem;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                                        <i class="fas fa-shopping-cart"></i>
                                        <span>Achte Kounye a</span>
                                    </button>
                                </form>
                            <?php else: ?>
                                <button disabled style="width:100%;margin-top:1.5rem;padding:1rem;background:#9ca3af;color:#fff;border:none;border-radius:0.75rem;font-weight:bold;font-size:1.125rem;cursor:not-allowed;opacity:0.5;">
                                    <i class="fas fa-times-circle" style="margin-right:8px;"></i>
                                    <?= $expired ? 'Ekspire' : 'Epuize' ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ===== FEATURES ===== -->
    <section class="features-section">
        <div style="max-width:80rem; margin:0 auto; padding:0 1rem;">
            <div style="display:grid; grid-template-columns:1fr; gap:1.25rem;" class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-cube"></i></div>
                    <div><h3>Livraison Gratuite</h3><p>Livraison gratuite pour les commandes de plus de 180 $</p></div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-credit-card"></i></div>
                    <div><h3>Paiement Flexible</h3><p>Plusieurs options de paiement sécurisé</p></div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-headset"></i></div>
                    <div><h3>Support 24×7</h3><p>Disponibles en ligne tous les jours</p></div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== FOOTER ===== -->
    <footer class="main-footer">
        <div style="max-width:80rem; margin:0 auto; padding:0 1rem;">
            <div style="display:grid; grid-template-columns:1fr; gap:2rem;" class="footer-grid">
                <div>
                    <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1rem;">
                        <div class="footer-logo-icon">L</div>
                        <span class="footer-logo-text">LE-STOCK.</span>
                    </div>
                    <p class="footer-desc">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                    <div style="display:flex; gap:0.65rem; margin-top:1.25rem;">
                        <a href="#" class="social-btn" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-btn" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-btn" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                        <a href="#" class="social-btn" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
                <div>
                    <h4>Entreprise</h4>
                    <div class="footer-links" style="display:flex; flex-direction:column; gap:0.25rem;">
                        <a href="#">À Propos</a><a href="#">Blog</a><a href="#">Contactez-nous</a><a href="#">Carrières</a>
                    </div>
                </div>
                <div>
                    <h4>Service Client</h4>
                    <div class="footer-links" style="display:flex; flex-direction:column; gap:0.25rem;">
                        <a href="#">Mon Compte</a><a href="#">Suivre ma Commande</a><a href="#">Retours</a><a href="#">FAQ</a>
                    </div>
                </div>
                <div>
                    <h4>Coordonnées</h4>
                    <div>
                        <div class="contact-item"><i class="fas fa-phone"></i><span>+0123-456-789</span></div>
                        <div class="contact-item"><i class="fas fa-envelope"></i><span>example@gmail.com</span></div>
                        <div class="contact-item"><i class="fas fa-map-marker-alt"></i><span>8502 Preston Rd.<br>Inglewood, Maine 98380</span></div>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p class="footer-copy">Copyright © 2024 LE-STOCK. Tous droits réservés.</p>
                <div style="display:flex; gap:0.75rem;">
                    <button class="footer-lang">Français <i class="fas fa-chevron-down" style="font-size:0.65rem;"></i></button>
                    <button class="footer-lang">HTG <i class="fas fa-chevron-down" style="font-size:0.65rem;"></i></button>
                </div>
            </div>
        </div>
    </footer>

    <!-- ===== JAVASCRIPT ===== -->
    <script>
        // ===== IMAGE SLIDER =====
        const sliders = {};

        function moveSlide(dealId, direction) {
            if (!sliders[dealId]) {
                sliders[dealId] = {
                    current: 0,
                    total: document.querySelectorAll(`#slider-${dealId} .slide`).length
                };
            }
            sliders[dealId].current += direction;
            if (sliders[dealId].current >= sliders[dealId].total) sliders[dealId].current = 0;
            if (sliders[dealId].current < 0) sliders[dealId].current = sliders[dealId].total - 1;
            updateSlider(dealId);
        }

        function goToSlide(dealId, index) {
            if (!sliders[dealId]) {
                sliders[dealId] = {
                    current: 0,
                    total: document.querySelectorAll(`#slider-${dealId} .slide`).length
                };
            }
            sliders[dealId].current = index;
            updateSlider(dealId);
        }

        function updateSlider(dealId) {
            const container = document.querySelector(`#slider-${dealId} .slides-container`);
            const dots = document.querySelectorAll(`#slider-${dealId} .dot`);
            if (container) container.style.transform = `translateX(-${sliders[dealId].current * 100}%)`;
            dots.forEach((dot, index) => dot.classList.toggle('active', index === sliders[dealId].current));
        }

        // Auto-slide
        setInterval(() => {
            document.querySelectorAll('.image-slider').forEach(slider => {
                const dealId = slider.id.replace('slider-', '');
                if (sliders[dealId] && sliders[dealId].total > 1) moveSlide(dealId, 1);
            });
        }, 5000);

        // Init sliders
        document.querySelectorAll('.image-slider').forEach(slider => {
            const dealId = slider.id.replace('slider-', '');
            sliders[dealId] = {
                current: 0,
                total: slider.querySelectorAll('.slide').length
            };
        });

        // ===== COUNTDOWN =====
        function updateCountdowns() {
            document.querySelectorAll('.countdown-box').forEach(box => {
                const endDate = new Date(box.dataset.end);
                const now = new Date();
                const diff = endDate - now;
                const display = box.querySelector('.countdown-time');

                if (diff <= 0) {
                    display.textContent = 'EKPIRE!';
                    display.classList.add('expired');
                    return;
                }

                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                display.textContent = days > 0
                    ? `${days}j ${hours}h ${minutes}m`
                    : `${String(hours).padStart(2,'0')}:${String(minutes).padStart(2,'0')}:${String(seconds).padStart(2,'0')}`;
            });
        }

        setInterval(updateCountdowns, 1000);
        updateCountdowns();

        // ===== QUANTITY =====
        function changeQty(dealId, change) {
            const input = document.getElementById('qty-' + dealId);
            let newVal = parseInt(input.value) + change;
            const max = parseInt(input.max) || 10;
            const min = parseInt(input.min) || 1;
            if (newVal >= min && newVal <= max) {
                input.value = newVal;
            }
        }

        // ===== CART BADGE =====
        function updateCartBadge() {
            fetch('panier/get_cart_count.php').then(r => r.json()).then(d => {
                const b = document.getElementById('cart-badge');
                if (b) {
                    const old = parseInt(b.textContent) || 0;
                    b.textContent = d.count || 0;
                    if (d.count !== old && old !== 0) {
                        b.classList.add('badge-bounce');
                        setTimeout(() => b.classList.remove('badge-bounce'), 500);
                    }
                }
            }).catch(e => console.error(e));
        }

        // Hide notification after 5 seconds
        setTimeout(() => {
            const notif = document.getElementById('cart-notification');
            if (notif) notif.classList.remove('show');
        }, 5000);

        // Init
        document.addEventListener('DOMContentLoaded', function() {
            updateCartBadge();
        });
    </script>

</body>

</html>