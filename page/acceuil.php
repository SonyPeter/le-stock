<?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';
// require_once dirname(__DIR__) . '/includes/header.php';

// Rekipere tout kategori ak pwodwi yo
$categories_with_products = [];

try {
    // Pran tout kategori yo
    $cats = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

    foreach ($cats as $cat) {
        // Pran pwodwi ki nan kategori sa a (pa category_id)
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE p.category_id = ? AND p.status = 'disponible' 
            ORDER BY p.id DESC 
            LIMIT 8
        ");
        $stmt->execute([$cat['id']]);
        $products = $stmt->fetchAll();

        // Si gen pwodwi nan kategori sa a, ajoute l nan tablo a
        if (count($products) > 0) {
            $categories_with_products[] = [
                'category' => $cat,
                'products' => $products
            ];
        }
    }

    // Pran pwodwi an promosyon (pou yon seksyon espesyal)
    $promo_products = $pdo->query("
        SELECT p.*, c.name as category_name 
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.price_promo IS NOT NULL 
        AND p.price_promo > 0 
        AND p.price_promo < p.price 
        AND p.status = 'disponible' 
        ORDER BY p.id DESC 
        LIMIT 4
    ")->fetchAll();
} catch (PDOException $e) {
    die("Erè: " . $e->getMessage());
}

// Fonksyon pou tronke tèks
function truncate($text, $length = 50)
{
    if (strlen($text) > $length) {
        return substr($text, 0, $length) . '...';
    }
    return $text;
}

// Fonksyon pou verifye si pwodwi an promosyon
function isPromo($product)
{
    return !empty($product['price_promo']) && $product['price_promo'] > 0 && $product['price_promo'] < $product['price'];
}

// Fonksyon pou kalkule pousantaj rabè
function getDiscountPercent($product)
{
    if (isPromo($product)) {
        return round((1 - $product['price_promo'] / $product['price']) * 100);
    }
    return 0;
}
?>

<!DOCTYPE html>
<html lang="ht">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LE-STOCK - Akèy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }

        /* Header */
        .header {
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f172a;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo span {
            color: #3b82f6;
        }

        .search-bar {
            flex: 1;
            max-width: 500px;
            margin: 0 2rem;
            position: relative;
        }

        .search-bar input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 2px solid #e2e8f0;
            border-radius: 9999px;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .search-bar input:focus {
            outline: none;
            border-color: #3b82f6;
        }

        .search-bar i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .header-btn {
            position: relative;
            color: #64748b;
            text-decoration: none;
            font-size: 1.25rem;
            transition: color 0.2s;
        }

        .header-btn:hover {
            color: #3b82f6;
        }

        .header-btn .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ef4444;
            color: white;
            font-size: 0.625rem;
            font-weight: 700;
            padding: 0.125rem 0.375rem;
            border-radius: 9999px;
        }

        /* Hero Section ak Promosyon */
        .hero-section {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            padding: 3rem 2rem;
            margin-bottom: 3rem;
        }

        .hero-content {
            max-width: 1400px;
            margin: 0 auto;
        }

        .hero-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }

        .hero-subtitle {
            font-size: 1.125rem;
            color: #94a3b8;
            margin-bottom: 2rem;
        }

        /* Grid Pwodwi */
        .products-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .product-card {
            background: white;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .product-image {
            height: 220px;
            position: relative;
            overflow: hidden;
            background: #f1f5f9;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .product-badges {
            position: absolute;
            top: 1rem;
            left: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .badge-promo {
            background: #ef4444;
            color: white;
            padding: 0.375rem 0.875rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .badge-new {
            background: #10b981;
            color: white;
            padding: 0.375rem 0.875rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .product-actions {
            position: absolute;
            top: 1rem;
            right: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            opacity: 0;
            transform: translateX(10px);
            transition: all 0.3s;
        }

        .product-card:hover .product-actions {
            opacity: 1;
            transform: translateX(0);
        }

        .action-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: none;
            color: #64748b;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.2s;
        }

        .action-btn:hover {
            background: #3b82f6;
            color: white;
        }

        .product-info {
            padding: 1.25rem;
        }

        .product-category {
            font-size: 0.75rem;
            color: #3b82f6;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .product-name {
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.75rem;
            line-height: 1.4;
            height: 2.8em;
            overflow: hidden;
        }

        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .product-price {
            display: flex;
            flex-direction: column;
        }

        .price-current {
            font-size: 1.25rem;
            font-weight: 800;
            color: #0f172a;
        }

        .price-current.promo {
            color: #ef4444;
        }

        .price-old {
            font-size: 0.875rem;
            color: #94a3b8;
            text-decoration: line-through;
        }

        .add-to-cart {
            background: #0f172a;
            color: white;
            border: none;
            padding: 0.75rem 1.25rem;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .add-to-cart:hover {
            background: #3b82f6;
        }

        /* Seksyon Kategori */
        .category-section {
            margin-bottom: 4rem;
        }

        .category-header {
            max-width: 1400px;
            margin: 0 auto 1.5rem;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .category-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .category-title i {
            width: 40px;
            height: 40px;
            background: #dbeafe;
            color: #3b82f6;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .category-count {
            color: #64748b;
            font-size: 0.875rem;
        }

        .view-all {
            color: #3b82f6;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .view-all:hover {
            color: #2563eb;
        }

        /* Empty State */
        .empty-category {
            text-align: center;
            padding: 3rem;
            color: #64748b;
        }

        /* Footer */
        .footer {
            background: #0f172a;
            color: white;
            padding: 3rem 2rem;
            margin-top: 4rem;
        }

        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
        }

        .footer-section h4 {
            font-weight: 700;
            margin-bottom: 1rem;
            text-transform: uppercase;
        }

        .footer-section a {
            color: #94a3b8;
            text-decoration: none;
            display: block;
            margin-bottom: 0.5rem;
            transition: color 0.2s;
        }

        .footer-section a:hover {
            color: white;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            }

            .footer-content {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .header-content {
                flex-wrap: wrap;
                gap: 1rem;
            }

            .search-bar {
                order: 3;
                max-width: 100%;
                margin: 0;
                width: 100%;
            }

            .hero-title {
                font-size: 1.875rem;
            }

            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }

            .footer-content {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="accueil.php" class="logo">
                <i class="fas fa-bolt" style="color: #3b82f6;"></i>
                LE-<span>STOCK</span>
            </a>
            <a href="../index.php" class="text-gray-700 hover:text-blue-600 font-medium transition relative group">
                Retour
                <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-blue-600 transition-all group-hover:w-full"></span>
            </a>

            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Chache pwodwi, kategori...">
            </div>

            <div class="header-actions">
                <a href="favoris.php" class="header-btn" title="Favori">
                    <i class="fas fa-heart"></i>
                </a>
                <a href="panier.php" class="header-btn" title="Panier">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="badge">0</span>
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="profile.php" class="header-btn" title="Pwofil">
                        <i class="fas fa-user"></i>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="header-btn" title="Konekte">
                        <i class="fas fa-sign-in-alt"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Hero Section ak Promosyon -->
    <?php if (count($promo_products) > 0): ?>
        <section class="hero-section">
            <div class="hero-content">
                <h1 class="hero-title">🎉 Super Promosyon!</h1>
                <p class="hero-subtitle">Pwodwi seleksyone ak rabè ekskluzif. Pwofite kounye a!</p>

                <div class="products-grid" style="margin-top: 2rem;">
                    <?php foreach ($promo_products as $pr): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="../uploads/products/<?= $pr['image'] ?>" alt="<?= htmlspecialchars($pr['name']) ?>" loading="lazy" onerror="this.src='../assets/img/placeholder.png'">
                                <div class="product-badges">
                                    <span class="badge-promo">-<?= getDiscountPercent($pr) ?>%</span>
                                </div>
                                <div class="product-actions">
                                    <button class="action-btn" onclick="addToFavorites(<?= $pr['id'] ?>)" title="Ajoute nan favori">
                                        <i class="fas fa-heart"></i>
                                    </button>
                                    <button class="action-btn" onclick="quickView(<?= $pr['id'] ?>)" title="Gade rapid">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="product-info">
                                <div class="product-category"><?= htmlspecialchars($pr['category_name']) ?></div>
                                <h3 class="product-name"><?= htmlspecialchars($pr['name']) ?></h3>
                                <div class="product-footer">
                                    <div class="product-price">
                                        <span class="price-current promo"><?= number_format($pr['price_promo']) ?> HTG</span>
                                        <span class="price-old"><?= number_format($pr['price']) ?> HTG</span>
                                    </div>
                                    <button class="add-to-cart" onclick="addToCart(<?= $pr['id'] ?>)">
                                        <i class="fas fa-cart-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Seksyon Kategori ak Pwodwi -->
    <main>
        <?php if (count($categories_with_products) === 0): ?>
            <div class="empty-category" style="padding: 5rem 2rem;">
                <i class="fas fa-box-open" style="font-size: 4rem; color: #cbd5e1; margin-bottom: 1.5rem;"></i>
                <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem;">Pa gen pwodwi disponib</h2>
                <p style="color: #64748b;">Tann yon ti moman, pwodwi yo pral disponib byento!</p>
            </div>
        <?php else: ?>
            <?php foreach ($categories_with_products as $index => $cat_data): ?>
                <section class="category-section" style="<?= $index > 0 ? 'margin-top: 3rem;' : '' ?>">
                    <div class="category-header">
                        <h2 class="category-title">
                            <i class="fas fa-<?= getCategoryIcon($cat_data['category']['name']) ?>"></i>
                            <?= htmlspecialchars($cat_data['category']['name']) ?>
                        </h2>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <span class="category-count"><?= count($cat_data['products']) ?> pwodwi</span>
                            <a href="categorie.php?id=<?= $cat_data['category']['id'] ?>" class="view-all">
                                Wè tout <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>

                    <div class="products-container">
                        <div class="products-grid">
                            <?php foreach ($cat_data['products'] as $pr): ?>
                                <?php $is_promo = isPromo($pr); ?>
                                <div class="product-card">
                                    <div class="product-image">
                                        <img src="../uploads/products/<?= $pr['image'] ?>" alt="<?= htmlspecialchars($pr['name']) ?>" loading="lazy" onerror="this.src='../assets/img/placeholder.png'">

                                        <?php if ($is_promo): ?>
                                            <div class="product-badges">
                                                <span class="badge-promo">-<?= getDiscountPercent($pr) ?>%</span>
                                            </div>
                                        <?php elseif (strtotime($pr['created_at']) > strtotime('-7 days')): ?>
                                            <div class="product-badges">
                                                <span class="badge-new">NOUVO</span>
                                            </div>
                                        <?php endif; ?>

                                        <div class="product-actions">
                                            <button class="action-btn" onclick="addToFavorites(<?= $pr['id'] ?>)" title="Ajoute nan favori">
                                                <i class="fas fa-heart"></i>
                                            </button>
                                            <button class="action-btn" onclick="quickView(<?= $pr['id'] ?>)" title="Gade rapid">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="product-info">
                                        <div class="product-category"><?= htmlspecialchars($pr['category_name']) ?></div>
                                        <h3 class="product-name"><?= htmlspecialchars(truncate($pr['name'], 40)) ?></h3>
                                        <div class="product-footer">
                                            <div class="product-price">
                                                <?php if ($is_promo): ?>
                                                    <span class="price-current promo"><?= number_format($pr['price_promo']) ?> HTG</span>
                                                    <span class="price-old"><?= number_format($pr['price']) ?> HTG</span>
                                                <?php else: ?>
                                                    <span class="price-current"><?= number_format($pr['price']) ?> HTG</span>
                                                <?php endif; ?>
                                            </div>
                                            <button class="add-to-cart" onclick="addToCart(<?= $pr['id'] ?>)">
                                                <i class="fas fa-cart-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>



    <!-- Quick View Modal -->
    <div id="quickViewModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 2rem;">
        <div style="background: white; border-radius: 1rem; max-width: 800px; width: 100%; max-height: 90vh; overflow-y: auto; position: relative;">
            <button onclick="closeQuickView()" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #64748b;">
                <i class="fas fa-times"></i>
            </button>
            <div id="quickViewContent" style="padding: 2rem;">
                <!-- Kontni pral chaje dinamikman -->
            </div>
        </div>
    </div>



    <script>
        // Fonksyon pou ajoute nan panier
        function addToCart(productId) {
            fetch('add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'product_id=' + productId + '&qty=1'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const badge = document.querySelector('.fa-shopping-cart + .badge');
                        if (badge) {
                            badge.textContent = parseInt(badge.textContent) + 1;
                        }
                        showNotification('Pwodwi ajoute nan panier!', 'success');
                    } else {
                        showNotification(data.message || 'Erè, eseye ankò.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Erè rezo, eseye ankò.', 'error');
                });
        }

        // Fonksyon pou ajoute nan favori
        function addToFavorites(productId) {
            fetch('add_to_favorites.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'product_id=' + productId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Ajoute nan favori!', 'success');
                    } else {
                        showNotification(data.message || 'Ou dwe konekte anvan.', 'error');
                    }
                });
        }

        // Fonksyon pou gade rapid
        function quickView(productId) {
            const modal = document.getElementById('quickViewModal');
            const content = document.getElementById('quickViewContent');

            fetch('get_product.php?id=' + productId)
                .then(response => response.json())
                .then(data => {
                    const isPromo = data.price_promo && data.price_promo > 0 && data.price_promo < data.price;
                    content.innerHTML = `
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                            <img src="../uploads/products/${data.image}" style="width: 100%; border-radius: 0.75rem;" onerror="this.src='../assets/img/placeholder.png'">
                            <div>
                                <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem;">${data.name}</h2>
                                <p style="color: #3b82f6; font-weight: 600; margin-bottom: 1rem;">${data.category_name}</p>
                                <p style="color: #64748b; margin-bottom: 1.5rem; line-height: 1.6;">${data.description || 'Pa gen deskripsyon.'}</p>
                                <div style="margin-bottom: 1.5rem;">
                                    ${isPromo ? `
                                        <span style="font-size: 1.5rem; font-weight: 800; color: #ef4444;">${new Intl.NumberFormat().format(data.price_promo)} HTG</span>
                                        <span style="text-decoration: line-through; color: #94a3b8; margin-left: 0.5rem;">${new Intl.NumberFormat().format(data.price)} HTG</span>
                                    ` : `
                                        <span style="font-size: 1.5rem; font-weight: 800; color: #0f172a;">${new Intl.NumberFormat().format(data.price)} HTG</span>
                                    `}
                                </div>
                                <button onclick="addToCart(${data.id}); closeQuickView();" class="add-to-cart" style="width: 100%; justify-content: center;">
                                    <i class="fas fa-cart-plus"></i> Ajoute nan Panier
                                </button>
                            </div>
                        </div>
                    `;
                    modal.style.display = 'flex';
                });
        }

        function closeQuickView() {
            document.getElementById('quickViewModal').style.display = 'none';
        }

        // Fonksyon notifikasyon
        function showNotification(message, type) {
            const notif = document.createElement('div');
            notif.style.cssText = `
                position: fixed;
                top: 100px;
                right: 2rem;
                padding: 1rem 1.5rem;
                border-radius: 0.75rem;
                font-weight: 600;
                z-index: 10000;
                animation: slideIn 0.3s ease;
                ${type === 'success' ? 'background: #dcfce7; color: #166534;' : 'background: #fee2e2; color: #991b1b;'}
            `;
            notif.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i> ${message}`;
            document.body.appendChild(notif);

            setTimeout(() => {
                notif.style.opacity = '0';
                setTimeout(() => notif.remove(), 300);
            }, 3000);
        }

        // Fèmen modal lè klike deyò
        window.onclick = function(event) {
            const modal = document.getElementById('quickViewModal');
            if (event.target === modal) {
                closeQuickView();
            }
        }
    </script>

    <style>
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
    </style>
</body>

</html>

<?php
require_once dirname(__DIR__) . '/includes/footer.php';
// Fonksyon pou jwenn ikon kategori a
function getCategoryIcon($categoryName)
{
    $icons = [
        'telefon' => 'mobile-alt',
        'telephone' => 'mobile-alt',
        'phone' => 'mobile-alt',
        'odinatè' => 'laptop',
        'ordinateur' => 'laptop',
        'computer' => 'laptop',
        'vetman' => 'tshirt',
        'habillement' => 'tshirt',
        'clothes' => 'tshirt',
        'manje' => 'utensils',
        'nourriture' => 'utensils',
        'food' => 'utensils',
        'kay' => 'home',
        'maison' => 'home',
        'home' => 'home',
        'machin' => 'car',
        'voiture' => 'car',
        'car' => 'car',
        'bote' => 'shoe-prints',
        'chaussure' => 'shoe-prints',
        'shoes' => 'shoe-prints',
        'mont' => 'clock',
        'montre' => 'clock',
        'watch' => 'clock',
        'sak' => 'shopping-bag',
        'sac' => 'shopping-bag',
        'bag' => 'shopping-bag',
        'bijou' => 'gem',
        'bijoux' => 'gem',
        'jewelry' => 'gem',
        'elektwonik' => 'plug',
        'electronique' => 'plug',
        'electronics' => 'plug',
        'jwèt' => 'gamepad',
        'jouet' => 'gamepad',
        'toy' => 'gamepad',
        'espò' => 'futbol',
        'sport' => 'futbol',
        'sports' => 'futbol',
        'beaute' => 'spray-can',
        'beauté' => 'spray-can',
        'beauty' => 'spray-can',
        'sante' => 'heartbeat',
        'santé' => 'heartbeat',
        'health' => 'heartbeat',
        'liv' => 'book',
        'livre' => 'book',
        'book' => 'book',
        'mizik' => 'music',
        'musique' => 'music',
        'music' => 'music',
        'default' => 'box'
    ];

    $name = strtolower($categoryName);
    return $icons[$name] ?? $icons['default'];
}
?>