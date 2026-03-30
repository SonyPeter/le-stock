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

    $userStats = [
        'total_orders' => 0,
        'total_spent' => 0,
        'total_favorites' => 0
    ];

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
        $userStats['total_orders'] = 0;
        $userStats['total_spent'] = 0;
    }

    try {
        $favStmt = $pdo->prepare("SELECT COUNT(*) as total FROM favoris WHERE user_id = ?");
        $favStmt->execute([$user_id]);
        $favResult = $favStmt->fetch(PDO::FETCH_ASSOC);
        $userStats['total_favorites'] = $favResult['total'] ?? 0;
    } catch (PDOException $e) {
        $userStats['total_favorites'] = 0;
    }

    $merchantPoints = 0;
    if (strtolower($user['role'] ?? '') === 'merchant') {
        try {
            $pointsStmt = $pdo->prepare("SELECT points FROM merchant_points WHERE user_id = ?");
            $pointsStmt->execute([$user_id]);
            $pointsResult = $pointsStmt->fetch(PDO::FETCH_ASSOC);
            $merchantPoints = $pointsResult['points'] ?? 0;
        } catch (PDOException $e) {
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

 $isAdmin = strtolower($user['role'] ?? '') === 'admin';
 $isMerchant = strtolower($user['role'] ?? '') === 'merchant';
 $status_demann = strtolower($user['merchant_status'] ?? '');
?>

<!DOCTYPE html>
<html lang="ht">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pwofil - <?= htmlspecialchars($user['prenom']) ?></title>
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
                            <i class="fas fa-tachometer-alt"></i> Acceuil
                        </a>
                        <a href="admin_dashboard.php" class="flex items-center gap-2 text-red-600 hover:text-red-700 font-bold transition-colors text-sm">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <div style="display:flex; align-items:center; gap:0.5rem;">
                    <a href="panier/Panier.php" class="icon-btn" style="padding:0.5rem; border-radius:50%; text-decoration:none; position:relative;" title="Panier">
                        <i class="fas fa-shopping-cart" style="font-size:1.15rem;"></i>
                        <span id="cart-badge" class="cart-badge" style="position:absolute; top:-2px; right:-2px; font-size:0.7rem; padding:0.1rem 0.4rem; border-radius:9999px;">0</span>
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
                        Apèsi pwofil
                    </a>
                    <a href="?tab=settings" class="flex items-center gap-3 px-3 py-3 rounded-xl text-slate-700 hover:bg-slate-50 font-medium transition-colors text-sm">
                        <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center">
                            <i class="fas fa-cog text-xs text-slate-600"></i>
                        </div>
                        Paramèt
                    </a>

                    <div class="pt-3 mt-2 border-t border-slate-200/60">
                        <a href="logout.php" class="flex items-center gap-3 px-3 py-3 rounded-xl text-red-600 hover:bg-red-50 font-medium transition-colors text-sm">
                            <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
                                <i class="fas fa-sign-out-alt text-xs"></i>
                            </div>
                            Dekonekte
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
                    <div class="absolute -bottom-1 -right-1 sm:-bottom-2 sm:-right-2 w-6 h-6 sm:w-8 sm:h-8 bg-green-500 rounded-full border-2 sm:border-4 border-white flex items-center justify-center" title="Online">
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
                            ID: #LS-<?= str_pad($user['id'], 4, '0', STR_PAD_LEFT) ?>
                        </span>
                        <?php if ($isMerchant): ?>
                            <span class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-full bg-gradient-to-br from-amber-400 to-amber-500 text-white text-xs sm:text-sm font-bold flex items-center gap-2 shadow-lg">
                                <i class="fas fa-crown"></i> Machann Premium
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
            <div class="bg-white/85 backdrop-blur-[10px] border border-white/40 rounded-xl sm:rounded-2xl p-4 sm:p-6 hover:-translate-y-1 hover:shadow-[0_20px_40px_rgba(0,0,0,0.15)] hover:bg-white/95 transition-all">
                <div class="flex items-center justify-between mb-3 sm:mb-4">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-indigo-100 flex items-center justify-center text-indigo-600">
                        <i class="fas fa-shopping-bag text-lg sm:text-xl"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total</span>
                </div>
                <div class="text-2xl sm:text-3xl font-bold text-slate-800 mb-1" data-counter style="font-family: 'Space Grotesk', sans-serif;"><?= $userStats['total_orders'] ?? 0 ?></div>
                <div class="text-xs sm:text-sm text-slate-600">Komand yo</div>
            </div>

            <div class="bg-white/85 backdrop-blur-[10px] border border-white/40 rounded-xl sm:rounded-2xl p-4 sm:p-6 hover:-translate-y-1 hover:shadow-[0_20px_40px_rgba(0,0,0,0.15)] hover:bg-white/95 transition-all">
                <div class="flex items-center justify-between mb-3 sm:mb-4">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-pink-100 flex items-center justify-center text-pink-600">
                        <i class="fas fa-wallet text-lg sm:text-xl"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Depans</span>
                </div>
                <div class="text-2xl sm:text-3xl font-bold text-slate-800 mb-1" data-counter style="font-family: 'Space Grotesk', sans-serif;">
                    <?= number_format($userStats['total_spent'] ?? 0, 0, ',', ' ') ?> Gdes
                </div>
                <div class="text-xs sm:text-sm text-slate-600">Total depanse</div>
            </div>

            <div class="bg-white/85 backdrop-blur-[10px] border border-white/40 rounded-xl sm:rounded-2xl p-4 sm:p-6 hover:-translate-y-1 hover:shadow-[0_20px_40px_rgba(0,0,0,0.15)] hover:bg-white/95 transition-all">
                <div class="flex items-center justify-between mb-3 sm:mb-4">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600">
                        <i class="fas fa-heart text-lg sm:text-xl"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Favori</span>
                </div>
                <div class="text-2xl sm:text-3xl font-bold text-slate-800 mb-1" data-counter style="font-family: 'Space Grotesk', sans-serif;"><?= $userStats['total_favorites'] ?? 0 ?></div>
                <div class="text-xs sm:text-sm text-slate-600">Atik favori</div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="bg-white/95 backdrop-blur-[20px] border border-white/30 shadow-[0_8px_32px_rgba(0,0,0,0.1)] rounded-xl sm:rounded-2xl p-1.5 sm:p-2 mb-6 sm:mb-8 inline-flex gap-1 sm:gap-2 overflow-x-auto max-w-full">
            <a href="?tab=overview" class="px-4 py-2 sm:px-6 sm:py-3 rounded-lg sm:rounded-xl font-semibold text-xs sm:text-sm whitespace-nowrap transition-all border-b-[3px] <?= $activeTab === 'overview' ? 'bg-white shadow-md text-indigo-600 border-indigo-500' : 'text-slate-600 hover:text-slate-800 border-transparent' ?>">
                <i class="fas fa-chart-pie mr-1 sm:mr-2"></i>Apèsi
            </a>
            <a href="?tab=about" class="px-4 py-2 sm:px-6 sm:py-3 rounded-lg sm:rounded-xl font-semibold text-xs sm:text-sm whitespace-nowrap transition-all border-b-[3px] <?= $activeTab === 'about' ? 'bg-white shadow-md text-indigo-600 border-indigo-500' : 'text-slate-600 hover:text-slate-800 border-transparent' ?>">
                <i class="fas fa-user mr-1 sm:mr-2"></i>Enfòmasyon
            </a>
            <a href="?tab=settings" class="px-4 py-2 sm:px-6 sm:py-3 rounded-lg sm:rounded-xl font-semibold text-xs sm:text-sm whitespace-nowrap transition-all border-b-[3px] <?= $activeTab === 'settings' ? 'bg-white shadow-md text-indigo-600 border-indigo-500' : 'text-slate-600 hover:text-slate-800 border-transparent' ?>">
                <i class="fas fa-sliders-h mr-1 sm:mr-2"></i>Paramèt
            </a>
            <a href="?tab=security" class="px-4 py-2 sm:px-6 sm:py-3 rounded-lg sm:rounded-xl font-semibold text-xs sm:text-sm whitespace-nowrap transition-all border-b-[3px] <?= $activeTab === 'security' ? 'bg-white shadow-md text-indigo-600 border-indigo-500' : 'text-slate-600 hover:text-slate-800 border-transparent' ?>">
                <i class="fas fa-shield-alt mr-1 sm:mr-2"></i>Sekirite
            </a>
        </div>

        <!-- Tab Content -->
        <div class="bg-white/95 backdrop-blur-[20px] border border-white/30 shadow-[0_8px_32px_rgba(0,0,0,0.1)] rounded-2xl sm:rounded-3xl p-6 sm:p-8 min-h-[300px] sm:min-h-[400px]">

            <?php if ($activeTab === 'overview'): ?>
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-800 mb-4 sm:mb-6" style="font-family: 'Space Grotesk', sans-serif;">Aktivite Resan</h2>

                    <div class="space-y-3 sm:space-y-4">
                        <div class="bg-white/80 backdrop-blur-[8px] border border-white/40 hover:bg-white hover:translate-x-2.5 hover:shadow-[0_8px_25px_rgba(0,0,0,0.1)] transition-all flex items-center gap-3 sm:gap-4 p-3 sm:p-4 rounded-xl sm:rounded-2xl cursor-pointer">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 shrink-0">
                                <i class="fas fa-sign-in-alt"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-slate-800 text-sm sm:text-base">Koneksyon</h4>
                                <p class="text-xs sm:text-sm text-slate-600">Ou konekte soti sou aparèy ou a</p>
                            </div>
                            <span class="text-xs text-slate-500 shrink-0">Jodi a</span>
                        </div>

                        <div class="bg-white/80 backdrop-blur-[8px] border border-white/40 hover:bg-white hover:translate-x-2.5 hover:shadow-[0_8px_25px_rgba(0,0,0,0.1)] transition-all flex items-center gap-3 sm:gap-4 p-3 sm:p-4 rounded-xl sm:rounded-2xl cursor-pointer">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-green-100 flex items-center justify-center text-green-600 shrink-0">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-slate-800 text-sm sm:text-base">Pwofil Mizajou</h4>
                                <p class="text-xs sm:text-sm text-slate-600">Ou modifye enfòmasyon pwofil ou</p>
                            </div>
                            <span class="text-xs text-slate-500 shrink-0">Yè</span>
                        </div>

                        <div class="bg-white/80 backdrop-blur-[8px] border border-white/40 hover:bg-white hover:translate-x-2.5 hover:shadow-[0_8px_25px_rgba(0,0,0,0.1)] transition-all flex items-center gap-3 sm:gap-4 p-3 sm:p-4 rounded-xl sm:rounded-2xl cursor-pointer">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 shrink-0">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-slate-800 text-sm sm:text-base">Nouvo Komand</h4>
                                <p class="text-xs sm:text-sm text-slate-600">Ou pase yon nouvo komand #1234</p>
                            </div>
                            <span class="text-xs text-slate-500 shrink-0">3 jou de sa</span>
                        </div>
                    </div>

                    <div class="mt-6 sm:mt-8 p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-gradient-to-r from-indigo-50/90 to-pink-50/90 backdrop-blur-sm border border-indigo-100">
                        <div class="flex items-center gap-3 sm:gap-4">
                            <div class="w-10 h-10 sm:w-14 sm:h-14 rounded-full bg-white shadow-lg flex items-center justify-center text-xl sm:text-2xl shrink-0">
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
                <div class="max-w-3xl">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-800 mb-4 sm:mb-6" style="font-family: 'Space Grotesk', sans-serif;">Enfòmasyon Pèsonèl</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-white/88 backdrop-blur-[12px] border border-white/50 hover:-translate-y-1 hover:shadow-[0_20px_40px_rgba(0,0,0,0.15)] hover:bg-white/95 transition-all">
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

                        <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-white/88 backdrop-blur-[12px] border border-white/50 hover:-translate-y-1 hover:shadow-[0_20px_40px_rgba(0,0,0,0.15)] hover:bg-white/95 transition-all">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg bg-pink-100 flex items-center justify-center text-pink-600">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <span class="text-xs font-bold text-slate-500 uppercase">Imèl</span>
                            </div>
                            <p class="text-base sm:text-lg font-semibold text-slate-800 break-all"><?= htmlspecialchars($user['email']) ?></p>
                        </div>

                        <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-white/88 backdrop-blur-[12px] border border-white/50 hover:-translate-y-1 hover:shadow-[0_20px_40px_rgba(0,0,0,0.15)] hover:bg-white/95 transition-all">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg bg-amber-100 flex items-center justify-center text-amber-600">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <span class="text-xs font-bold text-slate-500 uppercase">Telefòn</span>
                            </div>
                            <p class="text-base sm:text-lg font-semibold text-slate-800"><?= $user['telephone'] ?: 'Pa ajoute' ?></p>
                        </div>

                        <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-white/88 backdrop-blur-[12px] border border-white/50 hover:-translate-y-1 hover:shadow-[0_20px_40px_rgba(0,0,0,0.15)] hover:bg-white/95 transition-all">
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
                <div class="max-w-2xl">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-800 mb-4 sm:mb-6" style="font-family: 'Space Grotesk', sans-serif;">Modifye Pwofil</h2>

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
                                <input type="text" name="prenom" value="<?= htmlspecialchars($user['prenom'] ?? '') ?>"
                                    class="bg-white/92 backdrop-blur-[12px] border border-white/60 shadow-[0_4px_20px_rgba(0,0,0,0.08)] focus:bg-white focus:border-indigo-500 focus:shadow-[0_0_0_4px_rgba(102,126,234,0.15),0_8px_30px_rgba(0,0,0,0.12)] focus:-translate-y-0.5 transition-all w-full px-3 py-2.5 sm:px-4 sm:py-3 rounded-xl focus:outline-none font-medium text-base text-slate-800">
                            </div>
                            <div>
                                <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-2">Nom</label>
                                <input type="text" name="nom" value="<?= htmlspecialchars($user['nom'] ?? '') ?>"
                                    class="bg-white/92 backdrop-blur-[12px] border border-white/60 shadow-[0_4px_20px_rgba(0,0,0,0.08)] focus:bg-white focus:border-indigo-500 focus:shadow-[0_0_0_4px_rgba(102,126,234,0.15),0_8px_30px_rgba(0,0,0,0.12)] focus:-translate-y-0.5 transition-all w-full px-3 py-2.5 sm:px-4 sm:py-3 rounded-xl focus:outline-none font-medium text-base text-slate-800">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-2">Telefòn</label>
                            <div class="relative">
                                <i class="fas fa-phone absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                                <input type="tel" name="phone" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>"
                                    class="bg-white/92 backdrop-blur-[12px] border border-white/60 shadow-[0_4px_20px_rgba(0,0,0,0.08)] focus:bg-white focus:border-indigo-500 focus:shadow-[0_0_0_4px_rgba(102,126,234,0.15),0_8px_30px_rgba(0,0,0,0.12)] focus:-translate-y-0.5 transition-all w-full pl-9 sm:pl-12 pr-3 sm:pr-4 py-2.5 sm:py-3 rounded-xl focus:outline-none font-medium text-base text-slate-800">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-2">Adrès</label>
                            <div class="relative">
                                <i class="fas fa-map-marker-alt absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                                <input type="text" name="adresse" value="<?= htmlspecialchars($user['adresse'] ?? '') ?>"
                                    class="bg-white/92 backdrop-blur-[12px] border border-white/60 shadow-[0_4px_20px_rgba(0,0,0,0.08)] focus:bg-white focus:border-indigo-500 focus:shadow-[0_0_0_4px_rgba(102,126,234,0.15),0_8px_30px_rgba(0,0,0,0.12)] focus:-translate-y-0.5 transition-all w-full pl-9 sm:pl-12 pr-3 sm:pr-4 py-2.5 sm:py-3 rounded-xl focus:outline-none font-medium text-base text-slate-800">
                            </div>
                        </div>

                        <button type="submit" name="update_info" class="bg-gradient-to-br from-[#667eea] to-[#764ba2] hover:-translate-y-0.5 hover:shadow-[0_10px_30px_rgba(102,126,234,0.4)] transition-all w-full py-3 sm:py-4 rounded-xl text-white font-bold text-base sm:text-lg shadow-lg flex items-center justify-center gap-2">
                            <i class="fas fa-save"></i>
                            Sove Chanjman yo
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

                        <?php elseif ($status_demann === 'pending'): ?>
                            <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 shadow-lg">
                                <div class="flex items-center gap-3 sm:gap-4 mb-4">
                                    <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 animate-pulse shrink-0">
                                        <i class="fas fa-hourglass-half text-xl sm:text-2xl"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-bold text-blue-900 text-sm sm:text-base">Demann Machann an kou...</h4>
                                        <p class="text-xs sm:text-sm text-blue-700">Ekip nou ap verifye dokiman ou yo. Sa ka pran 24-48 èdtan.</p>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="flex justify-between text-xs font-semibold text-blue-800 mb-2">
                                        <span>Soumèt</span>
                                        <span>Verifikasyon</span>
                                        <span>Apwobasyon</span>
                                    </div>
                                    <div class="bg-white/30 rounded-full overflow-hidden h-3">
                                        <div class="bg-gradient-to-r from-blue-500 to-violet-500 h-full rounded-full transition-all duration-1000" style="width: 50%"></div>
                                    </div>
                                </div>

                                <div class="flex justify-between items-center text-xs text-blue-600">
                                    <span class="flex items-center gap-1"><i class="fas fa-check-circle text-green-500"></i> Demann soumèt</span>
                                    <span class="flex items-center gap-1 animate-pulse"><i class="fas fa-spinner fa-spin"></i> An verifikasyon</span>
                                    <span class="text-blue-300"><i class="fas fa-crown"></i> Apwobasyon final</span>
                                </div>

                                <div class="mt-4 p-3 bg-white/70 rounded-lg text-xs text-blue-800">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Ou pral resevwa yon notifikasyon lè demann ou a apwouve oswa rejte.
                                </div>
                            </div>

                        <?php elseif ($status_demann === 'rejected'): ?>
                            <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-red-50 border-2 border-red-200 flex items-center gap-3 sm:gap-4">
                                <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-red-100 flex items-center justify-center text-red-600 shrink-0">
                                    <i class="fas fa-times-circle text-xl sm:text-2xl"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-red-900 text-sm sm:text-base">Demann rejte</h4>
                                    <p class="text-xs sm:text-sm text-red-700">Demann ou a pa ka apwouve. Kontakte sipò pou plis enfòmasyon.</p>
                                    <a href="form_machann.php" class="inline-block mt-2 text-xs font-bold text-red-600 hover:text-red-800 underline">
                                        Eseye ankò
                                    </a>
                                </div>
                            </div>

                        <?php elseif ($isAdmin): ?>

                        <?php else: ?>
                            <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-white/88 backdrop-blur-[12px] border border-white/50 hover:-translate-y-1 hover:shadow-[0_20px_40px_rgba(0,0,0,0.15)] hover:bg-white/95 transition-all">
                                <div class="flex flex-col sm:flex-row items-start gap-3 sm:gap-4">
                                    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-gradient-to-br from-indigo-500 to-pink-500 flex items-center justify-center text-white text-xl sm:text-2xl shadow-lg shrink-0">
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
                <div class="max-w-2xl">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-800 mb-4 sm:mb-6" style="font-family: 'Space Grotesk', sans-serif;">Sekirite Kont</h2>

                    <div class="space-y-4 sm:space-y-6">
                        <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-white/88 backdrop-blur-[12px] border border-white/50 shadow-sm">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3 sm:mb-4">
                                <div>
                                    <h4 class="font-bold text-slate-800 text-sm sm:text-base">Modpas</h4>
                                    <p class="text-xs sm:text-sm text-slate-500">Dènye chanjman: 30 jou de sa</p>
                                </div>
                                <button class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-lg border border-slate-300 hover:bg-white/80 font-medium text-xs sm:text-sm transition-colors bg-white/92 backdrop-blur-[12px] shadow-[0_4px_20px_rgba(0,0,0,0.08)]">
                                    Modifye
                                </button>
                            </div>
                            <div class="w-full bg-slate-200/50 rounded-full h-1.5 sm:h-2">
                                <div class="bg-green-500 h-1.5 sm:h-2 rounded-full" style="width: 85%"></div>
                            </div>
                            <p class="text-xs text-slate-500 mt-2">Fòs modpas: Bon</p>
                        </div>

                        <div class="p-4 sm:p-6 rounded-xl sm:rounded-2xl bg-white/88 backdrop-blur-[12px] border border-white/50 shadow-sm">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600 shrink-0">
                                        <i class="fas fa-mobile-alt"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-800 text-sm sm:text-base">Verifikasyon De Etap</h4>
                                        <p class="text-xs sm:text-sm text-slate-500">Aktive pou sekirite siperyè</p>
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
            link.addEventListener('click', function() {
                closeMobileMenu();
            });
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && mobileMenuOpen) {
                closeMobileMenu();
            }
        });

        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768 && mobileMenuOpen) {
                closeMobileMenu();
            }
        });

        document.querySelectorAll('a[href^="?tab="]').forEach(link => {
            link.addEventListener('click', function() {
                document.body.style.cursor = 'wait';
            });
        });

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

        document.addEventListener('DOMContentLoaded', () => {
            const stats = document.querySelectorAll('[data-counter]');
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