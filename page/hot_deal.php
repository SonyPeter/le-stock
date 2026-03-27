<?php
// Korige chemen pou jwenn baz done a
require_once dirname(__DIR__) . '/config/db.php';

// KORIJE: Chemen relatif pou itilize nan HTML - depi nan pages/ ale nan uploads/
function getImageFullPath($imageName)
{
    return '../uploads/hot_deals/' . $imageName;
}

function imageExists($imageName)
{
    // Chemen absoli pou verifye si fichye a egziste toutbon
    // __DIR__ = /mnt/kimi/upload/pages (kote hot_deals.php ye)
    // dirname(__DIR__) = /mnt/kimi/upload (rasin sit la)
    $basePath = dirname(__DIR__) . '/uploads/hot_deals/';
    $fullPath = $basePath . $imageName;

    return file_exists($fullPath) && is_file($fullPath);
}

// REKIPERE pwodwi ki asosye ak hot deals yo pou panye a
function getProductForDeal($deal, $pdo)
{
    // Eseye jwenn pwodwi a nan tab products la dapre non deal la
    $stmt = $pdo->prepare("SELECT id FROM products WHERE name LIKE ? LIMIT 1");
    $stmt->execute(['%' . $deal['titre'] . '%']);
    $product = $stmt->fetch();

    if ($product) {
        return $product['id'];
    }

    // Si pa jwenn, kreye yon pwodwi tanporè oswa retounen ID espesyal
    // Opsyon 2: Ajoute yon kolòn product_id nan tab hot_deals
    return null;
}

// REKIPERE pwodwi ID si li egziste nan URL (pou lyen direk)
$product_id_from_url = $_GET['product_id'] ?? null;

// AJOUTE NAN PANYE - si fòm soumèt
$cart_message = '';
$cart_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    session_start();

    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $deal_id = intval($_POST['deal_id']);
    $quantity = intval($_POST['quantity'] ?? 1);

    try {
        // Jwenn enfòmasyon deal la
        $stmt = $pdo->prepare("SELECT * FROM hot_deals WHERE id = ? AND en_stock = 1 AND (date_fin IS NULL OR date_fin > NOW())");
        $stmt->execute([$deal_id]);
        $deal = $stmt->fetch();

        if (!$deal) {
            $cart_message = 'Deal sa a pa disponib ankò!';
        } else {
            // Verifye si pwodwi a egziste nan tab products la
            $stmt = $pdo->prepare("SELECT id, stock_qty FROM products WHERE name = ? LIMIT 1");
            $stmt->execute([$deal['titre']]);
            $product = $stmt->fetch();

            $product_id = null;

            if ($product) {
                $product_id = $product['id'];
            } else {
                // Kreye pwodwi a nan tab products si li pa egziste
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

                // Ajoute imaj pwodwi a si genyen
                if (!empty($deal['images'])) {
                    $first_image = $deal['images'][0];
                    $stmt = $pdo->prepare("UPDATE products SET image = ? WHERE id = ?");
                    $stmt->execute([$first_image['image_name'], $product_id]);
                }
            }

            // Verifye si pwodwi a deja nan panier
            $stmt = $pdo->prepare("SELECT id, quantity FROM panier WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            $existing = $stmt->fetch();

            if ($existing) {
                // Mete ajou kantite a
                $new_qty = $existing['quantity'] + $quantity;
                $stmt = $pdo->prepare("UPDATE panier SET quantity = ? WHERE id = ?");
                $stmt->execute([$new_qty, $existing['id']]);
            } else {
                // Ajoute nouvo atik nan panier
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

// Rekipere Hot Deals yo
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

$showDebug = false; // Mete true pou debug
?>
<!DOCTYPE html>
<html lang="ht">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hot Deals | LE-STOCK</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
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

        .slider-btn.prev {
            left: 16px;
        }

        .slider-btn.next {
            right: 16px;
        }

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

        .stock-badge.out {
            background: #ef4444;
        }

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

        .countdown-time.expired {
            color: #f87171;
        }

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

            0%,
            100% {
                transform: scale(1) rotate(-2deg);
            }

            50% {
                transform: scale(1.1) rotate(2deg);
            }
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

        /* NOUVO: Styles pou mesaj panye a */
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

        .cart-notification.show {
            transform: translateX(0);
        }

        .cart-notification.success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .cart-notification.error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .cart-notification a {
            color: white;
            text-decoration: underline;
            margin-left: 8px;
        }

        /* NOUVO: Styles pou chwazi kantite */
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

        .qty-btn:hover {
            border-color: #3b82f6;
            color: #3b82f6;
        }

        .qty-input {
            width: 60px;
            text-align: center;
            font-weight: 700;
            font-size: 18px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 6px;
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 32px;
            }

            .deal-price {
                font-size: 24px;
            }

            .image-slider {
                height: 250px;
            }
        }
    </style>
</head>

<body>

    <!-- NOUVO: Notifikasyon Panye -->
    <?php if ($cart_message): ?>
        <div id="cart-notification" class="cart-notification <?php echo $cart_success ? 'success' : 'error'; ?> show">
            <i class="fas <?php echo $cart_success ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
            <?php echo htmlspecialchars($cart_message); ?>
            <?php if ($cart_success): ?>
                <a href="panier/panier.php"><i class="fas fa-shopping-cart"></i> Wè Panye a</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

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

    <div class="container mx-auto px-4 pb-16 max-w-7xl">
        <?php if (empty($hot_deals)): ?>
            <div class="empty-state">
                <i class="fas fa-fire-extinguisher"></i>
                <h2 class="text-2xl font-bold mb-4">Pa gen Hot Deals pou kounye a</h2>
                <p>Tounen pita pou wè nouvo pwomosyon yo!</p>
                <a href="index.php" class="inline-block mt-8 px-8 py-3 bg-white text-purple-600 rounded-full font-bold hover:bg-gray-100 transition">
                    <i class="fas fa-arrow-left mr-2"></i> Retounen nan akèy
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
                                <div class="absolute inset-0 bg-black bg-opacity-60 flex items-center justify-center z-20">
                                    <span class="bg-red-500 text-white px-6 py-3 rounded-full font-bold text-xl transform -rotate-12">EXPIRE</span>
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

                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-2 line-clamp-2"><?= htmlspecialchars($deal['titre']) ?></h3>
                            <p class="text-gray-600 text-sm mb-4 line-clamp-2"><?= htmlspecialchars($deal['description']) ?></p>

                            <div class="savings">
                                <i class="fas fa-piggy-bank mr-1"></i> Ou ekonomize <?= number_format($savings) ?> HTG
                            </div>

                            <div class="price-container">
                                <span class="deal-price"><?= number_format($deal['prix_deal']) ?> HTG</span>
                                <span class="original-price"><?= number_format($deal['prix_original']) ?> HTG</span>
                            </div>

                            <?php if ($deal['date_fin']): ?>
                                <div class="countdown-box" data-end="<?= $deal['date_fin'] ?>">
                                    <div class="countdown-label"><i class="fas fa-clock mr-1"></i> Ekspire nan</div>
                                    <div class="countdown-time" id="countdown-<?= $deal['id'] ?>">Calculating...</div>
                                </div>
                            <?php endif; ?>

                            <!-- NOUVO: Fòm pou ajoute nan panye a -->
                            <?php if (!$expired && !$out_of_stock): ?>
                                <form method="POST" action="" class="mt-6">
                                    <input type="hidden" name="deal_id" value="<?= $deal['id'] ?>">
                                    <input type="hidden" name="add_to_cart" value="1">

                                    <!-- Chwazi kantite -->
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

                                    <button type="submit" class="w-full py-4 bg-gradient-to-r from-orange-500 to-red-600 text-white rounded-xl font-bold text-lg hover:from-orange-600 hover:to-red-700 transition-all transform hover:scale-[1.02] active:scale-[0.98] shadow-lg flex items-center justify-center gap-2">
                                        <i class="fas fa-shopping-cart"></i>
                                        <span>Achte Kounye a</span>
                                    </button>
                                </form>
                            <?php else: ?>
                                <button disabled class="w-full mt-6 py-4 bg-gray-400 text-white rounded-xl font-bold text-lg cursor-not-allowed opacity-50">
                                    <i class="fas fa-times-circle mr-2"></i>
                                    <?= $expired ? 'Ekspire' : 'Epuize' ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
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

        setInterval(() => {
            document.querySelectorAll('.image-slider').forEach(slider => {
                const dealId = slider.id.replace('slider-', '');
                if (sliders[dealId] && sliders[dealId].total > 1) moveSlide(dealId, 1);
            });
        }, 5000);

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

                display.textContent = days > 0 ? `${days}j ${hours}h ${minutes}m` : `${String(hours).padStart(2,'0')}:${String(minutes).padStart(2,'0')}:${String(seconds).padStart(2,'0')}`;
            });
        }

        setInterval(updateCountdowns, 1000);
        updateCountdowns();

        document.querySelectorAll('.image-slider').forEach(slider => {
            const dealId = slider.id.replace('slider-', '');
            sliders[dealId] = {
                current: 0,
                total: slider.querySelectorAll('.slide').length
            };
        });

        // NOUVO: Fonksyon pou chanje kantite a
        function changeQty(dealId, change) {
            const input = document.getElementById('qty-' + dealId);
            let newVal = parseInt(input.value) + change;
            const max = parseInt(input.max) || 10;
            const min = parseInt(input.min) || 1;

            if (newVal >= min && newVal <= max) {
                input.value = newVal;
            }
        }

        // NOUVO: Kache notifikasyon apre 5 segond
        setTimeout(() => {
            const notif = document.getElementById('cart-notification');
            if (notif) {
                notif.classList.remove('show');
            }
        }, 5000);
    </script>

</body>

</html>