<?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';

// Rekipere pwodwi an promosyon organize pa kategori
$promo_by_category = [];

try {
    // Pran tout kategori ki gen pwodwi an promosyon
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
    $categories_with_promo = $stmt->fetchAll();

    // Pou chak kategori, pran pwodwi an promosyon yo
    foreach ($categories_with_promo as $cat) {
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE p.category_id = ? 
            AND p.price_promo IS NOT NULL 
            AND p.price_promo > 0 
            AND p.price_promo < p.price
            AND p.status = 'disponible'
            ORDER BY p.id DESC
        ");
        $stmt->execute([$cat['id']]);
        $products = $stmt->fetchAll();

        if (count($products) > 0) {
            $promo_by_category[] = [
                'category' => $cat,
                'products' => $products
            ];
        }
    }

    // Pran tout pwodwi an promosyon (pou yon seksyon "Tout Promosyon" anlè)
    $all_promos = $pdo->query("
        SELECT p.*, c.name as category_name 
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.price_promo IS NOT NULL 
        AND p.price_promo > 0 
        AND p.price_promo < p.price
        AND p.status = 'disponible'
        ORDER BY p.id DESC
        LIMIT 8
    ")->fetchAll();
} catch (PDOException $e) {
    die("Erè: " . $e->getMessage());
}

// Fonksyon pou kalkule pousantaj rabè
function getDiscountPercent($price, $price_promo)
{
    return round((1 - $price_promo / $price) * 100);
}

// Fonksyon pou kalkule ekonomi
function getSavings($price, $price_promo)
{
    return $price - $price_promo;
}
?>

<!DOCTYPE html>
<html lang="ht">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promosyon - LE-STOCK</title>
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

        .nav-links {
            display: flex;
            gap: 2rem;
        }

        .nav-links a {
            color: #64748b;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: #3b82f6;
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

        /* Hero Section Promosyon */
        .promo-hero {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            padding: 4rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .promo-hero::before {
            content: '🎉';
            position: absolute;
            font-size: 20rem;
            opacity: 0.1;
            top: -5rem;
            right: -5rem;
            transform: rotate(15deg);
        }

        .promo-hero-content {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .promo-hero h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-transform: uppercase;
        }

        .promo-hero p {
            font-size: 1.25rem;
            opacity: 0.9;
        }

        .countdown {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 2rem;
        }

        .countdown-item {
            background: rgba(255, 255, 255, 0.2);
            padding: 1rem 2rem;
            border-radius: 1rem;
            text-align: center;
        }

        .countdown-item .number {
            font-size: 2.5rem;
            font-weight: 800;
            display: block;
        }

        .countdown-item .label {
            font-size: 0.875rem;
            text-transform: uppercase;
            opacity: 0.8;
        }

        /* Seksyon Kategori Promosyon */
        .category-promo-section {
            margin: 4rem 0;
        }

        .section-header {
            max-width: 1400px;
            margin: 0 auto 2rem;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-title {
            font-size: 1.875rem;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            width: 48px;
            height: 48px;
            background: #fee2e2;
            color: #dc2626;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .view-all {
            color: #3b82f6;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
            border: 2px solid transparent;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            border-color: #fecaca;
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

        .discount-badge {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: #dc2626;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 800;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
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
            background: #dc2626;
            color: white;
        }

        .product-info {
            padding: 1.25rem;
        }

        .product-category {
            font-size: 0.75rem;
            color: #dc2626;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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

        .price-container {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.75rem;
        }

        .price-promo {
            font-size: 1.5rem;
            font-weight: 800;
            color: #dc2626;
        }

        .price-old {
            font-size: 1rem;
            color: #94a3b8;
            text-decoration: line-through;
        }

        .savings {
            background: #dcfce7;
            color: #166534;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #f1f5f9;
        }

        .stock-info {
            font-size: 0.875rem;
            color: #64748b;
        }

        .stock-info.low {
            color: #dc2626;
            font-weight: 600;
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
            background: #dc2626;
        }

        /* Empty State */
        .empty-promo {
            text-align: center;
            padding: 5rem 2rem;
            color: #64748b;
        }

        .empty-promo i {
            font-size: 5rem;
            color: #cbd5e1;
            margin-bottom: 1.5rem;
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
            .promo-hero h1 {
                font-size: 2rem;
            }

            .countdown {
                gap: 1rem;
            }

            .countdown-item {
                padding: 0.75rem 1rem;
            }

            .countdown-item .number {
                font-size: 1.5rem;
            }

            .nav-links {
                display: none;
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

            <nav class="nav-links">
                <a href="accueil.php">Akèy</a>
                <a href="categories.php">Kategori</a>
                <a href="promotions.php" class="active">Promosyon</a>
                <a href="nouveautes.php">Nouvèlte</a>
            </nav>

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

    <!-- Hero Section Promosyon -->
    <section class="promo-hero">
        <div class="promo-hero-content">
            <h1>🎉 Gwo Promosyon!</h1>
            <p>Ekonomize jiska 50% sou pwodwi seleksyone nou yo. Ofri sa yo pa dire lontan!</p>

            <div class="countdown">
                <div class="countdown-item">
                    <span class="number" id="days">02</span>
                    <span class="label">Jou</span>
                </div>
                <div class="countdown-item">
                    <span class="number" id="hours">14</span>
                    <span class="label">Èdtan</span>
                </div>
                <div class="countdown-item">
                    <span class="number" id="minutes">35</span>
                    <span class="label">Minit</span>
                </div>
                <div class="countdown-item">
                    <span class="number" id="seconds">48</span>
                    <span class="label">Segonn</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Kontni Prensipal -->
    <main>
        <?php if (count($promo_by_category) === 0): ?>
            <div class="empty-promo">
                <i class="fas fa-percentage"></i>
                <h2 style="font-size: 1.875rem; font-weight: 700; margin-bottom: 0.5rem;">Pa gen promosyon pou kounye a</h2>
                <p>Tounen pita pou wè nouvo ofri nou yo!</p>
                <a href="accueil.php" style="display: inline-block; margin-top: 1.5rem; background: #3b82f6; color: white; padding: 0.875rem 2rem; border-radius: 0.75rem; font-weight: 600; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Retounen nan Akèy
                </a>
            </div>
        <?php else: ?>

            <!-- Seksyon "Tout Promosyon" (premye 8 yo) -->
            <?php if (count($all_promos) > 0): ?>
                <section class="category-promo-section" style="margin-top: 3rem;">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-fire"></i>
                            Pi Gwo Rabè
                        </h2>
                        <span style="color: #64748b;"><?= count($all_promos) ?> pwodwi</span>
                    </div>

                    <div class="products-container">
                        <div class="products-grid">
                            <?php foreach ($all_promos as $pr):
                                $discount = getDiscountPercent($pr['price'], $pr['price_promo']);
                                $savings = getSavings($pr['price'], $pr['price_promo']);
                            ?>
                                <div class="product-card">
                                    <div class="product-image">
                                        <img src="../uploads/products/<?= $pr['image'] ?>" alt="<?= htmlspecialchars($pr['name']) ?>" loading="lazy" onerror="this.src='../assets/img/placeholder.png'">
                                        <span class="discount-badge">-<?= $discount ?>%</span>
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
                                        <div class="product-category">
                                            <i class="fas fa-tag"></i> <?= htmlspecialchars($pr['category_name']) ?>
                                        </div>
                                        <h3 class="product-name"><?= htmlspecialchars($pr['name']) ?></h3>

                                        <div class="price-container">
                                            <span class="price-promo"><?= number_format($pr['price_promo']) ?> HTG</span>
                                            <span class="price-old"><?= number_format($pr['price']) ?> HTG</span>
                                        </div>

                                        <div class="savings">
                                            <i class="fas fa-piggy-bank"></i> Ou ekonomize <?= number_format($savings) ?> HTG
                                        </div>

                                        <div class="product-footer">
                                            <span class="stock-info <?= $pr['stock_qty'] < 5 ? 'low' : '' ?>">
                                                <i class="fas fa-box"></i> <?= $pr['stock_qty'] ?> rete
                                            </span>
                                            <button class="add-to-cart" onclick="addToCart(<?= $pr['id'] ?>)">
                                                <i class="fas fa-cart-plus"></i> Ajoute
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Chak Kategori ak Promosyon Li -->
            <?php foreach ($promo_by_category as $index => $cat_data): ?>
                <section class="category-promo-section">
                    <div class="section-header">
                        <h2 class="section-title" style="font-size: 1.5rem;">
                            <i class="fas fa-<?= getCategoryIcon($cat_data['category']['name']) ?>" style="width: 40px; height: 40px; font-size: 1.25rem;"></i>
                            <?= htmlspecialchars($cat_data['category']['name']) ?> an Promosyon
                        </h2>
                        <a href="categorie.php?id=<?= $cat_data['category']['id'] ?>" class="view-all">
                            Wè tout <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>

                    <div class="products-container">
                        <div class="products-grid">
                            <?php
                            // Limite a 4 pwodwi pa kategori sou paj sa a
                            $limited_products = array_slice($cat_data['products'], 0, 4);
                            foreach ($limited_products as $pr):
                                $discount = getDiscountPercent($pr['price'], $pr['price_promo']);
                                $savings = getSavings($pr['price'], $pr['price_promo']);
                            ?>
                                <div class="product-card">
                                    <div class="product-image">
                                        <img src="../uploads/products/<?= $pr['image'] ?>" alt="<?= htmlspecialchars($pr['name']) ?>" loading="lazy" onerror="this.src='../assets/img/placeholder.png'">
                                        <span class="discount-badge">-<?= $discount ?>%</span>
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
                                        <div class="product-category">
                                            <i class="fas fa-tag"></i> <?= htmlspecialchars($pr['category_name']) ?>
                                        </div>
                                        <h3 class="product-name"><?= htmlspecialchars($pr['name']) ?></h3>

                                        <div class="price-container">
                                            <span class="price-promo"><?= number_format($pr['price_promo']) ?> HTG</span>
                                            <span class="price-old"><?= number_format($pr['price']) ?> HTG</span>
                                        </div>

                                        <div class="savings">
                                            <i class="fas fa-piggy-bank"></i> Ekonomize <?= number_format($savings) ?> HTG
                                        </div>

                                        <div class="product-footer">
                                            <span class="stock-info <?= $pr['stock_qty'] < 5 ? 'low' : '' ?>">
                                                <i class="fas fa-box"></i> <?= $pr['stock_qty'] ?> rete
                                            </span>
                                            <button class="add-to-cart" onclick="addToCart(<?= $pr['id'] ?>)">
                                                <i class="fas fa-cart-plus"></i> Ajoute
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (count($cat_data['products']) > 4): ?>
                            <div style="text-align: center; margin-top: 1.5rem;">
                                <a href="categorie.php?id=<?= $cat_data['category']['id'] ?>&filter=promo" style="display: inline-block; background: white; color: #dc2626; padding: 0.75rem 2rem; border-radius: 0.75rem; font-weight: 600; text-decoration: none; border: 2px solid #fecaca;">
                                    Wè tout <?= count($cat_data['products']) ?> pwodwi an promosyon <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endforeach; ?>

        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>LE-STOCK</h4>
                <p style="color: #94a3b8; line-height: 1.6;">Pi bon platfòm pou achte ak vann pwodwi nan peyi a.</p>
            </div>
            <div class="footer-section">
                <h4>Kategori Popilè</h4>
                <?php foreach (array_slice($promo_by_category, 0, 4) as $cat): ?>
                    <a href="categorie.php?id=<?= $cat['category']['id'] ?>">
                        <?= htmlspecialchars($cat['category']['name']) ?> (<?= count($cat['products']) ?>)
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="footer-section">
                <h4>Èd</h4>
                <a href="#">Kijan pou kòmande</a>
                <a href="#">Livrezon</a>
                <a href="#">Retounen pwodwi</a>
                <a href="#">Kontakte nou</a>
            </div>
            <div class="footer-section">
                <h4>Swiv Nou</h4>
                <div style="display: flex; gap: 1rem;">
                    <a href="#"><i class="fab fa-facebook fa-lg"></i></a>
                    <a href="#"><i class="fab fa-instagram fa-lg"></i></a>
                    <a href="#"><i class="fab fa-twitter fa-lg"></i></a>
                    <a href="#"><i class="fab fa-whatsapp fa-lg"></i></a>
                </div>
            </div>
        </div>
        <div style="max-width: 1400px; margin: 2rem auto 0; padding-top: 2rem; border-top: 1px solid #1e293b; text-align: center; color: #64748b;">
            <p>&copy; <?= date('Y') ?> LE-STOCK. Tout dwa rezève.</p>
        </div>
    </footer>

    <!-- Quick View Modal -->
    <div id="quickViewModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 2rem;">
        <div style="background: white; border-radius: 1rem; max-width: 800px; width: 100%; max-height: 90vh; overflow-y: auto; position: relative;">
            <button onclick="closeQuickView()" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #64748b;">
                <i class="fas fa-times"></i>
            </button>
            <div id="quickViewContent" style="padding: 2rem;"></div>
        </div>
    </div>

    <script>
        // Kontdown (egzanp senp)
        function updateCountdown() {
            const endDate = new Date();
            endDate.setDate(endDate.getDate() + 2); // 2 jou ankò

            setInterval(() => {
                const now = new Date();
                const diff = endDate - now;

                if (diff > 0) {
                    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                    document.getElementById('days').textContent = String(days).padStart(2, '0');
                    document.getElementById('hours').textContent = String(hours).padStart(2, '0');
                    document.getElementById('minutes').textContent = String(minutes).padStart(2, '0');
                    document.getElementById('seconds').textContent = String(seconds).padStart(2, '0');
                }
            }, 1000);
        }
        updateCountdown();

        // Fonksyon pou ajoute nan panier
        function addToCart(productId) {
            fetch('add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'product_id=' + productId + '&qty=1'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Pwodwi ajoute nan panier!', 'success');
                    } else {
                        showNotification(data.message || 'Erè, eseye ankò.', 'error');
                    }
                });
        }

        function addToFavorites(productId) {
            fetch('add_to_favorites.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'product_id=' + productId
                })
                .then(response => response.json())
                .then(data => {
                    showNotification(data.success ? 'Ajoute nan favori!' : 'Ou dwe konekte.', data.success ? 'success' : 'error');
                });
        }

        function quickView(productId) {
            const modal = document.getElementById('quickViewModal');
            const content = document.getElementById('quickViewContent');

            fetch('get_product.php?id=' + productId)
                .then(response => response.json())
                .then(data => {
                    const discount = Math.round((1 - data.price_promo / data.price) * 100);
                    content.innerHTML = `
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                            <img src="../uploads/products/${data.image}" style="width: 100%; border-radius: 0.75rem;" onerror="this.src='../assets/img/placeholder.png'">
                            <div>
                                <span style="background: #dc2626; color: white; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700;">-${discount}%</span>
                                <h2 style="font-size: 1.5rem; font-weight: 700; margin: 1rem 0;">${data.name}</h2>
                                <p style="color: #64748b; margin-bottom: 1rem;">${data.description || 'Pa gen deskripsyon.'}</p>
                                <div style="margin-bottom: 1.5rem;">
                                    <span style="font-size: 2rem; font-weight: 800; color: #dc2626;">${new Intl.NumberFormat().format(data.price_promo)} HTG</span>
                                    <span style="text-decoration: line-through; color: #94a3b8; margin-left: 1rem;">${new Intl.NumberFormat().format(data.price)} HTG</span>
                                </div>
                                <button onclick="addToCart(${data.id}); closeQuickView();" style="width: 100%; background: #dc2626; color: white; padding: 1rem; border-radius: 0.75rem; font-weight: 700; border: none; cursor: pointer;">
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

        function showNotification(message, type) {
            const notif = document.createElement('div');
            notif.style.cssText = `
                position: fixed; top: 100px; right: 2rem; padding: 1rem 1.5rem;
                border-radius: 0.75rem; font-weight: 600; z-index: 10000;
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

        window.onclick = function(event) {
            const modal = document.getElementById('quickViewModal');
            if (event.target === modal) closeQuickView();
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
        'default' => 'percent'
    ];

    $name = strtolower($categoryName);
    return $icons[$name] ?? $icons['default'];
}
?>