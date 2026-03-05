<?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';

// Si itilizatè a pa konekte, voye l nan login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

// 1. Rekipere enfòmasyon itilizatè a
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Done egzanp pou demo (nan yon vre pwoyè, sa yo ta soti nan baz done)
$user['points'] = $user['points'] ?? 2450;
$user['total_orders'] = 24;
$user['favorites_count'] = 3;
$user['member_status'] = $user['member_status'] ?? 'Gold';
$user['avatar'] = $user['avatar'] ?? 'https://images.unsplash.com/photo-1758273705998-05655eea4635?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHx3b21hbiUyMHNob3BwaW5nJTIwb25saW5lJTIwaGFwcHl8ZW58MXx8fHwxNzcyNzM5NzYyfDA&ixlib=rb-4.1.0&q=80&w=1080';

// 2. Traitement Modifikasyon Profil
if (isset($_POST['update_profile'])) {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $birthdate = $_POST['birthdate'] ?? '';

    if (!empty($nom) && !empty($prenom) && !empty($email)) {
        $update = $pdo->prepare("UPDATE users SET nom = ?, prenom = ?, email = ?, telephone = ? WHERE id = ?");
        if ($update->execute([$nom, $prenom, $email, $telephone, $user_id])) {
            $message = "Profil ou mete ajou ak siksè!";
            // Rafrechi done yo
            $user['nom'] = $nom;
            $user['prenom'] = $prenom;
            $user['email'] = $email;
            $user['telephone'] = $telephone;
        }
    } else {
        $error = "Tanpri ranpli tout chan yo.";
    }
}

// 3. Traitement Demann Machann (Upload Prèv)
if (isset($_POST['apply_merchant'])) {
    if (isset($_FILES['proof']) && $_FILES['proof']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $filename = $_FILES['proof']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_name = "proof_" . time() . "_" . $user_id . "." . $ext;
            $path = "assets/img/" . $new_name;

            if (move_uploaded_file($_FILES['proof']['tmp_name'], $path)) {
                $stmt = $pdo->prepare("UPDATE users SET merchant_status = 'pending', proof_payment = ? WHERE id = ?");
                $stmt->execute([$new_name, $user_id]);
                $message = "Demann ou an voye! Admin nan ap verifye sa.";
                $user['merchant_status'] = 'pending';
            }
        } else {
            $error = "Fòma sa a pa aksepte (JPG, PNG, PDF sèlman).";
        }
    } else {
        $error = "Ou dwe voye yon prèv peman.";
    }
}

// Done egzanp pou kòmand yo
$orders = [
    [
        'orderId' => '2026030501',
        'date' => '28 Février 2026',
        'status' => 'delivered',
        'total' => 1850,
        'items' => [
            ['name' => "Robe d'été élégante", 'image' => 'https://images.unsplash.com/photo-1579664531470-ac357f8f8e2b?w=200', 'quantity' => 2, 'price' => 950],
            ['name' => 'Vase décoratif moderne', 'image' => 'https://images.unsplash.com/photo-1645743712272-1aef8753a06a?w=200', 'quantity' => 1, 'price' => 900]
        ]
    ],
    [
        'orderId' => '2026022801',
        'date' => '20 Février 2026',
        'status' => 'shipped',
        'total' => 3200,
        'items' => [
            ['name' => 'Écouteurs sans fil premium', 'image' => 'https://images.unsplash.com/photo-1758979792186-32a5da91f24d?w=200', 'quantity' => 1, 'price' => 3200]
        ]
    ],
    [
        'orderId' => '2026021501',
        'date' => '10 Février 2026',
        'status' => 'pending',
        'total' => 1200,
        'items' => [
            ['name' => 'Coussin décoratif', 'image' => 'https://images.unsplash.com/photo-1645743712272-1aef8753a06a?w=200', 'quantity' => 3, 'price' => 1200]
        ]
    ]
];

// Done egzanp pou adrès yo
$addresses = [
    [
        'id' => 1,
        'name' => 'Domicile',
        'phone' => '+509 34 56 78 90',
        'address' => '45 Rue Capois, Apt 12',
        'city' => 'Port-au-Prince',
        'zipCode' => 'HT6110',
        'isDefault' => true
    ],
    [
        'id' => 2,
        'name' => 'Bureau',
        'phone' => '+509 34 56 78 90',
        'address' => '78 Rue Pavée, Étage 3',
        'city' => 'Pétion-Ville',
        'zipCode' => 'HT6140',
        'isDefault' => false
    ]
];

// Done egzanp pou favori yo
$favorites = [
    ['id' => 1, 'name' => "Robe d'été élégante", 'image' => 'https://images.unsplash.com/photo-1579664531470-ac357f8f8e2b?w=400', 'price' => 950],
    ['id' => 2, 'name' => 'Écouteurs sans fil', 'image' => 'https://images.unsplash.com/photo-1758979792186-32a5da91f24d?w=400', 'price' => 3200],
    ['id' => 3, 'name' => 'Vase décoratif', 'image' => 'https://images.unsplash.com/photo-1645743712272-1aef8753a06a?w=400', 'price' => 900]
];

// Fonksyon pou jere estati kòmand yo
function getStatusLabel($status) {
    $labels = [
        'delivered' => ['label' => 'Livré', 'class' => 'bg-green-100 text-green-700'],
        'shipped' => ['label' => 'Expédié', 'class' => 'bg-blue-100 text-blue-700'],
        'pending' => ['label' => 'En attente', 'class' => 'bg-yellow-100 text-yellow-700']
    ];
    return $labels[$status] ?? ['label' => $status, 'class' => 'bg-gray-100 text-gray-700'];
}

// Tab aktif
$activeTab = $_GET['tab'] ?? 'profile';
?>

<!DOCTYPE html>
<html lang="ht">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Mwen - Le Stock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body { font-family: 'Inter', sans-serif; }
        
        .gradient-text {
            background: linear-gradient(to right, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .gradient-bg {
            background: linear-gradient(to right, #3b82f6, #8b5cf6, #ec4899);
        }
        
        .card-gradient-1 { background: linear-gradient(to right, #3b82f6, #06b6d4); }
        .card-gradient-2 { background: linear-gradient(to right, #8b5cf6, #ec4899); }
        .card-gradient-3 { background: linear-gradient(to right, #eab308, #f97316); }
        .card-gradient-4 { background: linear-gradient(to right, #22c55e, #10b981); }
        
        .loyalty-gradient { 
            background: linear-gradient(to bottom right, #facc15, #f97316, #ec4899); 
        }
        
        .hover-scale { transition: transform 0.3s ease; }
        .group:hover .hover-scale { transform: scale(1.1); }
        
        .tab-active {
            background: linear-gradient(to right, #3b82f6, #8b5cf6);
            color: white;
        }
        
        .fade-in { animation: fadeIn 0.3s ease-in; }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .sidebar-link {
            transition: all 0.3s ease;
        }
        
        .sidebar-link:hover {
            background: linear-gradient(to right, #f3f4f6, #f9fafb);
        }
        
        .sidebar-link.active {
            background: linear-gradient(to right, #3b82f6, #8b5cf6);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="min-h-screen bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50">

    <!-- Header -->
    <header class="bg-white/80 backdrop-blur-lg border-b border-gray-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 gradient-bg rounded-xl flex items-center justify-center">
                    <i class="fas fa-shopping-bag text-white text-xl"></i>
                </div>
                <h1 class="text-2xl font-bold gradient-text">Le Stock</h1>
            </div>
            <a href="logout.php" class="px-4 py-2 border-2 border-gray-300 rounded-lg hover:border-red-500 hover:text-red-500 transition-colors flex items-center gap-2 text-sm font-medium">
                <i class="fas fa-sign-out-alt"></i>
                Dekonekte
            </a>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 py-8">

        <!-- Welcome Banner -->
        <div class="mb-8 p-8 rounded-2xl gradient-bg text-white shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full -ml-24 -mb-24"></div>
            <div class="relative z-10">
                <div class="flex items-center gap-2 mb-2">
                    <i class="fas fa-sparkles text-yellow-300"></i>
                    <span class="text-sm font-medium">Byenveni ankò!</span>
                </div>
                <h2 class="text-3xl font-bold mb-1">Bonjou, <?= htmlspecialchars($user['prenom']) ?> ! 👋</h2>
                <p class="text-blue-100">Men yon apèsi sou aktivite ou yo</p>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <!-- Kòmand -->
            <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden group border-0">
                <div class="h-2 card-gradient-1"></div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-3 card-gradient-1 rounded-xl hover-scale">
                            <i class="fas fa-box text-white text-xl"></i>
                        </div>
                        <i class="fas fa-arrow-trend-up text-green-500 text-lg"></i>
                    </div>
                    <p class="text-3xl font-bold mb-1"><?= $user['total_orders'] ?></p>
                    <p class="text-sm text-gray-600">Kòmand total</p>
                </div>
            </div>

            <!-- Favori -->
            <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden group border-0">
                <div class="h-2 card-gradient-2"></div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-3 card-gradient-2 rounded-xl hover-scale">
                            <i class="fas fa-heart text-white text-xl"></i>
                        </div>
                        <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-semibold"><?= count($favorites) ?></span>
                    </div>
                    <p class="text-3xl font-bold mb-1"><?= count($favorites) ?></p>
                    <p class="text-sm text-gray-600">Atik favori</p>
                </div>
            </div>

            <!-- Pwen -->
            <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden group border-0">
                <div class="h-2 card-gradient-3"></div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-3 card-gradient-3 rounded-xl hover-scale">
                            <i class="fas fa-gift text-white text-xl"></i>
                        </div>
                        <i class="fas fa-sparkles text-yellow-500 text-lg"></i>
                    </div>
                    <p class="text-3xl font-bold mb-1"><?= number_format($user['points'], 0, ',', ',') ?></p>
                    <p class="text-sm text-gray-600">Pwen fidèlite</p>
                </div>
            </div>

            <!-- Estati -->
            <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden group border-0">
                <div class="h-2 card-gradient-4"></div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-3 card-gradient-4 rounded-xl hover-scale">
                            <i class="fas fa-award text-white text-xl"></i>
                        </div>
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-semibold"><?= $user['member_status'] ?></span>
                    </div>
                    <p class="text-3xl font-bold mb-1"><?= $user['member_status'] ?></p>
                    <p class="text-sm text-gray-600">Estati manm</p>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
        <div class="mb-6 bg-green-500 text-white p-4 rounded-2xl shadow-lg flex items-center gap-3 fade-in">
            <i class="fas fa-check-circle"></i> <?= $message ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="mb-6 bg-red-500 text-white p-4 rounded-2xl shadow-lg flex items-center gap-3 fade-in">
            <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

            <!-- Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Profile Card -->
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden border-0">
                    <div class="h-24 gradient-bg"></div>
                    <div class="p-6 -mt-12 relative">
                        <div class="flex flex-col items-center mb-6">
                            <div class="relative group">
                                <div class="absolute inset-0 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full blur-lg opacity-75 group-hover:opacity-100 transition-opacity"></div>
                                <?php if (!empty($user['avatar']) && strpos($user['avatar'], 'http') === 0): ?>
                                <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Photo de profil" 
                                     class="relative w-24 h-24 rounded-full object-cover border-4 border-white shadow-xl"
                                     onerror="this.src='https://via.placeholder.com/150'">
                                <?php else: ?>
                                <div class="relative w-24 h-24 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-3xl font-bold border-4 border-white shadow-xl">
                                    <?= strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1)) ?>
                                </div>
                                <?php endif; ?>
                                <button class="absolute bottom-0 right-0 bg-gradient-to-r from-blue-500 to-purple-500 text-white p-2 rounded-full hover:scale-110 transition-transform shadow-lg">
                                    <i class="fas fa-camera text-sm"></i>
                                </button>
                            </div>
                            <h2 class="mt-4 font-bold text-xl"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h2>
                            <p class="text-sm text-gray-500"><?= htmlspecialchars($user['email']) ?></p>
                            <span class="mt-3 px-3 py-1 bg-gradient-to-r from-yellow-500 to-orange-500 text-white text-xs font-semibold rounded-full shadow-md flex items-center gap-1">
                                <i class="fas fa-gift text-xs"></i>
                                Kliyan <?= $user['member_status'] ?>
                            </span>
                        </div>

                        <hr class="border-gray-200 mb-4">

                        <!-- Navigation -->
                        <nav class="space-y-1">
                            <a href="?tab=profile" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl <?= $activeTab === 'profile' ? 'active' : 'text-gray-700' ?>">
                                <i class="fas fa-user w-5"></i>
                                <span class="font-medium">Pwofil mwen</span>
                            </a>
                            <a href="?tab=orders" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl <?= $activeTab === 'orders' ? 'active' : 'text-gray-700' ?>">
                                <i class="fas fa-box w-5"></i>
                                <span>Kòmand mwen</span>
                            </a>
                            <a href="?tab=favorites" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl <?= $activeTab === 'favorites' ? 'active' : 'text-gray-700' ?>">
                                <i class="fas fa-heart w-5"></i>
                                <span>Favori mwen</span>
                                <span class="ml-auto px-2 py-0.5 bg-purple-100 text-purple-700 text-xs rounded-full"><?= count($favorites) ?></span>
                            </a>
                            <a href="?tab=addresses" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl <?= $activeTab === 'addresses' ? 'active' : 'text-gray-700' ?>">
                                <i class="fas fa-map-marker-alt w-5"></i>
                                <span>Adrès</span>
                            </a>
                            <?php if ($user['role'] == 'merchant'): ?>
                            <a href="merchant_dashboard.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700">
                                <i class="fas fa-store w-5"></i>
                                <span>Dashboard Machann</span>
                            </a>
                            <?php else: ?>
                            <a href="?tab=merchant" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl <?= $activeTab === 'merchant' ? 'active' : 'text-gray-700' ?>">
                                <i class="fas fa-store w-5"></i>
                                <span>Vin machann</span>
                            </a>
                            <?php endif; ?>
                            <a href="?tab=notifications" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl <?= $activeTab === 'notifications' ? 'active' : 'text-gray-700' ?>">
                                <i class="fas fa-bell w-5"></i>
                                <span>Notifikasyon</span>
                            </a>
                            <a href="?tab=settings" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl <?= $activeTab === 'settings' ? 'active' : 'text-gray-700' ?>">
                                <i class="fas fa-cog w-5"></i>
                                <span>Paramèt</span>
                            </a>
                        </nav>
                    </div>
                </div>

                <!-- Loyalty Points Card -->
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden border-0">
                    <div class="loyalty-gradient p-6 text-white">
                        <div class="flex items-center justify-between mb-3">
                            <span class="font-medium">Pwen fidèlite</span>
                            <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                                <i class="fas fa-gift text-xl"></i>
                            </div>
                        </div>
                        <p class="text-4xl font-bold mb-2"><?= number_format($user['points'], 0, ',', ',') ?></p>
                        <div class="flex items-center gap-2 text-yellow-100 text-sm">
                            <i class="fas fa-sparkles"></i>
                            <p>= <?= number_format($user['points'] / 10, 0, ',', ',') ?> HTG rediksyon</p>
                        </div>
                    </div>
                    <div class="p-4">
                        <button class="w-full py-2.5 bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-600 hover:to-orange-600 text-white font-medium rounded-xl shadow-lg transition-all">
                            Itilize pwen mwen
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="lg:col-span-3">
                
                <!-- Tabs Navigation (Mobile/Desktop) -->
                <div class="mb-6 bg-white rounded-xl shadow-lg p-1 overflow-x-auto">
                    <div class="flex space-x-1 min-w-max">
                        <a href="?tab=profile" class="px-4 py-2 rounded-lg text-sm font-medium transition-all <?= $activeTab === 'profile' ? 'tab-active' : 'text-gray-600 hover:bg-gray-100' ?>">Pwofil</a>
                        <a href="?tab=orders" class="px-4 py-2 rounded-lg text-sm font-medium transition-all <?= $activeTab === 'orders' ? 'tab-active' : 'text-gray-600 hover:bg-gray-100' ?>">Kòmand</a>
                        <a href="?tab=favorites" class="px-4 py-2 rounded-lg text-sm font-medium transition-all <?= $activeTab === 'favorites' ? 'tab-active' : 'text-gray-600 hover:bg-gray-100' ?>">Favori</a>
                        <a href="?tab=addresses" class="px-4 py-2 rounded-lg text-sm font-medium transition-all <?= $activeTab === 'addresses' ? 'tab-active' : 'text-gray-600 hover:bg-gray-100' ?>">Adrès</a>
                        <?php if ($user['role'] != 'merchant'): ?>
                        <a href="?tab=merchant" class="px-4 py-2 rounded-lg text-sm font-medium transition-all <?= $activeTab === 'merchant' ? 'tab-active' : 'text-gray-600 hover:bg-gray-100' ?>">Machann</a>
                        <?php endif; ?>
                        <a href="?tab=settings" class="px-4 py-2 rounded-lg text-sm font-medium transition-all <?= $activeTab === 'settings' ? 'tab-active' : 'text-gray-600 hover:bg-gray-100' ?>">Paramèt</a>
                    </div>
                </div>

                <!-- Tab Contents -->
                <div class="fade-in">
                    
                    <?php if ($activeTab === 'profile'): ?>
                    <!-- Profile Tab -->
                    <div class="bg-white rounded-2xl shadow-xl border-0 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-50 to-purple-50 border-b px-6 py-4">
                            <h3 class="flex items-center gap-2 font-bold text-lg text-gray-800">
                                <i class="fas fa-user-edit text-blue-600"></i>
                                Enfòmasyon pèsonèl
                            </h3>
                        </div>
                        <div class="p-6 space-y-6">
                            <form method="POST" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <label class="font-medium text-gray-700 text-sm">Non</label>
                                        <input type="text" name="prenom" value="<?= htmlspecialchars($user['prenom']) ?>" 
                                               class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:outline-none transition-colors">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="font-medium text-gray-700 text-sm">Siyati</label>
                                        <input type="text" name="nom" value="<?= htmlspecialchars($user['nom']) ?>" 
                                               class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:outline-none transition-colors">
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="font-medium text-gray-700 text-sm">Imèl</label>
                                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" 
                                           class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:outline-none transition-colors">
                                </div>

                                <div class="space-y-2">
                                    <label class="font-medium text-gray-700 text-sm">Telefòn</label>
                                    <div class="relative">
                                        <i class="fas fa-phone absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                        <input type="tel" name="telephone" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>" 
                                               class="w-full pl-12 px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:outline-none transition-colors">
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="font-medium text-gray-700 text-sm">Dat nesans</label>
                                    <input type="date" name="birthdate" value="<?= $user['birthdate'] ?? '' ?>" 
                                           class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:outline-none transition-colors">
                                </div>

                                <hr class="border-gray-200">

                                <div class="space-y-2">
                                    <label class="font-medium text-gray-700 text-sm">Modpas aktyèl</label>
                                    <input type="password" name="current_password" placeholder="••••••••" 
                                           class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:outline-none transition-colors">
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <label class="font-medium text-gray-700 text-sm">Nouvo modpas</label>
                                        <input type="password" name="new_password" placeholder="••••••••" 
                                               class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:outline-none transition-colors">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="font-medium text-gray-700 text-sm">Konfime modpas</label>
                                        <input type="password" name="confirm_password" placeholder="••••••••" 
                                               class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:outline-none transition-colors">
                                    </div>
                                </div>

                                <button type="submit" name="update_profile" class="px-8 py-3 gradient-bg hover:opacity-90 text-white font-bold rounded-xl shadow-lg transition-all">
                                    <i class="fas fa-save mr-2"></i>Sove chanjman
                                </button>
                            </form>
                        </div>
                    </div>

                    <?php elseif ($activeTab === 'orders'): ?>
                    <!-- Orders Tab -->
                    <div class="space-y-6">
                        <?php foreach ($orders as $order): 
                            $status = getStatusLabel($order['status']);
                        ?>
                        <div class="bg-white rounded-2xl shadow-lg border-0 overflow-hidden hover:shadow-xl transition-shadow">
                            <div class="p-6 border-b border-gray-100">
                                <div class="flex flex-wrap items-center justify-between gap-4">
                                    <div>
                                        <p class="text-sm text-gray-500 mb-1">Kòmand #<?= $order['orderId'] ?></p>
                                        <p class="font-semibold text-lg"><?= $order['date'] ?></p>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="px-3 py-1 rounded-full text-sm font-medium <?= $status['class'] ?>">
                                            <?= $status['label'] ?>
                                        </span>
                                        <p class="text-xl font-bold gradient-text"><?= number_format($order['total'], 0, ',', ',') ?> HTG</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="space-y-4">
                                    <?php foreach ($order['items'] as $item): ?>
                                    <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl">
                                        <img src="<?= htmlspecialchars($item['image']) ?>" 
                                             alt="<?= htmlspecialchars($item['name']) ?>" 
                                             class="w-20 h-20 object-cover rounded-lg"
                                             onerror="this.src='https://via.placeholder.com/80'">
                                        <div class="flex-1">
                                            <h4 class="font-medium mb-1"><?= htmlspecialchars($item['name']) ?></h4>
                                            <p class="text-sm text-gray-500">Kantite: <?= $item['quantity'] ?></p>
                                        </div>
                                        <p class="font-semibold"><?= number_format($item['price'], 0, ',', ',') ?> HTG</p>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mt-4 flex gap-3">
                                    <button class="px-4 py-2 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:text-blue-500 transition-colors text-sm font-medium">
                                        Gade detay
                                    </button>
                                    <?php if ($order['status'] === 'delivered'): ?>
                                    <button class="px-4 py-2 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:text-blue-500 transition-colors text-sm font-medium">
                                        Kòmande ankò
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php elseif ($activeTab === 'favorites'): ?>
                    <!-- Favorites Tab -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($favorites as $item): ?>
                        <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden group border-0">
                            <div class="relative aspect-square overflow-hidden">
                                <img src="<?= htmlspecialchars($item['image']) ?>" 
                                     alt="<?= htmlspecialchars($item['name']) ?>" 
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                                     onerror="this.src='https://via.placeholder.com/400'">
                                <button class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm p-2.5 rounded-full shadow-lg hover:scale-110 transition-transform">
                                    <i class="fas fa-heart text-red-500"></i>
                                </button>
                                <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            </div>
                            <div class="p-5 bg-gradient-to-br from-white to-gray-50">
                                <h3 class="font-medium mb-3 truncate"><?= htmlspecialchars($item['name']) ?></h3>
                                <p class="text-2xl font-bold gradient-text mb-4"><?= number_format($item['price'], 0, ',', ',') ?> HTG</p>
                                <button class="w-full py-2.5 gradient-bg hover:opacity-90 text-white font-medium rounded-xl shadow-lg transition-all">
                                    <i class="fas fa-cart-plus mr-2"></i>Ajoute nan panye
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php elseif ($activeTab === 'addresses'): ?>
                    <!-- Addresses Tab -->
                    <div class="mb-6">
                        <button onclick="openAddAddressModal()" class="px-4 py-2.5 gradient-bg hover:opacity-90 text-white font-medium rounded-xl shadow-lg transition-all flex items-center gap-2">
                            <i class="fas fa-map-marker-alt"></i>
                            Ajoute nouvo adrès
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($addresses as $address): ?>
                        <div class="bg-white rounded-2xl shadow-lg border-0 p-6 relative <?= $address['isDefault'] ? 'ring-2 ring-blue-500' : '' ?>">
                            <?php if ($address['isDefault']): ?>
                            <span class="absolute top-4 right-4 px-2 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">Default</span>
                            <?php endif; ?>
                            <div class="flex items-start gap-3 mb-4">
                                <div class="p-3 gradient-bg rounded-xl text-white">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-lg"><?= htmlspecialchars($address['name']) ?></h3>
                                    <p class="text-gray-500 text-sm"><?= htmlspecialchars($address['phone']) ?></p>
                                </div>
                            </div>
                            <div class="space-y-1 text-gray-600 mb-4">
                                <p><?= htmlspecialchars($address['address']) ?></p>
                                <p><?= htmlspecialchars($address['zipCode'] . ' ' . $address['city']) ?></p>
                            </div>
                            <div class="flex gap-2">
                                <button class="flex-1 py-2 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:text-blue-500 transition-colors text-sm font-medium">
                                    Modifye
                                </button>
                                <?php if (!$address['isDefault']): ?>
                                <button class="flex-1 py-2 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:text-blue-500 transition-colors text-sm font-medium">
                                    Fè default
                                </button>
                                <button class="px-3 py-2 border-2 border-red-200 text-red-500 rounded-lg hover:bg-red-50 transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php elseif ($activeTab === 'merchant' && $user['role'] != 'merchant'): ?>
                    <!-- Merchant Application Tab -->
                    <div class="bg-white rounded-2xl shadow-xl border-0 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-50 to-purple-50 border-b px-6 py-4">
                            <h3 class="flex items-center gap-2 font-bold text-lg text-gray-800">
                                <i class="fas fa-store text-blue-600"></i>
                                Vin yon Machann
                            </h3>
                        </div>
                        <div class="p-8">
                            <p class="text-gray-600 mb-6">Achte pwodwi an gwo pou w revann epi akimile pwen kach. Peze yon sèl fwa 500 HTG pou w ka vin machann.</p>

                            <?php if ($user['merchant_status'] == 'pending'): ?>
                                <div class="bg-orange-100 text-orange-700 p-6 rounded-2xl font-bold text-center">
                                    <i class="fas fa-clock text-3xl mb-2"></i>
                                    <p>Demann ou an ap verifye pa Admin nan...</p>
                                </div>
                            <?php elseif ($user['merchant_status'] == 'rejected'): ?>
                                <div class="bg-red-100 text-red-700 p-6 rounded-2xl font-bold text-center mb-6">
                                    <i class="fas fa-times-circle text-3xl mb-2"></i>
                                    <p>Demann ou an te rejte. Ou ka re-voye yon lòt prèv.</p>
                                </div>
                            <?php endif; ?>

                            <?php if ($user['merchant_status'] != 'pending'): ?>
                            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                                <div class="border-2 border-dashed border-gray-300 p-8 rounded-2xl text-center hover:border-blue-500 transition-colors">
                                    <input type="file" name="proof" id="proof" class="hidden" required onchange="updateFileName()">
                                    <label for="proof" class="cursor-pointer block">
                                        <i class="fas fa-cloud-upload-alt text-5xl text-indigo-400 mb-4"></i>
                                        <p id="file-name" class="text-gray-600 font-medium">Klike la pou voye prèv peman ou (500 HTG)</p>
                                        <p class="text-sm text-gray-400 mt-2">JPG, PNG, PDF sèlman</p>
                                    </label>
                                </div>
                                <button type="submit" name="apply_merchant" class="w-full py-3 gradient-bg hover:opacity-90 text-white font-bold rounded-xl shadow-lg transition-all">
                                    <i class="fas fa-paper-plane mr-2"></i>Voye demann machann nan
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php elseif ($activeTab === 'notifications' || $activeTab === 'settings'): ?>
                    <!-- Settings/Notifications Tab -->
                    <div class="space-y-6">
                        <!-- Notifications -->
                        <div class="bg-white rounded-2xl shadow-xl border-0 overflow-hidden">
                            <div class="bg-gradient-to-r from-blue-50 to-purple-50 border-b px-6 py-4">
                                <h3 class="flex items-center gap-2 font-bold text-lg text-gray-800">
                                    <i class="fas fa-bell text-blue-600"></i>
                                    Notifikasyon
                                </h3>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="flex items-center justify-between p-4 bg-gradient-to-r from-gray-50 to-white rounded-xl border-2 border-gray-100">
                                    <div>
                                        <p class="font-medium">Notifikasyon pa imèl</p>
                                        <p class="text-sm text-gray-500">Resevwa ofr ak mizajou pa imèl</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" checked class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-blue-500 peer-checked:to-purple-500"></div>
                                    </label>
                                </div>
                                <div class="flex items-center justify-between p-4 bg-gradient-to-r from-gray-50 to-white rounded-xl border-2 border-gray-100">
                                    <div>
                                        <p class="font-medium">Notifikasyon pa SMS</p>
                                        <p class="text-sm text-gray-500">Resevwa alèt livrezon pa SMS</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-blue-500 peer-checked:to-purple-500"></div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Privacy -->
                        <div class="bg-white rounded-2xl shadow-xl border-0 overflow-hidden">
                            <div class="bg-gradient-to-r from-blue-50 to-purple-50 border-b px-6 py-4">
                                <h3 class="flex items-center gap-2 font-bold text-lg text-gray-800">
                                    <i class="fas fa-shield-alt text-blue-600"></i>
                                    Konfidansyalite
                                </h3>
                            </div>
                            <div class="p-6 space-y-4">
                                <button class="w-full py-3 border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:text-blue-500 hover:bg-blue-50 transition-all font-medium">
                                    <i class="fas fa-download mr-2"></i>Telechaje done mwen
                                </button>
                                <button onclick="confirmDelete()" class="w-full py-3 bg-gradient-to-r from-red-500 to-pink-500 hover:from-red-600 hover:to-pink-600 text-white rounded-xl shadow-lg transition-all font-medium">
                                    <i class="fas fa-trash-alt mr-2"></i>Siprime kont mwen
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

    <!-- Modal pou ajoute adrès -->
    <div id="addressModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6">
            <h3 class="text-xl font-bold mb-4">Nouvo adrès</h3>
            <form class="space-y-4">
                <input type="text" placeholder="Non adrès la (egzanp: Kay mwen)" class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:outline-none">
                <input type="text" placeholder="Adrès konplè" class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:outline-none">
                <div class="grid grid-cols-2 gap-4">
                    <input type="text" placeholder="Vil" class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:outline-none">
                    <input type="text" placeholder="Kòd postal" class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:outline-none">
                </div>
                <input type="tel" placeholder="Telefòn" class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:outline-none">
                <div class="flex gap-3 mt-6">
                    <button type="button" onclick="closeAddAddressModal()" class="flex-1 py-3 border-2 border-gray-200 rounded-xl hover:bg-gray-50 transition-colors font-medium">Anile</button>
                    <button type="submit" class="flex-1 py-3 gradient-bg text-white rounded-xl hover:opacity-90 transition-opacity font-medium">Sove</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddAddressModal() {
            document.getElementById('addressModal').classList.remove('hidden');
            document.getElementById('addressModal').classList.add('flex');
        }
        
        function closeAddAddressModal() {
            document.getElementById('addressModal').classList.add('hidden');
            document.getElementById('addressModal').classList.remove('flex');
        }
        
        function confirmDelete() {
            if (confirm('Èske ou sèten ou vle siprime kont ou? Aksyon sa irevokabl.')) {
                alert('Demann sipresyon an voye');
            }
        }
        
        function updateFileName() {
            const input = document.getElementById('proof');
            const label = document.getElementById('file-name');
            if (input.files.length > 0) {
                label.innerText = "Fichye chwazi: " + input.files[0].name;
                label.classList.add('text-indigo-600');
            }
        }
        
        // Fèmen modal lè klike deyò
        document.getElementById('addressModal').addEventListener('click', function(e) {
            if (e.target === this) closeAddAddressModal();
        });
    </script>

</body>
</html>