<?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch user stats - verifye si tab yo egziste avan
    $userStats = [
        'total_orders' => 0,
        'total_spent' => 0,
        'total_favorites' => 0
    ];

    // Eseye pran estatistik komand si tab la egziste
    try {
        $stats = $pdo->prepare("SELECT 
            COUNT(DISTINCT c.id) as total_orders,
            COALESCE(SUM(c.total), 0) as total_spent
            FROM commandes c
            WHERE c.user_id = ? AND c.status != 'cancelled'");
        $stats->execute([$user_id]);
        $orderStats = $stats->fetch(PDO::FETCH_ASSOC);
        $userStats['total_orders'] = $orderStats['total_orders'] ?? 0;
        $userStats['total_spent'] = $orderStats['total_spent'] ?? 0;
    } catch (PDOException $e) {
        // Tab commandes pa egziste, kite default values
        $userStats['total_orders'] = 0;
        $userStats['total_spent'] = 0;
    }

    // Eseye pran kantite favori si tab la egziste
    try {
        $favStmt = $pdo->prepare("SELECT COUNT(*) as total FROM favoris WHERE user_id = ?");
        $favStmt->execute([$user_id]);
        $favResult = $favStmt->fetch(PDO::FETCH_ASSOC);
        $userStats['total_favorites'] = $favResult['total'] ?? 0;
    } catch (PDOException $e) {
        // Tab favoris pa egziste
        $userStats['total_favorites'] = 0;
    }

    // Si se machann, pran pwen li yo
    $merchantPoints = 0;
    if (strtolower($user['role'] ?? '') === 'merchant') {
        try {
            $pointsStmt = $pdo->prepare("SELECT points FROM merchant_points WHERE user_id = ?");
            $pointsStmt->execute([$user_id]);
            $pointsResult = $pointsStmt->fetch(PDO::FETCH_ASSOC);
            $merchantPoints = $pointsResult['points'] ?? 0;
        } catch (PDOException $e) {
            // Tab merchant_points pa egziste
            $merchantPoints = 0;
        }
    }
} catch (PDOException $e) {
    die("Erè baz de done: " . $e->getMessage());
}

$activeTab = $_GET['tab'] ?? 'overview';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_info'])) {
    $prenom = htmlspecialchars($_POST['prenom']);
    $nom = htmlspecialchars($_POST['nom']);
    $adresse = htmlspecialchars($_POST['adresse']);
    $phone = htmlspecialchars($_POST['phone']);

    $update = $pdo->prepare("UPDATE users SET prenom = ?, nom = ?, adresse = ?, telephone = ? WHERE id = ?");
    if ($update->execute([$prenom, $nom, $adresse, $phone, $user_id])) {
        header("Location: profile.php?tab=settings&success=1");
        exit();
    }
}

// Verifye si se admin
$isAdmin = strtolower($user['role'] ?? '') === 'admin';
// Verifye si se machann
$isMerchant = strtolower($user['role'] ?? '') === 'merchant';
?>
<!DOCTYPE html>
<html lang="ht">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pwofil - <?= htmlspecialchars($user['prenom']) ?></title>
    <!-- Tailwind CSS CLI -->
    <link rel="stylesheet" href="/le-stock/css/style.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css ">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300 ;400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
            background: #f5f5f5;
        }

        /* IMAJ BACKGROUND FLOU 5% */
        .bg-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            overflow: hidden;
        }

        .bg-image {
            width: 100%;
            height: 100%;
            background-image: url('/le-stock/assets/img/stock7.png');
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            /* FLOU 5% */
            filter: blur(5px);
            -webkit-filter: blur(5px);
            transform: scale(1.03);
            /* Evite bò ki klè akoz flou a */
        }

        /* Overlay pou kontras */
        .bg-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.25);
            z-index: -1;
        }

        .font-display {
            font-family: 'Space Grotesk', sans-serif;
        }

        /* GLASS MORPHISM POU CHAMPS FÒM YO */
        .glass-input {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .glass-input:focus {
            background: rgba(255, 255, 255, 1);
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15), 0 8px 30px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: all 0.3s ease;
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            background: rgba(255, 255, 255, 0.95);
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-20px);
            }
        }

        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-slide-up {
            animation: slideUp 0.5s ease-out forwards;
        }

        @keyframes pulse-slow {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        .animate-pulse-slow {
            animation: pulse-slow 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        .nav-tab {
            position: relative;
            transition: all 0.3s ease;
        }

        .nav-tab::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: width 0.3s ease;
            border-radius: 2px;
        }

        .nav-tab.active::after,
        .nav-tab:hover::after {
            width: 100%;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .badge-merchant {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        }

        .badge-admin {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .activity-item {
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        .activity-item:hover {
            background: rgba(255, 255, 255, 0.98);
            transform: translateX(10px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .avatar-glow {
            box-shadow: 0 0 60px rgba(99, 102, 241, 0.4);
        }

        .text-shadow {
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
        }

        /* RESPONSIVE POU MOBIL */
        @media (max-width: 768px) {
            .bg-image {
                background-attachment: scroll;
                filter: blur(4px);
                -webkit-filter: blur(4px);
                transform: scale(1.05);
            }

            .glass-input {
                font-size: 16px;
            }
        }

        @media (max-width: 480px) {
            .bg-image {
                filter: blur(3px);
                -webkit-filter: blur(3px);
                transform: scale(1.08);
            }
        }

        @media (max-width: 640px) {
            .nav-tab {
                padding: 0.5rem 0.75rem;
                font-size: 0.75rem;
            }
        }
    </style>
</head>

<body class="text-slate-800">

    <!-- IMAJ BACKGROUND FLOU 5% -->
    <div class="bg-container">
        <div class="bg-image"></div>
    </div>
    <div class="bg-overlay"></div>

    <!-- Navigation -->
    <nav class="glass-panel fixed w-full z-50 top-0 left-0 border-b border-white/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-pink-500 flex items-center justify-center text-white shadow-lg animate-pulse-slow">
                        <i class="fas fa-bolt text-lg sm:text-xl"></i>
                    </div>
                    <span class="text-xl sm:text-2xl font-display font-bold text-slate-800 tracking-tight">LE-STOCK</span>
                </div>

                <div class="hidden md:flex items-center gap-6 lg:gap-8">
                    <?php if ($isAdmin): ?>
                        <!-- Lyen Dashboard pou Admin -->
                        <a href="admin_dashboard.php" class="flex items-center gap-2 text-red-600 hover:text-red-700 font-bold transition-colors text-sm">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    <?php else: ?>
                        <!-- Lyen regilye pou lòt moun -->
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

                <button class="md:hidden text-slate-600 text-xl">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-20 sm:pt-24 pb-12 px-4 sm:px-6 max-w-7xl mx-auto relative z-10">

        <!-- Profile Header -->
        <div class="glass-panel rounded-2xl sm:rounded-3xl p-6 sm:p-8 mb-6 sm:mb-8 animate-slide-up relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 sm:w-96 sm:h-96 bg-gradient-to-br from-indigo-500/20 to-pink-500/20 rounded-full blur-3xl -mr-10 sm:-mr-20 -mt-10 sm:-mt-20"></div>

            <div class="relative z-10 flex flex-col lg:flex-row items-center gap-6 sm:gap-8">
                <div class="relative">
                    <div class="w-24 h-24 sm:w-32 sm:h-32 rounded-2xl sm:rounded-3xl bg-gradient-to-br from-indigo-500 to-pink-500 flex items-center justify-center text-white text-3xl sm:text-4xl font-display font-bold shadow-2xl avatar-glow animate-float">
                        <?= strtoupper(substr($user['prenom'] ?? '', 0, 1) . substr($user['nom'] ?? '', 0, 1)) ?>
                    </div>
                    <div class="absolute -bottom-1 -right-1 sm:-bottom-2 sm:-right-2 w-6 h-6 sm:w-8 sm:h-8 bg-green-500 rounded-full border-2 sm:border-4 border-white flex items-center justify-center" title="Online">
                        <div class="w-2 h-2 sm:w-3 sm:h-3 bg-white rounded-full animate-pulse"></div>
                    </div>
                </div>

                <div class="flex-1 text-center lg:text-left">
                    <h1 class="text-2xl sm:text-4xl lg:text-5xl font-display font-bold text-slate-800 mb-2 text-shadow">
                        <?= htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?>
                    </h1>
                    <p class="text-slate-600 mb-4 flex items-center justify-center lg:justify-start gap-2 text-sm sm:text-base">
                        <i class="fas fa-envelope text-indigo-500"></i>
                        <?= htmlspecialchars($user['email']) ?>
                    </p>

                    <div class="flex flex-wrap gap-2 sm:gap-3 justify-center lg:justify-start">
                        <span class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-full bg-slate-100/80 backdrop-blur-sm text-slate-700 text-xs sm:text-sm font-medium flex items-center gap-2 border border-white/50">
                            <i class="fas fa-id-badge text-indigo-500"></i>
                            ID: #LS-<?= str_pad($user['id'], 4, '0', STR_PAD_LEFT) ?>
                        </span>
                        <?php if ($isMerchant): ?>
                            <span class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-full badge-merchant text-white text-xs sm:text-sm font-bold flex items-center gap-2 shadow-lg">
                                <i class="fas fa-crown"></i> Machann Premium
                            </span>
                        <?php elseif ($isAdmin): ?>
                            <span class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-full badge-admin text-white text-xs sm:text-sm font-bold flex items-center gap-2 shadow-lg">
                                <i class="fas fa-shield-alt"></i> Admin
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex gap-3 sm:gap-4">
                    <a href="?tab=settings" class="px-4 py-2 sm:px-6 sm:py-3 rounded-xl sm:rounded-2xl bg-white/80 backdrop-blur-sm hover:bg-white text-slate-700 font-semibold transition-all flex items-center gap-2 text-sm border border-white/50 shadow-lg">
                        <i class="fas fa-cog"></i> <span class="hidden sm:inline">Paramèt</span>
                    </a>
                    <button onclick="alert('Fonksyonalite nan devlopman')" class="px-4 py-2 sm:px-6 sm:py-3 rounded-xl sm:rounded-2xl bg-slate-800 hover:bg-slate-900 text-white font-semibold transition-all flex items-center gap-2 shadow-lg text-sm">
                        <i class="fas fa-share-alt"></i> <span class="hidden sm:inline">Pataje</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 mb-6 sm:mb-8">
            <div class="glass-card rounded-xl sm:rounded-2xl p-4 sm:p-6 stat-card">
                <div class="flex items-center justify-between mb-3 sm:mb-4">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-indigo-100 flex items-center justify-center text-indigo-600">
                        <i class="fas fa-shopping-bag text-lg sm:text-xl"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total</span>
                </div>
                <div class="text-2xl sm:text-3xl font-display font-bold text-slate-800 mb-1"><?= $userStats['total_orders'] ?? 0 ?></div>
                <div class="text-xs sm:text-sm text-slate-600">Komand yo</div>
            </div>

            <div class="glass-card rounded-xl sm:rounded-2xl p-4 sm:p-6 stat-card">
                <div class="flex items-center justify-between mb-3 sm:mb-4">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-pink-100 flex items-center justify-center text-pink-600">
                        <i class="fas fa-wallet text-lg sm:text-xl"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Depans</span>
                </div>
                <div class="text-2xl sm:text-3xl font-display font-bold text-slate-800 mb-1">
                    <?= number_format($userStats['total_spent'] ?? 0, 0, ',', ' ') ?> Gdes
                </div>
                <div class="text-xs sm:text-sm text-slate-600">Total depanse</div>
            </div>

            <div class="glass-card rounded-xl sm:rounded-2xl p-4 sm:p-6 stat-card">
                <div class="flex items-center justify-between mb-3 sm:mb-4">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600">
                        <i class="fas fa-heart text-lg sm:text-xl"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Favori</span>
                </div>
                <div class="text-2xl sm:text-3xl font-display font-bold text-slate-800 mb-1"><?= $userStats['total_favorites'] ?? 0 ?></div>
                <div class="text-xs sm:text-sm text-slate-600">Atik favori</div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="glass-panel rounded-xl sm:rounded-2xl p-1.5 sm:p-2 mb-6 sm:mb-8 inline-flex gap-1 sm:gap-2 overflow-x-auto max-w-full">
            <a href="?tab=overview" class="nav-tab px-4 py-2 sm:px-6 sm:py-3 rounded-lg sm:rounded-xl font-semibold text-xs sm:text-sm whitespace-nowrap <?= $activeTab === 'overview' ? 'active bg-white shadow-md text-indigo-600' : 'text-slate-600 hover:text-slate-800' ?>">
                <i class="fas fa-chart-pie mr-1 sm:mr-2"></i>Apèsi
            </a>
            <a href="?tab=about" class="nav-tab px-4 py-2 sm:px-6 sm:py-3 rounded-lg sm:rounded-xl font-semibold text-xs sm:text-sm whitespace-nowrap <?= $activeTab === 'about' ? 'active bg-white shadow-md text-indigo-600' : 'text-slate-600 hover:text-slate-800' ?>">
                <i class="fas fa-user mr-1 sm:mr-2"></i>Enfòmasyon
            </a>
            <a href="?tab=settings" class="nav-tab px-4 py-2 sm:px-6 sm:py-3 rounded-lg sm:rounded-xl font-semibold text-xs sm:text-sm whitespace-nowrap <?= $activeTab === 'settings' ? 'active bg-white shadow-md text-indigo-600' : 'text-slate-600 hover:text-slate-800' ?>">
                <i class="fas fa-sliders-h mr-1 sm:mr-2"></i>Paramèt
            </a>
            <a href="?tab=security" class="nav-tab px-4 py-2 sm:px-6 sm:py-3 rounded-lg sm:rounded-xl font-semibold text-xs sm:text-sm whitespace-nowrap <?= $activeTab === 'security' ? 'active bg-white shadow-md text-indigo-600' : 'text-slate-600 hover:text-slate-800' ?>">
                <i class="fas fa-shield-alt mr-1 sm:mr-2"></i>Sekirite
            </a>
        </div>

        <!-- Tab Content -->
        <div class="glass-panel rounded-2xl sm:rounded-3xl p-6 sm:p-8 min-h-[300px] sm:min-h-[400px]">

            <?php if ($activeTab === 'overview'): ?>
                <div class="animate-slide-up">
                    <h2 class="text-xl sm:text-2xl font-display font-bold text-slate-800 mb-4 sm:mb-6">Aktivite Resan</h2>

                    <div class="space-y-3 sm:space-y-4">
                        <div class="activity-item flex items-center gap-3 sm:gap-4 p-3 sm:p-4 rounded-xl sm:rounded-2xl cursor-pointer">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 flex-shrink-0">
                                <i class="fas fa-sign-in-alt"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-slate-800 text-sm sm:text-base">Koneksyon</h4>
                                <p class="text-xs sm:text-sm text-slate-600">Ou konekte soti sou aparèy ou a</p>
                            </div>
                            <span class="text-xs text-slate-500 flex-shrink-0">Jodi a</span>
                        </div>

                        <div class="activity-item flex items-center gap-3 sm:gap-4 p-3 sm:p-4 rounded-xl sm:rounded-2xl cursor-pointer">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-green-100 flex items-center justify-center text-green-600 flex-shrink-0">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-slate-800 text-sm sm:text-base">Pwofil Mizajou</h4>
                                <p class="text-xs sm:text-sm text-slate-600">Ou modifye enfòmasyon pwofil ou</p>
                            </div>
                            <span class="text-xs text-slate-500 flex-shrink-0">Yè</span>
                        </div>

                        <div class="activity-item flex items-center gap-3 sm:gap-4 p-3 sm:p-4 rounded-xl sm:rounded-2xl cursor-pointer">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 flex-shrink-0">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-slate-800 text-sm sm:text-base">Nouvo Komand</h4>
                                <p class="text-xs sm:text-sm text-slate-600">Ou pase yon nouvo komand #1234</p>
                            </div>
                            <span class="text-xs text-slate-500 flex-shrink-0">3 jou de sa</span>
                        </div>
                    </div>

                    <div class="mt-6 sm:mt-8 p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-gradient-to-r from-indigo-50/90 to-pink-50/90 backdrop-blur-sm border border-indigo-100">
                        <div class="flex items-center gap-3 sm:gap-4">
                            <div class="w-10 h-10 sm:w-14 sm:h-14 rounded-full bg-white shadow-lg flex items-center justify-center text-xl sm:text-2xl flex-shrink-0">
                                🎉
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-800 text-sm sm:text-base">Byenvenu nan LE-STOCK!</h4>
                                <p class="text-xs sm:text-sm text-slate-600 mt-1">Kòmanse achte pou jwenn pwen fidélite ak rabè espesyal.</p>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($activeTab === 'about'): ?>
                <div class="animate-slide-up max-w-3xl">
                    <h2 class="text-xl sm:text-2xl font-display font-bold text-slate-800 mb-4 sm:mb-6">Enfòmasyon Pèsonèl</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl glass-card hover:shadow-lg transition-shadow">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600">
                                    <i class="fas fa-user"></i>
                                </div>
                                <span class="text-xs font-bold text-slate-500 uppercase">Non Konplè</span>
                            </div>
                            <p class="text-base sm:text-lg font-semibold text-slate-800">
                                <?= htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?>
                            </p>
                        </div>

                        <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl glass-card hover:shadow-lg transition-shadow">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg bg-pink-100 flex items-center justify-center text-pink-600">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <span class="text-xs font-bold text-slate-500 uppercase">Imèl</span>
                            </div>
                            <p class="text-base sm:text-lg font-semibold text-slate-800 break-all"><?= htmlspecialchars($user['email']) ?></p>
                        </div>

                        <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl glass-card hover:shadow-lg transition-shadow">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg bg-amber-100 flex items-center justify-center text-amber-600">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <span class="text-xs font-bold text-slate-500 uppercase">Telefòn</span>
                            </div>
                            <p class="text-base sm:text-lg font-semibold text-slate-800"><?= $user['telephone'] ?: 'Pa ajoute' ?></p>
                        </div>

                        <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl glass-card hover:shadow-lg transition-shadow">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg bg-green-100 flex items-center justify-center text-green-600">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <span class="text-xs font-bold text-slate-500 uppercase">Adrès</span>
                            </div>
                            <p class="text-base sm:text-lg font-semibold text-slate-800"><?= $user['adresse'] ?: 'Pa ajoute' ?></p>
                        </div>
                    </div>

                    <div class="mt-6 sm:mt-8 p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-gradient-to-r from-slate-800 to-slate-900 text-white">
                        <h4 class="font-bold mb-2 flex items-center gap-2 text-sm sm:text-base">
                            <i class="fas fa-info-circle text-indigo-400"></i>
                            Enfòmasyon Kont
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4 text-xs sm:text-sm">
                            <div>
                                <span class="text-slate-400">Manm depi:</span>
                                <p class="font-semibold"><?= date('d M Y', strtotime($user['created_at'] ?? 'now')) ?></p>
                            </div>
                            <div>
                                <span class="text-slate-400">Dènye mizajou:</span>
                                <p class="font-semibold"><?= date('d M Y', strtotime($user['updated_at'] ?? 'now')) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($activeTab === 'settings'): ?>
                <div class="animate-slide-up max-w-2xl">
                    <h2 class="text-xl sm:text-2xl font-display font-bold text-slate-800 mb-4 sm:mb-6">Modifye Pwofil</h2>

                    <?php if (isset($_GET['success'])): ?>
                        <div class="mb-4 sm:mb-6 p-3 sm:p-4 rounded-xl bg-green-100/90 backdrop-blur-sm text-green-700 flex items-center gap-3 animate-pulse border border-green-200">
                            <i class="fas fa-check-circle text-lg sm:text-xl"></i>
                            <span class="font-semibold text-sm sm:text-base">Pwofil ou mete ajou avèk siksè!</span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-4 sm:space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <div>
                                <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-2">Prenom</label>
                                <!-- GLASS INPUT -->
                                <input type="text" name="prenom" value="<?= htmlspecialchars($user['prenom'] ?? '') ?>"
                                    class="glass-input w-full px-3 py-2.5 sm:px-4 sm:py-3 rounded-xl focus:outline-none font-medium text-sm text-slate-800">
                            </div>
                            <div>
                                <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-2">Nom</label>
                                <!-- GLASS INPUT -->
                                <input type="text" name="nom" value="<?= htmlspecialchars($user['nom'] ?? '') ?>"
                                    class="glass-input w-full px-3 py-2.5 sm:px-4 sm:py-3 rounded-xl focus:outline-none font-medium text-sm text-slate-800">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-2">Telefòn</label>
                            <div class="relative">
                                <i class="fas fa-phone absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                                <!-- GLASS INPUT -->
                                <input type="tel" name="phone" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>"
                                    class="glass-input w-full pl-9 sm:pl-12 pr-3 sm:pr-4 py-2.5 sm:py-3 rounded-xl focus:outline-none font-medium text-sm text-slate-800">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-2">Adrès</label>
                            <div class="relative">
                                <i class="fas fa-map-marker-alt absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                                <!-- GLASS INPUT -->
                                <input type="text" name="adresse" value="<?= htmlspecialchars($user['adresse'] ?? '') ?>"
                                    class="glass-input w-full pl-9 sm:pl-12 pr-3 sm:pr-4 py-2.5 sm:py-3 rounded-xl focus:outline-none font-medium text-sm text-slate-800">
                            </div>
                        </div>

                        <button type="submit" name="update_info" class="btn-primary w-full py-3 sm:py-4 rounded-xl text-white font-bold text-base sm:text-lg shadow-lg flex items-center justify-center gap-2">
                            <i class="fas fa-save"></i>
                            Sove Chanjman yo
                        </button>
                    </form>

                    <!-- Merchant/Admin Section -->
                    <div class="mt-8 sm:mt-12 pt-6 sm:pt-8 border-t border-slate-200/60">
                        <?php
                        $status_demann = strtolower($user['merchant_status'] ?? '');
                        ?>

                        <?php if ($isMerchant): ?>
                            <!-- Machann: Montre pwen ak cashout -->
                            <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-gradient-to-r from-amber-400 to-orange-500 text-white shadow-xl">
                                <div class="flex flex-col sm:flex-row items-center gap-4">
                                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-crown text-3xl sm:text-4xl"></i>
                                    </div>
                                    <div class="flex-1 text-center sm:text-left">
                                        <h3 class="text-lg sm:text-xl font-bold">Machann Premium</h3>
                                        <p class="mt-1 opacity-90 text-sm sm:text-base">Ou gen aksè a tout avantaj machann yo.</p>

                                        <div class="mt-4 flex flex-col sm:flex-row items-center gap-3 sm:gap-4">
                                            <div class="bg-white/20 rounded-xl px-4 py-2 flex items-center gap-2">
                                                <i class="fas fa-coins"></i>
                                                <span class="font-bold text-lg"><?= number_format($merchantPoints, 0, ',', ' ') ?> Pwen</span>
                                            </div>
                                            <a href="cashout.php" class="inline-flex items-center gap-2 px-4 py-2 sm:px-6 sm:py-3 bg-white text-orange-600 rounded-xl font-bold transition-all shadow-lg hover:shadow-xl text-sm">
                                                <i class="fas fa-money-bill-wave"></i>
                                                Mande Cashout
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        <?php elseif ($isAdmin): ?>
                            <!-- Admin: Pa montre anyen nan seksyon sa -->
                            <!-- Admin pa wè seksyon "Vle vin Machann" -->

                        <?php elseif ($status_demann === 'pending'): ?>
                            <!-- Demann an kou -->
                            <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-amber-50/90 backdrop-blur-sm border-2 border-amber-200 flex items-center gap-3 sm:gap-4">
                                <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 animate-pulse flex-shrink-0">
                                    <i class="fas fa-hourglass-half text-xl sm:text-2xl"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-amber-900 text-sm sm:text-base">Demann an kou...</h4>
                                    <p class="text-xs sm:text-sm text-amber-700">Ekip nou ap verifye peman ou. Sa ka pran 24-48 èdtan.</p>
                                </div>
                            </div>

                        <?php else: ?>
                            <!-- Itilizatè regilye: Montre bouton vin machann -->
                            <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl glass-card">
                                <div class="flex flex-col sm:flex-row items-start gap-3 sm:gap-4">
                                    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-gradient-to-br from-indigo-500 to-pink-500 flex items-center justify-center text-white text-xl sm:text-2xl shadow-lg flex-shrink-0">
                                        <i class="fas fa-store"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-base sm:text-lg font-bold text-slate-800">Vle vin Machann?</h4>
                                        <p class="text-xs sm:text-sm text-slate-600 mt-1 mb-3 sm:mb-4">Kreye boutik ou pwòp ou epi kòmanse vann pwodwi ou yo sou LE-STOCK.</p>
                                        <a href="form_machann.php" class="inline-flex items-center gap-2 px-4 py-2 sm:px-6 sm:py-3 bg-slate-800 hover:bg-slate-900 text-white rounded-xl font-semibold transition-all shadow-lg hover:shadow-xl text-sm">
                                            <i class="fas fa-arrow-right"></i>
                                            Kòmanse Maintenant
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($activeTab === 'security'): ?>
                <div class="animate-slide-up max-w-2xl">
                    <h2 class="text-xl sm:text-2xl font-display font-bold text-slate-800 mb-4 sm:mb-6">Sekirite Kont</h2>

                    <div class="space-y-4 sm:space-y-6">
                        <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl glass-card shadow-sm">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3 sm:mb-4">
                                <div>
                                    <h4 class="font-bold text-slate-800 text-sm sm:text-base">Modpas</h4>
                                    <p class="text-xs sm:text-sm text-slate-500">Dènye chanjman: 30 jou de sa</p>
                                </div>
                                <button class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-lg border border-slate-300 hover:bg-white/80 font-medium text-xs sm:text-sm transition-colors glass-input">
                                    Modifye
                                </button>
                            </div>
                            <div class="w-full bg-slate-200/50 rounded-full h-1.5 sm:h-2">
                                <div class="bg-green-500 h-1.5 sm:h-2 rounded-full" style="width: 85%"></div>
                            </div>
                            <p class="text-xs text-slate-500 mt-2">Fòs modpas: Bon</p>
                        </div>

                        <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl glass-card shadow-sm">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600 flex-shrink-0">
                                        <i class="fas fa-mobile-alt"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-800 text-sm sm:text-base">Verifikasyon De Etap</h4>
                                        <p class="text-xs sm:text-sm text-slate-500">Aktive pou sekirite siperyè</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                                    <input type="checkbox" class="sr-only peer">
                                    <div class="w-10 h-5 sm:w-11 sm:h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 sm:after:h-5 sm:after:w-5 after:transition-all peer-checked:bg-indigo-500"></div>
                                </label>
                            </div>
                        </div>

                        <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-red-50/90 backdrop-blur-sm border border-red-100">
                            <h4 class="font-bold text-red-800 mb-2 flex items-center gap-2 text-sm sm:text-base">
                                <i class="fas fa-exclamation-triangle"></i>
                                Zòn danje
                            </h4>
                            <p class="text-xs sm:text-sm text-red-600 mb-3 sm:mb-4">Aksyon sa yo pa ka defèt. Sijè pou atansyon.</p>
                            <button class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white font-medium text-xs sm:text-sm transition-colors">
                                <i class="fas fa-trash-alt mr-1 sm:mr-2"></i>Siprime Kont
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Add smooth scrolling and interactive effects
        document.querySelectorAll('a[href^="?tab="]').forEach(link => {
            link.addEventListener('click', function(e) {
                document.body.style.cursor = 'wait';
            });
        });

        // Animate numbers on load
        const animateValue = (obj, start, end, duration) => {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                obj.innerHTML = Math.floor(progress * (end - start) + start);
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        };

        // Trigger number animations
        document.addEventListener('DOMContentLoaded', () => {
            const stats = document.querySelectorAll('.stat-card .text-2xl, .stat-card .text-3xl');
            stats.forEach(stat => {
                const value = parseInt(stat.innerText) || 0;
                if (value > 0) {
                    const originalText = stat.innerText;
                    stat.innerText = '0';
                    animateValue(stat, 0, value, 1000);
                    setTimeout(() => {
                        stat.innerText = originalText;
                    }, 1000);
                }
            });
        });
    </script>
</body>

</html>