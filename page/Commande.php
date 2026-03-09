<?php
session_start();

// Done kòmand yo (nan yon vèsyon reyèl, sa yo ta soti nan baz done)
$orders = [
    [
        'orderId' => 'CMD-2026030501',
        'date' => '28 Février 2026',
        'status' => 'delivered',
        'total' => 2800,
        'items' => [
            [
                'id' => 1,
                'name' => "Robe d'été élégante",
                'image' => 'https://images.unsplash.com/photo-1579664531470-ac357f8f8e2b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxmYXNoaW9uJTIwY2xvdGhlcyUyMHByb2R1Y3R8ZW58MXx8fHwxNzcyNjM1NDU3fDA&ixlib=rb-4.1.0&q=80&w=1080  ',
                'quantity' => 2,
                'price' => 950
            ],
            [
                'id' => 2,
                'name' => 'Vase décoratif moderne',
                'image' => 'https://images.unsplash.com/photo-1645743712272-1aef8753a06a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxob21lJTIwZGVjb3IlMjBwcm9kdWN0fGVufDF8fHx8MTc3MjczOTc2M3ww&ixlib=rb-4.1.0&q=80&w=1080  ',
                'quantity' => 1,
                'price' => 900
            ]
        ],
        'shipping' => [
            'address' => '45 Avenue Mohammed V, Apt 12',
            'city' => 'Casablanca',
            'zipCode' => '20000'
        ],
        'trackingNumber' => 'TRK123456789'
    ],
    [
        'orderId' => 'CMD-2026022801',
        'date' => '20 Février 2026',
        'status' => 'shipped',
        'total' => 3200,
        'items' => [
            [
                'id' => 3,
                'name' => 'Écouteurs sans fil premium',
                'image' => 'https://images.unsplash.com/photo-1758979792186-32a5da91f24d?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxlbGVjdHJvbmljcyUyMGdhZGdldCUyMHByb2R1Y3R8ZW58MXx8fHwxNzcyNjM1NDU4fDA&ixlib=rb-4.1.0&q=80&w=1080  ',
                'quantity' => 1,
                'price' => 3200
            ]
        ],
        'shipping' => [
            'address' => '78 Rue Ibn Batouta, Étage 3',
            'city' => 'Rabat',
            'zipCode' => '10000'
        ],
        'trackingNumber' => 'TRK987654321'
    ],
    [
        'orderId' => 'CMD-2026021501',
        'date' => '10 Février 2026',
        'status' => 'pending',
        'total' => 1200,
        'items' => [
            [
                'id' => 4,
                'name' => 'Coussin décoratif',
                'image' => 'https://images.unsplash.com/photo-1645743712272-1aef8753a06a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxob21lJTIwZGVjb3IlMjBwcm9kdWN0fGVufDF8fHx8MTc3MjczOTc2M3ww&ixlib=rb-4.1.0&q=80&w=1080  ',
                'quantity' => 3,
                'price' => 400
            ]
        ],
        'shipping' => [
            'address' => '45 Avenue Mohammed V, Apt 12',
            'city' => 'Casablanca',
            'zipCode' => '20000'
        ],
        'trackingNumber' => 'TRK456789123'
    ],
    [
        'orderId' => 'CMD-2026020801',
        'date' => '5 Février 2026',
        'status' => 'delivered',
        'total' => 4500,
        'items' => [
            [
                'id' => 5,
                'name' => 'Sac à main élégant',
                'image' => 'https://images.unsplash.com/photo-1584917865442-de89df76afd3?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxsdXh1cnklMjBiYWd8ZW58MXx8fHwxNzQxNTYzNDU3fDA&ixlib=rb-4.1.0&q=80&w=1080  ',
                'quantity' => 1,
                'price' => 4500
            ]
        ],
        'shipping' => [
            'address' => '45 Avenue Mohammed V, Apt 12',
            'city' => 'Casablanca',
            'zipCode' => '20000'
        ],
        'trackingNumber' => 'TRK789123456'
    ]
];

// Konfigirasyon estati
$statusConfig = [
    'delivered' => [
        'label' => 'Livrée',
        'color' => 'bg-green-100 text-green-700 border-green-300',
        'icon' => 'fa-check',
        'dotColor' => 'bg-green-500',
        'btnColor' => 'bg-green-600 hover:bg-green-700'
    ],
    'shipped' => [
        'label' => 'En cours de livraison',
        'color' => 'bg-blue-100 text-blue-700 border-blue-300',
        'icon' => 'fa-truck',
        'dotColor' => 'bg-blue-500',
        'btnColor' => 'bg-blue-600 hover:bg-blue-700'
    ],
    'pending' => [
        'label' => 'En préparation',
        'color' => 'bg-yellow-100 text-yellow-700 border-yellow-300',
        'icon' => 'fa-clock',
        'dotColor' => 'bg-yellow-500',
        'btnColor' => 'bg-yellow-600 hover:bg-yellow-700'
    ]
];

// Jere filtè yo
$filterStatus = $_GET['status'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';
$expandedOrder = $_GET['expand'] ?? null;

// Filtre kòmand yo
$filteredOrders = array_filter($orders, function($order) use ($filterStatus, $searchQuery) {
    $matchesFilter = $filterStatus === 'all' || $order['status'] === $filterStatus;
    $matchesSearch = empty($searchQuery) || stripos($order['orderId'], $searchQuery) !== false;
    return $matchesFilter && $matchesSearch;
});

// Rekalkile estatistik
$totalOrders = count($orders);
$deliveredCount = count(array_filter($orders, fn($o) => $o['status'] === 'delivered'));
$shippedCount = count(array_filter($orders, fn($o) => $o['status'] === 'shipped'));
$pendingCount = count(array_filter($orders, fn($o) => $o['status'] === 'pending'));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes commandes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css  ">
    <link rel="stylesheet" href="\le-stock\css\style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f9fafb;
        }
        
        /* Header Styles */
        .header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .header-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        @media (min-width: 640px) {
            .header-container {
                padding: 1rem;
            }
        }
        
        @media (min-width: 1024px) {
            .header-container {
                padding: 1.25rem 1rem;
            }
        }
        
        /* Logo Container - BIGGER LOGO */
        .logo-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .logo-img {
            height: 50px;
            width: auto;
            max-width: 180px;
            object-fit: contain;
            /* For circular logo: border-radius: 50%; */
        }
        
        @media (min-width: 640px) {
            .logo-img {
                height: 60px;
                max-width: 220px;
            }
        }
        
        @media (min-width: 1024px) {
            .logo-img {
                height: 70px;
                max-width: 260px;
            }
        }
        
        .logo-text {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
        }
        
        @media (min-width: 640px) {
            .logo-text {
                font-size: 1.5rem;
            }
        }
        
        .nav-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            border: 2px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            text-decoration: none;
            transition: all 0.2s;
            white-space: nowrap;
        }
        
        @media (min-width: 640px) {
            .nav-link {
                padding: 0.5rem 1rem;
            }
        }
        
        .nav-link:hover {
            background-color: #f3f4f6;
        }
        
        /* Main Container */
        .main-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 1rem;
        }
        
        @media (min-width: 640px) {
            .main-container {
                padding: 1.5rem 1rem;
            }
        }
        
        @media (min-width: 1024px) {
            .main-container {
                padding: 2rem 1rem;
            }
        }
        
        /* Page Title */
        .page-header {
            margin-bottom: 1rem;
        }
        
        @media (min-width: 640px) {
            .page-header {
                margin-bottom: 1.5rem;
            }
        }
        
        @media (min-width: 1024px) {
            .page-header {
                margin-bottom: 2rem;
            }
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.25rem;
        }
        
        @media (min-width: 640px) {
            .page-title {
                font-size: 1.875rem;
            }
        }
        
        @media (min-width: 1024px) {
            .page-title {
                font-size: 2.25rem;
            }
        }
        
        .page-subtitle {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        @media (min-width: 640px) {
            .page-subtitle {
                font-size: 1rem;
            }
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        
        @media (min-width: 640px) {
            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 1rem;
                margin-bottom: 1.5rem;
            }
        }
        
        @media (min-width: 1024px) {
            .stats-grid {
                gap: 1.5rem;
                margin-bottom: 2rem;
            }
        }
        
        .stat-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 0.875rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        
        @media (min-width: 640px) {
            .stat-card {
                padding: 1.25rem;
            }
        }
        
        @media (min-width: 1024px) {
            .stat-card {
                padding: 1.5rem;
            }
        }
        
        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .stat-icon {
            font-size: 1.25rem;
        }
        
        @media (min-width: 640px) {
            .stat-icon {
                font-size: 1.5rem;
            }
        }
        
        @media (min-width: 1024px) {
            .stat-icon {
                font-size: 2rem;
            }
        }
        
        .stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
        }
        
        @media (min-width: 640px) {
            .stat-value {
                font-size: 1.5rem;
            }
        }
        
        @media (min-width: 1024px) {
            .stat-value {
                font-size: 2rem;
            }
        }
        
        .stat-label {
            font-size: 0.75rem;
            color: #6b7280;
        }
        
        @media (min-width: 640px) {
            .stat-label {
                font-size: 0.875rem;
            }
        }
        
        /* Filter Section */
        .filter-section {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        
        @media (min-width: 640px) {
            .filter-section {
                margin-bottom: 1.5rem;
            }
        }
        
        .filter-container {
            padding: 0.875rem;
        }
        
        @media (min-width: 640px) {
            .filter-container {
                padding: 1.25rem;
            }
        }
        
        @media (min-width: 1024px) {
            .filter-container {
                padding: 1.5rem;
            }
        }
        
        .filter-form {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        @media (min-width: 1024px) {
            .filter-form {
                flex-direction: row;
                gap: 1rem;
            }
        }
        
        .search-box {
            position: relative;
            flex: 1;
        }
        
        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }
        
        .search-input {
            width: 100%;
            padding: 0.625rem 1rem 0.625rem 2.5rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: border-color 0.2s;
        }
        
        @media (min-width: 640px) {
            .search-input {
                padding: 0.75rem 1rem 0.75rem 2.5rem;
                font-size: 1rem;
            }
        }
        
        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
        }
        
        .filter-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        @media (min-width: 640px) {
            .filter-buttons {
                gap: 0.75rem;
            }
        }
        
        .filter-btn {
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
            border: 2px solid #d1d5db;
            background: white;
            color: #374151;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            flex: 1;
            justify-content: center;
        }
        
        @media (min-width: 640px) {
            .filter-btn {
                padding: 0.625rem 1rem;
                font-size: 0.875rem;
                gap: 0.5rem;
                flex: none;
            }
        }
        
        .filter-btn:hover {
            background-color: #f3f4f6;
        }
        
        .filter-btn.active-all {
            background-color: #111827;
            color: white;
            border-color: #111827;
        }
        
        .filter-btn.active-delivered {
            background-color: #16a34a;
            color: white;
            border-color: #16a34a;
        }
        
        .filter-btn.active-shipped {
            background-color: #2563eb;
            color: white;
            border-color: #2563eb;
        }
        
        .filter-btn.active-pending {
            background-color: #ca8a04;
            color: white;
            border-color: #ca8a04;
        }
        
        /* Orders List */
        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        @media (min-width: 640px) {
            .orders-list {
                gap: 1rem;
            }
        }
        
        @media (min-width: 1024px) {
            .orders-list {
                gap: 1.5rem;
            }
        }
        
        .order-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .order-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        }
        
        .order-header {
            background: #f9fafb;
            padding: 0.875rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        @media (min-width: 640px) {
            .order-header {
                padding: 1.25rem;
            }
        }
        
        @media (min-width: 1024px) {
            .order-header {
                padding: 1.5rem;
            }
        }
        
        .order-header-content {
            display: flex;
            flex-direction: column;
            gap: 0.875rem;
        }
        
        @media (min-width: 1024px) {
            .order-header-content {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
            }
        }
        
        .order-info {
            flex: 1;
            min-width: 0;
        }
        
        .order-title-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .order-id {
            font-size: 0.9375rem;
            font-weight: 700;
            color: #111827;
            word-break: break-all;
        }
        
        @media (min-width: 640px) {
            .order-id {
                font-size: 1.125rem;
            }
        }
        
        @media (min-width: 1024px) {
            .order-id {
                font-size: 1.25rem;
            }
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.6875rem;
            font-weight: 600;
            border: 1px solid;
            white-space: nowrap;
        }
        
        @media (min-width: 640px) {
            .status-badge {
                font-size: 0.75rem;
                padding: 0.375rem 0.875rem;
            }
        }
        
        .order-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.625rem;
            font-size: 0.75rem;
            color: #6b7280;
        }
        
        @media (min-width: 640px) {
            .order-meta {
                gap: 1rem;
                font-size: 0.875rem;
            }
        }
        
        .order-meta-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .order-meta-item i {
            color: #9ca3af;
        }
        
        .order-price {
            font-weight: 600;
            color: #111827;
        }
        
        .order-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        @media (min-width: 640px) {
            .order-actions {
                gap: 0.75rem;
            }
        }
        
        @media (min-width: 1024px) {
            .order-actions {
                flex-wrap: nowrap;
            }
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid #d1d5db;
            background: white;
            color: #374151;
            flex: 1;
        }
        
        @media (min-width: 640px) {
            .btn {
                padding: 0.625rem 1rem;
                font-size: 0.875rem;
            }
        }
        
        @media (min-width: 1024px) {
            .btn {
                flex: none;
            }
        }
        
        .btn:hover {
            background-color: #f3f4f6;
        }
        
        /* Details Panel */
        .details-panel {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        
        .details-panel.expanded {
            max-height: 2000px;
        }
        
        .details-content {
            padding: 0.875rem;
        }
        
        @media (min-width: 640px) {
            .details-content {
                padding: 1.25rem;
            }
        }
        
        @media (min-width: 1024px) {
            .details-content {
                padding: 1.5rem;
            }
        }
        
        .details-section {
            margin-bottom: 1.25rem;
        }
        
        @media (min-width: 640px) {
            .details-section {
                margin-bottom: 1.5rem;
            }
        }
        
        .details-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.875rem;
        }
        
        @media (min-width: 640px) {
            .details-title {
                font-size: 1rem;
                margin-bottom: 1rem;
            }
        }
        
        @media (min-width: 1024px) {
            .details-title {
                font-size: 1.125rem;
            }
        }
        
        /* Order Items */
        .items-list {
            display: flex;
            flex-direction: column;
            gap: 0.625rem;
        }
        
        @media (min-width: 640px) {
            .items-list {
                gap: 0.875rem;
            }
        }
        
        .item-card {
            display: flex;
            gap: 0.75rem;
            padding: 0.75rem;
            background: #f9fafb;
            border-radius: 0.5rem;
        }
        
        @media (min-width: 640px) {
            .item-card {
                gap: 1rem;
                padding: 1rem;
            }
        }
        
        .item-image {
            width: 3.5rem;
            height: 3.5rem;
            object-fit: cover;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
            flex-shrink: 0;
        }
        
        @media (min-width: 640px) {
            .item-image {
                width: 5rem;
                height: 5rem;
            }
        }
        
        @media (min-width: 1024px) {
            .item-image {
                width: 6rem;
                height: 6rem;
            }
        }
        
        .item-details {
            flex: 1;
            min-width: 0;
        }
        
        .item-name {
            font-weight: 500;
            color: #111827;
            font-size: 0.8125rem;
            margin-bottom: 0.25rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        @media (min-width: 640px) {
            .item-name {
                font-size: 1rem;
            }
        }
        
        .item-qty {
            font-size: 0.75rem;
            color: #6b7280;
            margin-bottom: 0.25rem;
        }
        
        @media (min-width: 640px) {
            .item-qty {
                font-size: 0.875rem;
            }
        }
        
        .item-price {
            font-size: 0.8125rem;
            font-weight: 600;
            color: #111827;
        }
        
        @media (min-width: 640px) {
            .item-price {
                font-size: 0.9375rem;
                margin-top: 0.5rem;
            }
        }
        
        @media (min-width: 1024px) {
            .item-price {
                font-size: 1rem;
            }
        }
        
        /* Divider */
        .divider {
            border-top: 1px solid #e5e7eb;
            margin: 1.25rem 0;
        }
        
        @media (min-width: 640px) {
            .divider {
                margin: 1.5rem 0;
            }
        }
        
        /* Grid for shipping and tracking */
        .details-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        @media (min-width: 768px) {
            .details-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1.25rem;
            }
        }
        
        @media (min-width: 1024px) {
            .details-grid {
                gap: 1.5rem;
            }
        }
        
        .info-box {
            background: #f9fafb;
            padding: 0.875rem;
            border-radius: 0.5rem;
        }
        
        @media (min-width: 640px) {
            .info-box {
                padding: 1rem;
            }
        }
        
        @media (min-width: 1024px) {
            .info-box {
                padding: 1.25rem;
            }
        }
        
        .info-box-title {
            font-size: 0.8125rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.625rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        @media (min-width: 640px) {
            .info-box-title {
                font-size: 0.9375rem;
                margin-bottom: 0.75rem;
            }
        }
        
        @media (min-width: 1024px) {
            .info-box-title {
                font-size: 1rem;
            }
        }
        
        .info-box-title i {
            color: #3b82f6;
        }
        
        .info-text {
            font-size: 0.8125rem;
            color: #111827;
            line-height: 1.5;
        }
        
        @media (min-width: 640px) {
            .info-text {
                font-size: 0.9375rem;
            }
        }
        
        .tracking-number {
            font-family: monospace;
            font-weight: 600;
            font-size: 0.8125rem;
            color: #111827;
            margin-bottom: 0.625rem;
            word-break: break-all;
        }
        
        @media (min-width: 640px) {
            .tracking-number {
                font-size: 0.9375rem;
                margin-bottom: 0.75rem;
            }
        }
        
        @media (min-width: 1024px) {
            .tracking-number {
                font-size: 1rem;
            }
        }
        
        .btn-primary {
            width: 100%;
            padding: 0.625rem 1rem;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        @media (min-width: 640px) {
            .btn-primary {
                padding: 0.75rem 1rem;
                font-size: 0.9375rem;
            }
        }
        
        .btn-primary:hover {
            background: #1d4ed8;
        }
        
        /* Timeline */
        .timeline {
            margin-top: 1.25rem;
        }
        
        @media (min-width: 640px) {
            .timeline {
                margin-top: 1.5rem;
            }
        }
        
        @media (min-width: 1024px) {
            .timeline {
                margin-top: 2rem;
            }
        }
        
        .timeline-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.875rem;
        }
        
        @media (min-width: 640px) {
            .timeline-title {
                font-size: 1rem;
                margin-bottom: 1rem;
            }
        }
        
        @media (min-width: 1024px) {
            .timeline-title {
                font-size: 1.125rem;
            }
        }
        
        .timeline-item {
            position: relative;
            display: flex;
            gap: 0.875rem;
            padding-bottom: 1.25rem;
        }
        
        @media (min-width: 640px) {
            .timeline-item {
                gap: 1rem;
                padding-bottom: 1.5rem;
            }
        }
        
        .timeline-line {
            position: absolute;
            left: 12px;
            top: 28px;
            bottom: 0;
            width: 2px;
            background-color: #e5e7eb;
        }
        
        @media (min-width: 640px) {
            .timeline-line {
                left: 15px;
                top: 32px;
            }
        }
        
        .timeline-item:last-child .timeline-line {
            display: none;
        }
        
        .timeline-dot {
            position: relative;
            z-index: 10;
            flex-shrink: 0;
            width: 1.625rem;
            height: 1.625rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        @media (min-width: 640px) {
            .timeline-dot {
                width: 2rem;
                height: 2rem;
            }
        }
        
        .timeline-dot.green {
            background: #22c55e;
        }
        
        .timeline-dot.blue {
            background: #3b82f6;
        }
        
        .timeline-dot.gray {
            background: #111827;
        }
        
        .timeline-dot i {
            color: white;
            font-size: 0.625rem;
        }
        
        @media (min-width: 640px) {
            .timeline-dot i {
                font-size: 0.75rem;
            }
        }
        
        .timeline-content {
            flex: 1;
            padding-top: 0.125rem;
        }
        
        @media (min-width: 640px) {
            .timeline-content {
                padding-top: 0.25rem;
            }
        }
        
        .timeline-status {
            font-weight: 500;
            color: #111827;
            font-size: 0.8125rem;
        }
        
        @media (min-width: 640px) {
            .timeline-status {
                font-size: 1rem;
            }
        }
        
        .timeline-date {
            font-size: 0.6875rem;
            color: #6b7280;
        }
        
        @media (min-width: 640px) {
            .timeline-date {
                font-size: 0.875rem;
            }
        }
        
        /* Empty State */
        .empty-state {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 1.5rem 1rem;
            text-align: center;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        
        @media (min-width: 640px) {
            .empty-state {
                padding: 2.5rem 2rem;
            }
        }
        
        @media (min-width: 1024px) {
            .empty-state {
                padding: 3rem;
            }
        }
        
        .empty-icon {
            font-size: 2.5rem;
            color: #d1d5db;
            margin-bottom: 0.75rem;
        }
        
        @media (min-width: 640px) {
            .empty-icon {
                font-size: 3.5rem;
                margin-bottom: 1rem;
            }
        }
        
        @media (min-width: 1024px) {
            .empty-icon {
                font-size: 4rem;
            }
        }
        
        .empty-title {
            font-size: 1rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.375rem;
        }
        
        @media (min-width: 640px) {
            .empty-title {
                font-size: 1.25rem;
                margin-bottom: 0.5rem;
            }
        }
        
        .empty-text {
            color: #6b7280;
            font-size: 0.8125rem;
            margin-bottom: 1.25rem;
        }
        
        @media (min-width: 640px) {
            .empty-text {
                font-size: 1rem;
                margin-bottom: 1.5rem;
            }
        }
        
        .btn-reset {
            display: inline-flex;
            align-items: center;
            padding: 0.625rem 1.25rem;
            background: #2563eb;
            color: white;
            border-radius: 0.5rem;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.2s;
            font-size: 0.875rem;
        }
        
        @media (min-width: 640px) {
            .btn-reset {
                padding: 0.75rem 1.5rem;
                font-size: 1rem;
            }
        }
        
        .btn-reset:hover {
            background: #1d4ed8;
        }
        
        /* Utility classes */
        .hide-mobile {
            display: none;
        }
        
        @media (min-width: 640px) {
            .hide-mobile {
                display: inline;
            }
        }
        
        /* Status badge colors */
        .bg-green-100 { background-color: #dcfce7; }
        .text-green-700 { color: #15803d; }
        .border-green-300 { border-color: #86efac; }
        
        .bg-blue-100 { background-color: #dbeafe; }
        .text-blue-700 { color: #1d4ed8; }
        .border-blue-300 { border-color: #93c5fd; }
        
        .bg-yellow-100 { background-color: #fef9c3; }
        .text-yellow-700 { color: #a16207; }
        .border-yellow-300 { border-color: #fde047; }
        
        .text-blue-600 { color: #2563eb; }
        .text-green-600 { color: #16a34a; }
        .text-yellow-600 { color: #ca8a04; }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <!-- Logo Section - BIGGER RESPONSIVE LOGO -->
            <div class="logo-container">
                <!-- 
                    MODIFY HERE: Replace the src attribute with your logo image path
                    Logo will be: 50px on mobile, 60px on tablet, 70px on desktop
                    Max-width: 180px mobile, 220px tablet, 260px desktop
                -->
                <img src="\le-stock\assets\img\le stock entreprise copy.png" alt="Logo" class="logo-img" onerror="this.style.display='none'">
                
                <!-- Optional: Add your site name as text if you don't have an image logo -->
                <!-- <span class="logo-text">Votre Site</span> -->
            </div>
            
            <a href="mes-commandes.php" class="nav-link">
                <i class="fas fa-box"></i>
                <span class="hide-mobile">Mes commandes</span>
            </a>
        </div>
    </header>

    <main class="main-container">
        
        <!-- Page Title -->
        <div class="page-header">
            <h1 class="page-title">Mes commandes</h1>
            <p class="page-subtitle">Suivez et gérez toutes vos commandes en un seul endroit</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <i class="fas fa-box stat-icon text-blue-600"></i>
                    <span class="stat-value"><?= $totalOrders ?></span>
                </div>
                <p class="stat-label">Total commandes</p>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <i class="fas fa-check stat-icon text-green-600"></i>
                    <span class="stat-value"><?= $deliveredCount ?></span>
                </div>
                <p class="stat-label">Livrées</p>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <i class="fas fa-truck stat-icon text-blue-600"></i>
                    <span class="stat-value"><?= $shippedCount ?></span>
                </div>
                <p class="stat-label">En livraison</p>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <i class="fas fa-clock stat-icon text-yellow-600"></i>
                    <span class="stat-value"><?= $pendingCount ?></span>
                </div>
                <p class="stat-label">En préparation</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <div class="filter-container">
                <form method="GET" action="" class="filter-form">
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" placeholder="Rechercher une commande..." 
                            value="<?= htmlspecialchars($searchQuery) ?>" class="search-input">
                    </div>

                    <div class="filter-buttons">
                        <button type="submit" name="status" value="all" 
                            class="filter-btn <?= $filterStatus === 'all' ? 'active-all' : '' ?>">
                            <i class="fas fa-filter"></i>
                            Toutes
                        </button>
                        <button type="submit" name="status" value="delivered" 
                            class="filter-btn <?= $filterStatus === 'delivered' ? 'active-delivered' : '' ?>">
                            Livrées
                        </button>
                        <button type="submit" name="status" value="shipped" 
                            class="filter-btn <?= $filterStatus === 'shipped' ? 'active-shipped' : '' ?>">
                            En livraison
                        </button>
                        <button type="submit" name="status" value="pending" 
                            class="filter-btn <?= $filterStatus === 'pending' ? 'active-pending' : '' ?>">
                            En préparation
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Orders List -->
        <div class="orders-list">
            <?php if (empty($filteredOrders)): ?>
                <div class="empty-state">
                    <i class="fas fa-box empty-icon"></i>
                    <h3 class="empty-title">Aucune commande trouvée</h3>
                    <p class="empty-text">Essayez de modifier vos filtres ou votre recherche</p>
                    <a href="?status=all&search=" class="btn-reset">
                        Réinitialiser les filtres
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($filteredOrders as $order): 
                    $config = $statusConfig[$order['status']];
                    $isExpanded = $expandedOrder === $order['orderId'];
                ?>
                    <div class="order-card">
                        <!-- Order Header -->
                        <div class="order-header">
                            <div class="order-header-content">
                                <div class="order-info">
                                    <div class="order-title-row">
                                        <h3 class="order-id"><?= htmlspecialchars($order['orderId']) ?></h3>
                                        <span class="status-badge <?= $config['color'] ?>">
                                            <i class="fas <?= $config['icon'] ?> text-xs"></i>
                                            <?= $config['label'] ?>
                                        </span>
                                    </div>
                                    <div class="order-meta">
                                        <div class="order-meta-item">
                                            <i class="fas fa-calendar"></i>
                                            <span><?= htmlspecialchars($order['date']) ?></span>
                                        </div>
                                        <div class="order-meta-item">
                                            <i class="fas fa-box"></i>
                                            <span><?= count($order['items']) ?> article<?= count($order['items']) > 1 ? 's' : '' ?></span>
                                        </div>
                                        <div class="order-meta-item">
                                            <i class="fas fa-dollar-sign"></i>
                                            <span class="order-price"><?= number_format($order['total'], 2) ?> MAD</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="order-actions">
                                    <button onclick="downloadInvoice('<?= $order['orderId'] ?>')" class="btn">
                                        <i class="fas fa-download"></i>
                                        <span class="hide-mobile">Facture</span>
                                    </button>
                                    <a href="?status=<?= $filterStatus ?>&search=<?= urlencode($searchQuery) ?>&expand=<?= $isExpanded ? '' : $order['orderId'] ?>" class="btn">
                                        <?php if ($isExpanded): ?>
                                            <i class="fas fa-chevron-up"></i>
                                            <span class="hide-mobile">Masquer</span>
                                        <?php else: ?>
                                            <i class="fas fa-eye"></i>
                                            <span class="hide-mobile">Détails</span>
                                        <?php endif; ?>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Order Details -->
                        <div class="details-panel <?= $isExpanded ? 'expanded' : '' ?>">
                            <?php if ($isExpanded): ?>
                                <div class="details-content">
                                    <!-- Order Items -->
                                    <div class="details-section">
                                        <h4 class="details-title">Articles commandés</h4>
                                        <div class="items-list">
                                            <?php foreach ($order['items'] as $item): ?>
                                                <div class="item-card">
                                                    <img src="<?= htmlspecialchars($item['image']) ?>" 
                                                        alt="<?= htmlspecialchars($item['name']) ?>" 
                                                        class="item-image"
                                                        onerror="this.src='/le-stock/assets/img/placeholder.jpg'">
                                                    <div class="item-details">
                                                        <h5 class="item-name"><?= htmlspecialchars($item['name']) ?></h5>
                                                        <p class="item-qty">Quantité: <?= $item['quantity'] ?></p>
                                                        <p class="item-price">
                                                            <?= number_format($item['price'], 2) ?> MAD × <?= $item['quantity'] ?> = <?= number_format($item['price'] * $item['quantity'], 2) ?> MAD
                                                        </p>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="divider"></div>

                                    <!-- Shipping & Tracking -->
                                    <div class="details-grid">
                                        <div>
                                            <h4 class="info-box-title">
                                                <i class="fas fa-map-marker-alt"></i>
                                                Adresse de livraison
                                            </h4>
                                            <div class="info-box">
                                                <p class="info-text"><?= htmlspecialchars($order['shipping']['address']) ?></p>
                                                <p class="info-text" style="margin-top: 0.25rem;">
                                                    <?= htmlspecialchars($order['shipping']['city']) ?>, <?= htmlspecialchars($order['shipping']['zipCode']) ?>
                                                </p>
                                            </div>
                                        </div>

                                        <?php if ($order['status'] !== 'pending'): ?>
                                            <div>
                                                <h4 class="info-box-title">
                                                    <i class="fas fa-truck"></i>
                                                    Suivi de colis
                                                </h4>
                                                <div class="info-box">
                                                    <p style="font-size: 0.75rem; color: #6b7280; margin-bottom: 0.25rem;">Numéro de suivi</p>
                                                    <p class="tracking-number">
                                                        <?= htmlspecialchars($order['trackingNumber']) ?>
                                                    </p>
                                                    <button onclick="trackPackage('<?= $order['trackingNumber'] ?>')" class="btn-primary">
                                                        <i class="fas fa-truck"></i>
                                                        Suivre le colis
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Order Timeline -->
                                    <div class="timeline">
                                        <h4 class="timeline-title">Historique de la commande</h4>
                                        <div>
                                            <?php if ($order['status'] === 'delivered'): ?>
                                                <div class="timeline-item">
                                                    <div class="timeline-line"></div>
                                                    <div class="timeline-dot green">
                                                        <i class="fas fa-check"></i>
                                                    </div>
                                                    <div class="timeline-content">
                                                        <p class="timeline-status">Commande livrée</p>
                                                        <p class="timeline-date"><?= htmlspecialchars($order['date']) ?></p>
                                                    </div>
                                                </div>
                                                <div class="timeline-item">
                                                    <div class="timeline-line"></div>
                                                    <div class="timeline-dot blue">
                                                        <i class="fas fa-truck"></i>
                                                    </div>
                                                    <div class="timeline-content">
                                                        <p class="timeline-status">En cours de livraison</p>
                                                        <p class="timeline-date">Il y a 2 jours</p>
                                                    </div>
                                                </div>
                                            <?php elseif ($order['status'] === 'shipped'): ?>
                                                <div class="timeline-item">
                                                    <div class="timeline-line"></div>
                                                    <div class="timeline-dot blue">
                                                        <i class="fas fa-truck"></i>
                                                    </div>
                                                    <div class="timeline-content">
                                                        <p class="timeline-status">En cours de livraison</p>
                                                        <p class="timeline-date"><?= htmlspecialchars($order['date']) ?></p>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <div class="timeline-item">
                                                <div class="timeline-dot gray">
                                                    <i class="fas fa-box"></i>
                                                </div>
                                                <div class="timeline-content">
                                                    <p class="timeline-status">Commande confirmée</p>
                                                    <p class="timeline-date"><?= htmlspecialchars($order['date']) ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function downloadInvoice(orderId) {
            alert('Téléchargement de la facture ' + orderId + '...');
        }
        
        function trackPackage(trackingNumber) {
            alert('Suivi du colis: ' + trackingNumber);
        }
    </script>
</body>
</html>