<?php
session_start();
require_once dirname(__DIR__) . '/page/login.php';

$error = "";
$success = "";

// ===== TRAITEMENT LOGIN SELMAN =====
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Tanpri ranpli tout chan yo.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['firstname'] . ' ' . $user['lastname'];
                header("Location: ../index.php");
                exit();
            } else {
                $error = "Imèl oswa modpas pa kòrèk.";
            }
        } catch (PDOException $e) {
            $error = "Gen yon erè. Tanpri eseye ankò.";
        }
    }
}

// Si gen paramèt "success" nan URL (soti nan inscription)
if (isset($_GET['success']) && $_GET['success'] === 'registered') {
    $success = "Kont kreye avèk siksè! Konekte kounye a.";
}
?>

<!DOCTYPE html>
<html lang="ht">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Le Stock</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <style>
        /* Gradient background */
        .bg-gradient-purple {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        /* Background image overlay */
        .bg-image-overlay::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('/le-stock/assets/img/stock.png') center/cover no-repeat;
            opacity: 0.3;
            z-index: 0;
        }

        /* Animasyon fade in */
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

        .animate-fade-in {
            animation: fadeInUp 0.8s ease-out forwards;
        }

        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.2s; }
        .delay-300 { animation-delay: 0.3s; }
        .delay-400 { animation-delay: 0.4s; }
        .delay-500 { animation-delay: 0.5s; }

        /* Pulse effect pou stats */
        @keyframes pulse-glow {
            0%, 100% {
                transform: scale(1);
                text-shadow: 0 0 20px rgba(255,255,255,0.3);
            }
            50% {
                transform: scale(1.05);
                text-shadow: 0 0 30px rgba(255,255,255,0.6);
            }
        }

        .stat-glow {
            animation: pulse-glow 2s ease-in-out infinite;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col lg:flex-row bg-gray-50">

    <!-- ===== KOTE GOCH: FORM LOGIN ===== -->
    <div class="w-full lg:w-1/2 bg-white flex flex-col items-center justify-center px-4 py-10 sm:px-6 lg:px-8 order-2 lg:order-1">
        <div class="w-full max-w-md">
            
            <!-- Logo -->
            <div class="mx-auto w-14 h-14 bg-black rounded-xl flex items-center justify-center mb-5">
                <i class="fas fa-shopping-bag text-white text-2xl"></i>
            </div>

            <!-- Tit -->
            <h1 class="text-center text-2xl sm:text-3xl font-bold text-gray-900 mb-2">
                Bienvenue !
            </h1>
            <p class="text-center text-sm text-gray-500 mb-8">
                Connectez-vous pour accéder à votre espace client
            </p>

            <!-- Messages -->
            <?php if($error): ?>
                <div class="w-full bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-5 text-sm text-center">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="w-full bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-5 text-sm text-center">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" action="" class="space-y-4">
                <input type="hidden" name="action" value="login">
                
                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Adresse email
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400 text-sm"></i>
                        </div>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="vous@exemple.com" 
                            required
                            class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                        >
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Mot de passe
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400 text-sm"></i>
                        </div>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="••••••••" 
                            required
                            class="w-full pl-10 pr-10 py-3 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                        >
                        <div 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer text-gray-400 hover:text-gray-600 transition-colors"
                            onclick="togglePassword('password', this)"
                        >
                            <i class="fas fa-eye text-sm"></i>
                        </div>
                    </div>
                </div>

                <!-- Options -->
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="remember" 
                            class="w-4 h-4 rounded border-gray-300 text-black focus:ring-black"
                        >
                        <span class="text-sm text-gray-600">Se souvenir de moi</span>
                    </label>
                    <a href="#" class="text-sm font-medium text-black hover:opacity-70 hover:underline transition-opacity">
                        Mot de passe oublié ?
                    </a>
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="w-full bg-black text-white py-3.5 rounded-lg font-semibold text-base hover:bg-gray-800 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200"
                >
                    Se connecter
                </button>
            </form>

            <!-- Divider -->
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-200"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-4 bg-white text-gray-400">Ou</span>
                </div>
            </div>

            <!-- Social Buttons -->
            <div class="space-y-3">
                <a 
                    href="#" 
                    class="w-full flex items-center justify-center gap-2.5 px-4 py-3 border border-gray-200 rounded-lg bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 hover:-translate-y-0.5 transition-all duration-200"
                >
                    <i class="fab fa-google text-red-500 text-lg"></i>
                    Continuer avec Google
                </a>
                <a 
                    href="#" 
                    class="w-full flex items-center justify-center gap-2.5 px-4 py-3 border border-gray-200 rounded-lg bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 hover:-translate-y-0.5 transition-all duration-200"
                >
                    <i class="fab fa-facebook-f text-blue-600 text-lg"></i>
                    Continuer avec Facebook
                </a>
            </div>

            <!-- Register Link -->
            <p class="mt-6 text-center text-sm text-gray-500">
                Pas encore de compte ? 
                <a href="inscription.php" class="font-semibold text-black hover:opacity-70 hover:underline transition-opacity">
                    Créer un compte gratuitement
                </a>
            </p>
        </div>
    </div>

    <!-- ===== KOTE DWAT: INFO PANEL ===== -->
    <div class="w-full lg:w-1/2 bg-gradient-purple relative overflow-hidden flex flex-col justify-center px-6 py-12 sm:px-10 lg:px-16 order-1 lg:order-2 min-h-[400px] lg:min-h-screen bg-image-overlay">
        
        <!-- Content -->
        <div class="relative z-10 text-white">
            
            <!-- Tit -->
            <h2 class="text-3xl sm:text-4xl font-bold leading-tight mb-4 animate-fade-in">
                Votre boutique en ligne de confiance
            </h2>
            <p class="text-base opacity-90 mb-10 leading-relaxed max-w-lg animate-fade-in delay-100">
                Des milliers de produits, des millions de clients satisfaits
            </p>

            <!-- Stats avec animasyon defileman -->
            <div class="flex flex-wrap gap-8 sm:gap-10 mb-10">
                
                <!-- Stat 1: 500K+ Clients -->
                <div class="text-center animate-fade-in delay-200">
                    <span 
                        class="block text-3xl sm:text-4xl font-bold stat-glow" 
                        data-target="500"
                        data-suffix="K+"
                    >
                        0
                    </span>
                    <span class="text-sm opacity-80 mt-1 block">Clients</span>
                </div>

                <!-- Stat 2: 50K+ Produits -->
                <div class="text-center animate-fade-in delay-300">
                    <span 
                        class="block text-3xl sm:text-4xl font-bold stat-glow" 
                        data-target="50"
                        data-suffix="K+"
                    >
                        0
                    </span>
                    <span class="text-sm opacity-80 mt-1 block">Produits</span>
                </div>

                <!-- Stat 3: 4.9 Satisfaction -->
                <div class="text-center animate-fade-in delay-400">
                    <div class="flex items-center justify-center gap-1.5 stat-glow">
                        <i class="fas fa-star text-yellow-400 text-xl"></i>
                        <span 
                            class="text-3xl sm:text-4xl font-bold" 
                            data-target="4.9"
                            data-suffix=""
                            data-decimals="1"
                        >
                            0
                        </span>
                    </div>
                    <span class="text-sm opacity-80 mt-1 block">Satisfaction</span>
                </div>

            </div>

            <!-- Features -->
            <div class="space-y-5 max-w-md">
                
                <!-- Feature 1 -->
                <div class="flex items-start gap-4 animate-fade-in delay-500">
                    <div class="w-11 h-11 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-shield-alt text-lg"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold text-base mb-0.5">100% Sécurisé</h4>
                        <p class="text-sm opacity-80 leading-relaxed">Vos données sont protégées avec un cryptage SSL</p>
                    </div>
                </div>

                <!-- Feature 2 -->
                <div class="flex items-start gap-4 animate-fade-in delay-500">
                    <div class="w-11 h-11 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-truck text-lg"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold text-base mb-0.5">Livraison Express</h4>
                        <p class="text-sm opacity-80 leading-relaxed">Recevez vos commandes en 24-48h partout en France</p>
                    </div>
                </div>

                <!-- Feature 3 -->
                <div class="flex items-start gap-4 animate-fade-in delay-500">
                    <div class="w-11 h-11 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-credit-card text-lg"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold text-base mb-0.5">Paiement Flexible</h4>
                        <p class="text-sm opacity-80 leading-relaxed">CB, PayPal, virement - Paiement en 3x sans frais</p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Toggle password visibility
        function togglePassword(inputId, iconContainer) {
            const input = document.getElementById(inputId);
            const icon = iconContainer.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Animasyon defileman pou stats yo
        function animateValue(element, start, end, duration, decimals = 0) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                
                // Easing function pou animasyon pi dous
                const easeOutQuart = 1 - Math.pow(1 - progress, 4);
                
                let current = start + (end - start) * easeOutQuart;
                
                if (decimals > 0) {
                    element.textContent = current.toFixed(decimals);
                } else {
                    element.textContent = Math.floor(current);
                }
                
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                } else {
                    // Ajoute suffix lè fini
                    const suffix = element.getAttribute('data-suffix') || '';
                    element.textContent = element.textContent + suffix;
                }
            };
            window.requestAnimationFrame(step);
        }

        // Lanse animasyon yo lè paj la chaje
        document.addEventListener('DOMContentLoaded', () => {
            const stats = document.querySelectorAll('[data-target]');
            
            stats.forEach((stat, index) => {
                const target = parseFloat(stat.getAttribute('data-target'));
                const decimals = parseInt(stat.getAttribute('data-decimals')) || 0;
                const delay = index * 200; // Delai ant chak stat
                
                setTimeout(() => {
                    animateValue(stat, 0, target, 2000, decimals);
                }, delay);
            });
        });
    </script>

</body>
</html>