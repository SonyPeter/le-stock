<?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';

$error = "";
$success = "";

// 1. Kalkile Estatistik yo (Reèl + Bonus)
try {
    // Kliyan: Reèl + 100
    $count_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user' OR role = 'merchant'")->fetchColumn();
    $display_users = 100 + $count_users;

    // Pwodwi: Reèl + 1000
    $count_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $display_products = 1000 + $count_products;
} catch (Exception $e) {
    $display_users = 100;
    $display_products = 1000;
}

// 2. Traitement Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];
                $_SESSION['role'] = $user['role'];

                // Redireksyon otomatik
                if ($user['role'] === 'admin') header("Location: admin/index.php");
                elseif ($user['role'] === 'merchant') header("Location: merchant_dashboard.php");
                else header("Location: ../index.php");
                exit();
            } else {
                $error = "Imèl oswa modpas pa kòrèk.";
            }
        } catch (PDOException $e) {
            $error = "Erè teknik: " . $e->getMessage();
        }
    } else {
        $error = "Tanpri ranpli tout chan yo.";
    }
}
?>

<!DOCTYPE html>
<html lang="ht">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Le Stock</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="\le-stock\css\style.css">
    <style>
        .bg-image-side {
            background: linear-gradient(rgba(118, 75, 162, 0.8), rgba(102, 126, 234, 0.8)),
                url('/le-stock/assets/img/stock.png') center/cover no-repeat;
        }
        
        /* Amelyorasyon responsive pou tèks */
        @media (max-width: 1024px) {
            .bg-image-side {
                min-height: 400px;
            }
        }
        
        @media (max-width: 640px) {
            .bg-image-side {
                min-height: 300px;
                padding: 2rem 1.5rem;
            }
            
            .stats-grid {
                gap: 1rem;
            }
            
            .feature-card {
                padding: 1rem;
            }
        }
    </style>
</head>

<body class="bg-white overflow-x-hidden font-sans antialiased">

    <div class="flex flex-col lg:flex-row w-full min-h-screen">

        <!-- Seksyon Fòm (Goch sou desktop, anba sou mobil) -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-8 lg:p-16 order-2 lg:order-1">
            <div class="w-full max-w-md">

                <div class="flex justify-center mb-6">
                    <div class="w-16 h-16 bg-indigo-600 rounded-2xl flex items-center justify-center shadow-xl">
                        <i class="fas fa-shopping-bag text-white text-3xl"></i>
                    </div>
                </div>

                <h1 class="text-2xl sm:text-3xl font-bold text-center text-gray-900 mb-2">Bienvenue !</h1>
                <p class="text-center text-gray-500 mb-6 sm:mb-8 text-sm sm:text-base">Connectez-vous à votre espace Le Stock</p>

                <?php if ($error): ?>
                    <div class="bg-red-50 text-red-600 p-4 rounded-xl mb-6 border border-red-100 text-sm flex items-center gap-3">
                        <i class="fas fa-exclamation-circle text-lg"></i> 
                        <span><?= $error ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Adresse email</label>
                        <div class="relative">
                            <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="email" name="email" required placeholder="nom@exemple.com"
                                class="w-full pl-12 pr-4 py-3.5 sm:py-4 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all text-base placeholder:text-gray-400">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Mot de passe</label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="password" name="password" id="pass" required placeholder="••••••••"
                                class="w-full pl-12 pr-12 py-3.5 sm:py-4 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all text-base placeholder:text-gray-400">
                            <button type="button" onclick="togglePassword()" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-indigo-600 transition-colors focus:outline-none">
                                <i id="eye-icon" class="fas fa-eye text-lg"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-sm">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-gray-600">Se souvenir de moi</span>
                        </label>
                        <a href="#" class="font-bold text-indigo-600 hover:underline hover:text-indigo-800 transition-colors">Mot de passe oublié ?</a>
                    </div>

                    <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-xl font-bold hover:bg-indigo-700 active:bg-indigo-800 transition-all shadow-lg shadow-indigo-200 hover:shadow-xl text-base sm:text-lg">
                        Se connecter
                    </button>
                </form>

                <p class="text-center mt-8 text-sm text-gray-500">
                    Pas encore de compte ? 
                    <a href="inscription.php" class="font-bold text-indigo-600 hover:underline hover:text-indigo-800 transition-colors">Créer un compte</a>
                </p>
            </div>
        </div>

        <!-- Seksyon Imaj ak enfòmasyon (Dwat sou desktop, anwo sou mobil) -->
        <div class="w-full lg:w-1/2 bg-image-side flex flex-col justify-center px-6 sm:px-10 lg:px-20 text-white order-1 lg:order-2 py-12 sm:py-16 lg:py-20">
            <div class="max-w-xl mx-auto lg:mx-0">
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-4 sm:mb-6 leading-tight">Votre centrale d'achat intelligente.</h2>
                <p class="text-base sm:text-lg opacity-90 mb-8 sm:mb-12">Gérez votre stock, accumulez des points et boostez votre business.</p>

                <div class="stats-grid grid grid-cols-3 gap-4 sm:gap-8 mb-10 sm:mb-16">
                    <div class="text-center sm:text-left">
                        <span class="block text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight"><?= $display_users ?>+</span>
                        <span class="text-xs sm:text-sm uppercase tracking-widest opacity-70">Clients</span>
                    </div>
                    <div class="text-center sm:text-left">
                        <span class="block text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight"><?= $display_products ?>+</span>
                        <span class="text-xs sm:text-sm uppercase tracking-widest opacity-70">Produits</span>
                    </div>
                    <div class="text-center sm:text-left">
                        <div class="flex items-center justify-center sm:justify-start gap-1">
                            <i class="fas fa-star text-yellow-400 text-xl sm:text-2xl"></i>
                            <span class="text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight">4.9</span>
                        </div>
                        <span class="text-xs sm:text-sm uppercase tracking-widest opacity-70">Avis</span>
                    </div>
                </div>

                <div class="space-y-4 sm:space-y-6">
                    <div class="feature-card flex items-center gap-4 p-3 sm:p-4 bg-white/10 backdrop-blur-lg rounded-2xl border border-white/20 hover:bg-white/20 transition-colors">
                        <i class="fas fa-shield-alt text-xl sm:text-2xl text-indigo-200 flex-shrink-0"></i>
                        <div>
                            <h4 class="font-bold text-sm sm:text-base">Système de points Fidélité</h4>
                            <p class="text-xs sm:text-sm opacity-70">Gagnez des points sur chaque achat.</p>
                        </div>
                    </div>
                    <div class="feature-card flex items-center gap-4 p-3 sm:p-4 bg-white/10 backdrop-blur-lg rounded-2xl border border-white/20 hover:bg-white/20 transition-colors">
                        <i class="fas fa-bolt text-xl sm:text-2xl text-yellow-300 flex-shrink-0"></i>
                        <div>
                            <h4 class="font-bold text-sm sm:text-base">Gestion de Stock Express</h4>
                            <p class="text-xs sm:text-sm opacity-70">Admin panel intuitif pour vos produits.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('pass');
            const icon = document.getElementById('eye-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>

</html>