<?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';

function getImageFullPath($imageName)
{
    return '../uploads/hot_deals/' . $imageName;
}

function imageExists($imageName)
{
    $basePath = dirname(__DIR__) . '/uploads/hot_deals/';
    $fullPath = $basePath . $imageName;
    return file_exists($fullPath) && is_file($fullPath);
}

 $product_id_from_url = $_GET['product_id'] ?? null;
 $cart_message = '';
 $cart_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $deal_id = intval($_POST['deal_id']);
    $quantity = intval($_POST['quantity'] ?? 1);

    try {
        $stmt = $pdo->prepare("SELECT * FROM hot_deals WHERE id = ? AND en_stock = 1 AND (date_fin IS NULL OR date_fin > NOW())");
        $stmt->execute([$deal_id]);
        $deal = $stmt->fetch();

        if (!$deal) {
            $cart_message = 'Ce deal n\'est plus disponible !';
        } else {
            $stmt = $pdo->prepare("SELECT id, stock_qty FROM products WHERE name = ? LIMIT 1");
            $stmt->execute([$deal['titre']]);
            $product = $stmt->fetch();
            $product_id = null;

            if ($product) {
                $product_id = $product['id'];
            } else {
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

                if (!empty($deal['images'])) {
                    $first_image = $deal['images'][0];
                    $stmt = $pdo->prepare("UPDATE products SET image = ? WHERE id = ?");
                    $stmt->execute([$first_image['image_name'], $product_id]);
                }
            }

            $stmt = $pdo->prepare("SELECT id, quantity FROM panier WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            $existing = $stmt->fetch();

            if ($existing) {
                $new_qty = $existing['quantity'] + $quantity;
                $stmt = $pdo->prepare("UPDATE panier SET quantity = ? WHERE id = ?");
                $stmt->execute([$new_qty, $existing['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO panier (user_id, product_id, quantity, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$user_id, $product_id, $quantity]);
            }

            $cart_success = true;
            $cart_message = 'Le deal a été ajouté à votre panier !';
        }
    } catch (PDOException $e) {
        error_log("Erreur ajout au panier : " . $e->getMessage());
        $cart_message = 'Erreur lors de l\'ajout du deal. Veuillez réessayer.';
    }
}

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

 $showDebug = false;
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hot Deals | LE-STOCK</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    maxWidth: {
                        'site': '80rem',
                    },
                    animation: {
                        'float-up': 'floatUp linear infinite',
                        'badge-bounce': 'badgeBounce 0.5s ease',
                        'whatsapp-pulse': 'whatsappPulse 2s ease-in-out infinite',
                    },
                    keyframes: {
                        floatUp: {
                            '0%': { transform: 'translateY(100%) scale(0)', opacity: '0' },
                            '20%': { opacity: '1' },
                            '100%': { transform: 'translateY(-100vh) scale(1)', opacity: '0' },
                        },
                        badgeBounce: {
                            '0%, 100%': { transform: 'scale(1)' },
                            '50%': { transform: 'scale(1.4)' },
                        },
                        whatsappPulse: {
                            '0%, 100%': { boxShadow: '0 0 0 0 rgba(37, 211, 102, 0.4)' },
                            '50%': { boxShadow: '0 0 0 10px rgba(37, 211, 102, 0)' },
                        },
                    },
                },
            },
        }
    </script>
    <style>
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #2563eb; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #1d4ed8; }

        .nav-link-underline::after {
            content: '';
            position: absolute;
            bottom: -6px; left: 50%; transform: translateX(-50%);
            width: 0; height: 2.5px;
            background: #fff;
            border-radius: 2px;
            transition: width 0.3s;
        }
        .nav-link-underline:hover::after,
        .nav-link-underline.active-link::after { width: 70%; }

        .footer-heading::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0;
            width: 28px; height: 2.5px;
            background: #3b82f6;
            border-radius: 2px;
        }

        .whatsapp-btn {
            background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
            transition: all 0.3s ease;
        }
        .whatsapp-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 211, 102, 0.4);
        }
        .whatsapp-btn:active {
            transform: translateY(0);
        }
    </style>
</head>

<body class="bg-slate-100 min-h-screen text-slate-800 font-sans">

    <!-- ===== HEADER ===== -->
    <header class="bg-gradient-to-br from-blue-900 via-blue-600 to-blue-500 shadow-lg shadow-blue-600/35 sticky top-0 z-[500]">
        <div class="max-w-site mx-auto px-6">
            <div class="flex items-center justify-between h-[68px]">
                <a href="accueil.php" class="flex items-center no-underline shrink-0">
                    <img src="\le-stock\assets\img\le stock entreprise copy2.png" alt="LE-STOCK"
                         class="max-h-[50px] sm:max-h-[38px] lg:max-h-[60px] transition-transform hover:scale-105 brightness-0 invert"
                         onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22180%22 height=%2250%22><rect fill=%22%231d4ed8%22 width=%22180%22 height=%2250%22 rx=%228%22/><text x=%2290%22 y=%2232%22 fill=%22white%22 font-family=%22Inter%22 font-weight=%22800%22 font-size=%2218%22 text-anchor=%22middle%22>LE-STOCK</text></svg>'; this.classList.remove('brightness-0','invert');">
                </a>

                <nav class="items-center gap-9 hidden lg:!flex">
                    <a href="../index.php" class="nav-link-underline relative text-blue-200/85 no-underline text-sm font-medium tracking-tight transition-colors hover:text-white">Accueil</a>
                    <a href="promotion.php" class="nav-link-underline relative text-blue-200/85 no-underline text-sm font-medium tracking-tight transition-colors hover:text-white">Promotions</a>
                    <a href="Affiliation" class="nav-link-underline relative text-blue-200/85 no-underline text-sm font-medium tracking-tight transition-colors hover:text-white">Affiliations</a>
                    <a href="hot_deal.php" class="nav-link-underline active-link relative text-white no-underline text-sm font-medium tracking-tight">Hot Deals</a>
                </nav>

                <div class="flex items-center gap-1.5">
                    <a href="panier/Panier.php" class="text-blue-200 transition-all w-[42px] h-[42px] flex items-center justify-center rounded-xl hover:text-white hover:bg-white/15 no-underline relative" title="Panier">
                        <i class="fas fa-shopping-bag text-lg"></i>
                        <span id="cart-badge" class="absolute -top-1 -right-1 bg-red-600 text-white font-extrabold text-[0.65rem] min-w-[18px] h-[18px] flex items-center justify-center rounded-full border-2 border-blue-900/80">0</span>
                    </a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="profile.php" class="text-blue-200 transition-all w-[42px] h-[42px] flex items-center justify-center rounded-xl hover:text-white hover:bg-white/15 no-underline" title="Profil">
                            <i class="fas fa-user text-[0.95rem]"></i>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="text-blue-200 transition-all w-[42px] h-[42px] flex items-center justify-center rounded-xl hover:text-white hover:bg-white/15 no-underline" title="Connexion">
                            <i class="fas fa-sign-in-alt text-[0.95rem]"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- ===== HERO ===== -->
    <section class="relative bg-gradient-to-br from-slate-900 via-blue-900 to-blue-600 overflow-hidden">
        <div class="absolute inset-0 overflow-hidden pointer-events-none" id="heroParticles"></div>
        <div class="absolute -top-1/2 -right-[20%] w-[600px] h-[600px] bg-[radial-gradient(circle,rgba(251,191,36,0.15)_0%,transparent_70%)] rounded-full"></div>
        <div class="absolute -bottom-[40%] -left-[10%] w-[500px] h-[500px] bg-[radial-gradient(circle,rgba(239,68,68,0.1)_0%,transparent_70%)] rounded-full"></div>

        <div class="relative z-[2] text-center py-16 px-6 md:py-20 md:px-8">
            <h1 class="text-[clamp(2.2rem,6vw,4rem)] font-black text-white leading-[1.1] mb-4">
                Hot Deals
            </h1>
            <p class="text-[clamp(0.95rem,2vw,1.2rem)] text-blue-200/90 max-w-[550px] mx-auto leading-relaxed font-normal">
                Offres exclusives à durée limitée : ne manquez pas nos Hot Deals du moment ! Notre plateforme propose la vente de Hot Deals et vous pouvez nous contacter pour publier les produits que vous souhaitez vendre.
            </p>
        </div>
    </section>

    <!-- ===== FILTER BAR ===== -->
    <div class="bg-white border-b border-slate-200 sticky top-[68px] z-[400] shadow-[0_1px_3px_rgba(0,0,0,0.04)]">
        <div class="max-w-site mx-auto px-6 py-3.5 flex items-center justify-between gap-4">
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <i class="fas fa-tags text-blue-600"></i>
                <span><strong class="text-slate-800 bg-blue-50 px-2.5 py-0.5 rounded-md text-xs"><?= count($hot_deals) ?></strong> deals disponibles</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5 px-4 py-1.5 rounded-full border border-blue-600 bg-blue-50 text-xs font-medium text-blue-600 cursor-pointer transition-all">
                    <i class="fas fa-fire text-[0.7rem]"></i> Tous
                </div>
                <div class="flex items-center gap-1.5 px-4 py-1.5 rounded-full border border-slate-200 bg-white text-xs font-medium text-slate-600 cursor-pointer transition-all hover:border-blue-600 hover:bg-blue-50 hover:text-blue-600">
                    <i class="fas fa-clock text-[0.7rem]"></i> Bientôt expirés
                </div>
                <div class="flex items-center gap-1.5 px-4 py-1.5 rounded-full border border-slate-200 bg-white text-xs font-medium text-slate-600 cursor-pointer transition-all hover:border-blue-600 hover:bg-blue-50 hover:text-blue-600">
                    <i class="fas fa-arrow-down text-[0.7rem]"></i> Plus récents
                </div>
            </div>
        </div>
    </div>

    <!-- ===== NOTIFICATION ===== -->
    <?php if ($cart_message): ?>
        <div id="cart-notification" class="fixed top-20 right-6 px-6 py-4 rounded-[14px] text-white font-semibold text-sm z-[9999] translate-x-0 transition-transform duration-[400ms] shadow-2xl flex items-center gap-3 max-w-[380px] <?= $cart_success ? 'bg-gradient-to-br from-emerald-600 to-emerald-700' : 'bg-gradient-to-br from-red-600 to-red-700' ?>">
            <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center shrink-0">
                <i class="fas <?= $cart_success ? 'fa-check' : 'fa-exclamation' ?>"></i>
            </div>
            <div>
                <div><?= htmlspecialchars($cart_message) ?></div>
                <?php if ($cart_success): ?>
                    <a href="panier/Panier.php" class="text-white underline whitespace-nowrap font-bold">Voir le panier →</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($showDebug): ?>
        <div class="bg-amber-50 border-2 border-amber-500 p-6 my-6 mx-auto max-w-[800px] rounded-[14px] text-amber-800 text-sm">
            <strong><i class="fas fa-bug"></i> DEBUG</strong>
            <p>Total Deals: <?= count($hot_deals) ?></p>
            <p>Base Path: <?= dirname(__DIR__) . '/uploads/hot_deals/' ?></p>
            <?php foreach ($hot_deals as $deal): ?>
                <hr class="my-3 border-amber-300">
                <p><strong><?= htmlspecialchars($deal['titre']) ?></strong> — <?= count($deal['images']) ?> images</p>
                <?php foreach ($deal['images'] as $img): ?>
                    <p><?= htmlspecialchars($img['image_name']) ?> → <?= $img['exists'] ? '✓' : '✗' ?></p>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ===== DEALS ===== -->
    <section class="max-w-site mx-auto px-6 py-8 pb-12">
        <?php if (empty($hot_deals)): ?>
            <div class="text-center py-20 px-8 max-w-[500px] mx-auto">
                <div class="w-[120px] h-[120px] mx-auto mb-8 bg-gradient-to-br from-amber-100 to-amber-200 rounded-full flex items-center justify-center shadow-xl shadow-amber-400/20">
                    <i class="fas fa-fire-extinguisher text-5xl text-amber-500"></i>
                </div>
                <h2 class="text-2xl font-extrabold text-slate-900 mb-2">Aucun deal pour le moment</h2>
                <p class="text-slate-500 text-[0.95rem] mb-8 leading-relaxed">Revenez bientôt pour découvrir nos nouvelles offres exclusives à prix cassé !</p>
                <a href="accueil.php" class="inline-flex items-center gap-2 px-8 py-3.5 bg-gradient-to-br from-blue-600 to-blue-700 text-white rounded-[14px] font-bold text-sm no-underline transition-all shadow-lg shadow-blue-600/30 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-blue-600/40">
                    <i class="fas fa-arrow-left"></i> Retour à l'accueil
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-7 lg:gap-8">
                <?php foreach ($hot_deals as $deal):
                    $discount = round((($deal['prix_original'] - $deal['prix_deal']) / $deal['prix_original']) * 100);
                    $savings = $deal['prix_original'] - $deal['prix_deal'];
                    $image_count = count($deal['images']);
                    $expired = $deal['date_fin'] && strtotime($deal['date_fin']) < time();
                    $out_of_stock = !$deal['en_stock'] || ($deal['quantite_limite'] !== null && $deal['quantite_limite'] <= 0);
                    $is_disabled = $expired || $out_of_stock;
                ?>
                    <div class="deal-card bg-white rounded-2xl overflow-hidden border border-slate-200 transition-all duration-[400ms] hover:-translate-y-2 hover:shadow-xl hover:shadow-blue-600/15 hover:border-transparent relative <?= $expired ? 'opacity-70 saturate-50 hover:translate-none hover:shadow-none' : '' ?>" id="deal-<?= $deal['id'] ?>">
                        <!-- Image Slider -->
                        <div class="relative h-[280px] sm:h-[300px] overflow-hidden bg-slate-100" id="slider-<?= $deal['id'] ?>">
                            <!-- Discount Badge -->
                            <div class="absolute top-3.5 left-3.5 bg-gradient-to-br from-red-600 to-red-500 text-white px-3.5 py-1.5 rounded-[10px] font-extrabold text-xs z-10 shadow-lg shadow-red-600/35 tracking-tight">
                                -<?= $discount ?>%
                            </div>

                            <!-- Stock Badge -->
                            <?php if ($out_of_stock): ?>
                                <div class="absolute top-3.5 right-3.5 px-3 py-1 rounded-[10px] text-[0.7rem] font-bold uppercase tracking-wider z-10 flex items-center gap-1.5 bg-red-500/90 text-white backdrop-blur-sm">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span> Épuisé
                                </div>
                            <?php else: ?>
                                <div class="absolute top-3.5 right-3.5 px-3 py-1 rounded-[10px] text-[0.7rem] font-bold uppercase tracking-wider z-10 flex items-center gap-1.5 bg-emerald-500/90 text-white backdrop-blur-sm">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span> En Stock
                                </div>
                            <?php endif; ?>

                            <!-- Expired Overlay -->
                            <?php if ($expired): ?>
                                <div class="absolute inset-0 bg-slate-900/65 backdrop-blur-sm flex items-center justify-center z-20">
                                    <span class="bg-gradient-to-br from-red-600 to-red-700 text-white px-8 py-2.5 rounded-xl font-extrabold text-sm tracking-widest uppercase shadow-xl shadow-red-600/40 -rotate-[5deg]">
                                        <i class="fas fa-clock mr-2"></i>Expiré
                                    </span>
                                </div>
                            <?php endif; ?>

                            <!-- Slides -->
                            <div class="slides-container flex h-full transition-transform duration-500">
                                <?php if ($image_count === 0): ?>
                                    <div class="min-w-full h-full flex items-center justify-center">
                                        <div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-slate-200 to-slate-100 text-slate-400 gap-2">
                                            <i class="fas fa-image text-4xl"></i>
                                            <span class="text-sm font-medium">Aucune image</span>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($deal['images'] as $img): ?>
                                        <div class="min-w-full h-full flex items-center justify-center">
                                            <?php if ($img['exists']): ?>
                                                <img src="<?= htmlspecialchars($img['full_path']) ?>"
                                                     alt="<?= htmlspecialchars($deal['titre']) ?>"
                                                     loading="lazy"
                                                     class="w-full h-full object-cover transition-transform duration-500 deal-card:hover:scale-[1.04]"
                                                     onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-slate-200 to-slate-100 text-slate-400 gap-2\'><i class=\'fas fa-exclamation-triangle text-4xl\'></i><span class=\'text-sm font-medium\'>Erreur de chargement</span></div>'">
                                            <?php else: ?>
                                                <div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-slate-200 to-slate-100 text-slate-400 gap-2">
                                                    <i class="fas fa-image text-4xl"></i>
                                                    <span class="text-sm font-medium">Image introuvable</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <!-- Slider Controls -->
                            <?php if ($image_count > 1): ?>
                                <button class="absolute top-1/2 -translate-y-1/2 left-3 w-9 h-9 rounded-full bg-white/95 border-none cursor-pointer flex items-center justify-center text-sm text-gray-700 transition-all shadow-md z-10 opacity-0 deal-card:hover:opacity-100 hover:bg-blue-600 hover:text-white hover:scale-110" onclick="moveSlide(<?= $deal['id'] ?>, -1)">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <button class="absolute top-1/2 -translate-y-1/2 right-3 w-9 h-9 rounded-full bg-white/95 border-none cursor-pointer flex items-center justify-center text-sm text-gray-700 transition-all shadow-md z-10 opacity-0 deal-card:hover:opacity-100 hover:bg-blue-600 hover:text-white hover:scale-110" onclick="moveSlide(<?= $deal['id'] ?>, 1)">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                                <div class="absolute bottom-3.5 left-1/2 -translate-x-1/2 flex gap-1.5 z-10">
                                    <?php for ($i = 0; $i < $image_count; $i++): ?>
                                        <span class="w-2 h-2 rounded-full bg-white/40 cursor-pointer transition-all border-none <?= $i === 0 ? 'bg-white w-[22px] rounded-sm' : '' ?>" onclick="goToSlide(<?= $deal['id'] ?>, <?= $i ?>)"></span>
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Card Body -->
                        <div class="px-6 py-5 sm:py-7">
                            <div class="text-[0.7rem] font-bold text-blue-600 uppercase tracking-widest mb-2">Hot Deal</div>
                            <h3 class="text-[1.05rem] font-bold text-slate-900 leading-snug mb-2 line-clamp-2 min-h-[2.8em]"><?= htmlspecialchars($deal['titre']) ?></h3>
                            <p class="text-[0.82rem] text-slate-500 leading-relaxed mb-4 line-clamp-2"><?= htmlspecialchars($deal['description']) ?></p>

                            <div class="inline-flex items-center gap-1.5 bg-gradient-to-br from-emerald-50 to-emerald-100 text-emerald-800 px-3 py-1 rounded-lg text-[0.72rem] font-bold mb-3.5 border border-emerald-200">
                                <i class="fas fa-piggy-bank"></i>
                                Économisez <?= number_format($savings) ?> HTG
                            </div>

                            <div class="flex items-baseline gap-2.5 mb-4">
                                <span class="text-[1.65rem] font-black text-red-600 leading-none">
                                    <?= number_format($deal['prix_deal']) ?> <small class="text-[0.7em] font-semibold">HTG</small>
                                </span>
                                <span class="text-sm text-slate-400 line-through font-medium"><?= number_format($deal['prix_original']) ?> HTG</span>
                            </div>

                            <!-- Countdown -->
                            <?php if ($deal['date_fin']): ?>
                                <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-[14px] px-5 py-4 mb-5 border border-white/[0.06] <?= $expired ? 'expired' : '' ?>" data-end="<?= $deal['date_fin'] ?>" id="cd-box-<?= $deal['id'] ?>">
                                    <div class="flex items-center gap-1.5 text-[0.68rem] uppercase tracking-widest text-white/50 mb-2.5 font-semibold">
                                        <i class="fas fa-hourglass-half text-amber-500 text-[0.75rem]"></i> Expire dans
                                    </div>
                                    <div class="flex items-center gap-2" id="cd-<?= $deal['id'] ?>">
                                        <div class="text-center">
                                            <span class="countdown-num text-[1.35rem] font-black text-white leading-none min-w-[36px] block" id="cd-d-<?= $deal['id'] ?>">--</span>
                                            <div class="text-[0.55rem] text-white/35 uppercase tracking-wider mt-0.5 font-medium">Jours</div>
                                        </div>
                                        <span class="text-xl font-bold text-white/20 -mt-2">:</span>
                                        <div class="text-center">
                                            <span class="countdown-num text-[1.35rem] font-black text-white leading-none min-w-[36px] block" id="cd-h-<?= $deal['id'] ?>">--</span>
                                            <div class="text-[0.55rem] text-white/35 uppercase tracking-wider mt-0.5 font-medium">Heures</div>
                                        </div>
                                        <span class="text-xl font-bold text-white/20 -mt-2">:</span>
                                        <div class="text-center">
                                            <span class="countdown-num text-[1.35rem] font-black text-white leading-none min-w-[36px] block" id="cd-m-<?= $deal['id'] ?>">--</span>
                                            <div class="text-[0.55rem] text-white/35 uppercase tracking-wider mt-0.5 font-medium">Min</div>
                                        </div>
                                        <span class="text-xl font-bold text-white/20 -mt-2">:</span>
                                        <div class="text-center">
                                            <span class="countdown-num text-[1.35rem] font-black text-white leading-none min-w-[36px] block" id="cd-s-<?= $deal['id'] ?>">--</span>
                                            <div class="text-[0.55rem] text-white/35 uppercase tracking-wider mt-0.5 font-medium">Sec</div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Actions -->
                            <?php if (!$is_disabled): ?>
                                <form method="POST" action="">
                                    <input type="hidden" name="deal_id" value="<?= $deal['id'] ?>">
                                    <input type="hidden" name="add_to_cart" value="1">
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center border-2 border-slate-200 rounded-xl overflow-hidden shrink-0 transition-colors focus-within:border-blue-600">
                                            <button type="button" class="w-[38px] h-[38px] border-none bg-slate-50 cursor-pointer flex items-center justify-center text-slate-600 text-xs transition-all hover:bg-slate-200 hover:text-slate-900" onclick="changeQty(<?= $deal['id'] ?>, -1)">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" name="quantity" id="qty-<?= $deal['id'] ?>" value="1" min="1"
                                                max="<?= $deal['quantite_limite'] ?? 10 ?>"
                                                class="w-11 text-center font-bold text-sm border-none border-l-2 border-r-2 border-slate-200 py-2 bg-white text-slate-900 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" readonly>
                                            <button type="button" class="w-[38px] h-[38px] border-none bg-slate-50 cursor-pointer flex items-center justify-center text-slate-600 text-xs transition-all hover:bg-slate-200 hover:text-slate-900" onclick="changeQty(<?= $deal['id'] ?>, 1)">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        <button type="submit" class="flex-1 h-[42px] border-none rounded-xl font-bold text-sm cursor-pointer transition-all flex items-center justify-center gap-2 bg-gradient-to-br from-blue-600 to-blue-700 text-white shadow-lg shadow-blue-600/30 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-blue-600/40 active:translate-y-0 active:scale-[0.98]">
                                            <i class="fas fa-cart-plus"></i>
                                            Ajouter
                                        </button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <button class="w-full h-[46px] border-none rounded-xl font-bold text-sm cursor-not-allowed opacity-45 bg-slate-400 text-white flex items-center justify-center gap-2">
                                    <i class="fas fa-ban"></i>
                                    <?= $expired ? 'Expiré' : 'Épuisé' ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- ===== FEATURES ===== -->
    <section class="bg-white border-t border-slate-200 py-12">
        <div class="max-w-site mx-auto px-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
            <div class="flex items-center gap-4 px-6 py-5 rounded-2xl border border-slate-200 transition-all bg-white hover:border-blue-200 hover:shadow-lg hover:shadow-blue-600/10 hover:-translate-y-0.5">
                <div class="w-[50px] h-[50px] rounded-[14px] flex items-center justify-center shrink-0 text-xl bg-blue-50 text-blue-600">
                    <i class="fas fa-truck-fast"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 mb-0.5">Livraison Gratuite</h3>
                    <p class="text-xs text-slate-500 leading-snug">Pour les commandes de plus de 180 $</p>
                </div>
            </div>
            <div class="flex items-center gap-4 px-6 py-5 rounded-2xl border border-slate-200 transition-all bg-white hover:border-blue-200 hover:shadow-lg hover:shadow-blue-600/10 hover:-translate-y-0.5">
                <div class="w-[50px] h-[50px] rounded-[14px] flex items-center justify-center shrink-0 text-xl bg-emerald-50 text-emerald-600">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 mb-0.5">Paiement Sécurisé</h3>
                    <p class="text-xs text-slate-500 leading-snug">Plusieurs options de paiement fiables</p>
                </div>
            </div>
            <div class="flex items-center gap-4 px-6 py-5 rounded-2xl border border-slate-200 transition-all bg-white hover:border-blue-200 hover:shadow-lg hover:shadow-blue-600/10 hover:-translate-y-0.5">
                <div class="w-[50px] h-[50px] rounded-[14px] flex items-center justify-center shrink-0 text-xl bg-amber-50 text-amber-600">
                    <i class="fas fa-headset"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 mb-0.5">Support 24/7</h3>
                    <p class="text-xs text-slate-500 leading-snug">Disponibles en ligne tous les jours</p>
                </div>
            </div>
            <!-- Contactez-nous WhatsApp -->
            <a href="https://wa.me/50912345678?text=Bonjour%20LE-STOCK%2C%20je%20souhaite%20avoir%20plus%20d%27informations%20sur%20vos%20Hot%20Deals." target="_blank" rel="noopener noreferrer" class="flex items-center gap-4 px-6 py-5 rounded-2xl border-2 border-emerald-200 transition-all bg-gradient-to-br from-emerald-50/80 to-white hover:border-emerald-400 hover:shadow-lg hover:shadow-emerald-500/15 hover:-translate-y-0.5 no-underline group">
                <div class="w-[50px] h-[50px] rounded-[14px] flex items-center justify-center shrink-0 text-xl bg-gradient-to-br from-emerald-500 to-emerald-600 text-white animate-whatsapp-pulse shadow-lg shadow-emerald-500/30">
                    <i class="fab fa-whatsapp text-2xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-bold text-slate-900 mb-0.5 flex items-center gap-1.5">
                        Contactez-nous
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md bg-emerald-100 text-emerald-700 text-[0.6rem] font-extrabold uppercase tracking-wider leading-none">WhatsApp</span>
                    </h3>
                    <p class="text-xs text-emerald-700 font-semibold leading-snug flex items-center gap-1.5">
                        <i class="fab fa-whatsapp text-[0.7rem]"></i>
                        +509 32 73 29 20
                    </p>
                </div>
                <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center shrink-0 transition-all group-hover:bg-emerald-500 group-hover:text-white text-emerald-600">
                    <i class="fas fa-arrow-right text-xs transition-transform group-hover:translate-x-0.5"></i>
                </div>
            </a>
        </div>
    </section>

    <!-- ===== FOOTER ===== -->
    <footer class="bg-gradient-to-br from-slate-900 to-blue-900 text-blue-200 pt-14 pb-6">
        <div class="max-w-site mx-auto px-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-[1.5fr_1fr_1fr_1.2fr] gap-8">
            <!-- Col 1 -->
            <div>
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-400 rounded-xl flex items-center justify-center text-white font-black text-xl shadow-lg shadow-blue-500/30">L</div>
                    <span class="text-white font-extrabold text-xl tracking-tight">LE-STOCK</span>
                </div>
                <p class="text-slate-400 leading-relaxed text-sm">Votre destination pour les meilleures affaires. Qualité, prix et confiance depuis 2024.</p>
                <div class="flex gap-2 mt-5">
                    <a href="#" class="w-[38px] h-[38px] bg-white/[0.06] border border-white/[0.08] rounded-[10px] flex items-center justify-center text-slate-400 no-underline transition-all text-sm hover:bg-blue-600 hover:border-blue-600 hover:text-white hover:-translate-y-0.5 hover:shadow-lg hover:shadow-blue-600/30" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="w-[38px] h-[38px] bg-white/[0.06] border border-white/[0.08] rounded-[10px] flex items-center justify-center text-slate-400 no-underline transition-all text-sm hover:bg-blue-600 hover:border-blue-600 hover:text-white hover:-translate-y-0.5 hover:shadow-lg hover:shadow-blue-600/30" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="w-[38px] h-[38px] bg-white/[0.06] border border-white/[0.08] rounded-[10px] flex items-center justify-center text-slate-400 no-underline transition-all text-sm hover:bg-blue-600 hover:border-blue-600 hover:text-white hover:-translate-y-0.5 hover:shadow-lg hover:shadow-blue-600/30" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    <a href="#" class="w-[38px] h-[38px] bg-white/[0.06] border border-white/[0.08] rounded-[10px] flex items-center justify-center text-slate-400 no-underline transition-all text-sm hover:bg-blue-600 hover:border-blue-600 hover:text-white hover:-translate-y-0.5 hover:shadow-lg hover:shadow-blue-600/30" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                </div>
            </div>

            <!-- Col 2 -->
            <div>
                <h4 class="footer-heading text-white font-bold text-sm mb-5 relative pb-2.5">Entreprise</h4>
                <div class="flex flex-col gap-1">
                    <a href="../index" class="text-slate-400 no-underline text-sm transition-all inline-block py-0.5 hover:text-white hover:translate-x-0.5">Accueil</a>
                    <a href="#" class="text-slate-400 no-underline text-sm transition-all inline-block py-0.5 hover:text-white hover:translate-x-0.5">Blog</a>
                    <a href="Contacte" class="text-slate-400 no-underline text-sm transition-all inline-block py-0.5 hover:text-white hover:translate-x-0.5">Contactez-nous</a>
                    <a href="#" class="text-slate-400 no-underline text-sm transition-all inline-block py-0.5 hover:text-white hover:translate-x-0.5">Carrières</a>
                </div>
            </div>

            <!-- Col 3 -->
            <div>
                <h4 class="footer-heading text-white font-bold text-sm mb-5 relative pb-2.5">Service Client</h4>
                <div class="flex flex-col gap-1">
                    <a href="#" class="text-slate-400 no-underline text-sm transition-all inline-block py-0.5 hover:text-white hover:translate-x-0.5">Mon Compte</a>
                    <a href="#" class="text-slate-400 no-underline text-sm transition-all inline-block py-0.5 hover:text-white hover:translate-x-0.5">Suivre ma Commande</a>
                    <a href="#" class="text-slate-400 no-underline text-sm transition-all inline-block py-0.5 hover:text-white hover:translate-x-0.5">Retours</a>
                    <a href="#" class="text-slate-400 no-underline text-sm transition-all inline-block py-0.5 hover:text-white hover:translate-x-0.5">FAQ</a>
                </div>
            </div>

            <!-- Col 4 -->
            <div>
                <h4 class="footer-heading text-white font-bold text-sm mb-5 relative pb-2.5">Coordonnées</h4>
                <div>
                    <div class="text-slate-400 flex items-start gap-3 mb-3.5 text-sm">
                        <i class="fas fa-phone text-blue-500 mt-0.5 w-4 text-center"></i>
                        <span> +50941726999/32733920</span>
                    </div>
                    <div class="text-slate-400 flex items-start gap-3 mb-3.5 text-sm">
                        <i class="fas fa-envelope text-blue-500 mt-0.5 w-4 text-center"></i>
                        <span> lestockentreprise@gmail.com</span>
                    </div>
                    <div class="text-slate-400 flex items-start gap-3 mb-3.5 text-sm">
                        <i class="fas fa-map-marker-alt text-blue-500 mt-0.5 w-4 text-center"></i>
                        <span> 12 Rue 24-A <br>
                           Cap-Haïtien, Nord, Haïti</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="max-w-site mx-auto px-6">
            <div class="border-t border-white/[0.06] mt-10 pt-6 flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center">
                <p class="text-slate-500 text-xs">© 2024 LE-STOCK. Tous droits réservés.</p>
                <div class="flex gap-2">
                    <button class="bg-white/[0.06] text-slate-400 border border-white/[0.08] px-3.5 py-1.5 rounded-lg text-xs cursor-pointer flex items-center gap-1.5 transition-all hover:bg-white/10 hover:text-white">
                        <i class="fas fa-globe text-[0.7rem]"></i> Français <i class="fas fa-chevron-down text-[0.55rem]"></i>
                    </button>
                    <button class="bg-white/[0.06] text-slate-400 border border-white/[0.08] px-3.5 py-1.5 rounded-lg text-xs cursor-pointer flex items-center gap-1.5 transition-all hover:bg-white/10 hover:text-white">
                        HTG <i class="fas fa-chevron-down text-[0.55rem]"></i>
                    </button>
                </div>
            </div>
        </div>
    </footer>

    <!-- ===== JAVASCRIPT ===== -->
    <script>
        // ===== HERO PARTICLES =====
        (function () {
            const container = document.getElementById('heroParticles');
            if (!container) return;
            for (let i = 0; i < 20; i++) {
                const span = document.createElement('span');
                span.className = 'absolute bg-white/30 rounded-full animate-float-up';
                span.style.left = Math.random() * 100 + '%';
                span.style.animationDuration = (4 + Math.random() * 6) + 's';
                span.style.animationDelay = Math.random() * 5 + 's';
                const size = (2 + Math.random() * 4) + 'px';
                span.style.width = size;
                span.style.height = size;
                container.appendChild(span);
            }
        })();

        // ===== IMAGE SLIDER =====
        const sliders = {};

        function moveSlide(dealId, direction) {
            if (!sliders[dealId]) initSlider(dealId);
            sliders[dealId].current += direction;
            if (sliders[dealId].current >= sliders[dealId].total) sliders[dealId].current = 0;
            if (sliders[dealId].current < 0) sliders[dealId].current = sliders[dealId].total - 1;
            updateSlider(dealId);
        }

        function goToSlide(dealId, index) {
            if (!sliders[dealId]) initSlider(dealId);
            sliders[dealId].current = index;
            updateSlider(dealId);
        }

        function initSlider(dealId) {
            const el = document.getElementById('slider-' + dealId);
            if (!el) return;
            sliders[dealId] = { current: 0, total: el.querySelectorAll('.min-w-full').length };
        }

        function updateSlider(dealId) {
            const container = document.querySelector('#slider-' + dealId + ' .slides-container');
            const sliderEl = document.getElementById('slider-' + dealId);
            if (container) container.style.transform = 'translateX(-' + (sliders[dealId].current * 100) + '%)';

            const allDots = sliderEl.querySelectorAll('[onclick*="goToSlide(' + dealId + '"]');
            allDots.forEach((dot, i) => {
                if (i === sliders[dealId].current) {
                    dot.className = 'w-[22px] h-2 rounded-sm bg-white cursor-pointer transition-all border-none';
                } else {
                    dot.className = 'w-2 h-2 rounded-full bg-white/40 cursor-pointer transition-all border-none';
                }
            });
        }

        document.querySelectorAll('[id^="slider-"]').forEach(slider => {
            initSlider(slider.id.replace('slider-', ''));
        });

        setInterval(() => {
            Object.keys(sliders).forEach(id => {
                if (sliders[id].total > 1) moveSlide(id, 1);
            });
        }, 5000);

        // ===== COUNTDOWN =====
        function updateCountdowns() {
            document.querySelectorAll('[data-end]').forEach(box => {
                const dealId = box.id.replace('cd-box-', '');
                const endDate = new Date(box.dataset.end);
                const now = new Date();
                const diff = endDate - now;

                const dEl = document.getElementById('cd-d-' + dealId);
                const hEl = document.getElementById('cd-h-' + dealId);
                const mEl = document.getElementById('cd-m-' + dealId);
                const sEl = document.getElementById('cd-s-' + dealId);

                if (diff <= 0) {
                    if (dEl) dEl.textContent = '0';
                    if (hEl) hEl.textContent = '00';
                    if (mEl) mEl.textContent = '00';
                    if (sEl) sEl.textContent = '00';
                    box.classList.add('expired');
                    if (dEl) dEl.classList.add('text-red-400');
                    if (hEl) hEl.classList.add('text-red-400');
                    if (mEl) mEl.classList.add('text-red-400');
                    if (sEl) sEl.classList.add('text-red-400');
                    return;
                }

                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                if (dEl) dEl.textContent = days;
                if (hEl) hEl.textContent = String(hours).padStart(2, '0');
                if (mEl) mEl.textContent = String(minutes).padStart(2, '0');
                if (sEl) sEl.textContent = String(seconds).padStart(2, '0');
            });
        }

        setInterval(updateCountdowns, 1000);
        updateCountdowns();

        // ===== QUANTITY =====
        function changeQty(dealId, change) {
            const input = document.getElementById('qty-' + dealId);
            if (!input) return;
            let val = parseInt(input.value) + change;
            const max = parseInt(input.max) || 10;
            if (val >= 1 && val <= max) input.value = val;
        }

        // ===== CART BADGE =====
        function updateCartBadge() {
            fetch('panier/get_cart_count.php')
                .then(r => r.json())
                .then(d => {
                    const b = document.getElementById('cart-badge');
                    if (b) {
                        const old = parseInt(b.textContent) || 0;
                        b.textContent = d.count || 0;
                        if (d.count !== old && old !== 0) {
                            b.classList.add('animate-badge-bounce');
                            setTimeout(() => b.classList.remove('animate-badge-bounce'), 500);
                        }
                    }
                })
                .catch(() => { });
        }

        // ===== NOTIFICATION =====
        setTimeout(() => {
            const n = document.getElementById('cart-notification');
            if (n) n.classList.add('translate-x-[calc(100%+2rem)]');
            setTimeout(() => { if (n) n.remove(); }, 500);
        }, 4500);

        // ===== INIT =====
        document.addEventListener('DOMContentLoaded', updateCartBadge);
    </script>

</body>
</html>