<?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Récupérer les infos utilisateur
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit();
    }

    // Déterminer l'UI ID à afficher
    $displayId = $user['ui_id'] ?? ('LS-' . str_pad($user['id'], 4, '0', STR_PAD_LEFT));

    // Initialiser les stats
    $userStats = [
        'total_orders' => 0,
        'total_spent' => 0,
        'total_favorites' => 0,
        'pending_orders' => 0,
        'validated_orders' => 0,
        'delivered_orders' => 0,
        'rejected_orders' => 0,
        'cancelled_orders' => 0
    ];

    // Récupérer les stats de commandes - MÊME LOGIQUE QUE commandes.php
    // Utilise la table "orders" avec "total_amount" comme dans commandes.php
    try {
        $stats = $pdo->prepare("SELECT 
            COUNT(DISTINCT o.id) as total_orders,
            COALESCE(SUM(o.total_amount), 0) as total_spent,
            SUM(CASE WHEN o.status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
            SUM(CASE WHEN o.status IN ('validated', 'approved', 'processing') THEN 1 ELSE 0 END) as validated_orders,
            SUM(CASE WHEN o.status IN ('delivered', 'completed', 'shipped') THEN 1 ELSE 0 END) as delivered_orders,
            SUM(CASE WHEN o.status IN ('rejected', 'refused') THEN 1 ELSE 0 END) as rejected_orders,
            SUM(CASE WHEN o.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders
            FROM orders o
            WHERE o.user_id = ?");
        $stats->execute([$user_id]);
        $orderStats = $stats->fetch(PDO::FETCH_ASSOC);

        $userStats['total_orders'] = (int)($orderStats['total_orders'] ?? 0);
        $userStats['total_spent'] = (float)($orderStats['total_spent'] ?? 0);
        $userStats['pending_orders'] = (int)($orderStats['pending_orders'] ?? 0);
        $userStats['validated_orders'] = (int)($orderStats['validated_orders'] ?? 0);
        $userStats['delivered_orders'] = (int)($orderStats['delivered_orders'] ?? 0);
        $userStats['rejected_orders'] = (int)($orderStats['rejected_orders'] ?? 0);
        $userStats['cancelled_orders'] = (int)($orderStats['cancelled_orders'] ?? 0);
    } catch (PDOException $e) {
        error_log("Erreur stats commandes: " . $e->getMessage());
    }

    // Récupérer le nombre de favoris
    try {
        $favStmt = $pdo->prepare("SELECT COUNT(*) as total FROM favoris WHERE user_id = ?");
        $favStmt->execute([$user_id]);
        $favResult = $favStmt->fetch(PDO::FETCH_ASSOC);
        $userStats['total_favorites'] = (int)($favResult['total'] ?? 0);
    } catch (PDOException $e) {
        error_log("Erreur favoris: " . $e->getMessage());
    }

    // Récupérer les 5 dernières commandes - MÊME TABLE que commandes.php
    // Utilise "orders" et "order_items" comme dans commandes.php
    $recentOrders = [];
    try {
        // D'abord essayer avec product_name (le plus courant dans order_items)
        $recentStmt = $pdo->prepare("
            SELECT o.id, o.transaction_ref as reference, o.total_amount as total, 
                   o.status, o.created_at, o.reject_reason,
                   COUNT(oi.id) as item_count,
                   GROUP_CONCAT(oi.product_name SEPARATOR ', ') as products
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = ?
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT 5
        ");
        $recentStmt->execute([$user_id]);
        $recentOrders = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Si product_name n'existe pas, essayer avec name
        error_log("Erreur commandes récentes (product_name): " . $e->getMessage());
        try {
            $recentStmt = $pdo->prepare("
                SELECT o.id, o.transaction_ref as reference, o.total_amount as total, 
                       o.status, o.created_at, o.reject_reason,
                       COUNT(oi.id) as item_count,
                       GROUP_CONCAT(oi.name SEPARATOR ', ') as products
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE o.user_id = ?
                GROUP BY o.id
                ORDER BY o.created_at DESC
                LIMIT 5
            ");
            $recentStmt->execute([$user_id]);
            $recentOrders = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e2) {
            // Si ça échoue encore, récupérer sans les noms de produits
            error_log("Erreur commandes récentes (name): " . $e2->getMessage());
            try {
                $recentStmt = $pdo->prepare("
                    SELECT o.id, o.transaction_ref as reference, o.total_amount as total, 
                           o.status, o.created_at, o.reject_reason,
                           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count,
                           NULL as products
                    FROM orders o
                    WHERE o.user_id = ?
                    ORDER BY o.created_at DESC
                    LIMIT 5
                ");
                $recentStmt->execute([$user_id]);
                $recentOrders = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e3) {
                error_log("Erreur commandes récentes (final): " . $e3->getMessage());
            }
        }
    }

    // Récupérer les points marchand si applicable
    $merchantPoints = 0;
    if (strtolower($user['role'] ?? '') === 'merchant') {
        try {
            $pointsStmt = $pdo->prepare("SELECT COALESCE(points, 0) as points FROM merchant_points WHERE user_id = ?");
            $pointsStmt->execute([$user_id]);
            $pointsResult = $pointsStmt->fetch(PDO::FETCH_ASSOC);
            $merchantPoints = (int)($pointsResult['points'] ?? 0);
        } catch (PDOException $e) {
            $merchantPoints = 0;
        }
    }
} catch (PDOException $e) {
    die("Erreur base de données: " . $e->getMessage());
}

$activeTab = $_GET['tab'] ?? 'overview';

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_info'])) {
    $prenom = htmlspecialchars(trim($_POST['prenom']));
    $nom = htmlspecialchars(trim($_POST['nom']));
    $adresse = htmlspecialchars(trim($_POST['adresse']));
    $phone = htmlspecialchars(trim($_POST['phone']));

    $update = $pdo->prepare("UPDATE users SET prenom = ?, nom = ?, adresse = ?, telephone = ? WHERE id = ?");
    if ($update->execute([$prenom, $nom, $adresse, $phone, $user_id])) {
        header("Location: profile.php?tab=settings&success=1");
        exit();
    }
}

$isAdmin = strtolower($user['role'] ?? '') === 'admin';
$isMerchant = strtolower($user['role'] ?? '') === 'merchant';
$status_demann = strtolower($user['merchant_status'] ?? '');

// Fonction pour formater le temps écoulé
function timeAgo($datetime)
{
    if (empty($datetime)) return 'N/A';
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return $diff->y . ' an' . ($diff->y > 1 ? 's' : '');
    if ($diff->m > 0) return $diff->m . ' mois';
    if ($diff->d > 0) return $diff->d . ' jour' . ($diff->d > 1 ? 's' : '');
    if ($diff->h > 0) return $diff->h . ' heure' . ($diff->h > 1 ? 's' : '');
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
    return 'À l\'instant';
}

// Fonction pour le statut de commande - MÊME LOGIQUE QUE commandes.php
function getOrderStatus($status)
{
    $statuses = [
        'pending' => ['label' => 'En attente', 'color' => 'amber', 'icon' => 'clock'],
        'validated' => ['label' => 'Validé', 'color' => 'blue', 'icon' => 'check-circle'],
        'approved' => ['label' => 'Approuvé', 'color' => 'blue', 'icon' => 'check-circle'],
        'processing' => ['label' => 'En traitement', 'color' => 'blue', 'icon' => 'cog'],
        'shipped' => ['label' => 'Expédié', 'color' => 'purple', 'icon' => 'truck'],
        'delivered' => ['label' => 'Livré', 'color' => 'green', 'icon' => 'box'],
        'completed' => ['label' => 'Terminé', 'color' => 'green', 'icon' => 'check-double'],
        'rejected' => ['label' => 'Rejeté', 'color' => 'red', 'icon' => 'times-circle'],
        'refused' => ['label' => 'Rejeté', 'color' => 'red', 'icon' => 'times-circle'],
        'cancelled' => ['label' => 'Annulé', 'color' => 'gray', 'icon' => 'ban']
    ];
    return $statuses[strtolower($status)] ?? ['label' => $status, 'color' => 'slate', 'icon' => 'question-circle'];
}

// Fonction pour formater le prix - MÊME LOGIQUE QUE commandes.php
function formatPrice($price)
{
    return number_format($price, 2) . ' HTG';
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - <?= htmlspecialchars($user['prenom']) ?></title>
    <link rel="stylesheet" href="/le-stock/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
</head>

<body class="font-['Inter',sans-serif] min-h-screen relative overflow-x-hidden bg-[#f5f5f5] text-slate-800">

    <div class="fixed inset-0 z-[-2] overflow-hidden">
        <div class="w-full h-full bg-cover bg-center bg-no-repeat bg-fixed blur-[5px] scale-[1.03] max-md:blur-[4px] max-md:bg-scroll max-md:scale-105 max-[480px]:blur-[3px] max-[480px]:scale-[110%]" style="background-image: url('/le-stock/assets/img/stock7.png')"></div>
    </div>
    <div class="fixed inset-0 bg-black/25 z-[-1]"></div>

    <!-- Overlay mobile -->
    <div id="mobile-overlay" class="fixed inset-0 bg-black/40 z-40 md:hidden opacity-0 invisible transition-all duration-300" onclick="closeMobileMenu()"></div>

    <!-- Navigation -->
    <nav class="bg-white/95 backdrop-blur-[20px] border border-white/30 shadow-[0_8px_32px_rgba(0,0,0,0.1)] fixed w-full z-50 top-0 left-0 border-b border-white/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-pink-500 flex items-center justify-center text-white shadow-lg animate-pulse">
                        <i class="fas fa-bolt text-lg sm:text-xl"></i>
                    </div>
                    <span class="text-xl sm:text-2xl font-bold tracking-tight text-slate-800" style="font-family: 'Space Grotesk', sans-serif;">LE-STOCK</span>
                </div>

                <!-- Menu Desktop -->
                <div class="hidden md:flex items-center gap-6 lg:gap-8">
                    <?php if ($isAdmin): ?>
                        <a href="acceuil.php" class="flex items-center gap-2 text-red-600 hover:text-red-700 font-bold transition-colors text-sm">
                            <i class="fas fa-tachometer-alt"></i> Accueil
                        </a>
                        <a href="admin_dashboard.php" class="flex items-center gap-2 text-red-600 hover:text-red-700 font-bold transition-colors text-sm">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    <?php else: ?>
                        <a href="panier.php" class="flex items-center gap-2 text-slate-600 hover:text-indigo-500 font-medium transition-colors text-sm">
                            <i class="fas fa-shopping-bag"></i> Panier
                        </a>
                        <a href="commandes.php" class="flex items-center gap-2 text-slate-600 hover:text-indigo-500 font-medium transition-colors text-sm">
                            <i class="fas fa-receipt"></i> Commandes
                        </a>
                        <a href="favoris.php" class="flex items-center gap-2 text-slate-600 hover:text-indigo-500 font-medium transition-colors text-sm">
                            <i class="fas fa-heart"></i> Favoris
                        </a>
                    <?php endif; ?>

                    <div class="flex items-center gap-3 pl-4 lg:pl-6 border-l border-slate-200">
                        <div class="text-right hidden lg:block">
                            <p class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></p>
                            <p class="text-xs text-slate-500"><?= ucfirst($user['role'] ?? 'User') ?></p>
                        </div>
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-gradient-to-br from-indigo-500 to-pink-500 flex items-center justify-center text-white font-bold text-xs sm:text-sm shadow-lg">
                            <?= strtoupper(substr($user['prenom'] ?? '', 0, 1) . substr($user['nom'] ?? '', 0, 1)) ?>
                        </div>
                    </div>
                </div>

                <!-- Bouton Hamburger -->
                <button id="hamburger-btn" class="md:hidden text-slate-600 text-xl p-2 rounded-lg hover:bg-slate-100 transition-colors" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars" id="hamburger-icon"></i>
                </button>
            </div>

            <!-- Menu Mobile -->
            <div id="mobile-menu" class="md:hidden max-h-0 overflow-hidden opacity-0 transition-all duration-300">
                <div class="pt-4 pb-2 border-t border-slate-200/60 mt-4">
                    <div class="flex items-center gap-3 pb-4 mb-3 border-b border-slate-200/60">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-pink-500 flex items-center justify-center text-white font-bold text-sm shadow-lg">
                            <?= strtoupper(substr($user['prenom'] ?? '', 0, 1) . substr($user['nom'] ?? '', 0, 1)) ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-800 truncate"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></p>
                            <p class="text-xs text-slate-500"><?= ucfirst($user['role'] ?? 'User') ?></p>
                        </div>
                    </div>

                    <?php if ($isAdmin): ?>
                        <a href="admin_dashboard.php" class="flex items-center gap-3 px-3 py-3 rounded-xl text-red-600 hover:bg-red-50 font-bold transition-colors text-sm">
                            <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
                                <i class="fas fa-tachometer-alt text-xs"></i>
                            </div>
                            Dashboard
                        </a>
                    <?php else: ?>
                        <a href="panier.php" class="flex items-center gap-3 px-3 py-3 rounded-xl text-slate-700 hover:bg-slate-50 font-medium transition-colors text-sm">
                            <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center">
                                <i class="fas fa-shopping-bag text-xs text-indigo-600"></i>
                            </div>
                            Panier
                        </a>
                        <a href="commandes.php" class="flex items-center gap-3 px-3 py-3 rounded-xl text-slate-700 hover:bg-slate-50 font-medium transition-colors text-sm">
                            <div class="w-8 h-8 rounded-lg bg-pink-100 flex items-center justify-center">
                                <i class="fas fa-receipt text-xs text-pink-600"></i>
                            </div>
                            Commandes
                        </a>
                        <a href="favoris.php" class="flex items-center gap-3 px-3 py-3 rounded-xl text-slate-700 hover:bg-slate-50 font-medium transition-colors text-sm">
                            <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                                <i class="fas fa-heart text-xs text-amber-600"></i>
                            </div>
                            Favoris
                        </a>
                    <?php endif; ?>

                    <a href="?tab=overview" class="flex items-center gap-3 px-3 py-3 rounded-xl text-slate-700 hover:bg-slate-50 font-medium transition-colors text-sm">
                        <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center">
                            <i class="fas fa-chart-pie text-xs text-green-600"></i>
                        </div>
                        Aperçu profil
                    </a>
                    <a href="?tab=settings" class="flex items-center gap-3 px-3 py-3 rounded-xl text-slate-700 hover:bg-slate-50 font-medium transition-colors text-sm">
                        <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center">
                            <i class="fas fa-cog text-xs text-slate-600"></i>
                        </div>
                        Paramètres
                    </a>

                    <div class="pt-3 mt-2 border-t border-slate-200/60">
                        <a href="logout.php" class="flex items-center gap-3 px-3 py-3 rounded-xl text-red-600 hover:bg-red-50 font-medium transition-colors text-sm">
                            <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
                                <i class="fas fa-sign-out-alt text-xs"></i>
                            </div>
                            Déconnexion
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-20 sm:pt-24 pb-12 px-4 sm:px-6 max-w-7xl mx-auto relative z-10">

        <!-- Profile Header -->
        <div class="bg-white/95 backdrop-blur-[20px] border border-white/30 shadow-[0_8px_32px_rgba(0,0,0,0.1)] rounded-2xl sm:rounded-3xl p-6 sm:p-8 mb-6 sm:mb-8 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 sm:w-96 sm:h-96 bg-gradient-to-br from-indigo-500/20 to-pink-500/20 rounded-full blur-3xl -mr-10 sm:-mr-20 -mt-10 sm:-mt-20"></div>

            <div class="relative z-10 flex flex-col lg:flex-row items-center gap-6 sm:gap-8">
                <div class="relative">
                    <div class="w-24 h-24 sm:w-32 sm:h-32 rounded-2xl sm:rounded-3xl bg-gradient-to-br from-indigo-500 to-pink-500 flex items-center justify-center text-white text-3xl sm:text-4xl font-bold shadow-[0_0_60px_rgba(99,102,241,0.4)]" style="font-family: 'Space Grotesk', sans-serif;">
                        <?= strtoupper(substr($user['prenom'] ?? '', 0, 1) . substr($user['nom'] ?? '', 0, 1)) ?>
                    </div>
                    <div class="absolute -bottom-1 -right-1 sm:-bottom-2 sm:-right-2 w-6 h-6 sm:w-8 sm:h-8 bg-green-500 rounded-full border-2 sm:border-4 border-white flex items-center justify-center" title="En ligne">
                        <div class="w-2 h-2 sm:w-3 sm:h-3 bg-white rounded-full animate-pulse"></div>
                    </div>
                </div>

                <div class="flex-1 text-center lg:text-left">
                    <h1 class="text-2xl sm:text-4xl lg:text-5xl font-bold text-slate-800 mb-2 [text-shadow:0_2px_8px_rgba(0,0,0,0.4)]" style="font-family: 'Space Grotesk', sans-serif;">
                        <?= htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?>
                    </h1>
                    <p class="text-slate-600 mb-4 flex items-center justify-center lg:justify-start gap-2 text-sm sm:text-base">
                        <i class="fas fa-envelope text-indigo-500"></i>
                        <?= htmlspecialchars($user['email']) ?>
                    </p>

                    <div class="flex flex-wrap gap-2 sm:gap-3 justify-center lg:justify-start">
                        <span class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-full bg-slate-100/80 backdrop-blur-sm text-slate-700 text-xs sm:text-sm font-medium flex items-center gap-2 border border-white/50">
                            <i class="fas fa-id-badge text-indigo-500"></i>
                            UI ID: <?= htmlspecialchars($displayId) ?>
                        </span>
                        <?php if ($isMerchant): ?>
                            <span class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-full bg-gradient-to-br from-amber-400 to-amber-500 text-white text-xs sm:text-sm font-bold flex items-center gap-2 shadow-lg">
                                <i class="fas fa-crown"></i> Marchand Premium
                            </span>
                        <?php elseif ($isAdmin): ?>
                            <span class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-full bg-gradient-to-br from-red-500 to-red-600 text-white text-xs sm:text-sm font-bold flex items-center gap-2 shadow-lg">
                                <i class="fas fa-shield-alt"></i> Admin
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex gap-3 sm:gap-4">
                    <a href="?tab=settings" class="px-4 py-2 sm:px-6 sm:py-3 rounded-xl sm:rounded-2xl bg-white/80 backdrop-blur-sm hover:bg-white text-slate-700 font-semibold transition-all flex items-center gap-2 text-sm border border-white/50 shadow-lg">
                        <i class="fas fa-cog"></i> <span class="hidden sm:inline">Paramètres</span>
                    </a>
                    <button onclick="alert('Fonctionnalité en développement')" class="px-4 py-2 sm:px-6 sm:py-3 rounded-xl sm:rounded-2xl bg-slate-800 hover:bg-slate-900 text-white font-semibold transition-all flex items-center gap-2 shadow-lg text-sm">
                        <i class="fas fa-share-alt"></i> <span class="hidden sm:inline">Partager</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Grid - CORRIGÉ POUR UTILISER LES MÊMES DONNÉES QUE commandes.php -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4 mb-6 sm:mb-8">
            <div class="bg-white/85 backdrop-blur-[10px] border border-white/40 rounded-xl sm:rounded-2xl p-3 sm:p-4 hover:-translate-y-1 hover:shadow-[0_20px_40px_rgba(0,0,0,0.15)] hover:bg-white/95 transition-all border-l-4 border-yellow-500">
                <div class="flex items-center justify-between mb-2 sm:mb-3">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg bg-yellow-100 flex items-center justify-center text-yellow-600">
                        <i class="fas fa-clock text-sm sm:text-lg"></i>
                    </div>
                </div>
                <div class="text-xl sm:text-2xl font-bold text-slate-800 mb-0.5" data-counter="<?= $userStats['pending_orders'] ?>" style="font-family: 'Space Grotesk', sans-serif;">0</div>
                <div class="text-[10px] sm:text-xs text-slate-600">En attente</div>
            </div>

            <div class="bg-white/85 backdrop-blur-[10px] border border-white/40 rounded-xl sm:rounded-2xl p-3 sm:p-4 hover:-translate-y-1 hover:shadow-[0_20px_40px_rgba(0,0,0,0.15)] hover:bg-white/95 transition-all border-l-4 border-blue-500">
                <div class="flex items-center justify-between mb-2 sm:mb-3">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600">
                        <i class="fas fa-check-circle text-sm sm:text-lg"></i>
                    </div>
                </div>
                <div class="text-xl sm:text-2xl font-bold text-slate-800 mb-0.5" data-counter="<?= $userStats['validated_orders'] ?>" style="font-family: 'Space Grotesk', sans-serif;">0</div>
                <div class="text-[10px] sm:text-xs text-slate-600">Validé</div>
            </div>

            <div class="bg-white/85 backdrop-blur-[10px] border border-white/40 rounded-xl sm:rounded-2xl p-3 sm:p-4 hover:-translate-y-1 hover:shadow-[0_20px_40px_rgba(0,0,0,0.15)] hover:bg-white/95 transition-all border-l-4 border-green-500">
                <div class="flex items-center justify-between mb-2 sm:mb-3">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg bg-green-100 flex items-center justify-center text-green-600">
                        <i class="fas fa-box text-sm sm:text-lg"></i>
                    </div>
                </div>
                <div class="text-xl sm:text-2xl font-bold text-slate-800 mb-0.5" data-counter="<?= $userStats['delivered_orders'] ?>" style="font-family: 'Space Grotesk', sans-serif;">0</div>
                <div class="text-[10px] sm:text-xs text-slate-600">Livré</div>
            </div>

            <div class="bg-white/85 backdrop-blur-[10px] border border-white/40 rounded-xl sm:rounded-2xl p-3 sm:p-4 hover:-translate-y-1 hover:shadow-[0_20px_40px_rgba(0,0,0,0.15)] hover:bg-white/95 transition-all border-l-4 border-red-500">
                <div class="flex items-center justify-between mb-2 sm:mb-3">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg bg-red-100 flex items-center justify-center text-red-600">
                        <i class="fas fa-times-circle text-sm sm:text-lg"></i>
                    </div>
                </div>
                <div class="text-xl sm:text-2xl font-bold text-slate-800 mb-0.5" data-counter="<?= $userStats['rejected_orders'] ?>" style="font-family: 'Space Grotesk', sans-serif;">0</div>
                <div class="text-[10px] sm:text-xs text-slate-600">Rejeté</div>
            </div>

            <div class="bg-white/85 backdrop-blur-[10px] border border-white/40 rounded-xl sm:rounded-2xl p-3 sm:p-4 hover:-translate-y-1 hover:shadow-[0_20px_40px_rgba(0,0,0,0.15)] hover:bg-white/95 transition-all border-l-4 border-pink-500">
                <div class="flex items-center justify-between mb-2 sm:mb-3">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg bg-pink-100 flex items-center justify-center text-pink-600">
                        <i class="fas fa-wallet text-sm sm:text-lg"></i>
                    </div>
                </div>
                <div class="text-xl sm:text-2xl font-bold text-slate-800 mb-0.5" data-counter="<?= $userStats['total_spent'] ?>" data-type="currency" style="font-family: 'Space Grotesk', sans-serif;">0 HTG</div>
                <div class="text-[10px] sm:text-xs text-slate-600">Total dépensé</div>
            </div>

            <div class="bg-white/85 backdrop-blur-[10px] border border-white/40 rounded-xl sm:rounded-2xl p-3 sm:p-4 hover:-translate-y-1 hover:shadow-[0_20px_40px_rgba(0,0,0,0.15)] hover:bg-white/95 transition-all border-l-4 border-indigo-500">
                <div class="flex items-center justify-between mb-2 sm:mb-3">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600">
                        <i class="fas fa-shopping-bag text-sm sm:text-lg"></i>
                    </div>
                </div>
                <div class="text-xl sm:text-2xl font-bold text-slate-800 mb-0.5" data-counter="<?= $userStats['total_orders'] ?>" style="font-family: 'Space Grotesk', sans-serif;">0</div>
                <div class="text-[10px] sm:text-xs text-slate-600">Total commandes</div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="bg-white/95 backdrop-blur-[20px] border border-white/30 shadow-[0_8px_32px_rgba(0,0,0,0.1)] rounded-xl sm:rounded-2xl p-1.5 sm:p-2 mb-6 sm:mb-8 inline-flex gap-1 sm:gap-2 overflow-x-auto max-w-full">
            <a href="?tab=overview" class="px-4 py-2 sm:px-6 sm:py-3 rounded-lg sm:rounded-xl font-semibold text-xs sm:text-sm whitespace-nowrap transition-all border-b-[3px] <?= $activeTab === 'overview' ? 'bg-white shadow-md text-indigo-600 border-indigo-500' : 'text-slate-600 hover:text-slate-800 border-transparent' ?>">
                <i class="fas fa-chart-pie mr-1 sm:mr-2"></i>Aperçu
            </a>
            <a href="?tab=about" class="px-4 py-2 sm:px-6 sm:py-3 rounded-lg sm:rounded-xl font-semibold text-xs sm:text-sm whitespace-nowrap transition-all border-b-[3px] <?= $activeTab === 'about' ? 'bg-white shadow-md text-indigo-600 border-indigo-500' : 'text-slate-600 hover:text-slate-800 border-transparent' ?>">
                <i class="fas fa-user mr-1 sm:mr-2"></i>Informations
            </a>
            <a href="?tab=settings" class="px-4 py-2 sm:px-6 sm:py-3 rounded-lg sm:rounded-xl font-semibold text-xs sm:text-sm whitespace-nowrap transition-all border-b-[3px] <?= $activeTab === 'settings' ? 'bg-white shadow-md text-indigo-600 border-indigo-500' : 'text-slate-600 hover:text-slate-800 border-transparent' ?>">
                <i class="fas fa-sliders-h mr-1 sm:mr-2"></i>Paramètres
            </a>
            <a href="?tab=security" class="px-4 py-2 sm:px-6 sm:py-3 rounded-lg sm:rounded-xl font-semibold text-xs sm:text-sm whitespace-nowrap transition-all border-b-[3px] <?= $activeTab === 'security' ? 'bg-white shadow-md text-indigo-600 border-indigo-500' : 'text-slate-600 hover:text-slate-800 border-transparent' ?>">
                <i class="fas fa-shield-alt mr-1 sm:mr-2"></i>Sécurité
            </a>
        </div>

        <!-- Tab Content -->
        <div class="bg-white/95 backdrop-blur-[20px] border border-white/30 shadow-[0_8px_32px_rgba(0,0,0,0.1)] rounded-2xl sm:rounded-3xl p-6 sm:p-8 min-h-[300px] sm:min-h-[400px]">

            <?php if ($activeTab === 'overview'): ?>
                <div>
                    <div class="flex items-center justify-between mb-4 sm:mb-6">
                        <h2 class="text-xl sm:text-2xl font-bold text-slate-800" style="font-family: 'Space Grotesk', sans-serif;">Activité récente</h2>
                        <?php if ($userStats['total_orders'] > 0): ?>
                            <a href="commandes.php" class="text-indigo-600 hover:text-indigo-700 text-xs sm:text-sm font-semibold flex items-center gap-1">
                                Voir tout <i class="fas fa-arrow-right text-xs"></i>
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($recentOrders) && $userStats['total_orders'] === 0): ?>
                        <div class="space-y-3 sm:space-y-4">
                            <div class="bg-white/80 backdrop-blur-[8px] border border-white/40 hover:bg-white hover:translate-x-2.5 hover:shadow-[0_8px_25px_rgba(0,0,0,0.1)] transition-all flex items-center gap-3 sm:gap-4 p-3 sm:p-4 rounded-xl sm:rounded-2xl cursor-pointer">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 shrink-0">
                                    <i class="fas fa-sign-in-alt"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-semibold text-slate-800 text-sm sm:text-base">Connexion</h4>
                                    <p class="text-xs sm:text-sm text-slate-600">Vous êtes connecté depuis votre appareil</p>
                                </div>
                                <span class="text-xs text-slate-500 shrink-0">Aujourd'hui</span>
                            </div>
                        </div>

                        <div class="mt-6 sm:mt-8 p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-gradient-to-r from-indigo-50/90 to-pink-50/90 backdrop-blur-sm border border-indigo-100">
                            <div class="flex items-center gap-3 sm:gap-4">
                                <div class="w-10 h-10 sm:w-14 sm:h-14 rounded-full bg-white shadow-lg flex items-center justify-center text-xl sm:text-2xl shrink-0">
                                    🎉
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800 text-sm sm:text-base">Bienvenue sur LE-STOCK !</h4>
                                    <p class="text-xs sm:text-sm text-slate-600 mt-1">Vous n'avez pas encore passé de commande. Commencez vos achats pour gagner des points de fidélité et des réductions spéciales.</p>
                                    <a href="acceuil.php" class="inline-flex items-center gap-2 mt-3 px-4 py-2 bg-indigo-600 text-white rounded-lg text-xs sm:text-sm font-semibold hover:bg-indigo-700 transition-colors">
                                        <i class="fas fa-shopping-cart"></i> Commencer les achats
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Commandes récentes depuis la BD - MÊME FORMAT QUE commandes.php -->
                        <div class="space-y-3 sm:space-y-4">
                            <?php foreach ($recentOrders as $order):
                                $statusInfo = getOrderStatus($order['status']);
                                $colorClasses = [
                                    'amber' => 'bg-amber-100 text-amber-600',
                                    'blue' => 'bg-blue-100 text-blue-600',
                                    'purple' => 'bg-purple-100 text-purple-600',
                                    'green' => 'bg-green-100 text-green-600',
                                    'red' => 'bg-red-100 text-red-600',
                                    'gray' => 'bg-gray-100 text-gray-600',
                                    'slate' => 'bg-slate-100 text-slate-600'
                                ];
                                $colorClass = $colorClasses[$statusInfo['color']] ?? $colorClasses['slate'];
                                $isRejected = in_array($order['status'], ['rejected', 'refused']);
                                $isCancelled = $order['status'] === 'cancelled';
                            ?>
                                <a href="order-detail.php?id=<?= $order['id'] ?>" id="order-<?= $order['id'] ?>" class="bg-white/80 backdrop-blur-[8px] border border-white/40 hover:bg-white hover:translate-x-2.5 hover:shadow-[0_8px_25px_rgba(0,0,0,0.1)] transition-all flex items-center gap-3 sm:gap-4 p-3 sm:p-4 rounded-xl sm:rounded-2xl cursor-pointer block <?= ($isRejected || $isCancelled) ? 'opacity-70' : '' ?>">
                                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full <?= $colorClass ?> flex items-center justify-center shrink-0">
                                        <i class="fas fa-<?= $statusInfo['icon'] ?>"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-0.5">
                                            <h4 class="font-semibold text-slate-800 text-sm sm:text-base <?= ($isRejected || $isCancelled) ? 'line-through' : '' ?>">
                                                #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?>
                                            </h4>
                                            <span class="text-[10px] px-1.5 py-0.5 rounded-full <?= $colorClass ?> font-medium"><?= $statusInfo['label'] ?></span>
                                        </div>
                                        <p class="text-xs sm:text-sm text-slate-600 truncate">
                                            <?= !empty($order['products']) ? htmlspecialchars(mb_substr($order['products'], 0, 50)) . (mb_strlen($order['products']) > 50 ? '...' : '') : $order['item_count'] . ' article' . ($order['item_count'] > 1 ? 's' : '') ?>
                                        </p>
                                        <?php if ($isRejected && !empty($order['reject_reason'])): ?>
                                            <p class="text-[10px] text-red-600 mt-1 truncate">
                                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                                <?= htmlspecialchars(mb_substr($order['reject_reason'], 0, 60)) ?>...
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p class="font-bold text-slate-800 text-sm <?= ($isRejected || $isCancelled) ? 'line-through text-slate-400' : '' ?>">
                                            <?= formatPrice($order['total']) ?>
                                        </p>
                                        <span class="text-[10px] text-slate-500 mt-1 block"><?= timeAgo($order['created_at']) ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($userStats['total_orders'] > 5): ?>
                            <div class="mt-4 text-center">
                                <a href="commandes.php" class="inline-flex items-center gap-2 px-4 py-2 sm:px-6 sm:py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-semibold transition-colors text-sm">
                                    <i class="fas fa-list"></i> Voir toutes les <?= $userStats['total_orders'] ?> commandes
                                </a>
                            </div>
                        <?php endif; ?>

                        <!-- Résumé des dépenses - MÊMES STATUTS QUE commandes.php -->
                        <div class="mt-6 sm:mt-8 p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-gradient-to-r from-slate-800 to-slate-900 text-white">
                            <h4 class="font-bold mb-4 text-sm sm:text-base" style="font-family: 'Space Grotesk', sans-serif;">
                                <i class="fas fa-chart-bar mr-2 text-indigo-400"></i>Résumé de vos commandes
                            </h4>
                            <div class="grid grid-cols-3 sm:grid-cols-7 gap-3 text-center">
                                <div>
                                    <p class="text-lg sm:text-2xl font-bold text-yellow-400" style="font-family: 'Space Grotesk', sans-serif;"><?= $userStats['pending_orders'] ?></p>
                                    <p class="text-[10px] sm:text-xs text-slate-400 mt-1">En attente</p>
                                </div>
                                <div>
                                    <p class="text-lg sm:text-2xl font-bold text-blue-400" style="font-family: 'Space Grotesk', sans-serif;"><?= $userStats['validated_orders'] ?></p>
                                    <p class="text-[10px] sm:text-xs text-slate-400 mt-1">Validé</p>
                                </div>
                                <div>
                                    <p class="text-lg sm:text-2xl font-bold text-green-400" style="font-family: 'Space Grotesk', sans-serif;"><?= $userStats['delivered_orders'] ?></p>
                                    <p class="text-[10px] sm:text-xs text-slate-400 mt-1">Livré</p>
                                </div>
                                <div>
                                    <p class="text-lg sm:text-2xl font-bold text-red-400" style="font-family: 'Space Grotesk', sans-serif;"><?= $userStats['rejected_orders'] ?></p>
                                    <p class="text-[10px] sm:text-xs text-slate-400 mt-1">Rejeté</p>
                                </div>
                                <div>
                                    <p class="text-lg sm:text-2xl font-bold text-gray-400" style="font-family: 'Space Grotesk', sans-serif;"><?= $userStats['cancelled_orders'] ?></p>
                                    <p class="text-[10px] sm:text-xs text-slate-400 mt-1">Annulé</p>
                                </div>
                                <div>
                                    <p class="text-lg sm:text-2xl font-bold text-white" style="font-family: 'Space Grotesk', sans-serif;"><?= $userStats['total_orders'] ?></p>
                                    <p class="text-[10px] sm:text-xs text-slate-400 mt-1">Total</p>
                                </div>
                                <div>
                                    <p class="text-lg sm:text-2xl font-bold text-pink-400" style="font-family: 'Space Grotesk', sans-serif;"><?= number_format($userStats['total_spent'], 0, ',', ' ') ?></p>
                                    <p class="text-[10px] sm:text-xs text-slate-400 mt-1">HTG</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($activeTab === 'about'): ?>
                <div class="max-w-3xl">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-800 mb-4 sm:mb-6" style="font-family: 'Space Grotesk', sans-serif;">Informations personnelles</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-white/88 backdrop-blur-[12px] border border-white/50 hover:-translate-y-1 hover:shadow-[0_20px_40px_rgba(0,0,0,0.15)] hover:bg-white/95 transition-all">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600">
                                    <i class="fas fa-user"></i>
                                </div>
                                <span class="text-xs font-bold text-slate-500 uppercase">Nom complet</span>
                            </div>
                            <p class="text-base sm:text-lg font-semibold text-slate-800">
                                <?= htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?>
                            </p>
                        </div>

                        <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-white/88 backdrop-blur-[12px] border border-white/50 hover:-translate-y-1 hover:shadow-[0_20px_40px_rgba(0,0,0,0.15)] hover:bg-white/95 transition-all">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg bg-violet-100 flex items-center justify-center text-violet-600">
                                    <i class="fas fa-id-badge"></i>
                                </div>
                                <span class="text-xs font-bold text-slate-500 uppercase">UI ID</span>
                            </div>
                            <p class="text-base sm:text-lg font-semibold text-slate-800"><?= htmlspecialchars($displayId) ?></p>
                        </div>

                        <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-white/88 backdrop-blur-[12px] border border-white/50 hover:-translate-y-1 hover:shadow-[0_20px_40px_rgba(0,0,0,0.15)] hover:bg-white/95 transition-all">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg bg-pink-100 flex items-center justify-center text-pink-600">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <span class="text-xs font-bold text-slate-500 uppercase">E-mail</span>
                            </div>
                            <p class="text-base sm:text-lg font-semibold text-slate-800 break-all"><?= htmlspecialchars($user['email']) ?></p>
                        </div>

                        <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-white/88 backdrop-blur-[12px] border border-white/50 hover:-translate-y-1 hover:shadow-[0_20px_40px_rgba(0,0,0,0.15)] hover:bg-white/95 transition-all">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg bg-amber-100 flex items-center justify-center text-amber-600">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <span class="text-xs font-bold text-slate-500 uppercase">Téléphone</span>
                            </div>
                            <p class="text-base sm:text-lg font-semibold text-slate-800"><?= $user['telephone'] ?: 'Non ajouté' ?></p>
                        </div>

                        <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-white/88 backdrop-blur-[12px] border border-white/50 hover:-translate-y-1 hover:shadow-[0_20px_40px_rgba(0,0,0,0.15)] hover:bg-white/95 transition-all sm:col-span-2">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg bg-green-100 flex items-center justify-center text-green-600">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <span class="text-xs font-bold text-slate-500 uppercase">Adresse</span>
                            </div>
                            <p class="text-base sm:text-lg font-semibold text-slate-800"><?= $user['adresse'] ?: 'Non ajouté' ?></p>
                        </div>
                    </div>

                    <!-- Statistiques d'achat -->
                    <div class="mt-6 sm:mt-8 p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-gradient-to-r from-indigo-500 to-pink-500 text-white">
                        <h4 class="font-bold mb-4 flex items-center gap-2 text-sm sm:text-base">
                            <i class="fas fa-chart-bar"></i>
                            Statistiques de vos achats
                        </h4>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div class="bg-white/20 rounded-xl p-3 text-center">
                                <p class="text-2xl font-bold" style="font-family: 'Space Grotesk', sans-serif;"><?= $userStats['total_orders'] ?></p>
                                <p class="text-xs opacity-80 mt-1">Total commandes</p>
                            </div>
                            <div class="bg-white/20 rounded-xl p-3 text-center">
                                <p class="text-2xl font-bold" style="font-family: 'Space Grotesk', sans-serif;"><?= formatPrice($userStats['total_spent']) ?></p>
                                <p class="text-xs opacity-80 mt-1">Total dépensé</p>
                            </div>
                            <div class="bg-white/20 rounded-xl p-3 text-center">
                                <p class="text-2xl font-bold" style="font-family: 'Space Grotesk', sans-serif;"><?= $userStats['total_favorites'] ?></p>
                                <p class="text-xs opacity-80 mt-1">Favoris</p>
                            </div>
                            <div class="bg-white/20 rounded-xl p-3 text-center">
                                <p class="text-2xl font-bold" style="font-family: 'Space Grotesk', sans-serif;"><?= $userStats['total_orders'] > 0 ? formatPrice($userStats['total_spent'] / $userStats['total_orders']) : '0.00 HTG' ?></p>
                                <p class="text-xs opacity-80 mt-1">Moyenne/Commande</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 sm:mt-8 p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-gradient-to-r from-slate-800 to-slate-900 text-white">
                        <h4 class="font-bold mb-2 flex items-center gap-2 text-sm sm:text-base">
                            <i class="fas fa-info-circle text-indigo-400"></i>
                            Informations du compte
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4 text-xs sm:text-sm">
                            <div>
                                <span class="text-slate-400">UI ID :</span>
                                <p class="font-semibold"><?= htmlspecialchars($displayId) ?></p>
                            </div>
                            <div>
                                <span class="text-slate-400">Rôle :</span>
                                <p class="font-semibold"><?= ucfirst($user['role'] ?? 'User') ?></p>
                            </div>
                            <div>
                                <span class="text-slate-400">Membre depuis :</span>
                                <p class="font-semibold"><?= date('d M Y', strtotime($user['created_at'] ?? 'now')) ?></p>
                            </div>
                            <div>
                                <span class="text-slate-400">Dernière mise à jour :</span>
                                <p class="font-semibold"><?= date('d M Y', strtotime($user['updated_at'] ?? 'now')) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($activeTab === 'settings'): ?>
                <div class="max-w-2xl">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-800 mb-4 sm:mb-6" style="font-family: 'Space Grotesk', sans-serif;">Modifier le profil</h2>

                    <?php if (isset($_GET['success'])): ?>
                        <div class="mb-4 sm:mb-6 p-3 sm:p-4 rounded-xl bg-green-100/90 backdrop-blur-sm text-green-700 flex items-center gap-3 border border-green-200">
                            <i class="fas fa-check-circle text-lg sm:text-xl"></i>
                            <span class="font-semibold text-sm sm:text-base">Votre profil a été mis à jour avec succès !</span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-4 sm:space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <div>
                                <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-2">Prénom</label>
                                <input type="text" name="prenom" value="<?= htmlspecialchars($user['prenom'] ?? '') ?>"
                                    class="bg-white/92 backdrop-blur-[12px] border border-white/60 shadow-[0_4px_20px_rgba(0,0,0,0.08)] focus:bg-white focus:border-indigo-500 focus:shadow-[0_0_0_4px_rgba(102,126,234,0.15),0_8px_30px_rgba(0,0,0,0.12)] focus:-translate-y-0.5 transition-all w-full px-3 py-2.5 sm:px-4 sm:py-3 rounded-xl focus:outline-none font-medium text-base text-slate-800" required>
                            </div>
                            <div>
                                <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-2">Nom</label>
                                <input type="text" name="nom" value="<?= htmlspecialchars($user['nom'] ?? '') ?>"
                                    class="bg-white/92 backdrop-blur-[12px] border border-white/60 shadow-[0_4px_20px_rgba(0,0,0,0.08)] focus:bg-white focus:border-indigo-500 focus:shadow-[0_0_0_4px_rgba(102,126,234,0.15),0_8px_30px_rgba(0,0,0,0.12)] focus:-translate-y-0.5 transition-all w-full px-3 py-2.5 sm:px-4 sm:py-3 rounded-xl focus:outline-none font-medium text-base text-slate-800" required>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-2">Téléphone</label>
                            <div class="relative">
                                <i class="fas fa-phone absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                                <input type="tel" name="phone" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>"
                                    class="bg-white/92 backdrop-blur-[12px] border border-white/60 shadow-[0_4px_20px_rgba(0,0,0,0.08)] focus:bg-white focus:border-indigo-500 focus:shadow-[0_0_0_4px_rgba(102,126,234,0.15),0_8px_30px_rgba(0,0,0,0.12)] focus:-translate-y-0.5 transition-all w-full pl-9 sm:pl-12 pr-3 sm:pr-4 py-2.5 sm:py-3 rounded-xl focus:outline-none font-medium text-base text-slate-800">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-2">Adresse</label>
                            <div class="relative">
                                <i class="fas fa-map-marker-alt absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                                <input type="text" name="adresse" value="<?= htmlspecialchars($user['adresse'] ?? '') ?>"
                                    class="bg-white/92 backdrop-blur-[12px] border border-white/60 shadow-[0_4px_20px_rgba(0,0,0,0.08)] focus:bg-white focus:border-indigo-500 focus:shadow-[0_0_0_4px_rgba(102,126,234,0.15),0_8px_30px_rgba(0,0,0,0.12)] focus:-translate-y-0.5 transition-all w-full pl-9 sm:pl-12 pr-3 sm:pr-4 py-2.5 sm:py-3 rounded-xl focus:outline-none font-medium text-base text-slate-800">
                            </div>
                        </div>

                        <button type="submit" name="update_info" class="bg-gradient-to-br from-[#667eea] to-[#764ba2] hover:-translate-y-0.5 hover:shadow-[0_10px_30px_rgba(102,126,234,0.4)] transition-all w-full py-3 sm:py-4 rounded-xl text-white font-bold text-base sm:text-lg shadow-lg flex items-center justify-center gap-2">
                            <i class="fas fa-save"></i>
                            Enregistrer les modifications
                        </button>
                    </form>

                    <!-- Merchant/Admin Section -->
                    <div class="mt-8 sm:mt-12 pt-6 sm:pt-8 border-t border-slate-200/60">
                        <?php if ($isMerchant): ?>
                            <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-gradient-to-r from-amber-400 to-orange-500 text-white shadow-xl">
                                <div class="flex flex-col sm:flex-row items-center gap-4">
                                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                                        <i class="fas fa-crown text-3xl sm:text-4xl"></i>
                                    </div>
                                    <div class="flex-1 text-center sm:text-left">
                                        <h3 class="text-lg sm:text-xl font-bold">Marchand Premium</h3>
                                        <p class="mt-1 opacity-90 text-sm sm:text-base">Vous avez accès à tous les avantages marchand.</p>
                                        <div class="mt-4 flex flex-col sm:flex-row items-center gap-3 sm:gap-4">
                                            <div class="bg-white/20 rounded-xl px-4 py-2 flex items-center gap-2">
                                                <i class="fas fa-coins"></i>
                                                <span class="font-bold text-lg"><?= number_format($merchantPoints, 0, ',', ' ') ?> Points</span>
                                            </div>
                                            <a href="cashout.php" class="inline-flex items-center gap-2 px-4 py-2 sm:px-6 sm:py-3 bg-white text-orange-600 rounded-xl font-bold transition-all shadow-lg hover:shadow-xl text-sm">
                                                <i class="fas fa-money-bill-wave"></i>
                                                Demander un retrait
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($status_demann === 'pending'): ?>
                            <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 shadow-lg">
                                <div class="flex items-center gap-3 sm:gap-4 mb-4">
                                    <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 shrink-0">
                                        <i class="fas fa-hourglass-half text-xl sm:text-2xl animate-pulse"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-bold text-blue-900 text-sm sm:text-base">Demande de marchand en cours...</h4>
                                        <p class="text-xs sm:text-sm text-blue-700">Notre équipe vérifie vos documents. Cela peut prendre 24 à 48 heures.</p>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <div class="flex justify-between text-xs font-semibold text-blue-800 mb-2">
                                        <span>Soumise</span>
                                        <span>Vérification</span>
                                        <span>Approbation</span>
                                    </div>
                                    <div class="bg-white/30 rounded-full overflow-hidden h-3">
                                        <div class="bg-gradient-to-r from-blue-500 to-violet-500 h-full rounded-full transition-all duration-1000" style="width: 50%"></div>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center text-xs text-blue-600">
                                    <span class="flex items-center gap-1"><i class="fas fa-check-circle text-green-500"></i> Demande soumise</span>
                                    <span class="flex items-center gap-1"><i class="fas fa-spinner fa-spin"></i> En vérification</span>
                                    <span class="text-blue-300"><i class="fas fa-crown"></i> Approbation finale</span>
                                </div>
                            </div>
                        <?php elseif ($status_demann === 'rejected'): ?>
                            <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-red-50 border-2 border-red-200 flex items-center gap-3 sm:gap-4">
                                <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-red-100 flex items-center justify-center text-red-600 shrink-0">
                                    <i class="fas fa-times-circle text-xl sm:text-2xl"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-red-900 text-sm sm:text-base">Demande rejetée</h4>
                                    <p class="text-xs sm:text-sm text-red-700">Votre demande n'a pas pu être approuvée. Contactez le support pour plus d'informations.</p>
                                    <a href="form_machann.php" class="inline-block mt-2 text-xs font-bold text-red-600 hover:text-red-800 underline">Réessayer</a>
                                </div>
                            </div>
                        <?php elseif (!$isAdmin): ?>
                            <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-white/88 backdrop-blur-[12px] border border-white/50 hover:-translate-y-1 hover:shadow-[0_20px_40px_rgba(0,0,0,0.15)] hover:bg-white/95 transition-all">
                                <div class="flex flex-col sm:flex-row items-start gap-3 sm:gap-4">
                                    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-gradient-to-br from-indigo-500 to-pink-500 flex items-center justify-center text-white text-xl sm:text-2xl shadow-lg shrink-0">
                                        <i class="fas fa-store"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-base sm:text-lg font-bold text-slate-800">Devenir marchand ?</h4>
                                        <p class="text-xs sm:text-sm text-slate-600 mt-1 mb-3 sm:mb-4">Créez votre propre boutique et commencez à vendre vos produits sur LE-STOCK.</p>
                                        <a href="form_machann.php" class="inline-flex items-center gap-2 px-4 py-2 sm:px-6 sm:py-3 bg-slate-800 hover:bg-slate-900 text-white rounded-xl font-semibold transition-all shadow-lg hover:shadow-xl text-sm">
                                            <i class="fas fa-arrow-right"></i>
                                            Commencer maintenant
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($activeTab === 'security'): ?>
                <div class="max-w-2xl">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-800 mb-4 sm:mb-6" style="font-family: 'Space Grotesk', sans-serif;">Sécurité du compte</h2>
                    <div class="space-y-4 sm:space-y-6">
                        <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-white/88 backdrop-blur-[12px] border border-white/50 shadow-sm">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3 sm:mb-4">
                                <div>
                                    <h4 class="font-bold text-slate-800 text-sm sm:text-base">Mot de passe</h4>
                                    <p class="text-xs sm:text-sm text-slate-500">Dernière modification : il y a 30 jours</p>
                                </div>
                                <button class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-lg border border-slate-300 hover:bg-white/80 font-medium text-xs sm:text-sm transition-colors bg-white/92 backdrop-blur-[12px] shadow-[0_4px_20px_rgba(0,0,0,0.08)]">
                                    Modifier
                                </button>
                            </div>
                            <div class="w-full bg-slate-200/50 rounded-full h-1.5 sm:h-2">
                                <div class="bg-green-500 h-1.5 sm:h-2 rounded-full" style="width: 85%"></div>
                            </div>
                            <p class="text-xs text-slate-500 mt-2">Force du mot de passe : Bon</p>
                        </div>
                        <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-white/88 backdrop-blur-[12px] border border-white/50 shadow-sm">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600 shrink-0">
                                        <i class="fas fa-mobile-alt"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-800 text-sm sm:text-base">Vérification en deux étapes</h4>
                                        <p class="text-xs sm:text-sm text-slate-500">Activez pour une sécurité supérieure</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer shrink-0">
                                    <input type="checkbox" class="sr-only peer">
                                    <div class="w-10 h-5 sm:w-11 sm:h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 sm:after:h-5 sm:after:w-5 after:transition-all peer-checked:bg-indigo-500"></div>
                                </label>
                            </div>
                        </div>
                        <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-red-50/90 backdrop-blur-sm border border-red-100">
                            <h4 class="font-bold text-red-800 mb-2 flex items-center gap-2 text-sm sm:text-base">
                                <i class="fas fa-exclamation-triangle"></i>
                                Zone dangereuse
                            </h4>
                            <p class="text-xs sm:text-sm text-red-600 mb-3 sm:mb-4">Ces actions sont irréversibles. Faites preuve d'attention.</p>
                            <button class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white font-medium text-xs sm:text-sm transition-colors">
                                <i class="fas fa-trash-alt mr-1 sm:mr-2"></i>Supprimer le compte
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        let mobileMenuOpen = false;

        function toggleMobileMenu() {
            mobileMenuOpen = !mobileMenuOpen;
            const menu = document.getElementById('mobile-menu');
            const overlay = document.getElementById('mobile-overlay');
            const icon = document.getElementById('hamburger-icon');

            if (mobileMenuOpen) {
                menu.style.maxHeight = '400px';
                menu.style.opacity = '1';
                overlay.style.opacity = '1';
                overlay.style.visibility = 'visible';
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
                document.body.style.overflow = 'hidden';
            } else {
                closeMobileMenu();
            }
        }

        function closeMobileMenu() {
            mobileMenuOpen = false;
            const menu = document.getElementById('mobile-menu');
            const overlay = document.getElementById('mobile-overlay');
            const icon = document.getElementById('hamburger-icon');

            menu.style.maxHeight = '0';
            menu.style.opacity = '0';
            overlay.style.opacity = '0';
            overlay.style.visibility = 'hidden';
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
            document.body.style.overflow = '';
        }

        document.querySelectorAll('#mobile-menu a').forEach(link => {
            link.addEventListener('click', () => closeMobileMenu());
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && mobileMenuOpen) closeMobileMenu();
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768 && mobileMenuOpen) closeMobileMenu();
        });

        // Animation des compteurs
        const animateValue = (obj, start, end, duration, isCurrency = false) => {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                const easedProgress = 1 - Math.pow(1 - progress, 3);
                const currentValue = Math.floor(easedProgress * (end - start) + start);

                if (isCurrency) {
                    obj.innerHTML = currentValue.toLocaleString('fr-FR', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }) + ' HTG';
                } else {
                    obj.innerHTML = currentValue.toLocaleString('fr-FR');
                }

                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        };

        document.addEventListener('DOMContentLoaded', () => {
            const stats = document.querySelectorAll('[data-counter]');
            stats.forEach(stat => {
                const endValue = parseFloat(stat.getAttribute('data-counter')) || 0;
                const isCurrency = stat.getAttribute('data-type') === 'currency';

                if (endValue > 0) {
                    animateValue(stat, 0, endValue, 1500, isCurrency);
                } else if (isCurrency) {
                    stat.innerHTML = '0.00 HTG';
                } else {
                    stat.innerHTML = '0';
                }
            });
        });

        history.pushState(null, null, window.location.href);

        // 2. Chak fwa itilizatè a klike sou bouton "Bak", nou fòse l tounen devan
        window.onpopstate = function() {
            history.go(1);
        };

        // 3. Pou asire nou sa mache sou tout navigatè (Chrome, Firefox, Safari)
        // Nou toujou pouse yon nouvo eta nan istwa a chak fwa li eseye pati
        window.addEventListener('popstate', function(event) {
            history.pushState(null, null, window.location.href);
        });
    </script>
</body>

</html>