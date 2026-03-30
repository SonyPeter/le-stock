<?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';

 $categories_with_products = [];
 $all_products = [];

try {
    $cats = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

    foreach ($cats as $cat) {
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

        if (count($products) > 0) {
            $categories_with_products[] = [
                'category' => $cat,
                'products' => $products
            ];
        }
    }

    $all_products = $pdo->query("
        SELECT p.*, c.name as category_name 
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.status = 'disponible'
        ORDER BY p.id DESC
    ")->fetchAll();

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

    $all_categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

    $columns = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
    $has_color = in_array('color', $columns);
    $has_size = in_array('size', $columns);
    $has_brand = in_array('brand', $columns);

    $all_colors = [];
    $all_sizes = [];
    $all_brands = [];
    
    if ($has_color) {
        $all_colors = $pdo->query("SELECT DISTINCT color FROM products WHERE color IS NOT NULL AND color != ''")->fetchAll(PDO::FETCH_COLUMN);
    }
    if ($has_size) {
        $all_sizes = $pdo->query("SELECT DISTINCT size FROM products WHERE size IS NOT NULL AND size != ''")->fetchAll(PDO::FETCH_COLUMN);
    }
    if ($has_brand) {
        $all_brands = $pdo->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != ''")->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (PDOException $e) {
    die("Erè: " . $e->getMessage());
}

function truncate($text, $length = 50) {
    if (strlen($text) > $length) {
        return substr($text, 0, $length) . '...';
    }
    return $text;
}

function isPromo($product) {
    return !empty($product['price_promo']) && $product['price_promo'] > 0 && $product['price_promo'] < $product['price'];
}

function getDiscountPercent($product) {
    if (isPromo($product)) {
        return round((1 - $product['price_promo'] / $product['price']) * 100);
    }
    return 0;
}

 $selected_category = isset($_GET['category']) ? $_GET['category'] : 'All';
 $selected_color = isset($_GET['color']) ? $_GET['color'] : 'All';
 $selected_size = isset($_GET['size']) ? $_GET['size'] : 'All';
 $selected_brand = isset($_GET['brand']) ? $_GET['brand'] : 'All';
 $price_min = isset($_GET['price_min']) ? floatval($_GET['price_min']) : 0;
 $price_max = isset($_GET['price_max']) ? floatval($_GET['price_max']) : 999999;
 $sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'featured';
 $view_mode = isset($_GET['view']) ? $_GET['view'] : 'grid';
 $search_query = isset($_GET['search']) ? $_GET['search'] : '';
 $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
 $items_per_page = 12;

 $filtered_products = array_filter($all_products, function ($product) use ($selected_category, $selected_color, $selected_size, $selected_brand, $price_min, $price_max, $search_query, $has_color, $has_size, $has_brand) {
    $match_category = $selected_category === 'All' || $product['category_name'] === $selected_category;
    $match_color = !$has_color || $selected_color === 'All' || (isset($product['color']) && $product['color'] === $selected_color);
    $match_size = !$has_size || $selected_size === 'All' || (isset($product['size']) && $product['size'] === $selected_size);
    $match_brand = !$has_brand || $selected_brand === 'All' || (isset($product['brand']) && $product['brand'] === $selected_brand);
    $match_price = $product['price'] >= $price_min && $product['price'] <= $price_max;
    $match_search = $search_query === '' || 
        stripos($product['name'], $search_query) !== false ||
        (isset($product['description']) && stripos($product['description'], $search_query) !== false);
    
    return $match_category && $match_color && $match_size && $match_brand && $match_price && $match_search;
});

usort($filtered_products, function ($a, $b) use ($sort_by) {
    switch ($sort_by) {
        case 'price-low': return $a['price'] - $b['price'];
        case 'price-high': return $b['price'] - $a['price'];
        case 'name-asc': return strcmp($a['name'], $b['name']);
        case 'name-desc': return strcmp($b['name'], $a['name']);
        case 'rating': return (($b['rating'] ?? 0) - ($a['rating'] ?? 0));
        case 'newest': return strtotime($b['created_at'] ?? 'now') - strtotime($a['created_at'] ?? 'now');
        default: return 0;
    }
});

 $total_products = count($filtered_products);
 $total_pages = ceil($total_products / $items_per_page);
 $start_index = ($page - 1) * $items_per_page;
 $current_products = array_slice($filtered_products, $start_index, $items_per_page);

 $hero_videos = [
    [
        'src' => '\le-stock\assets\video\copy.mp4',
        'poster' => '\le-stock\assets\img\ten.jpg',
        'title' => 'Nouvo Koleksyon',
        'subtitle' => 'Dekouvri sa ki nouvo'
    ],
    [
        'src' => '\le-stock\assets\video\lv_0_20260315230933.mp4',
        'poster' => '../assets/videos/poster2.jpg',
        'title' => 'Pwodwi Popilè',
        'subtitle' => 'Pi bon chwa kliyan yo'
    ],
    [
        'src' => '\le-stock\assets\video\copy3.mp4',
        'poster' => '../assets/videos/poster3.jpg',
        'title' => 'Promosyòn Espesyal',
        'subtitle' => 'Rabè jiska 50%'
    ]
];
?>

<!DOCTYPE html>
<html lang="ht">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LE-STOCK - Galeri Pwodwi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="\le-stock\css\style.css">
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

        /* ===== CAROUSEL ===== */
        .video-carousel { position: relative; width: 100%; height: 450px; overflow: hidden; background: #1e293b; }
        @media (min-width: 768px) { .video-carousel { height: 520px; } }
        @media (min-width: 1024px) { .video-carousel { height: 580px; } }

        .video-slide { position: absolute; inset: 0; opacity: 0; transition: opacity 0.8s; z-index: 1; }
        .video-slide.active { opacity: 1; z-index: 2; }
        .video-slide video { width: 100%; height: 100%; object-fit: cover; }

        .video-overlay {
            position: absolute; inset: 0;
            background: linear-gradient(to bottom, rgba(15,23,42,0.25), rgba(15,23,42,0.75));
            display: flex; align-items: center; justify-content: center; z-index: 3;
        }
        .video-content { text-align: center; color: #fff; padding: 1.5rem; max-width: 800px; animation: fadeInUp 0.8s ease; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .glass-title {
            font-size: 2.2rem; font-weight: 800; margin-bottom: 1rem;
            background: rgba(37, 99, 235, 0.35); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
            border-radius: 16px; padding: 0.9rem 2rem;
            border: 1px solid rgba(96, 165, 250, 0.3);
            box-shadow: 0 8px 32px rgba(37, 99, 235, 0.25);
            display: inline-block; color: #fff; text-shadow: 2px 2px 6px rgba(0,0,0,0.7);
        }
        @media (min-width: 768px) { .glass-title { font-size: 3.2rem; padding: 1.2rem 2.5rem; } }
        @media (min-width: 1024px) { .glass-title { font-size: 3.8rem; padding: 1.5rem 3rem; } }

        .glass-subtitle {
            font-size: 1.15rem; margin-bottom: 0.75rem;
            background: rgba(16, 185, 129, 0.35); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
            border-radius: 12px; padding: 0.6rem 1.5rem;
            border: 1px solid rgba(16, 185, 129, 0.3);
            box-shadow: 0 6px 24px rgba(16, 185, 129, 0.2);
            display: inline-block; color: #fff; font-weight: 500; text-shadow: 1px 1px 3px rgba(0,0,0,0.6);
        }

        .glass-caption {
            font-size: 0.95rem;
            background: rgba(168, 85, 247, 0.35); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
            border-radius: 10px; padding: 0.5rem 1.2rem;
            border: 1px solid rgba(168, 85, 247, 0.3);
            box-shadow: 0 4px 20px rgba(168, 85, 247, 0.2);
            display: inline-block; color: #e0e7ff; font-weight: 400; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }

        .carousel-arrow {
            position: absolute; top: 50%; transform: translateY(-50%);
            width: 48px; height: 48px;
            background: rgba(37, 99, 235, 0.85); border: 2px solid rgba(96, 165, 250, 0.4);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            color: #fff; cursor: pointer; transition: all 0.3s; z-index: 10; font-size: 1.1rem;
        }
        .carousel-arrow:hover { background: rgba(29, 78, 216, 0.95); border-color: #60a5fa; transform: translateY(-50%) scale(1.1); }
        .carousel-arrow.prev { left: 16px; }
        .carousel-arrow.next { right: 16px; }
        @media (max-width: 640px) { .carousel-arrow { width: 38px; height: 38px; font-size: 0.9rem; } .carousel-arrow.prev { left: 8px; } .carousel-arrow.next { right: 8px; } }

        .carousel-dot {
            width: 11px; height: 11px; border-radius: 50%;
            background: rgba(255,255,255,0.4); cursor: pointer; transition: all 0.3s; border: 2px solid transparent;
        }
        .carousel-dot:hover { background: rgba(255,255,255,0.7); transform: scale(1.2); }
        .carousel-dot.active { background: #2563eb; border-color: #fff; transform: scale(1.3); box-shadow: 0 0 8px rgba(37,99,235,0.6); }

        .carousel-progress { position: absolute; bottom: 0; left: 0; height: 3px; background: #2563eb; z-index: 10; }

        /* ===== SEARCH BAR ===== */
        .search-section { background: #f8fafc; border-bottom: 1px solid #bfdbfe; }
        .search-input {
            width: 100%; padding: 0.75rem 1rem 0.75rem 3rem;
            border: 1px solid #bfdbfe; border-radius: 0.5rem;
            background: #eff6ff; color: #0f172a; outline: none; transition: all 0.3s;
        }
        .search-input::placeholder { color: #3b82f6; }
        .search-input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.15); }
        .btn-search { background: #2563eb; color: #fff; padding: 0.75rem 1.5rem; border-radius: 0.5rem; font-weight: 500; border: none; cursor: pointer; transition: background 0.3s; }
        .btn-search:hover { background: #1d4ed8; }
        .btn-filter-mobile { background: #dbeafe; color: #1e3a8a; padding: 0.75rem 1.5rem; border-radius: 0.5rem; font-weight: 500; border: none; cursor: pointer; transition: background 0.3s; }
        .btn-filter-mobile:hover { background: #bfdbfe; }

        /* ===== PRODUCTS SECTION ===== */
        .products-section {
            background: linear-gradient(160deg, #fafaf9 0%, #f5f5f0 40%, #eae8e4 100%);
            min-height: 100vh; padding: 0;
        }

        /* Sidebar */
        .filter-sidebar {
            background: #fff; border-radius: 0.75rem; box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            padding: 1.5rem; position: sticky; top: 5rem;
        }
        .filter-title { font-size: 1.1rem; font-weight: 600; color: #111827; margin-bottom: 1.5rem; }
        .filter-reset { font-size: 0.85rem; color: #2563eb; font-weight: 500; text-decoration: none; }
        .filter-reset:hover { text-decoration: underline; }
        .filter-label { font-size: 0.8rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.75rem; }
        .filter-btn {
            width: 100%; text-align: left; padding: 0.5rem 0.75rem; border-radius: 0.375rem;
            font-size: 0.875rem; transition: all 0.2s; border: none; cursor: pointer; background: transparent; color: #374151;
        }
        .filter-btn:hover { background: #f3f4f6; }
        .filter-btn.active { background: #2563eb; color: #fff; }

        /* Toolbar */
        .toolbar {
            background: #fff; border-radius: 0.75rem; box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            padding: 1rem 1.25rem; margin-bottom: 1.5rem;
            border: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;
        }
        .toolbar-text { font-size: 0.875rem; color: #6b7280; }
        .toolbar-text strong { color: #111827; }
        .view-btn {
            padding: 0.5rem; border-radius: 0.375rem; transition: all 0.2s; border: none; cursor: pointer;
            background: transparent; color: #6b7280;
        }
        .view-btn:hover { background: #f3f4f6; }
        .view-btn.active { background: #2563eb; color: #fff; }
        .sort-select {
            padding: 0.5rem 1rem; border: 1px solid #d1d5db; border-radius: 0.5rem;
            background: #fff; font-size: 0.875rem; color: #1f2937; outline: none;
        }
        .sort-select:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }

        /* Product Grid */
        .products-grid {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem;
        }
        @media (min-width: 640px) { .products-grid { gap: 1rem; } }
        @media (min-width: 768px) { .products-grid { gap: 1.25rem; } }
        @media (min-width: 1024px) { .products-grid { grid-template-columns: repeat(3, 1fr); gap: 1.5rem; } }

        /* Product Card */
        .product-card {
            background: #fff; border-radius: 0.75rem; overflow: hidden;
            border: 1px solid #e5e7eb;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.3s ease; position: relative;
        }
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.1);
            border-color: #2563eb;
        }
        .product-card .card-img {
            height: 140px; overflow: hidden; background: #f3f4f6; position: relative;
        }
        @media (min-width: 640px) { .product-card .card-img { height: 220px; } }
        .product-card .card-img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s; }
        .product-card:hover .card-img img { transform: scale(1.06); }

        .product-card .card-body { padding: 0.75rem; }
        @media (min-width: 640px) { .product-card .card-body { padding: 1.25rem; } }

        .product-card .card-category { font-size: 0.7rem; font-weight: 600; color: #2563eb; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.4rem; }
        @media (min-width: 640px) { .product-card .card-category { font-size: 0.75rem; margin-bottom: 0.5rem; } }

        .product-card .card-title {
            font-size: 0.85rem; font-weight: 700; color: #111827; margin-bottom: 0.6rem;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
            line-height: 1.35; min-height: 2.3em; text-decoration: none; transition: color 0.2s;
        }
        .product-card .card-title:hover { color: #2563eb; }
        @media (min-width: 640px) { .product-card .card-title { font-size: 0.95rem; min-height: 2.6em; } }

        .product-card .card-price { font-size: 1rem; font-weight: 800; color: #111827; }
        .product-card .card-price-old { font-size: 0.8rem; color: #9ca3af; text-decoration: line-through; }
        .product-card .card-price-promo { font-size: 1rem; font-weight: 800; color: #dc2626; }
        @media (min-width: 640px) { .product-card .card-price, .product-card .card-price-promo { font-size: 1.15rem; } }

        .product-card .btn-cart {
            background: #2563eb; color: #fff; border: none;
            width: 36px; height: 36px; border-radius: 0.625rem;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: background 0.2s; font-size: 0.85rem;
        }
        .product-card .btn-cart:hover { background: #1d4ed8; }
        .product-card .btn-cart:disabled { opacity: 0.5; cursor: not-allowed; }
        @media (min-width: 640px) { .product-card .btn-cart { width: 42px; height: 42px; font-size: 1rem; } }

        /* Badges */
        .badge-promo { background: #dc2626; color: #fff; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 700; }
        .badge-new { background: #059669; color: #fff; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 700; }

        /* Hover actions */
        .card-actions {
            position: absolute; top: 0.6rem; right: 0.6rem;
            display: flex; flex-direction: column; gap: 0.5rem;
            opacity: 0; transform: translateX(8px); transition: all 0.3s;
        }
        .product-card:hover .card-actions { opacity: 1; transform: translateX(0); }
        .card-action-btn {
            width: 34px; height: 34px; border-radius: 50%;
            background: #fff; border: 1px solid #e5e7eb; color: #6b7280;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.1); font-size: 0.8rem;
        }
        .card-action-btn:hover { background: #2563eb; color: #fff; border-color: #2563eb; }

        /* List View */
        .product-list-item {
            background: #fff; border-radius: 0.75rem; overflow: hidden;
            border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.3s; display: flex; flex-direction: column;
        }
        @media (min-width: 640px) { .product-list-item { flex-direction: row; } }
        .product-list-item:hover { border-color: #2563eb; box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
        .product-list-item .list-img { width: 100%; height: 180px; flex-shrink: 0; overflow: hidden; background: #f3f4f6; position: relative; }
        @media (min-width: 640px) { .product-list-item .list-img { width: 192px; height: auto; } }
        .product-list-item .list-img img { width: 100%; height: 100%; object-fit: cover; }
        .product-list-item .list-body { padding: 1.25rem; flex: 1; display: flex; flex-direction: column; justify-content: space-between; }

        /* Pagination */
        .page-btn {
            padding: 0.5rem 1rem; border: 1px solid #d1d5db; border-radius: 0.5rem;
            background: #fff; color: #374151; font-size: 0.875rem; transition: all 0.2s; text-decoration: none;
        }
        .page-btn:hover { background: #eff6ff; }
        .page-btn.active { background: #2563eb; color: #fff; border-color: #2563eb; }
        .page-btn.disabled { opacity: 0.4; pointer-events: none; cursor: not-allowed; }

        /* Empty state */
        .empty-state { background: #fff; border-radius: 0.75rem; box-shadow: 0 2px 10px rgba(0,0,0,0.06); padding: 3rem; text-align: center; border: 1px solid #e5e7eb; }
        .empty-state i { font-size: 3.5rem; color: #d1d5db; margin-bottom: 1rem; }
        .empty-state h3 { font-size: 1.25rem; font-weight: 600; color: #111827; margin-bottom: 0.5rem; }
        .empty-state p { color: #6b7280; margin-bottom: 1.5rem; }
        .btn-reset { background: #2563eb; color: #fff; padding: 0.75rem 1.5rem; border-radius: 0.5rem; font-weight: 500; border: none; cursor: pointer; transition: background 0.2s; text-decoration: none; display: inline-block; }
        .btn-reset:hover { background: #1d4ed8; }

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

        /* ===== MOBILE FILTERS ===== */
        .mobile-filters-overlay { position: fixed; inset: 0; z-index: 200; background: rgba(0,0,0,0.5); }
        .mobile-filters-panel { position: relative; height: 100%; overflow-y: auto; width: 80%; max-width: 320px; background: #f8fafc; }
        .mobile-filters-header { display: flex; padding: 1rem; border-bottom: 1px solid #bfdbfe; justify-content: space-between; align-items: center; }
        .mobile-filters-header h2 { font-size: 1.1rem; font-weight: 600; color: #0f172a; }
        .mobile-filters-close { padding: 0.5rem; background: none; border: none; cursor: pointer; color: #1e3a8a; border-radius: 50%; transition: background 0.2s; }
        .mobile-filters-close:hover { background: #eff6ff; }
        .mobile-filters-body { padding: 1.5rem; }

        /* ===== MODAL ===== */
        .modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.8); z-index: 1000; display: none; align-items: center; justify-content: center; padding: 2rem; }
        .modal-overlay.show { display: flex; }
        .modal-box { background: #fff; border-radius: 1rem; max-width: 48rem; width: 100%; max-height: 90vh; overflow-y: auto; position: relative; border: 1px solid #e5e7eb; }
        .modal-close { position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #9ca3af; z-index: 10; width: 2.5rem; height: 2.5rem; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: all 0.2s; }
        .modal-close:hover { background: #f3f4f6; color: #4b5563; }

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
    </style>
</head>

<body style="margin:0; background:#0f172a; color:#e2e8f0;">

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
                    <a href="../index.php" class="nav-link" style="text-decoration:none; font-size:0.9rem; font-weight:500;">Galerie</a>
                    <a href="promotion.php" class="nav-link" style="text-decoration:none; font-size:0.9rem; font-weight:500;">Promosyons</a>
                    <a href="hot_deal.php" class="nav-link" style="text-decoration:none; font-size:0.9rem; font-weight:500;">Hot-Deal</a>
                </nav>

                <!-- Icons -->
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <a href="panier/Panier.php" class="icon-btn" style="padding:0.5rem; border-radius:50%; text-decoration:none; position:relative;" title="Panier">
                        <i class="fas fa-shopping-cart" style="font-size:1.15rem;"></i>
                        <span id="cart-badge" class="cart-badge" style="position:absolute; top:-2px; right:-2px; font-size:0.7rem; padding:0.1rem 0.4rem; border-radius:9999px;">0</span>
                    </a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="profile.php" class="icon-btn" style="padding:0.5rem; border-radius:50%; text-decoration:none;" title="Pwofil">
                            <i class="fas fa-user" style="font-size:1.15rem;"></i>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="icon-btn" style="padding:0.5rem; border-radius:50%; text-decoration:none;" title="Konekte">
                            <i class="fas fa-sign-in-alt" style="font-size:1.15rem;"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Show nav on large screens -->
    <style>.lg-nav { display: none !important; } @media (min-width: 1024px) { .lg-nav { display: flex !important; } }</style>

    <!-- ===== VIDEO CAROUSEL ===== -->
    <section class="video-carousel">
        <?php foreach ($hero_videos as $index => $video): ?>
        <div class="video-slide <?= $index === 0 ? 'active' : '' ?>" data-index="<?= $index ?>">
            <video muted loop playsinline poster="<?= htmlspecialchars($video['poster']) ?>" preload="metadata">
                <source src="<?= htmlspecialchars($video['src']) ?>" type="video/mp4">
            </video>
            <div class="video-overlay">
                <div class="video-content">
                    <h1 class="glass-title"><?= htmlspecialchars($video['title']) ?></h1>
                    <p class="glass-subtitle"><?= htmlspecialchars($video['subtitle']) ?></p>
                    <p class="glass-caption">Navige nan <?= count($all_products) ?> pwodwi primye kalite</p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="carousel-progress" id="progressBar"></div>

        <button class="carousel-arrow prev" onclick="prevSlide()" aria-label="Videyo anvan">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button class="carousel-arrow next" onclick="nextSlide()" aria-label="Videyo pwochen">
            <i class="fas fa-chevron-right"></i>
        </button>

        <div style="position:absolute; bottom:28px; left:50%; transform:translateX(-50%); display:flex; gap:10px; z-index:10;">
            <?php foreach ($hero_videos as $index => $video): ?>
            <div class="carousel-dot <?= $index === 0 ? 'active' : '' ?>" onclick="goToSlide(<?= $index ?>)"></div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ===== SEARCH BAR ===== -->
    <div class="search-section" style="padding:1.25rem 1rem;">
        <div style="max-width:80rem; margin:0 auto;">
            <form method="GET" style="display:flex; flex-direction:column; align-items:center; gap:0.75rem;" class="search-form">
                <div style="flex:1; width:100%; position:relative;">
                    <i class="fas fa-search" style="position:absolute; left:1rem; top:50%; transform:translateY(-50%); color:#94a3b8;"></i>
                    <input type="text" name="search" id="searchInput" placeholder="Chache pwodwi pa non..." value="<?= htmlspecialchars($search_query) ?>" class="search-input">
                </div>
                <div style="display:flex; gap:0.5rem; width:100%;" class="search-btns">
                    <button type="submit" class="btn-search" style="flex:1; display:flex; align-items:center; justify-content:center; gap:0.5rem;">
                        <i class="fas fa-search"></i> Chache
                    </button>
                    <button type="button" onclick="toggleFilters()" class="btn-filter-mobile" style="flex:1; display:flex; align-items:center; justify-content:center; gap:0.5rem;" id="btnFilterMobile">
                        <i class="fas fa-sliders-h"></i> Filtre
                    </button>
                </div>
            </form>
        </div>
    </div>
    <style>@media(min-width:640px){.search-form{flex-direction:row;}.search-btns{width:auto;flex:none;}}</style>

    <!-- ===== MAIN PRODUCTS ===== -->
    <main class="products-section">
        <div style="max-width:80rem; margin:0 auto; padding:2rem 1rem;">
            <div style="display:flex; gap:2rem;">

                <!-- Sidebar Desktop -->
                <aside style="width:300px; flex-shrink:0;" class="sidebar-desktop">
                    <div class="filter-sidebar">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem;">
                            <h2 class="filter-title">Filtre</h2>
                            <a href="?" class="filter-reset">Reyinisyalize tout</a>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:1.5rem;">
                            <div>
                                <h3 class="filter-label">Kategori</h3>
                                <div style="display:flex; flex-direction:column; gap:0.35rem; max-height:200px; overflow-y:auto; padding-right:0.5rem;">
                                    <button onclick="window.location.href='?category=All<?= $search_query ? '&search=' . urlencode($search_query) : '' ?>'" class="filter-btn <?= $selected_category === 'All' ? 'active' : '' ?>">Tout Kategori</button>
                                    <?php foreach ($all_categories as $cat): ?>
                                        <button onclick="window.location.href='?category=<?= urlencode($cat['name']) ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>'" class="filter-btn <?= $selected_category === $cat['name'] ? 'active' : '' ?>"><?= htmlspecialchars($cat['name']) ?></button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div>
                                <h3 class="filter-label">Prix</h3>
                                <div style="display:flex; flex-direction:column; gap:0.35rem;">
                                    <button onclick="setPriceRange(0,999999)" class="filter-btn <?= ($price_min == 0 && $price_max == 999999) ? 'active' : '' ?>">Tout Prix</button>
                                    <button onclick="setPriceRange(0,1000)" class="filter-btn <?= ($price_min == 0 && $price_max == 1000) ? 'active' : '' ?>">Anba 1,000 HTG</button>
                                    <button onclick="setPriceRange(1000,5000)" class="filter-btn <?= ($price_min == 1000 && $price_max == 5000) ? 'active' : '' ?>">1,000 - 5,000 HTG</button>
                                    <button onclick="setPriceRange(5000,10000)" class="filter-btn <?= ($price_min == 5000 && $price_max == 10000) ? 'active' : '' ?>">5,000 - 10,000 HTG</button>
                                    <button onclick="setPriceRange(10000,999999)" class="filter-btn <?= ($price_min == 10000) ? 'active' : '' ?>">Pi wo 10,000 HTG</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>
                <style>.sidebar-desktop{display:none!important;}@media(min-width:1024px){.sidebar-desktop{display:block!important;}#btnFilterMobile{display:none!important;}}</style>

                <!-- Mobile Filters -->
                <div id="mobileFilters" style="display:none;">
                    <div class="mobile-filters-overlay" onclick="toggleFilters()">
                        <div class="mobile-filters-panel" onclick="event.stopPropagation()">
                            <div class="mobile-filters-header">
                                <h2>Filtre</h2>
                                <button class="mobile-filters-close" onclick="toggleFilters()"><i class="fas fa-times"></i></button>
                            </div>
                            <div class="mobile-filters-body">
                                <div style="display:flex; flex-direction:column; gap:1.5rem;">
                                    <div>
                                        <h3 class="filter-label">Kategori</h3>
                                        <div style="display:flex; flex-direction:column; gap:0.35rem;">
                                            <button onclick="window.location.href='?category=All<?= $search_query ? '&search=' . urlencode($search_query) : '' ?>'" class="filter-btn <?= $selected_category === 'All' ? 'active' : '' ?>">Tout Kategori</button>
                                            <?php foreach ($all_categories as $cat): ?>
                                                <button onclick="window.location.href='?category=<?= urlencode($cat['name']) ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>'" class="filter-btn <?= $selected_category === $cat['name'] ? 'active' : '' ?>"><?= htmlspecialchars($cat['name']) ?></button>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <h3 class="filter-label">Prix</h3>
                                        <div style="display:flex; flex-direction:column; gap:0.35rem;">
                                            <button onclick="setPriceRange(0,999999)" class="filter-btn <?= ($price_min == 0 && $price_max == 999999) ? 'active' : '' ?>">Tout Prix</button>
                                            <button onclick="setPriceRange(0,1000)" class="filter-btn <?= ($price_min == 0 && $price_max == 1000) ? 'active' : '' ?>">Anba 1,000 HTG</button>
                                            <button onclick="setPriceRange(1000,5000)" class="filter-btn <?= ($price_min == 1000 && $price_max == 5000) ? 'active' : '' ?>">1,000 - 5,000 HTG</button>
                                            <button onclick="setPriceRange(5000,10000)" class="filter-btn <?= ($price_min == 5000 && $price_max == 10000) ? 'active' : '' ?>">5,000 - 10,000 HTG</button>
                                            <button onclick="setPriceRange(10000,999999)" class="filter-btn <?= ($price_min == 10000) ? 'active' : '' ?>">Pi wo 10,000 HTG</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Area -->
                <div style="flex:1; min-width:0;">
                    <!-- Toolbar -->
                    <div class="toolbar">
                        <p class="toolbar-text">
                            Afiche <strong><?= $start_index + 1 ?></strong>-<strong><?= min($start_index + $items_per_page, $total_products) ?></strong> nan <strong><?= $total_products ?></strong> pwodwi
                        </p>
                        <div style="display:flex; align-items:center; gap:0.75rem;">
                            <div style="display:none; align-items:center; gap:0.35rem; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.2rem; background:#f9fafb;" class="view-toggle">
                                <button onclick="setViewMode('grid')" class="view-btn <?= $view_mode === 'grid' ? 'active' : '' ?>" title="Glay"><i class="fas fa-th"></i></button>
                                <button onclick="setViewMode('list')" class="view-btn <?= $view_mode === 'list' ? 'active' : '' ?>" title="Lis"><i class="fas fa-list"></i></button>
                            </div>
                            <style>.view-toggle{display:none!important;}@media(min-width:640px){.view-toggle{display:flex!important;}}</style>
                            <select onchange="window.location.href='?sort='+this.value<?= $selected_category !== 'All' ? "+'&category=" . urlencode($selected_category) . "'" : "''" ?><?= $search_query ? "+'&search=" . urlencode($search_query) . "'" : "''" ?>" class="sort-select">
                                <option value="featured" <?= $sort_by === 'featured' ? 'selected' : '' ?>>En Vitrine</option>
                                <option value="newest" <?= $sort_by === 'newest' ? 'selected' : '' ?>>Pi Nouvo</option>
                                <option value="price-low" <?= $sort_by === 'price-low' ? 'selected' : '' ?>>Prix: Ki pi ba</option>
                                <option value="price-high" <?= $sort_by === 'price-high' ? 'selected' : '' ?>>Prix: Wo</option>
                                <option value="name-asc" <?= $sort_by === 'name-asc' ? 'selected' : '' ?>>Non: A-Z</option>
                                <option value="name-desc" <?= $sort_by === 'name-desc' ? 'selected' : '' ?>>Non: Z-A</option>
                            </select>
                        </div>
                    </div>

                    <?php if (count($current_products) === 0): ?>
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h3>Pa gen pwodwi</h3>
                            <p>Eseye modifye filtè ou yo oswa rechèch ou a</p>
                            <a href="?" class="btn-reset">Reyinisyalize Filtre</a>
                        </div>
                    <?php else: ?>

                        <!-- Grid View -->
                        <?php if ($view_mode === 'grid'): ?>
                        <div class="products-grid">
                            <?php foreach ($current_products as $product): ?>
                                <?php $is_promo = isPromo($product); ?>
                                <div class="product-card">
                                    <div class="card-img">
                                        <a href="product-view.php?id=<?= $product['id'] ?>" style="display:block; width:100%; height:100%;">
                                            <img src="../uploads/products/<?= htmlspecialchars($product['image'] ?? 'placeholder.png') ?>" alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy" onerror="this.src='../assets/img/placeholder.png'">
                                        </a>
                                        <?php if ($is_promo): ?>
                                            <div style="position:absolute; top:0.6rem; left:0.6rem;"><span class="badge-promo">-<?= getDiscountPercent($product) ?>%</span></div>
                                        <?php elseif (isset($product['created_at']) && strtotime($product['created_at']) > strtotime('-7 days')): ?>
                                            <div style="position:absolute; top:0.6rem; left:0.6rem;"><span class="badge-new">NOUVO</span></div>
                                        <?php endif; ?>
                                        <div class="card-actions">
                                            <button onclick="addToFavorites(<?= $product['id'] ?>)" class="card-action-btn" title="Ajoute nan favori"><i class="fas fa-heart"></i></button>
                                            <button onclick="quickView(<?= $product['id'] ?>)" class="card-action-btn" title="Gade rapid"><i class="fas fa-eye"></i></button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="card-category"><?= htmlspecialchars($product['category_name']) ?></div>
                                        <a href="product-view.php?id=<?= $product['id'] ?>" class="card-title"><?= htmlspecialchars(truncate($product['name'], 40)) ?></a>
                                        <div style="display:flex; justify-content:space-between; align-items:center;">
                                            <div>
                                                <?php if ($is_promo): ?>
                                                    <span class="card-price-promo"><?= number_format($product['price_promo']) ?> HTG</span>
                                                    <div class="card-price-old"><?= number_format($product['price']) ?> HTG</div>
                                                <?php else: ?>
                                                    <span class="card-price"><?= number_format($product['price']) ?> HTG</span>
                                                <?php endif; ?>
                                            </div>
                                            <button onclick="addToCart(<?= $product['id'] ?>, this)" class="btn-cart"><i class="fas fa-cart-plus"></i></button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- List View -->
                        <?php else: ?>
                        <div style="display:flex; flex-direction:column; gap:1rem;">
                            <?php foreach ($current_products as $product): ?>
                                <?php $is_promo = isPromo($product); ?>
                                <div class="product-list-item">
                                    <div class="list-img">
                                        <a href="product-view.php?id=<?= $product['id'] ?>" style="display:block; width:100%; height:100%;">
                                            <img src="../uploads/products/<?= htmlspecialchars($product['image'] ?? 'placeholder.png') ?>" alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy" onerror="this.src='../assets/img/placeholder.png'">
                                        </a>
                                        <?php if ($is_promo): ?>
                                            <div style="position:absolute; top:0.6rem; left:0.6rem;"><span class="badge-promo">-<?= getDiscountPercent($product) ?>%</span></div>
                                        <?php elseif (isset($product['created_at']) && strtotime($product['created_at']) > strtotime('-7 days')): ?>
                                            <div style="position:absolute; top:0.6rem; left:0.6rem;"><span class="badge-new">NOUVO</span></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="list-body">
                                        <div>
                                            <div class="card-category"><?= htmlspecialchars($product['category_name']) ?></div>
                                            <a href="product-view.php?id=<?= $product['id'] ?>" style="font-size:1.1rem; font-weight:700; color:#111827; text-decoration:none; margin-bottom:0.5rem; display:block; transition:color 0.2s;" onmouseover="this.style.color='#2563eb'" onmouseout="this.style.color='#111827'"><?= htmlspecialchars($product['name']) ?></a>
                                            <p style="color:#6b7280; font-size:0.875rem; margin-bottom:1rem; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;"><?= htmlspecialchars(truncate($product['description'] ?? '', 100)) ?></p>
                                        </div>
                                        <div style="display:flex; justify-content:space-between; align-items:center;">
                                            <div>
                                                <?php if ($is_promo): ?>
                                                    <span class="card-price-promo" style="font-size:1.4rem;"><?= number_format($product['price_promo']) ?> HTG</span>
                                                    <div class="card-price-old"><?= number_format($product['price']) ?> HTG</div>
                                                <?php else: ?>
                                                    <span class="card-price" style="font-size:1.4rem;"><?= number_format($product['price']) ?> HTG</span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="display:flex; gap:0.5rem;">
                                                <button onclick="addToFavorites(<?= $product['id'] ?>)" class="card-action-btn" style="width:38px;height:38px;" title="Favori"><i class="fas fa-heart"></i></button>
                                                <button onclick="quickView(<?= $product['id'] ?>)" class="card-action-btn" style="width:38px;height:38px;" title="Gade rapid"><i class="fas fa-eye"></i></button>
                                                <button onclick="addToCart(<?= $product['id'] ?>, this)" class="btn-cart" style="width:auto; padding:0 1rem; border-radius:0.625rem; font-size:0.85rem; gap:0.4rem; display:flex; align-items:center;"><i class="fas fa-cart-plus" style="margin-right:0.35rem;"></i>Ajoute</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div style="margin-top:2rem; display:flex; align-items:center; justify-content:center; gap:0.5rem; flex-wrap:wrap;">
                                <?php 
                                $base_url = '?';
                                if ($selected_category !== 'All') $base_url .= 'category=' . urlencode($selected_category) . '&';
                                if ($search_query) $base_url .= 'search=' . urlencode($search_query) . '&';
                                if ($sort_by !== 'featured') $base_url .= 'sort=' . $sort_by . '&';
                                if ($view_mode !== 'grid') $base_url .= 'view=' . $view_mode . '&';
                                ?>
                                <a href="<?= $base_url ?>page=<?= max(1, $page - 1) ?>" class="page-btn <?= $page === 1 ? 'disabled' : '' ?>"><i class="fas fa-chevron-left"></i></a>
                                <?php 
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                if ($start_page > 1): ?>
                                    <a href="<?= $base_url ?>page=1" class="page-btn">1</a>
                                    <?php if ($start_page > 2): ?><span style="padding:0 0.4rem; color:#9ca3af;">...</span><?php endif; ?>
                                <?php endif; ?>
                                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <a href="<?= $base_url ?>page=<?= $i ?>" class="page-btn <?= $page === $i ? 'active' : '' ?>"><?= $i ?></a>
                                <?php endfor; ?>
                                <?php if ($end_page < $total_pages): ?>
                                    <?php if ($end_page < $total_pages - 1): ?><span style="padding:0 0.4rem; color:#9ca3af;">...</span><?php endif; ?>
                                    <a href="<?= $base_url ?>page=<?= $total_pages ?>" class="page-btn"><?= $total_pages ?></a>
                                <?php endif; ?>
                                <a href="<?= $base_url ?>page=<?= min($total_pages, $page + 1) ?>" class="page-btn <?= $page === $total_pages ? 'disabled' : '' ?>"><i class="fas fa-chevron-right"></i></a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- ===== FEATURES ===== -->
    <section class="features-section">
        <div style="max-width:80rem; margin:0 auto; padding:0 1rem;">
            <div style="display:grid; grid-template-columns:1fr; gap:1.25rem;" class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-cube"></i></div>
                    <div><h3>Free Shipping</h3><p>Free shipping for order above $180</p></div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-credit-card"></i></div>
                    <div><h3>Flexible Payment</h3><p>Multiple secure payment options</p></div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-headset"></i></div>
                    <div><h3>24×7 Support</h3><p>We support online all days</p></div>
                </div>
            </div>
        </div>
    </section>
    <style>@media(min-width:768px){.features-grid{grid-template-columns:repeat(3,1fr)!important;}}</style>

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
                    <h4>Company</h4>
                    <div class="footer-links" style="display:flex; flex-direction:column; gap:0.25rem;">
                        <a href="#">About Us</a><a href="#">Blog</a><a href="#">Contact Us</a><a href="#">Career</a>
                    </div>
                </div>
                <div>
                    <h4>Customer Services</h4>
                    <div class="footer-links" style="display:flex; flex-direction:column; gap:0.25rem;">
                        <a href="#">My Account</a><a href="#">Track Your Order</a><a href="#">Return</a><a href="#">FAQS</a>
                    </div>
                </div>
                <div>
                    <h4>Contact Info</h4>
                    <div>
                        <div class="contact-item"><i class="fas fa-phone"></i><span>+0123-456-789</span></div>
                        <div class="contact-item"><i class="fas fa-envelope"></i><span>example@gmail.com</span></div>
                        <div class="contact-item"><i class="fas fa-map-marker-alt"></i><span>8502 Preston Rd.<br>Inglewood, Maine 98380</span></div>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p class="footer-copy">Copyright © 2024 Clothing Website Design. All Rights Reserved.</p>
                <div style="display:flex; gap:0.75rem;">
                    <button class="footer-lang">English <i class="fas fa-chevron-down" style="font-size:0.65rem;"></i></button>
                    <button class="footer-lang">USD <i class="fas fa-chevron-down" style="font-size:0.65rem;"></i></button>
                </div>
            </div>
        </div>
    </footer>
    <style>@media(min-width:640px){.footer-grid{grid-template-columns:repeat(2,1fr)!important;}}@media(min-width:1024px){.footer-grid{grid-template-columns:repeat(4,1fr)!important;}}</style>

    <!-- ===== QUICK VIEW MODAL ===== -->
    <div id="quickViewModal" class="modal-overlay" onclick="closeQuickViewOnOverlay(event)">
        <div class="modal-box" onclick="event.stopPropagation()">
            <button class="modal-close" onclick="closeQuickView()"><i class="fas fa-times"></i></button>
            <div id="quickViewContent" style="padding:1.5rem;"></div>
        </div>
    </div>

    <!-- ===== JAVASCRIPT ===== -->
    <script>
        let currentSlide = 0;
        const slides = document.querySelectorAll('.video-slide');
        const dots = document.querySelectorAll('.carousel-dot');
        const progressBar = document.getElementById('progressBar');
        const totalSlides = slides.length;
        let slideInterval;
        const slideDuration = 6000;

        function playVideo(i) { const v = slides[i].querySelector('video'); if(v){ v.currentTime=0; v.play().catch(()=>{}); } }
        function pauseVideo(i) { const v = slides[i].querySelector('video'); if(v) v.pause(); }

        function showSlide(i) {
            slides.forEach((s,j) => { s.classList.remove('active'); pauseVideo(j); });
            dots.forEach(d => d.classList.remove('active'));
            slides[i].classList.add('active');
            dots[i].classList.add('active');
            playVideo(i);
            resetProgressBar();
        }

        function nextSlide() { currentSlide = (currentSlide + 1) % totalSlides; showSlide(currentSlide); }
        function prevSlide() { currentSlide = (currentSlide - 1 + totalSlides) % totalSlides; showSlide(currentSlide); }
        function goToSlide(i) { currentSlide = i; showSlide(i); clearInterval(slideInterval); slideInterval = setInterval(nextSlide, slideDuration); }

        function resetProgressBar() {
            progressBar.style.transition = 'none';
            progressBar.style.width = '0%';
            setTimeout(() => { progressBar.style.transition = 'width ' + slideDuration + 'ms linear'; progressBar.style.width = '100%'; }, 50);
        }

        function initCarousel() { playVideo(0); resetProgressBar(); slideInterval = setInterval(nextSlide, slideDuration); }

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) { clearInterval(slideInterval); pauseVideo(currentSlide); }
            else { playVideo(currentSlide); slideInterval = setInterval(nextSlide, slideDuration); resetProgressBar(); }
        });

        document.addEventListener('DOMContentLoaded', function() { initCarousel(); updateCartBadge(); });

        function toggleFilters() {
            const mf = document.getElementById('mobileFilters');
            if (mf.style.display === 'none') { mf.style.display = 'block'; document.body.style.overflow = 'hidden'; }
            else { mf.style.display = 'none'; document.body.style.overflow = 'auto'; }
        }

        function setViewMode(m) { const u = new URL(window.location.href); u.searchParams.set('view', m); window.location.href = u.toString(); }
        function setPriceRange(min, max) { const u = new URL(window.location.href); u.searchParams.set('price_min', min); u.searchParams.set('price_max', max); window.location.href = u.toString(); }

        function updateCartBadge() {
            fetch('panier/get_cart_count.php').then(r => r.json()).then(d => {
                const b = document.getElementById('cart-badge');
                if (b) {
                    const old = parseInt(b.textContent) || 0;
                    b.textContent = d.count || 0;
                    if (d.count !== old && old !== 0) { b.classList.add('badge-bounce'); setTimeout(() => b.classList.remove('badge-bounce'), 500); }
                }
            }).catch(e => console.error(e));
        }

        function addToCart(id, btn) {
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; }
            fetch('panier/add_to_cart.php', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: 'product_id='+id+'&qty=1' })
            .then(r => r.text()).then(t => { try { return JSON.parse(t); } catch(e) { throw new Error('Repons pa valide'); } })
            .then(d => { if(d.success) { updateCartBadge(); showNotif('Pwodwi ajoute nan panier!','success'); } else { showNotif(d.message||'Erè, eseye ankò.','error'); } })
            .catch(e => { console.error(e); showNotif('Erè: '+e.message,'error'); })
            .finally(() => { if(btn) setTimeout(() => { btn.disabled=false; btn.innerHTML='<i class="fas fa-cart-plus"></i>'; }, 500); });
        }

        function addToFavorites(id) {
            fetch('add_to_favorites.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'product_id='+id })
            .then(r => r.json()).then(d => { if(d.success) showNotif('Ajoute nan favori!','success'); else showNotif(d.message||'Ou dwe konekte anvan.','error'); })
            .catch(() => showNotif('Erè rezo, eseye ankò.','error'));
        }

        function quickView(id) {
            const modal = document.getElementById('quickViewModal');
            const content = document.getElementById('quickViewContent');
            fetch('get_product.php?id='+id).then(r => r.json()).then(d => {
                const promo = d.price_promo && d.price_promo > 0 && d.price_promo < d.price;
                content.innerHTML = `
                    <div style="display:grid; grid-template-columns:1fr; gap:1.5rem;" class="qv-grid">
                        <div style="aspect-ratio:1; background:#f3f4f6; border-radius:0.5rem; overflow:hidden;">
                            <img src="../uploads/products/${d.image||'placeholder.png'}" style="width:100%;height:100%;object-fit:cover;" onerror="this.src='../assets/img/placeholder.png'">
                        </div>
                        <div style="display:flex; flex-direction:column; justify-content:center;">
                            <h2 style="font-size:1.5rem; font-weight:700; margin-bottom:0.5rem; color:#111827;">${d.name}</h2>
                            <p style="color:#2563eb; font-weight:600; margin-bottom:1rem; text-transform:uppercase; letter-spacing:0.05em; font-size:0.85rem;">${d.category_name}</p>
                            <p style="color:#6b7280; margin-bottom:1.5rem; line-height:1.6;">${d.description||'Pa gen deskripsyon.'}</p>
                            <div style="margin-bottom:1.5rem;">
                                ${promo ? `<span style="font-size:1.8rem; font-weight:700; color:#dc2626;">${new Intl.NumberFormat().format(d.price_promo)} HTG</span>
                                <span style="color:#9ca3af; text-decoration:line-through; margin-left:0.75rem; font-size:1.15rem;">${new Intl.NumberFormat().format(d.price)} HTG</span>
                                <span style="margin-left:0.75rem; background:#fef2f2; color:#dc2626; padding:0.2rem 0.5rem; border-radius:0.25rem; font-size:0.85rem; font-weight:700;">- ${Math.round((1-d.price_promo/d.price)*100)}%</span>`
                                : `<span style="font-size:1.8rem; font-weight:700; color:#111827;">${new Intl.NumberFormat().format(d.price)} HTG</span>`}
                            </div>
                            <button onclick="addToCart(${d.id},this);closeQuickView();" style="background:#2563eb;color:#fff;border:none;padding:0.75rem 1.25rem;border-radius:0.75rem;font-weight:600;display:flex;align-items:center;justify-content:center;gap:0.5rem;transition:background 0.2s;cursor:pointer;width:100%;font-size:1rem;" onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='#2563eb'">
                                <i class="fas fa-cart-plus" style="margin-right:0.5rem;"></i> Ajoute nan Panier
                            </button>
                        </div>
                    </div>`;
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
            }).catch(e => { console.error(e); showNotif('Erè nan chajman pwodwi a','error'); });
        }
        <style>@media(min-width:768px){.qv-grid{grid-template-columns:repeat(2,1fr)!important;}}</style>

        function closeQuickView() { document.getElementById('quickViewModal').classList.remove('show'); document.body.style.overflow = 'auto'; }
        function closeQuickViewOnOverlay(e) { if(e.target === e.currentTarget) closeQuickView(); }

        function showNotif(msg, type) {
            const old = document.querySelector('.notif'); if(old) old.remove();
            const n = document.createElement('div');
            n.className = 'notif notif-' + type;
            n.innerHTML = '<i class="fas fa-' + (type==='success'?'check':'exclamation') + '-circle" style="margin-right:0.5rem;"></i>' + msg;
            document.body.appendChild(n);
            setTimeout(() => { n.style.opacity='0'; n.style.transform='translateX(100%)'; n.style.transition='all 0.3s'; setTimeout(() => n.remove(), 300); }, 3000);
        }

        window.addEventListener('resize', function() {
            if(window.innerWidth >= 1024) { document.getElementById('mobileFilters').style.display = 'none'; document.body.style.overflow = 'auto'; }
        });
    </script>
</body>

</html>