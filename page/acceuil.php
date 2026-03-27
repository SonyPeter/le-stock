<?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';

// Rekipere tout kategori ak pwodwi yo
$categories_with_products = [];
$all_products = [];

try {
    // Pran tout kategori yo
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

    // Pran tout pwodwi pou rechèch
    $all_products = $pdo->query("
        SELECT p.*, c.name as category_name 
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.status = 'disponible'
        ORDER BY p.id DESC
    ")->fetchAll();

    // Pran pwodwi an promosyon
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

    // Pran tout kategori pou filtè
    $all_categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

    // Tès si kolòn yo egziste nan baz done a
    $columns = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
    $has_color = in_array('color', $columns);
    $has_size = in_array('size', $columns);
    $has_brand = in_array('brand', $columns);

    // Pran valè pou filtè si kolòn yo egziste
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

// Jere filtè yo depi URL
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

// Filtre pwodwi yo
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

// Tri pwodwi yo
usort($filtered_products, function ($a, $b) use ($sort_by) {
    switch ($sort_by) {
        case 'price-low':
            return $a['price'] - $b['price'];
        case 'price-high':
            return $b['price'] - $a['price'];
        case 'name-asc':
            return strcmp($a['name'], $b['name']);
        case 'name-desc':
            return strcmp($b['name'], $a['name']);
        case 'rating':
            return (($b['rating'] ?? 0) - ($a['rating'] ?? 0));
        case 'newest':
            return strtotime($b['created_at'] ?? 'now') - strtotime($a['created_at'] ?? 'now');
        default:
            return 0;
    }
});

// Paginasyon
$total_products = count($filtered_products);
$total_pages = ceil($total_products / $items_per_page);
$start_index = ($page - 1) * $items_per_page;
$current_products = array_slice($filtered_products, $start_index, $items_per_page);

// Lis videyo pou karysel la - Ou ka modifye sa yo
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
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS (Compiled) -->
    <link rel="stylesheet" href="\le-stock\css\style.css">
    <style>
        /* Scrollbar styling - Koulè FLAMME PAL (Brick/Teracotta) */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #36dc9cff;
            /* brick fonse */
        }

        ::-webkit-scrollbar-thumb {
            background: #c2410c;
            /* brick mwayen */
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #ea580c;
            /* brick kle */
        }

        /* Logo container styles */
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-image {
            max-height: 60px;
            width: auto;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .logo-image:hover {
            transform: scale(1.05);
        }

        @media (max-width: 640px) {
            .logo-image {
                max-height: 45px;
            }
        }

        @media (min-width: 1024px) {
            .logo-image {
                max-height: 80px;
            }
        }

        /* NOUVO KARYSEL VIDEO STYLES */
        .video-carousel-section {
            position: relative;
            width: 100%;
            height: 400px;
            overflow: hidden;
            background-color: #44f1e8ff;
            /* brick tre fonse */
        }

        @media (min-width: 768px) {
            .video-carousel-section {
                height: 500px;
            }
        }

        @media (min-width: 1024px) {
            .video-carousel-section {
                height: 600px;
            }
        }

        .video-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 0.8s ease-in-out;
            z-index: 1;
        }

        .video-slide.active {
            opacity: 1;
            z-index: 2;
        }

        .video-slide video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .video-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom, rgba(67, 20, 7, 0.3), rgba(67, 20, 7, 0.7));
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 3;
        }

        .video-content {
            text-align: center;
            color: white;
            padding: 2rem;
            max-width: 800px;
            animation: fadeInUp 0.8s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* GLASSMORPHISM STYLES POU TÈKS YO - AMELYORE POU LIZIBITE */
        .glass-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            /* Glassmorphism effect pi fò */
            background: rgba(52, 213, 227, 0.34);
            /* brick fonse transparent */
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 16px;
            padding: 1rem 2rem;
            border: 1px solid rgba(52, 213, 227, 0.34);
            /* orange kle */
            box-shadow: 0 8px 32px rgba(52, 213, 227, 0.34);
            display: inline-block;
            color: #ffffff;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
        }

        @media (min-width: 768px) {
            .glass-title {
                font-size: 3.5rem;
                padding: 1.25rem 2.5rem;
            }
        }

        @media (min-width: 1024px) {
            .glass-title {
                font-size: 4rem;
                padding: 1.5rem 3rem;
            }
        }

        .glass-subtitle {
            font-size: 1.25rem;
            margin-bottom: 0.75rem;
            /* Glassmorphism effect pi fò */
            background: rgba(191, 231, 14, 0.34);
            /* brick fonse transparent */
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            border: 1px solid rgba(191, 231, 14, 0.34);
            /* orange kle */
            box-shadow: 0 6px 24pxrgba(191, 231, 14, 0.34));
            display: inline-block;
            color: #ffffff;
            font-weight: 500;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);
        }

        .glass-caption {
            font-size: 1rem;
            /* Glassmorphism effect pi fò */
            background: rgba(236, 71, 181, 0.4);
            /* brick fonse transparent */
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-radius: 10px;
            padding: 0.5rem 1.25rem;
            border: 1px solid rgba(236, 71, 181, 0.4);
            /* orange kle */
            box-shadow: 0 4px 20px rgba(236, 71, 181, 0.4);
            display: inline-block;
            color: #ffedd5;
            /* orange tre kle */
            font-weight: 400;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
        }

        /* Kontwòl karysel */
        .carousel-controls {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 12px;
            z-index: 10;
        }

        .carousel-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: rgba(255, 237, 213, 0.4);
            /* orange tre kle */
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .carousel-dot:hover {
            background-color: rgba(255, 237, 213, 0.7);
            transform: scale(1.2);
        }

        .carousel-dot.active {
            background-color: #ea580c;
            /* orange kle */
            border-color: #ffedd5;
            transform: scale(1.2);
        }

        /* Bouton flèch */
        .carousel-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 50px;
            height: 50px;
            background-color: rgba(124, 45, 18, 0.8);
            /* brick fonse */
            border: 2px solid rgba(251, 146, 60, 0.4);
            /* orange kle */
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffedd5;
            /* orange tre kle */
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10;
            font-size: 1.2rem;
        }

        .carousel-arrow:hover {
            background-color: rgba(194, 65, 12, 0.9);
            /* brick mwayen */
            border-color: #fb923c;
            /* orange kle */
            transform: translateY(-50%) scale(1.1);
        }

        .carousel-arrow.prev {
            left: 20px;
        }

        .carousel-arrow.next {
            right: 20px;
        }

        @media (max-width: 640px) {
            .carousel-arrow {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }

            .carousel-arrow.prev {
                left: 10px;
            }

            .carousel-arrow.next {
                right: 10px;
            }
        }

        /* Progress bar */
        .carousel-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 4px;
            background-color: #ea580c;
            /* orange kle */
            transition: width 0.1s linear;
            z-index: 10;
        }

        /* NOUVO STYLE POU SEKSYON PWODWI YO - BACKGROUND BEIGE */
        .products-section-beige {
            background: linear-gradient(135deg, #fafaf9 0%, #f5f5f0 50%, #e7e5e4 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }

        .products-section-beige .bg-slate-850 {
            background-color: rgba(255, 255, 255, 0.95) !important;
            border: 1px solid rgba(124, 45, 18, 0.1);
        }

        .products-section-beige .text-slate-200 {
            color: #1f2937 !important;
        }

        .products-section-beige .text-gray-100 {
            color: #111827 !important;
        }

        .products-section-beige .text-gray-400 {
            color: #4b5563 !important;
        }

        .products-section-beige .text-slate-300 {
            color: #374151 !important;
        }

        .products-section-beige .text-slate-500 {
            color: #6b7280 !important;
        }

        .products-section-beige .border-slate-600 {
            border-color: #d1d5db !important;
        }

        .products-section-beige .border-slate-700 {
            border-color: #e5e7eb !important;
        }

        .products-section-beige .bg-slate-900 {
            background-color: #fafaf9 !important;
            border: 1px solid #e5e7eb;
        }

        .products-section-beige .bg-slate-700 {
            background-color: #e5e7eb !important;
            color: #374151 !important;
        }

        .products-section-beige .bg-slate-700:hover {
            background-color: #d1d5db !important;
        }

        .products-section-beige .hover\:bg-slate-700:hover {
            background-color: #f3f4f6 !important;
        }

        .products-section-beige .product-card {
            background-color: #ffffff !important;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .products-section-beige .product-card:hover {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border-color: #ea580c;
            /* orange kle */
        }

        .products-section-beige .text-blue-400 {
            color: #c2410c !important;
            /* brick */
        }

        .products-section-beige .text-red-400 {
            color: #dc2626 !important;
        }

        .products-section-beige .text-emerald-500 {
            color: #059669 !important;
        }

        .products-section-beige h2,
        .products-section-beige h3,
        .products-section-beige h4 {
            color: #111827 !important;
        }

        .products-section-beige p {
            color: #4b5563 !important;
        }

        .products-section-beige .text-gray-600 {
            color: #4b5563 !important;
        }

        .products-section-beige .bg-primary {
            background-color: #c2410c !important;
            /* brick */
        }

        .products-section-beige .bg-primary:hover {
            background-color: #9a3412 !important;
            /* brick fonse */
        }

        /* Sidebar sticky nan beige section */
        .products-section-beige aside .bg-slate-850 {
            background-color: #ffffff !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        /* NOUVO HEADER STYLE - FLAMME PAL (BRICK/TERACOTTA) */
        .main-header {
            background: linear-gradient(135deg, #c14802ff 0%, #c14802ff, #c14802ff);
            /* brick fonse -> brick kle */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .main-header a {
            color: #ffffffff;
            /* orange tre kle */
            font-weight: 500;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .main-header a:hover {
            color: #ffffffff;
            /* orange kle */
        }

        .main-header .nav-link {
            color: #ffac91ff;
            /* orange mwayen */
            transition: all 0.3s ease;
            position: relative;
        }

        .main-header .nav-link:hover {
            color: #ffffff;
        }

        .main-header .nav-link.active {
            color: #9a3412;
            /* orange kle */
            font-weight: 600;
        }

        .main-header .nav-link::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 0;
            height: 2px;
            background-color: #9a3412;
            /* orange kle */
            transition: width 0.3s ease;
        }

        .main-header .nav-link:hover::after {
            width: 100%;
        }

        .main-header button,
        .main-header .icon-btn {
            color: #fed7aa;
            /* orange mwayen */
            transition: all 0.3s ease;
        }

        .main-header button:hover,
        .main-header .icon-btn:hover {
            color: #ffffff;
            background-color: rgba(251, 146, 60, 0.2);
            /* orange kle transparent */
        }

        .main-header .cart-badge {
            background-color: #ea580c;
            /* orange kle */
            color: white;
            font-weight: 700;
        }

        /* NOUVO FOOTER STYLE - FLAMME PAL (BRICK/TERACOTTA) */
        .main-footer-new {
            background: linear-gradient(135deg, #431407 0%, #7c2d12 50%, #9a3412 100%);
            /* brick tre fonse -> brick fonse -> brick mwayen */
            color: #fed7aa;
            /* orange mwayen */
            padding: 4rem 0 2rem;
            border-top: 4px solid #ea580c;
            /* orange kle */
        }

        .main-footer-new .footer-logo-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #ea580c 0%, #fb923c 100%);
            /* orange kle */
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
        }

        .main-footer-new .footer-logo-text {
            color: #ffffff;
            font-weight: 800;
            font-size: 1.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .main-footer-new h4 {
            color: #ffffff;
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .main-footer-new h4::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 3px;
            background: linear-gradient(90deg, #fb923c, #ea580c);
            /* orange */
            border-radius: 2px;
        }

        .main-footer-new p,
        .main-footer-new .footer-description {
            color: #fdba74;
            /* orange kle */
            line-height: 1.7;
        }

        .main-footer-new .footer-links a {
            color: #fdba74;
            /* orange kle */
            text-decoration: none;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: inline-block;
            padding: 0.25rem 0;
        }

        .main-footer-new .footer-links a:hover {
            color: #ffffff;
            transform: translateX(5px);
        }

        .main-footer-new .contact-info li {
            color: #fdba74;
            /* orange kle */
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .main-footer-new .contact-info i {
            color: #fb923c;
            /* orange kle */
            margin-top: 0.25rem;
            font-size: 1.1rem;
        }

        .main-footer-new .social-links a {
            width: 42px;
            height: 42px;
            background: rgba(251, 146, 60, 0.15);
            /* orange kle transparent */
            border: 2px solid rgba(251, 146, 60, 0.3);
            /* orange kle */
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }

        .main-footer-new .social-links a:hover {
            background: #ea580c;
            /* orange kle */
            border-color: #fb923c;
            /* orange kle */
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(234, 88, 12, 0.4);
            /* orange shadow */
        }

        .main-footer-new .footer-bottom {
            border-top: 1px solid rgba(251, 146, 60, 0.2);
            /* orange kle transparent */
            margin-top: 3rem;
            padding-top: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        @media (min-width: 640px) {
            .main-footer-new .footer-bottom {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
        }

        .main-footer-new .copyright {
            color: #fb923c;
            /* orange kle */
            font-size: 0.9rem;
        }

        .main-footer-new .footer-selector {
            background: rgba(251, 146, 60, 0.15);
            /* orange kle transparent */
            color: #fed7aa;
            /* orange mwayen */
            border: 1px solid rgba(251, 146, 60, 0.3);
            /* orange kle */
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .main-footer-new .footer-selector:hover {
            background: rgba(251, 146, 60, 0.25);
            /* orange kle transparent */
            color: #ffffff;
        }

        /* Features section FLAMME PAL */
        .features-section-new {
            background: linear-gradient(135deg, #7c2d12 0%, #c2410c 100%);
            /* brick fonse -> brick */
            padding: 3rem 0;
            border-bottom: 1px solid rgba(251, 146, 60, 0.2);
            /* orange kle transparent */
        }

        .features-section-new .feature-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .features-section-new .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
            border-color: #ea580c;
            /* orange kle */
        }

        .features-section-new .feature-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
            /* orange -> brick */
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 4px 6px -1px rgba(234, 88, 12, 0.4);
            /* orange shadow */
        }

        .features-section-new .feature-icon i {
            color: #ffffff;
            font-size: 1.5rem;
        }

        .features-section-new .feature-content h3 {
            color: #7c2d12;
            /* brick fonse */
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }

        .features-section-new .feature-content p {
            color: #4b5563;
            font-size: 0.9rem;
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

        .badge-bounce {
            animation: bounce 0.5s ease;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.2);
            }
        }

        /* MODIFICATION POU PRODUIT GRID - 2 kolòn sou mobil */
        .products-grid-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }

        @media (min-width: 640px) {
            .products-grid-container {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
        }

        @media (min-width: 768px) {
            .products-grid-container {
                grid-template-columns: repeat(2, 1fr);
                gap: 1.25rem;
            }
        }

        @media (min-width: 1024px) {
            .products-grid-container {
                grid-template-columns: repeat(3, 1fr);
                gap: 1.5rem;
            }
        }

        /* Ajusteman pou kart pwodwi sou mobil */
        @media (max-width: 639px) {
            .product-card .h-56 {
                height: 140px;
            }

            .product-card h3 {
                font-size: 0.875rem;
                height: 2.4em;
            }

            .product-card .text-xl {
                font-size: 1rem;
            }

            .product-card .text-sm {
                font-size: 0.75rem;
            }

            .product-card .p-5 {
                padding: 0.75rem;
            }

            .product-card button {
                padding: 0.5rem 0.75rem;
            }
        }

        /* Search Bar FLAMME PAL */
        .bg-slate-850 {
            background-color: #fafaf9 !important;
        }

        .border-slate-700 {
            border-color: #fed7aa !important;
            /* orange mwayen */
        }

        .bg-slate-900 {
            background-color: #fff7ed !important;
            /* orange tre tre kle */
        }

        .text-gray-100 {
            color: #431407 !important;
            /* brick tre fonse */
        }

        .placeholder-gray-500::placeholder {
            color: #9a3412;
            /* brick */
        }

        .focus\:ring-blue-500:focus {
            --tw-ring-color: #ea580c !important;
            /* orange kle */
        }

        .bg-primary {
            background-color: #c2410c !important;
            /* brick */
        }

        .bg-primary:hover {
            background-color: #9a3412 !important;
            /* brick fonse */
        }

        .bg-slate-700 {
            background-color: #ffedd5 !important;
            /* orange tre kle */
            color: #7c2d12 !important;
            /* brick fonse */
        }

        .bg-slate-700:hover {
            background-color: #fed7aa !important;
            /* orange mwayen */
        }

        /* Pagination FLAMME PAL */
        .hover\:bg-gray-100:hover {
            background-color: #fff7ed !important;
            /* orange tre tre kle */
        }

        .bg-blue-600 {
            background-color: #c2410c !important;
            /* brick */
        }

        .border-blue-600 {
            border-color: #c2410c !important;
            /* brick */
        }

        .hover\:bg-blue-700:hover {
            background-color: #9a3412 !important;
            /* brick fonse */
        }

        /* Quick View Modal FLAMME PAL */
        #quickViewModal {
            background-color: rgba(67, 20, 7, 0.8) !important;
            /* brick tre fonse transparent */
        }

        .text-blue-600 {
            color: #c2410c !important;
            /* brick */
        }

        .hover\:text-blue-800:hover {
            color: #7c2d12 !important;
            /* brick fonse */
        }

        .bg-blue-600 {
            background-color: #c2410c !important;
            /* brick */
        }

        .hover\:bg-blue-700:hover {
            background-color: #9a3412 !important;
            /* brick fonse */
        }

        /* Notification FLAMME PAL */
        .bg-emerald-100 {
            background-color: #fff7ed !important;
            /* orange tre tre kle */
        }

        .text-emerald-800 {
            color: #9a3412 !important;
            /* brick */
        }

        .border-emerald-300 {
            border-color: #fb923c !important;
            /* orange kle */
        }

        /* Mobile filters FLAMME PAL */
        #mobileFilters .bg-white {
            background-color: #fafaf9 !important;
        }

        #mobileFilters .border-gray-200 {
            border-color: #fed7aa !important;
            /* orange mwayen */
        }

        #mobileFilters .text-gray-800 {
            color: #431407 !important;
            /* brick tre fonse */
        }

        #mobileFilters .hover\:bg-gray-100:hover {
            background-color: #fff7ed !important;
            /* orange tre tre kle */
        }

        #mobileFilters .bg-blue-600 {
            background-color: #c2410c !important;
            /* brick */
        }

        #mobileFilters .text-gray-600 {
            color: #7c2d12 !important;
            /* brick fonse */
        }

        #mobileFilters .hover\:text-gray-600:hover {
            color: #9a3412 !important;
            /* brick */
        }
    </style>
</head>

<body class="bg-slate-900 text-slate-200 font-sans antialiased">
    <!-- Header - NOUVO STYLE FLAMME PAL -->
    <header class="main-header z-[100]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20 sm:h-24 lg:h-28">
                <!-- Logo - GWO E RESPONSIVE -->
                <a href="accueil.php" class="logo-container flex-shrink-0">
                    <!-- Ranplase src la ak logo antrepriz ou a -->
                    <img src="\le-stock\assets\img\le stock entreprise copy2.png"
                        alt="Logo Antrepriz"
                        class="logo-image"
                        style="filter: brightness(0) invert(1);"
                        onerror="this.src='https://via.placeholder.com/200x80/ffffff/7c2d12?text=LE-STOCK'; this.style.backgroundColor='#7c2d12'; this.style.padding='10px'; this.style.borderRadius='8px'; this.style.filter='none';">
                </a>

                <!-- Navigation Desktop -->
                <nav class="hidden lg:flex items-center gap-8">
                    <a href="../index.php" class="nav-link text-sm">Akèy</a>
                    <a href="promotion.php" class="nav-link text-sm">Promosyons</a>
                    <a href="hot_deal.php" class="nav-link text-sm">Hot-Deal</a>

                </nav>

                <!-- Icons -->
                <div class="flex items-center gap-2 md:gap-4">
                    <!-- <button onclick="focusSearch()" class="icon-btn p-2 rounded-full transition-colors" title="Chache">
                        <i class="fas fa-search text-lg"></i>
                    </button>
                    <a href="favoris.php" class="icon-btn p-2 rounded-full transition-colors relative" title="Favori">
                        <i class="fas fa-heart text-lg"></i>
                    </a> -->
                    <a href="panier/Panier.php" class="icon-btn p-2 rounded-full transition-colors relative" title="Panier">
                        <i class="fas fa-shopping-cart text-lg"></i>
                        <span id="cart-badge" class="cart-badge absolute -top-1 -right-1 text-xs font-bold px-1.5 py-0.5 rounded-full">0</span>
                    </a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="profile.php" class="icon-btn p-2 rounded-full transition-colors" title="Pwofil">
                            <i class="fas fa-user text-lg"></i>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="icon-btn p-2 rounded-full transition-colors" title="Konekte">
                            <i class="fas fa-sign-in-alt text-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- NOUVO KARYSEL VIDEO - Ranplase Hero Section -->
    <section class="video-carousel-section">
        <?php foreach ($hero_videos as $index => $video): ?>
            <div class="video-slide <?= $index === 0 ? 'active' : '' ?>" data-index="<?= $index ?>">
                <video
                    muted
                    loop
                    playsinline
                    poster="<?= htmlspecialchars($video['poster']) ?>"
                    preload="metadata">
                    <source src="<?= htmlspecialchars($video['src']) ?>" type="video/mp4">
                    Navigatè ou a pa sipòte videyo.
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

        <!-- Progress Bar -->
        <div class="carousel-progress" id="progressBar"></div>

        <!-- Controls -->
        <button class="carousel-arrow prev" onclick="prevSlide()" aria-label="Videyo anvan">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button class="carousel-arrow next" onclick="nextSlide()" aria-label="Videyo pwochen">
            <i class="fas fa-chevron-right"></i>
        </button>

        <!-- Dots -->
        <div class="carousel-controls">
            <?php foreach ($hero_videos as $index => $video): ?>
                <div class="carousel-dot <?= $index === 0 ? 'active' : '' ?>" onclick="goToSlide(<?= $index ?>)" data-index="<?= $index ?>"></div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Search Bar -->
    <div class="bg-slate-850 border-b border-slate-700 py-6 px-4">
        <div class="max-w-7xl mx-auto">
            <form method="GET" class="flex flex-col sm:flex-row items-center gap-4">
                <div class="flex-1 relative w-full">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="search" id="searchInput"
                        placeholder="Chache pwodwi pa non..."
                        value="<?= htmlspecialchars($search_query) ?>"
                        class="w-full pl-12 pr-4 py-3 border border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-slate-900 text-gray-100 placeholder-gray-500 transition-all">
                </div>
                <div class="flex gap-2 w-full sm:w-auto">
                    <button type="submit" class="flex-1 sm:flex-none px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                        <i class="fas fa-search mr-2"></i>Chache
                    </button>
                    <button type="button" onclick="toggleFilters()" class="flex-1 sm:flex-none px-6 py-3 bg-slate-700 text-gray-200 rounded-lg hover:bg-slate-600 transition-colors font-medium lg:hidden">
                        <i class="fas fa-sliders-h mr-2"></i>Filtre
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Main Content - AK BACKGROUND BEIGE -->
    <main class="products-section-beige">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex gap-8">
                <!-- Sidebar Filters - Desktop -->
                <aside class="w-80 flex-shrink-0 hidden lg:block">
                    <div class="bg-slate-850 rounded-lg shadow-lg p-6 sticky top-20">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="font-semibold text-lg">Filtre</h2>
                            <a href="?" class="text-sm text-blue-600 hover:underline font-medium">Reyinisyalize tout</a>
                        </div>

                        <div class="space-y-6">
                            <!-- Category Filter -->
                            <div>
                                <h3 class="text-sm font-semibold mb-3 uppercase tracking-wide text-gray-600">Kategori</h3>
                                <div class="space-y-2 max-h-48 overflow-y-auto pr-2">
                                    <button onclick="window.location.href='?category=All<?= $search_query ? '&search=' . urlencode($search_query) : '' ?>'"
                                        class="w-full text-left px-3 py-2 rounded-md text-sm transition-all duration-200 <?= $selected_category === 'All' ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                                        Tout Kategori
                                    </button>
                                    <?php foreach ($all_categories as $cat): ?>
                                        <button onclick="window.location.href='?category=<?= urlencode($cat['name']) ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>'"
                                            class="w-full text-left px-3 py-2 rounded-md text-sm transition-all duration-200 <?= $selected_category === $cat['name'] ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Price Range -->
                            <div>
                                <h3 class="text-sm font-semibold mb-3 uppercase tracking-wide text-gray-600">Prix</h3>
                                <div class="space-y-2">
                                    <button onclick="setPriceRange(0, 999999)" class="w-full text-left px-3 py-2 rounded-md text-sm transition-all duration-200 <?= ($price_min == 0 && $price_max == 999999) ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100' ?>">Tout Prix</button>
                                    <button onclick="setPriceRange(0, 1000)" class="w-full text-left px-3 py-2 rounded-md text-sm transition-all duration-200 <?= ($price_min == 0 && $price_max == 1000) ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100' ?>">Anba 1,000 HTG</button>
                                    <button onclick="setPriceRange(1000, 5000)" class="w-full text-left px-3 py-2 rounded-md text-sm transition-all duration-200 <?= ($price_min == 1000 && $price_max == 5000) ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100' ?>">1,000 - 5,000 HTG</button>
                                    <button onclick="setPriceRange(5000, 10000)" class="w-full text-left px-3 py-2 rounded-md text-sm transition-all duration-200 <?= ($price_min == 5000 && $price_max == 10000) ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100' ?>">5,000 - 10,000 HTG</button>
                                    <button onclick="setPriceRange(10000, 999999)" class="w-full text-left px-3 py-2 rounded-md text-sm transition-all duration-200 <?= ($price_min == 10000) ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100' ?>">Pi wo 10,000 HTG</button>
                                </div>
                            </div>

                            <!-- Color Filter -->
                            <?php if ($has_color && !empty($all_colors)): ?>
                                <div>
                                    <h3 class="text-sm font-semibold mb-3 uppercase tracking-wide text-gray-600">Koulè</h3>
                                    <select onchange="updateFilter('color', this.value)" class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-white text-gray-800">
                                        <option value="All" <?= $selected_color === 'All' ? 'selected' : '' ?>>Tout Koulè</option>
                                        <?php foreach ($all_colors as $color): ?>
                                            <option value="<?= htmlspecialchars($color) ?>" <?= $selected_color === $color ? 'selected' : '' ?>><?= htmlspecialchars($color) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <!-- Size Filter -->
                            <?php if ($has_size && !empty($all_sizes)): ?>
                                <div>
                                    <h3 class="text-sm font-semibold mb-3 uppercase tracking-wide text-gray-600">Tail</h3>
                                    <select onchange="updateFilter('size', this.value)" class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-white text-gray-800">
                                        <option value="All" <?= $selected_size === 'All' ? 'selected' : '' ?>>Tout Tail</option>
                                        <?php foreach ($all_sizes as $size): ?>
                                            <option value="<?= htmlspecialchars($size) ?>" <?= $selected_size === $size ? 'selected' : '' ?>><?= htmlspecialchars($size) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <!-- Brand Filter -->
                            <?php if ($has_brand && !empty($all_brands)): ?>
                                <div>
                                    <h3 class="text-sm font-semibold mb-3 uppercase tracking-wide text-gray-600">Mak</h3>
                                    <select onchange="updateFilter('brand', this.value)" class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-white text-gray-800">
                                        <option value="All" <?= $selected_brand === 'All' ? 'selected' : '' ?>>Tout Mak</option>
                                        <?php foreach ($all_brands as $brand): ?>
                                            <option value="<?= htmlspecialchars($brand) ?>" <?= $selected_brand === $brand ? 'selected' : '' ?>><?= htmlspecialchars($brand) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </aside>

                <!-- Mobile Filters Overlay -->
                <div id="mobileFilters" class="fixed inset-0 z-[200] bg-black/50 hidden lg:hidden" onclick="toggleFilters()">
                    <div class="relative h-full overflow-y-auto w-4/5 max-w-xs bg-white" onclick="event.stopPropagation()">
                        <div class="flex p-4 border-b border-gray-200 justify-between items-center">
                            <h2 class="font-semibold text-lg text-gray-800">Filtre</h2>
                            <button onclick="toggleFilters()" class="p-2 hover:bg-gray-100 rounded-full text-gray-600">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <div class="p-6 space-y-6">
                            <!-- Category Filter Mobile -->
                            <div>
                                <h3 class="text-sm font-semibold mb-3 uppercase tracking-wide text-gray-600">Kategori</h3>
                                <div class="space-y-2 max-h-48 overflow-y-auto">
                                    <button onclick="window.location.href='?category=All<?= $search_query ? '&search=' . urlencode($search_query) : '' ?>'"
                                        class="w-full text-left px-3 py-2 rounded-md text-sm transition-all duration-200 <?= $selected_category === 'All' ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                                        Tout Kategori
                                    </button>
                                    <?php foreach ($all_categories as $cat): ?>
                                        <button onclick="window.location.href='?category=<?= urlencode($cat['name']) ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>'"
                                            class="w-full text-left px-3 py-2 rounded-md text-sm transition-all duration-200 <?= $selected_category === $cat['name'] ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Price Range Mobile -->
                            <div>
                                <h3 class="text-sm font-semibold mb-3 uppercase tracking-wide text-gray-600">Prix</h3>
                                <div class="space-y-2">
                                    <button onclick="setPriceRange(0, 999999)" class="w-full text-left px-3 py-2 rounded-md text-sm transition-all duration-200 <?= ($price_min == 0 && $price_max == 999999) ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100' ?>">Tout Prix</button>
                                    <button onclick="setPriceRange(0, 1000)" class="w-full text-left px-3 py-2 rounded-md text-sm transition-all duration-200 <?= ($price_min == 0 && $price_max == 1000) ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100' ?>">Anba 1,000 HTG</button>
                                    <button onclick="setPriceRange(1000, 5000)" class="w-full text-left px-3 py-2 rounded-md text-sm transition-all duration-200 <?= ($price_min == 1000 && $price_max == 5000) ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100' ?>">1,000 - 5,000 HTG</button>
                                    <button onclick="setPriceRange(5000, 10000)" class="w-full text-left px-3 py-2 rounded-md text-sm transition-all duration-200 <?= ($price_min == 5000 && $price_max == 10000) ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100' ?>">5,000 - 10,000 HTG</button>
                                    <button onclick="setPriceRange(10000, 999999)" class="w-full text-left px-3 py-2 rounded-md text-sm transition-all duration-200 <?= ($price_min == 10000) ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100' ?>">Pi wo 10,000 HTG</button>
                                </div>
                            </div>

                            <!-- Color Filter Mobile -->
                            <?php if ($has_color && !empty($all_colors)): ?>
                                <div>
                                    <h3 class="text-sm font-semibold mb-3 uppercase tracking-wide text-gray-600">Koulè</h3>
                                    <select onchange="updateFilter('color', this.value)" class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-white text-gray-800">
                                        <option value="All" <?= $selected_color === 'All' ? 'selected' : '' ?>>Tout Koulè</option>
                                        <?php foreach ($all_colors as $color): ?>
                                            <option value="<?= htmlspecialchars($color) ?>" <?= $selected_color === $color ? 'selected' : '' ?>><?= htmlspecialchars($color) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <!-- Size Filter Mobile -->
                            <?php if ($has_size && !empty($all_sizes)): ?>
                                <div>
                                    <h3 class="text-sm font-semibold mb-3 uppercase tracking-wide text-gray-600">Tail</h3>
                                    <select onchange="updateFilter('size', this.value)" class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-white text-gray-800">
                                        <option value="All" <?= $selected_size === 'All' ? 'selected' : '' ?>>Tout Tail</option>
                                        <?php foreach ($all_sizes as $size): ?>
                                            <option value="<?= htmlspecialchars($size) ?>" <?= $selected_size === $size ? 'selected' : '' ?>><?= htmlspecialchars($size) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <!-- Brand Filter Mobile -->
                            <?php if ($has_brand && !empty($all_brands)): ?>
                                <div>
                                    <h3 class="text-sm font-semibold mb-3 uppercase tracking-wide text-gray-600">Mak</h3>
                                    <select onchange="updateFilter('brand', this.value)" class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-white text-gray-800">
                                        <option value="All" <?= $selected_brand === 'All' ? 'selected' : '' ?>>Tout Mak</option>
                                        <?php foreach ($all_brands as $brand): ?>
                                            <option value="<?= htmlspecialchars($brand) ?>" <?= $selected_brand === $brand ? 'selected' : '' ?>><?= htmlspecialchars($brand) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Products Section -->
                <div class="flex-1">
                    <!-- Toolbar -->
                    <div class="bg-white rounded-lg shadow-lg p-4 mb-6 flex items-center justify-between flex-wrap gap-4 border border-gray-200">
                        <div class="flex items-center gap-4">
                            <p class="text-sm text-gray-600">
                                Afiche <span class="font-semibold text-gray-900"><?= $start_index + 1 ?></span>-
                                <span class="font-semibold text-gray-900"><?= min($start_index + $items_per_page, $total_products) ?></span> nan
                                <span class="font-semibold text-gray-900"><?= $total_products ?></span> pwodwi
                            </p>
                        </div>

                        <div class="flex items-center gap-4">
                            <!-- View Toggle -->
                            <div class="hidden sm:flex items-center gap-2 border border-gray-300 rounded-lg p-1 bg-gray-50">
                                <button onclick="setViewMode('grid')" class="p-2 rounded-md transition-all duration-200 <?= $view_mode === 'grid' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-200' ?>" title="Glay">
                                    <i class="fas fa-th"></i>
                                </button>
                                <button onclick="setViewMode('list')" class="p-2 rounded-md transition-all duration-200 <?= $view_mode === 'list' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-200' ?>" title="Lis">
                                    <i class="fas fa-list"></i>
                                </button>
                            </div>

                            <!-- Sort -->
                            <select onchange="window.location.href='?sort='+this.value<?= $selected_category !== 'All' ? "+'&category=" . urlencode($selected_category) . "'" : "''" ?><?= $search_query ? "+'&search=" . urlencode($search_query) . "'" : "''" ?>" class="px-4 py-2 border border-gray-300 rounded-lg bg-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-gray-800">
                                <option value="featured" <?= $sort_by === 'featured' ? 'selected' : '' ?>>En Vitrine</option>
                                <option value="newest" <?= $sort_by === 'newest' ? 'selected' : '' ?>>Pi Nouvo</option>
                                <option value="price-low" <?= $sort_by === 'price-low' ? 'selected' : '' ?>>Prix: Ki pi ba</option>
                                <option value="price-high" <?= $sort_by === 'price-high' ? 'selected' : '' ?>>Prix: Wo</option>
                                <option value="name-asc" <?= $sort_by === 'name-asc' ? 'selected' : '' ?>>Non: A-Z</option>
                                <option value="name-desc" <?= $sort_by === 'name-desc' ? 'selected' : '' ?>>Non: Z-A</option>
                            </select>
                        </div>
                    </div>

                    <!-- Products Grid/List -->
                    <?php if (count($current_products) === 0): ?>
                        <div class="bg-white rounded-lg shadow-lg p-12 text-center border border-gray-200">
                            <i class="fas fa-search text-6xl text-gray-400 mb-4"></i>
                            <h3 class="text-xl font-semibold mb-2 text-gray-900">Pa gen pwodwi</h3>
                            <p class="text-gray-600 mb-6">Eseye modifye filtè ou yo oswa rechèch ou a</p>
                            <a href="?" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors inline-block font-medium">
                                Reyinisyalize Filtre
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Grid View -->
                        <?php if ($view_mode === 'grid'): ?>
                            <div class="products-grid-container">
                                <?php foreach ($current_products as $product): ?>
                                    <?php $is_promo = isPromo($product); ?>
                                    <div class="product-card bg-white rounded-xl overflow-hidden shadow-lg transition-all duration-300 relative border border-gray-200 hover:-translate-y-2 hover:shadow-2xl hover:border-blue-500 group">
                                        <!-- Image -->
                                        <div class="h-56 relative overflow-hidden bg-gray-100">
                                            <img src="../uploads/products/<?= htmlspecialchars($product['image'] ?? 'placeholder.png') ?>"
                                                alt="<?= htmlspecialchars($product['name']) ?>"
                                                loading="lazy"
                                                onerror="this.src='../assets/img/placeholder.png'"
                                                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">

                                            <!-- Badges -->
                                            <?php if ($is_promo): ?>
                                                <div class="absolute top-4 left-4 flex flex-col gap-2">
                                                    <span class="bg-red-500 text-white px-3.5 py-1 rounded-full text-xs font-bold">-<?= getDiscountPercent($product) ?>%</span>
                                                </div>
                                            <?php elseif (isset($product['created_at']) && strtotime($product['created_at']) > strtotime('-7 days')): ?>
                                                <div class="absolute top-4 left-4 flex flex-col gap-2">
                                                    <span class="bg-emerald-500 text-white px-3.5 py-1 rounded-full text-xs font-bold">NOUVO</span>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Actions -->
                                            <div class="absolute top-4 right-4 flex flex-col gap-2 opacity-0 translate-x-2.5 transition-all duration-300 group-hover:opacity-100 group-hover:translate-x-0">
                                                <button onclick="addToFavorites(<?= $product['id'] ?>)" class="w-10 h-10 rounded-full bg-white border border-gray-300 text-gray-600 flex items-center justify-center shadow-lg transition-all duration-200 hover:bg-blue-600 hover:text-white hover:border-blue-600" title="Ajoute nan favori">
                                                    <i class="fas fa-heart"></i>
                                                </button>
                                                <button onclick="quickView(<?= $product['id'] ?>)" class="w-10 h-10 rounded-full bg-white border border-gray-300 text-gray-600 flex items-center justify-center shadow-lg transition-all duration-200 hover:bg-blue-600 hover:text-white hover:border-blue-600" title="Gade rapid">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Info -->
                                        <div class="p-5">
                                            <div class="text-xs text-blue-600 font-semibold uppercase tracking-wide mb-2"><?= htmlspecialchars($product['category_name']) ?></div>
                                            <h3 class="text-base font-bold text-gray-900 mb-3 leading-snug line-clamp-2" style="height: 2.8em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;"><?= htmlspecialchars(truncate($product['name'], 40)) ?></h3>
                                            <div class="flex justify-between items-center">
                                                <div class="flex flex-col">
                                                    <?php if ($is_promo): ?>
                                                        <span class="text-xl font-extrabold text-red-600"><?= number_format($product['price_promo']) ?> HTG</span>
                                                        <span class="text-sm text-gray-500 line-through"><?= number_format($product['price']) ?> HTG</span>
                                                    <?php else: ?>
                                                        <span class="text-xl font-extrabold text-gray-900"><?= number_format($product['price']) ?> HTG</span>
                                                    <?php endif; ?>
                                                </div>
                                                <button onclick="addToCart(<?= $product['id'] ?>, this)" class="bg-blue-600 text-white border-0 px-5 py-3 rounded-xl font-semibold flex items-center gap-2 transition-all duration-200 hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed">
                                                    <i class="fas fa-cart-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- List View -->
                        <?php else: ?>
                            <div class="flex flex-col gap-4">
                                <?php foreach ($current_products as $product): ?>
                                    <?php $is_promo = isPromo($product); ?>
                                    <div class="bg-white rounded-xl overflow-hidden shadow-lg transition-all duration-300 relative border border-gray-200 hover:border-blue-500 flex flex-col sm:flex-row">
                                        <!-- Image -->
                                        <div class="w-full sm:w-48 h-48 sm:h-auto relative overflow-hidden bg-gray-100 flex-shrink-0">
                                            <img src="../uploads/products/<?= htmlspecialchars($product['image'] ?? 'placeholder.png') ?>"
                                                alt="<?= htmlspecialchars($product['name']) ?>"
                                                loading="lazy"
                                                onerror="this.src='../assets/img/placeholder.png'"
                                                class="w-full h-full object-cover">

                                            <!-- Badges -->
                                            <?php if ($is_promo): ?>
                                                <div class="absolute top-4 left-4">
                                                    <span class="bg-red-500 text-white px-3.5 py-1 rounded-full text-xs font-bold">-<?= getDiscountPercent($product) ?>%</span>
                                                </div>
                                            <?php elseif (isset($product['created_at']) && strtotime($product['created_at']) > strtotime('-7 days')): ?>
                                                <div class="absolute top-4 left-4">
                                                    <span class="bg-emerald-500 text-white px-3.5 py-1 rounded-full text-xs font-bold">NOUVO</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Info -->
                                        <div class="p-5 flex-1 flex flex-col justify-between">
                                            <div>
                                                <div class="text-xs text-blue-600 font-semibold uppercase tracking-wide mb-2"><?= htmlspecialchars($product['category_name']) ?></div>
                                                <h3 class="text-lg font-bold text-gray-900 mb-2"><?= htmlspecialchars($product['name']) ?></h3>
                                                <p class="text-gray-600 text-sm mb-4 line-clamp-2"><?= htmlspecialchars(truncate($product['description'] ?? '', 100)) ?></p>
                                            </div>

                                            <div class="flex justify-between items-center">
                                                <div class="flex flex-col">
                                                    <?php if ($is_promo): ?>
                                                        <span class="text-2xl font-extrabold text-red-600"><?= number_format($product['price_promo']) ?> HTG</span>
                                                        <span class="text-sm text-gray-500 line-through"><?= number_format($product['price']) ?> HTG</span>
                                                    <?php else: ?>
                                                        <span class="text-2xl font-extrabold text-gray-900"><?= number_format($product['price']) ?> HTG</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex gap-2">
                                                    <button onclick="addToFavorites(<?= $product['id'] ?>)" class="w-10 h-10 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center transition-all duration-200 hover:bg-blue-600 hover:text-white" title="Ajoute nan favori">
                                                        <i class="fas fa-heart"></i>
                                                    </button>
                                                    <button onclick="quickView(<?= $product['id'] ?>)" class="w-10 h-10 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center transition-all duration-200 hover:bg-blue-600 hover:text-white" title="Gade rapid">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button onclick="addToCart(<?= $product['id'] ?>, this)" class="bg-blue-600 text-white border-0 px-5 py-2 rounded-xl font-semibold flex items-center gap-2 transition-all duration-200 hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed">
                                                        <i class="fas fa-cart-plus mr-1"></i> Ajoute
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="mt-8 flex items-center justify-center gap-2 flex-wrap">
                                <?php
                                $base_url = '?';
                                if ($selected_category !== 'All') $base_url .= 'category=' . urlencode($selected_category) . '&';
                                if ($search_query) $base_url .= 'search=' . urlencode($search_query) . '&';
                                if ($sort_by !== 'featured') $base_url .= 'sort=' . $sort_by . '&';
                                if ($view_mode !== 'grid') $base_url .= 'view=' . $view_mode . '&';
                                ?>

                                <a href="<?= $base_url ?>page=<?= max(1, $page - 1) ?>"
                                    class="px-4 py-2 border border-gray-300 rounded-lg transition-all duration-200 text-gray-700 bg-white hover:bg-gray-100 <?= $page === 1 ? 'opacity-50 cursor-not-allowed pointer-events-none' : '' ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>

                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);

                                if ($start_page > 1): ?>
                                    <a href="<?= $base_url ?>page=1" class="px-4 py-2 border border-gray-300 rounded-lg transition-all duration-200 text-gray-700 bg-white hover:bg-gray-100">1</a>
                                    <?php if ($start_page > 2): ?>
                                        <span class="px-2 text-gray-500">...</span>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <a href="<?= $base_url ?>page=<?= $i ?>" class="px-4 py-2 border rounded-lg transition-all duration-200 <?= $page === $i ? 'bg-blue-600 text-white border-blue-600' : 'text-gray-700 bg-white border-gray-300 hover:bg-gray-100' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($end_page < $total_pages): ?>
                                    <?php if ($end_page < $total_pages - 1): ?>
                                        <span class="px-2 text-gray-500">...</span>
                                    <?php endif; ?>
                                    <a href="<?= $base_url ?>page=<?= $total_pages ?>" class="px-4 py-2 border border-gray-300 rounded-lg transition-all duration-200 text-gray-700 bg-white hover:bg-gray-100"><?= $total_pages ?></a>
                                <?php endif; ?>

                                <a href="<?= $base_url ?>page=<?= min($total_pages, $page + 1) ?>"
                                    class="px-4 py-2 border border-gray-300 rounded-lg transition-all duration-200 text-gray-700 bg-white hover:bg-gray-100 <?= $page === $total_pages ? 'opacity-50 cursor-not-allowed pointer-events-none' : '' ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- NOUVO FOOTER - FLAMME PAL -->
    <!-- Features Section -->
    <section class="features-section-new">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Free Shipping -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-cube"></i>
                    </div>
                    <div class="feature-content">
                        <h3>Free Shipping</h3>
                        <p>Free shipping for order above $180</p>
                    </div>
                </div>

                <!-- Flexible Payment -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="feature-content">
                        <h3>Flexible Payment</h3>
                        <p>Multiple secure payment options</p>
                    </div>
                </div>

                <!-- 24×7 Support -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div class="feature-content">
                        <h3>24×7 Support</h3>
                        <p>We support online all days</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Footer -->
    <footer class="main-footer-new">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Column 1 - Logo & About -->
                <div>
                    <div class="footer-logo" style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                        <div class="footer-logo-icon">L</div>
                        <span class="footer-logo-text">LE-STOCK.</span>
                    </div>
                    <p class="footer-description">
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
                    </p>
                    <div class="social-links" style="display: flex; gap: 0.75rem;">
                        <a href="#" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" aria-label="YouTube">
                            <i class="fab fa-youtube"></i>
                        </a>
                        <a href="#" aria-label="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                    </div>
                </div>

                <!-- Column 2 - Company -->
                <div>
                    <h4>Company</h4>
                    <ul class="footer-links" style="list-style: none; padding: 0; margin: 0;">
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Contact Us</a></li>
                        <li><a href="#">Career</a></li>
                    </ul>
                </div>

                <!-- Column 3 - Customer Services -->
                <div>
                    <h4>Customer Services</h4>
                    <ul class="footer-links" style="list-style: none; padding: 0; margin: 0;">
                        <li><a href="#">My Account</a></li>
                        <li><a href="#">Track Your Order</a></li>
                        <li><a href="#">Return</a></li>
                        <li><a href="#">FAQS</a></li>
                    </ul>
                </div>

                <!-- Column 4 - Contact Info -->
                <div>
                    <h4>Contact Info</h4>
                    <ul class="footer-links contact-info" style="list-style: none; padding: 0; margin: 0;">
                        <li>
                            <i class="fas fa-phone"></i>
                            <span>+0123-456-789</span>
                        </li>
                        <li>
                            <i class="fas fa-envelope"></i>
                            <span>example@gmail.com</span>
                        </li>
                        <li>
                            <i class="fas fa-map-marker-alt"></i>
                            <span>8502 Preston Rd.<br>Inglewood, Maine 98380</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <p class="copyright">Copyright © 2024 Clothing Website Design. All Rights Reserved.</p>
                <div class="footer-selectors" style="display: flex; gap: 1rem;">
                    <button class="footer-selector">
                        English <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <button class="footer-selector">
                        USD <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                </div>
            </div>
        </div>
    </footer>

    <!-- Quick View Modal -->
    <div id="quickViewModal" class="hidden fixed inset-0 bg-slate-900/80 z-[1000] items-center justify-center p-8" onclick="closeQuickViewOnOverlay(event)">
        <div class="bg-white rounded-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto relative border border-gray-200" onclick="event.stopPropagation()">
            <button onclick="closeQuickView()" class="absolute top-4 right-4 bg-transparent border-0 text-2xl cursor-pointer text-gray-400 z-10 w-10 h-10 flex items-center justify-center rounded-full transition-all duration-200 hover:bg-gray-100 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
            <div id="quickViewContent" class="p-6 md:p-8">
                <!-- Kontni pral chaje dinamikman -->
            </div>
        </div>
    </div>

    <script>
        // KAROUSEL VIDEO JAVASCRIPT
        let currentSlide = 0;
        const slides = document.querySelectorAll('.video-slide');
        const dots = document.querySelectorAll('.carousel-dot');
        const progressBar = document.getElementById('progressBar');
        const totalSlides = slides.length;
        let slideInterval;
        const slideDuration = 6000; // 6 segond pou chak videyo
        let progressInterval;

        // Fonksyon pou kòmanse videyo a
        function playVideo(index) {
            const video = slides[index].querySelector('video');
            if (video) {
                video.currentTime = 0;
                video.play().catch(e => console.log('Autoplay prevented:', e));
            }
        }

        // Fonksyon pou kanpe videyo a
        function pauseVideo(index) {
            const video = slides[index].querySelector('video');
            if (video) {
                video.pause();
            }
        }

        // Fonksyon pou montre yon slide espesifik
        function showSlide(index) {
            // Retire klas active nan tout slides
            slides.forEach((slide, i) => {
                slide.classList.remove('active');
                pauseVideo(i);
            });

            // Retire klas active nan tout dots
            dots.forEach(dot => dot.classList.remove('active'));

            // Ajoute klas active nan slide ak dot ki koresponn
            slides[index].classList.add('active');
            dots[index].classList.add('active');

            // Jwe videyo a
            playVideo(index);

            // Reyajiste progress bar
            resetProgressBar();
        }

        // Fonksyon pou slide pwochen
        function nextSlide() {
            currentSlide = (currentSlide + 1) % totalSlides;
            showSlide(currentSlide);
        }

        // Fonksyon pou slide anvan
        function prevSlide() {
            currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
            showSlide(currentSlide);
        }

        // Fonksyon pou ale nan yon slide espesifik
        function goToSlide(index) {
            currentSlide = index;
            showSlide(currentSlide);
            // Reyajiste entèval la
            clearInterval(slideInterval);
            slideInterval = setInterval(nextSlide, slideDuration);
        }

        // Progress bar animation
        function resetProgressBar() {
            progressBar.style.transition = 'none';
            progressBar.style.width = '0%';

            setTimeout(() => {
                progressBar.style.transition = `width ${slideDuration}ms linear`;
                progressBar.style.width = '100%';
            }, 50);
        }

        // Kòmanse karysel la
        function initCarousel() {
            // Jwe premye videyo a
            playVideo(0);

            // Kòmanse progress bar
            resetProgressBar();

            // Kòmanse entèval pou chanjman otomatik
            slideInterval = setInterval(nextSlide, slideDuration);
        }

        // Kanpe karysel la lè moun an nan paj la (pou ekonomize resous)
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                clearInterval(slideInterval);
                pauseVideo(currentSlide);
            } else {
                playVideo(currentSlide);
                slideInterval = setInterval(nextSlide, slideDuration);
                resetProgressBar();
            }
        });

        // Lè paj la fin chaje
        document.addEventListener('DOMContentLoaded', function() {
            initCarousel();
            updateCartBadge();
        });

        // Fonksyon pou montre/kache filtè yo (mobile)
        function toggleFilters() {
            const mobileFilters = document.getElementById('mobileFilters');
            const isHidden = mobileFilters.classList.contains('hidden');

            if (isHidden) {
                mobileFilters.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            } else {
                mobileFilters.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }
        }

        // Fonksyon pou mete focus sou rechèch
        function focusSearch() {
            document.getElementById('searchInput').focus();
        }

        // Fonksyon pou chanje view mode
        function setViewMode(mode) {
            const url = new URL(window.location.href);
            url.searchParams.set('view', mode);
            window.location.href = url.toString();
        }

        // Fonksyon pou mete paj pri
        function setPriceRange(min, max) {
            const url = new URL(window.location.href);
            url.searchParams.set('price_min', min);
            url.searchParams.set('price_max', max);
            window.location.href = url.toString();
        }

        // Fonksyon pou mete ajou filtè
        function updateFilter(type, value) {
            const url = new URL(window.location.href);
            url.searchParams.set(type, value);
            window.location.href = url.toString();
        }

        // Chaje kantite panier a
        function updateCartBadge() {
            fetch('panier/get_cart_count.php')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('cart-badge');
                    if (badge) {
                        const oldCount = parseInt(badge.textContent) || 0;
                        const newCount = data.count || 0;
                        badge.textContent = newCount;

                        if (newCount !== oldCount && oldCount !== 0) {
                            badge.classList.add('badge-bounce');
                            setTimeout(() => badge.classList.remove('badge-bounce'), 500);
                        }
                    }
                })
                .catch(error => console.error('Erè:', error));
        }

        // Ajoute nan panier
        function addToCart(productId, buttonElement) {
            if (buttonElement) {
                buttonElement.disabled = true;
                const originalContent = buttonElement.innerHTML;
                buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            }

            fetch('panier/add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'product_id=' + productId + '&qty=1'
                })
                .then(response => response.text())
                .then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Repons pa valide');
                    }
                })
                .then(data => {
                    if (data.success) {
                        updateCartBadge();
                        showNotification('Pwodwi ajoute nan panier!', 'success');
                    } else {
                        showNotification(data.message || 'Erè, eseye ankò.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Erè: ' + error.message, 'error');
                })
                .finally(() => {
                    if (buttonElement) {
                        setTimeout(() => {
                            buttonElement.disabled = false;
                            buttonElement.innerHTML = '<i class="fas fa-cart-plus"></i>';
                        }, 500);
                    }
                });
        }

        // Ajoute nan favori
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
                })
                .catch(error => {
                    showNotification('Erè rezo, eseye ankò.', 'error');
                });
        }

        // Gade rapid
        function quickView(productId) {
            const modal = document.getElementById('quickViewModal');
            const content = document.getElementById('quickViewContent');

            fetch('get_product.php?id=' + productId)
                .then(response => response.json())
                .then(data => {
                    const isPromo = data.price_promo && data.price_promo > 0 && data.price_promo < data.price;
                    content.innerHTML = `
                        <div class="grid md:grid-cols-2 gap-6 md:gap-8">
                            <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden">
                                <img src="../uploads/products/${data.image || 'placeholder.png'}" class="w-full h-full object-cover" onerror="this.src='../assets/img/placeholder.png'">
                            </div>
                            <div class="flex flex-col justify-center">
                                <h2 class="text-2xl font-bold mb-2 text-gray-900">${data.name}</h2>
                                <p class="text-blue-600 font-semibold mb-4 uppercase tracking-wide text-sm">${data.category_name}</p>
                                <p class="text-gray-600 mb-6 leading-relaxed">${data.description || 'Pa gen deskripsyon.'}</p>
                                <div class="mb-6">
                                    ${isPromo ? `
                                        <span class="text-3xl font-bold text-red-600">${new Intl.NumberFormat().format(data.price_promo)} HTG</span>
                                        <span class="text-gray-500 line-through ml-3 text-xl">${new Intl.NumberFormat().format(data.price)} HTG</span>
                                        <span class="ml-3 bg-red-100 text-red-700 px-2 py-1 rounded text-sm font-bold">- ${Math.round((1 - data.price_promo/data.price)*100)}%</span>
                                    ` : `
                                        <span class="text-3xl font-bold text-gray-900">${new Intl.NumberFormat().format(data.price)} HTG</span>
                                    `}
                                </div>
                                <button onclick="addToCart(${data.id}, this); closeQuickView();" class="bg-blue-600 text-white border-0 px-5 py-3 rounded-xl font-semibold flex items-center justify-center gap-2 transition-all duration-200 hover:bg-blue-700 w-full">
                                    <i class="fas fa-cart-plus mr-2"></i> Ajoute nan Panier
                                </button>
                            </div>
                        </div>
                    `;
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Erè nan chajman pwodwi a', 'error');
                });
        }

        function closeQuickView() {
            const modal = document.getElementById('quickViewModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }

        function closeQuickViewOnOverlay(event) {
            if (event.target === event.currentTarget) {
                closeQuickView();
            }
        }

        // Notifikasyon
        function showNotification(message, type) {
            const existingNotif = document.querySelector('.notification');
            if (existingNotif) existingNotif.remove();

            const notif = document.createElement('div');
            notif.className = `notification fixed top-24 right-8 px-6 py-4 rounded-xl font-semibold z-[10000] shadow-xl ${type === 'success' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-red-100 text-red-800 border border-red-300'}`;
            notif.style.animation = 'slideIn 0.3s ease';
            notif.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle mr-2"></i> ${message}`;
            document.body.appendChild(notif);

            setTimeout(() => {
                notif.style.opacity = '0';
                notif.style.transform = 'translateX(100%)';
                setTimeout(() => notif.remove(), 300);
            }, 3000);
        }

        // Gere chanjman gwosè ekran
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1024) {
                document.getElementById('mobileFilters').classList.add('hidden');
                document.body.style.overflow = 'auto';
            }
        });
    </script>
</body>

</html>