<?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';

// Vérification de l'ID du produit
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: galeri.php');
    exit;
}

$product_id = intval($_GET['id']);

try {
    // Récupération dynamique des données du produit depuis la BDD
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.id = ? AND p.status = 'disponible'
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        header('Location: galeri.php');
        exit;
    }

    // Vérifier dynamiquement quelles colonnes existent dans la table 'products'
    $columns = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);

    $has_color = in_array('color', $columns);
    $has_size = in_array('size', $columns);
    $has_brand = in_array('brand', $columns);
    $has_quantity = in_array('quantity', $columns);

    // --- LOGIQUE DE DISPONIBILITÉ ---
    $current_stock = $has_quantity ? (int)($product['quantity'] ?? 0) : -1;
    $is_available = ($current_stock === -1) || ($current_stock > 0);

    // Récupération des images supplémentaires depuis la BDD
    $product_images = [];
    try {
        $img_stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY display_order ASC");
        $img_stmt->execute([$product_id]);
        $product_images = $img_stmt->fetchAll();
    } catch (PDOException $e) {
    }

    // Récupération des avis depuis la BDD
    $reviews = [];
    $avg_rating = 0;
    $total_reviews = 0;
    try {
        $review_stmt = $pdo->prepare("
            SELECT r.*, u.name as user_name, u.avatar 
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.product_id = ? AND r.status = 'approved'
            ORDER BY r.created_at DESC
        ");
        $review_stmt->execute([$product_id]);
        $reviews = $review_stmt->fetchAll();
        $total_reviews = count($reviews);

        if ($total_reviews > 0) {
            $total_stars = 0;
            foreach ($reviews as $review) {
                $total_stars += $review['rating'];
            }
            $avg_rating = round($total_stars / $total_reviews, 1);
        }
    } catch (PDOException $e) {
    }

    // RÉCUPÉRATION DES PRODUITS SIMILAIRES DEPUIS LA BDD
    $similar_products = [];
    $sim_stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.category_id = ? AND p.id != ? AND p.status = 'disponible'
        ORDER BY RAND()
        LIMIT 4
    ");
    $sim_stmt->execute([$product['category_id'], $product_id]);
    $similar_products = $sim_stmt->fetchAll();

    // Vérification si l'utilisateur peut laisser un avis
    $can_review = false;
    $has_reviewed = false;
    if (isset($_SESSION['user_id'])) {
        try {
            $order_stmt = $pdo->prepare("
                SELECT COUNT(*) as count FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'completed'
            ");
            $order_stmt->execute([$_SESSION['user_id'], $product_id]);
            if ($order_stmt->fetch()['count'] > 0) $can_review = true;

            $existing_review = $pdo->prepare("SELECT id FROM reviews WHERE product_id = ? AND user_id = ?");
            $existing_review->execute([$product_id, $_SESSION['user_id']]);
            if ($existing_review->fetch()) {
                $has_reviewed = true;
                $can_review = false;
            }
        } catch (PDOException $e) {
        }
    }
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

// Fonctions utilitaires
function truncate($text, $length = 50)
{
    return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
}
function isPromo($product)
{
    return !empty($product['price_promo']) && $product['price_promo'] > 0 && $product['price_promo'] < $product['price'];
}
function getDiscountPercent($product)
{
    return isPromo($product) ? round((1 - $product['price_promo'] / $product['price']) * 100) : 0;
}

function renderStars($rating, $size = 'text-sm')
{
    $html = '<div class="flex items-center gap-1">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) $html .= '<i class="fas fa-star text-yellow-400 ' . $size . '"></i>';
        elseif ($i - 0.5 <= $rating) $html .= '<i class="fas fa-star-half-alt text-yellow-400 ' . $size . '"></i>';
        else $html .= '<i class="far fa-star text-gray-300 ' . $size . '"></i>';
    }
    return $html . '</div>';
}
function formatDate($date)
{
    return date('d M Y', strtotime($date));
}
?>

<!DOCTYPE html>
<html lang="ht">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - LE-STOCK</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #fafaf9 0%, #f5f5f0 50%, #e7e5e4 100%);
            color: #1f2937;
            line-height: 1.6;
        }

        .main-header {
            background: linear-gradient(135deg, #c14802ff 0%, #c14802ff 100%);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo-container img {
            max-height: 60px;
            width: auto;
            filter: brightness(0) invert(1);
        }

        .nav-links {
            display: flex;
            gap: 2rem;
        }

        .nav-links a {
            color: #ffac91ff;
            text-decoration: none;
            font-weight: 500;
            transition: 0.3s;
        }

        .nav-links a:hover {
            color: #fff;
        }

        .header-icons {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .icon-btn {
            color: #fed7aa;
            font-size: 1.25rem;
            padding: 0.5rem;
            border-radius: 50%;
            transition: 0.3s;
            position: relative;
        }

        .icon-btn:hover {
            color: #fff;
            background: rgba(251, 146, 60, 0.2);
        }

        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ea580c;
            color: white;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.1rem 0.4rem;
            border-radius: 9999px;
        }

        .breadcrumb {
            background: #fff;
            padding: 1rem 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .breadcrumb-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .breadcrumb a {
            color: #6b7280;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            color: #c2410c;
        }

        .breadcrumb span {
            color: #9ca3af;
            margin: 0 0.5rem;
        }

        .breadcrumb .current {
            color: #1f2937;
            font-weight: 500;
        }

        .main-content {
            max-width: 1280px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }

        .product-section {
            background: #fff;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .product-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }

        @media (min-width: 1024px) {
            .product-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .image-gallery {
            position: relative;
        }

        .main-image {
            width: 100%;
            height: 400px;
            background: #f3f4f6;
            border-radius: 0.75rem;
            overflow: hidden;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .main-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.3s;
        }

        .main-image:hover img {
            transform: scale(1.05);
        }

        .thumbnail-list {
            display: flex;
            gap: 0.75rem;
            overflow-x: auto;
        }

        .thumbnail {
            width: 80px;
            height: 80px;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            cursor: pointer;
            flex-shrink: 0;
            overflow: hidden;
        }

        .thumbnail.active,
        .thumbnail:hover {
            border-color: #c2410c;
        }

        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .product-category {
            color: #c2410c;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .product-title {
            font-size: 2rem;
            font-weight: 800;
            color: #111827;
            line-height: 1.2;
        }

        .product-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .rating-count {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .stock-status {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .in-stock {
            background: #d1fae5;
            color: #065f46;
        }

        .out-stock {
            background: #fee2e2;
            color: #991b1b;
        }

        .product-price {
            display: flex;
            align-items: baseline;
            gap: 1rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            border-radius: 0.75rem;
            border: 2px solid #fed7aa;
        }

        .current-price {
            font-size: 2.5rem;
            font-weight: 800;
            color: #c2410c;
        }

        .original-price {
            font-size: 1.5rem;
            color: #9ca3af;
            text-decoration: line-through;
        }

        .discount-badge {
            background: #dc2626;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 0.375rem;
            font-weight: 700;
            font-size: 0.875rem;
        }

        .product-description {
            color: #4b5563;
            line-height: 1.8;
        }

        .product-attributes {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 0.5rem;
        }

        .attribute-label {
            font-size: 0.75rem;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
        }

        .attribute-value {
            font-weight: 600;
            color: #1f2937;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-primary {
            flex: 1;
            min-width: 200px;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #c2410c 0%, #9a3412 100%);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(194, 65, 12, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(194, 65, 12, 0.4);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            padding: 1rem 1.5rem;
            background: #fff;
            color: #c2410c;
            border: 2px solid #c2410c;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-secondary:hover {
            background: #fff7ed;
        }

        .reviews-section,
        .similar-section {
            background: #fff;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        @media (min-width: 1024px) {
            .products-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .product-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            overflow: hidden;
            transition: 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            border-color: #c2410c;
        }

        .card-image {
            width: 100%;
            height: 200px;
            background: #f3f4f6;
            overflow: hidden;
        }

        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: 0.3s;
        }

        .product-card:hover .card-image img {
            transform: scale(1.05);
        }

        .card-content {
            padding: 1rem;
        }

        .card-category {
            font-size: 0.75rem;
            color: #c2410c;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0.25rem;
        }

        .card-title {
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-current-price {
            font-size: 1.25rem;
            font-weight: 800;
            color: #c2410c;
        }

        .card-original-price {
            font-size: 0.875rem;
            color: #9ca3af;
            text-decoration: line-through;
            margin-left: 0.5rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }

        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #f59e0b;
        }

        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .notification {
            position: fixed;
            top: 100px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            z-index: 10000;
            animation: slideIn 0.3s ease;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
        }

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

        .notification.success {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #10b981;
        }

        .notification.error {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #ef4444;
        }

        .main-footer {
            background: linear-gradient(135deg, #431407 0%, #7c2d12 50%, #9a3412 100%);
            color: #fed7aa;
            padding: 3rem 0 1.5rem;
            margin-top: 3rem;
            text-align: center;
        }

        @media (max-width: 768px) {
            .product-title {
                font-size: 1.5rem;
            }

            .current-price {
                font-size: 1.75rem;
            }

            .main-image {
                height: 300px;
            }

            .nav-links {
                display: none;
            }
        }
    </style>
</head>

<body>

    <header class="main-header">
        <div class="header-container">
            <a href="accueil.php" class="logo-container">
                <img src="\le-stock\assets\img\le stock entreprise copy2.png" alt="LE-STOCK" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <span style="display: none; color: white; font-weight: 800; font-size: 1.5rem;">LE-STOCK</span>
            </a>
            <nav class="nav-links">
                <a href="../index.php">Akèy</a>
                <a href="galeri.php">Galeri</a>
                <a href="promotion.php">Promosyons</a>
                <a href="hot_deal.php">Hot-Deal</a>
            </nav>
            <div class="header-icons">
                <a href="panier/Panier.php" class="icon-btn" title="Panier">
                    <i class="fas fa-shopping-cart"></i>
                    <span id="cart-badge" class="cart-badge">0</span>
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="profile.php" class="icon-btn"><i class="fas fa-user"></i></a>
                <?php else: ?>
                    <a href="login.php" class="icon-btn"><i class="fas fa-sign-in-alt"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- <div class="breadcrumb">
        <div class="breadcrumb-container">
            <a href="../index.php">Akèy</a><span>/</span>
            <a href="galeri.php">Galeri</a><span>/</span>
            <a href="galeri.php?category=<?= urlencode($product['category_name']) ?>"><?= htmlspecialchars($product['category_name']) ?></a><span>/</span>
            <span class="current"><?= htmlspecialchars(truncate($product['name'], 30)) ?></span>
        </div>
    </div> -->

    <main class="main-content">
        <section class="product-section">
            <div class="product-grid">

                <div class="image-gallery">
                    <div class="main-image">
                        <img src="../uploads/products/<?= htmlspecialchars($product['image'] ?? 'placeholder.png') ?>" alt="<?= htmlspecialchars($product['name']) ?>" id="mainImage" onerror="this.src='../assets/img/placeholder.png'">
                    </div>
                    <?php if (!empty($product_images)): ?>
                        <div class="thumbnail-list">
                            <div class="thumbnail active" onclick="changeImage(this, '../uploads/products/<?= htmlspecialchars($product['image']) ?>')">
                                <img src="../uploads/products/<?= htmlspecialchars($product['image']) ?>" alt="Principal">
                            </div>
                            <?php foreach ($product_images as $img): ?>
                                <div class="thumbnail" onclick="changeImage(this, '../uploads/products/<?= htmlspecialchars($img['image_path']) ?>')">
                                    <img src="../uploads/products/<?= htmlspecialchars($img['image_path']) ?>" alt="Vignette">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="product-info">
                    <div class="product-category"><?= htmlspecialchars($product['category_name']) ?></div>
                    <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>

                    <div class="product-meta">
                        <div style="display:flex; align-items:center; gap:0.5rem;">
                            <?= renderStars($avg_rating) ?>
                            <span class="rating-count">(<?= $total_reviews ?> revizyon)</span>
                        </div>
                        <span class="stock-status <?= $is_available ? 'in-stock' : 'out-stock' ?>">
                            <?= $is_available ? ($has_quantity ? 'Disponib (' . $current_stock . ' nan stok)' : 'Disponib') : 'Epiize' ?>
                        </span>
                    </div>

                    <div class="product-price">
                        <?php if (isPromo($product)): ?>
                            <span class="current-price"><?= number_format($product['price_promo']) ?> HTG</span>
                            <span class="original-price"><?= number_format($product['price']) ?> HTG</span>
                            <span class="discount-badge">-<?= getDiscountPercent($product) ?>%</span>
                        <?php else: ?>
                            <span class="current-price"><?= number_format($product['price']) ?> HTG</span>
                        <?php endif; ?>
                    </div>

                    <div class="product-description">
                        <?= nl2br(htmlspecialchars($product['description'] ?? 'Pa gen deskripsyon disponib.')) ?>
                    </div>

                    <?php if ($has_color || $has_size || $has_brand): ?>
                        <div class="product-attributes">
                            <?php if ($has_color && !empty($product['color'])): ?>
                                <div><span class="attribute-label">Koulè</span><span class="attribute-value"><?= htmlspecialchars($product['color']) ?></span></div>
                            <?php endif; ?>
                            <?php if ($has_size && !empty($product['size'])): ?>
                                <div><span class="attribute-label">Gwosè</span><span class="attribute-value"><?= htmlspecialchars($product['size']) ?></span></div>
                            <?php endif; ?>
                            <?php if ($has_brand && !empty($product['brand'])): ?>
                                <div><span class="attribute-label">Mak</span><span class="attribute-value"><?= htmlspecialchars($product['brand']) ?></span></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($is_available): ?>
                        <!-- LA PARTIE POUR AJOUTER LA QUANTITÉ A ÉTÉ RETIRÉE ICI -->
                        <div class="action-buttons">
                            <button onclick="addToCart(<?= $product['id'] ?>, this)" class="btn-primary" id="btnAddToCart">
                                <i class="fas fa-cart-plus"></i>
                                <span>Ajoute nan Panier</span>
                            </button>
                            <button onclick="addToFavorites(<?= $product['id'] ?>)" class="btn-secondary" id="btnFav">
                                <i class="far fa-heart"></i>
                                <span>Favori</span>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-circle"></i> Pwodwi sa pa disponib kounye a (Ruptè de stok).
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- SECTION DES PRODUITS SIMILAIRES (DYNAMIQUE DEPUIS LA BASE DE DONNÉES) -->
        <?php if (!empty($similar_products)): ?>
            <section class="similar-section">
                <h2 class="section-title">
                    <i class="fas fa-th-large" style="color: #c2410c;"></i>
                    Pwodwi Ki Sanble
                </h2>
                <div class="products-grid">
                    <?php foreach ($similar_products as $sim): ?>
                        <a href="product-view.php?id=<?= $sim['id'] ?>" class="product-card">
                            <div class="card-image">
                                <img src="../uploads/products/<?= htmlspecialchars($sim['image'] ?? 'placeholder.png') ?>"
                                    alt="<?= htmlspecialchars($sim['name']) ?>"
                                    onerror="this.src='../assets/img/placeholder.png'">
                            </div>
                            <div class="card-content">
                                <div class="card-category"><?= htmlspecialchars($sim['category_name']) ?></div>
                                <h3 class="card-title"><?= htmlspecialchars(truncate($sim['name'], 40)) ?></h3>
                                <div>
                                    <?php if (isPromo($sim)): ?>
                                        <span class="card-current-price"><?= number_format($sim['price_promo']) ?> HTG</span>
                                        <span class="card-original-price"><?= number_format($sim['price']) ?> HTG</span>
                                    <?php else: ?>
                                        <span class="card-current-price"><?= number_format($sim['price']) ?> HTG</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
        <!-- FIN SECTION PRODUITS SIMILAIRES -->

    </main>

    <footer class="main-footer">
        <p style="color: #fb923c;">&copy; <?= date('Y') ?> LE-STOCK. Tout Dwa Rezève.</p>
    </footer>

    <script>
        function changeImage(thumbnail, src) {
            document.getElementById('mainImage').src = src;
            document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
            thumbnail.classList.add('active');
        }

        // Ajout au panier SANS sélection de quantité (envoie 1 par défaut)
        function addToCart(productId, button) {
            const originalContent = button.innerHTML;

            button.disabled = true;
            button.innerHTML = '<span class="spinner"></span> Ajout...';

            fetch('panier/add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `product_id=${productId}&qty=1` // Quantité fixée à 1 automatiquement
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateCartBadge();
                        showNotification('Pwodwi ajoute nan panier avèk siksè!', 'success');
                    } else {
                        showNotification(data.message || 'Erè, eseye ankò.', 'error');
                    }
                })
                .catch(error => {
                    showNotification('Erè rezo, verifye koneksyon ou a.', 'error');
                })
                .finally(() => {
                    button.disabled = false;
                    button.innerHTML = originalContent;
                });
        }

        function addToFavorites(productId) {
            const btn = document.getElementById('btnFav');
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<span class="spinner" style="border-top-color:#c2410c; border-color:rgba(194,65,12,0.3);"></span>';

            fetch('add_to_favorites.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `product_id=${productId}`
                })
                .then(response => response.json())
                .then(data => {
                    showNotification(data.success ? 'Ajoute nan favori!' : (data.message || 'Ou dwe konekte anvan.'), data.success ? 'success' : 'error');
                })
                .catch(() => showNotification('Erè rezo.', 'error'))
                .finally(() => {
                    btn.innerHTML = originalContent;
                });
        }

        function updateCartBadge() {
            fetch('panier/get_cart_count.php')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('cart-badge');
                    if (badge) badge.textContent = data.count || 0;
                });
        }

        function showNotification(message, type) {
            const existing = document.querySelector('.notification');
            if (existing) existing.remove();

            const notif = document.createElement('div');
            notif.className = `notification ${type}`;
            notif.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i> ${message}`;
            document.body.appendChild(notif);

            setTimeout(() => {
                notif.style.opacity = '0';
                notif.style.transform = 'translateX(100%)';
                setTimeout(() => notif.remove(), 300);
            }, 3000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateCartBadge();
        });
    </script>
</body>

</html>