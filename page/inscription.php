<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Le Stock</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        poppins: ['Poppins', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        /* ============================================
           GLASSMORPHISM - PI TRANSPARENT
           ============================================ */
        
        /* Kontenè prensipal la - TRÈ TRANSPARENT */
        .glass-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }
        
        /* Zòn imaj nan - TRANSPARENT */
        .glass-image-zone {
            background: rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Zòn fòm nan - PI TRANSPARENT */
        .glass-form-zone {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        
        /* Input yo */
        .glass-input {
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: all 0.3s ease;
        }
        
        .glass-input:focus {
            background: rgba(255, 255, 255, 0.95);
            border-color: rgba(59, 130, 246, 0.8);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        
        /* Bouton */
        .glass-btn {
            background: rgba(37, 99, 235, 0.8);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }
        
        .glass-btn:hover {
            background: rgba(29, 78, 216, 0.9);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.4);
        }
        
        /* Shopping bags */
        .glass-bag {
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        /* Arrow */
        .arrow-curve {
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.2));
        }
        
        /* ============================================
           RESPONSIVE FIXES
           ============================================ */
        
        /* Mobile: Imaj pi piti */
        @media (max-width: 1023px) {
            .glass-image-zone {
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                min-height: 300px;
            }
            
            .glass-container {
                margin: 1rem;
            }
        }
        
        /* Mobile: Tit pi piti */
        @media (max-width: 640px) {
            .mobile-title {
                font-size: 1.75rem;
                line-height: 2rem;
            }
            
            .mobile-subtitle {
                font-size: 1.25rem;
            }
            
            .mobile-img {
                width: 12rem;
            }
            
            .mobile-bag {
                width: 2rem;
                height: 2.5rem;
            }
            
            .mobile-bag-lg {
                width: 2.25rem;
                height: 3rem;
            }
            
            .mobile-bag-sm {
                width: 1.75rem;
                height: 2.25rem;
            }
        }
        
        /* Trè piti ekran */
        @media (max-width: 380px) {
            .mobile-title {
                font-size: 1.5rem;
            }
            
            .mobile-subtitle {
                font-size: 1.125rem;
            }
            
            .mobile-img {
                width: 10rem;
            }
        }
        
        /* Desktop: Imaj pi gwo */
        @media (min-width: 1024px) {
            .desktop-img {
                width: 20rem;
            }
        }
        
        /* Landscape mobile */
        @media (max-height: 600px) and (orientation: landscape) {
            .glass-container {
                min-height: auto;
            }
            
            .landscape-padding {
                padding-top: 1rem;
                padding-bottom: 1rem;
            }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-0 sm:p-4 relative overflow-x-hidden overflow-y-auto">
    
    <!-- ============================================
         BACKGROUND IMAGE - LE STOCK
         ============================================ -->
    <div class="fixed inset-0 z-0">
        <img src="<?php echo '\le-stock\assets\img\stock6.png'; ?>" 
             alt="Background Le Stock" 
             class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-black/20"></div>
    </div>

    <!-- ============================================
         KONTENÈ GLASSMORPHISM - TOUT TRANSPARENT
         ============================================ -->
    <div class="relative z-10 w-full max-w-6xl mx-auto">
        <div class="glass-container rounded-none sm:rounded-3xl overflow-hidden flex flex-col lg:flex-row min-h-screen sm:min-h-[600px]">
            
            <!-- BÒ GOCH: Zòn imaj (TRANSPARENT) -->
            <div class="lg:w-5/12 glass-image-zone relative overflow-hidden flex-shrink-0">
                <!-- Gradient koulè sou background -->
                <div class="absolute inset-0 bg-gradient-to-br from-purple-600/30 to-pink-600/30"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"></div>
                
                <div class="relative z-10 h-full flex flex-col justify-center items-center p-4 sm:p-6 lg:p-8 text-center landscape-padding">
                    <h2 class="mobile-title text-3xl lg:text-4xl font-bold text-white mb-2 drop-shadow-lg">Envie de Shopping</h2>
                    <h3 class="mobile-subtitle text-xl lg:text-3xl font-bold text-yellow-300 mb-4 sm:mb-8 drop-shadow-md">Inscrit-toi Maintenant</h3>
                    
                    <!-- Imaj ak flèch -->
                    <div class="relative mt-2 sm:mt-4">
                        <!-- Flèch vèt -->
                        <svg class="absolute -top-4 sm:-top-8 right-0 w-20 sm:w-32 h-16 sm:h-24 arrow-curve z-20" viewBox="0 0 120 80" fill="none">
                            <path d="M10 70 Q 60 10, 100 40" stroke="#4ade80" stroke-width="8" fill="none" stroke-linecap="round"/>
                            <polygon points="95,35 110,45 100,55" fill="#4ade80"/>
                        </svg>
                        
                        <!-- Imaj mache -->
                        <div class="relative">
                            <img src="<?php echo '\le-stock\assets\img\anscrit.png'; ?>" 
                                 alt="Shopping" 
                                 class="mobile-img desktop-img w-48 sm:w-64 h-auto object-cover rounded-2xl shadow-2xl mx-auto"
                                 style="mask-image: linear-gradient(to bottom, black 80%, transparent 100%); -webkit-mask-image: linear-gradient(to bottom, black 80%, transparent 100%);">
                            
                            <!-- Sak mache yo -->
                            <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 translate-y-4 flex gap-1 sm:gap-2">
                                <div class="mobile-bag w-8 sm:w-12 h-10 sm:h-16 bg-yellow-400/60 glass-bag rounded-lg shadow-lg transform -rotate-12"></div>
                                <div class="mobile-bag-lg w-10 sm:w-14 h-12 sm:h-20 bg-pink-400/60 glass-bag rounded-lg shadow-lg transform rotate-6 flex items-center justify-center">
                                    <div class="w-5 sm:w-8 h-5 sm:h-8 bg-yellow-300 rounded-full opacity-60"></div>
                                </div>
                                <div class="mobile-bag w-8 sm:w-12 h-10 sm:h-16 bg-green-400/60 glass-bag rounded-lg shadow-lg transform rotate-12"></div>
                                <div class="mobile-bag-sm w-6 sm:w-10 h-8 sm:h-14 bg-orange-400/60 glass-bag rounded-lg shadow-lg transform -rotate-6"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BÒ DWAT: Zòn fòm (TRANSPARENT MEN KI KA LI) -->
            <div class="lg:w-7/12 glass-form-zone p-4 sm:p-6 lg:p-8 xl:p-12 flex flex-col justify-center flex-grow">
                <div class="max-w-md mx-auto w-full">
                    
                    <!-- Header -->
                    <div class="text-center mb-4 sm:mb-8">
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">Créez votre compte</h1>
                        <p class="text-gray-600 text-xs sm:text-sm">Remplissez les informations pour commencer</p>
                    </div>

                    <!-- Mesaj erè -->
                    <?php if(isset($_SESSION['error'])): ?>
                    <div class="bg-red-50/90 backdrop-blur-sm border-l-4 border-red-500 text-red-700 p-3 sm:p-4 mb-4 sm:mb-6 rounded-r-lg text-xs sm:text-sm">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                    <?php endif; ?>

                    <!-- Fòm -->
                    <form method="POST" action="traitement_inscription.php" class="space-y-3 sm:space-y-5" id="signupForm">
                        
                        <!-- Non & Prenon -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1 ml-1">Nom</label>
                                <input type="text" name="lastname" id="lastname" required
                                    class="w-full px-3 sm:px-4 py-2.5 sm:py-3 glass-input rounded-xl text-xs sm:text-sm outline-none placeholder-gray-500"
                                    placeholder="Votre nom">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1 ml-1">Prénom</label>
                                <input type="text" name="firstname" id="firstname" required
                                    class="w-full px-3 sm:px-4 py-2.5 sm:py-3 glass-input rounded-xl text-xs sm:text-sm outline-none placeholder-gray-500"
                                    placeholder="Votre prénom">
                            </div>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1 ml-1">Email</label>
                            <input type="email" name="email" id="email" required
                                class="w-full px-3 sm:px-4 py-2.5 sm:py-3 glass-input rounded-xl text-xs sm:text-sm outline-none placeholder-gray-500"
                                placeholder="votre@email.com">
                        </div>

                        <!-- Adrès -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1 ml-1">Adresse</label>
                            <input type="text" name="address" id="address"
                                class="w-full px-3 sm:px-4 py-2.5 sm:py-3 glass-input rounded-xl text-xs sm:text-sm outline-none placeholder-gray-500"
                                placeholder="Votre adresse complète">
                        </div>

                        <!-- Telefòn -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1 ml-1">Téléphone</label>
                            <input type="tel" name="phone" id="phone"
                                class="w-full px-3 sm:px-4 py-2.5 sm:py-3 glass-input rounded-xl text-xs sm:text-sm outline-none placeholder-gray-500"
                                placeholder="+509 00 00 00 00">
                        </div>

                        <!-- Modpas & Konfime -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1 ml-1">Mot de passe</label>
                                <input type="password" name="password" id="password" required
                                    class="w-full px-3 sm:px-4 py-2.5 sm:py-3 glass-input rounded-xl text-xs sm:text-sm outline-none placeholder-gray-500"
                                    placeholder="••••••••">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1 ml-1">Confirmer</label>
                                <input type="password" name="confirm_password" id="confirm_password" required
                                    class="w-full px-3 sm:px-4 py-2.5 sm:py-3 glass-input rounded-xl text-xs sm:text-sm outline-none placeholder-gray-500"
                                    placeholder="••••••••">
                            </div>
                        </div>

                        <!-- Bouton -->
                        <button type="submit" 
                            class="w-full glass-btn text-white py-3 sm:py-4 rounded-xl font-semibold text-xs sm:text-sm mt-4 sm:mt-6">
                            Créer Mon Compte
                        </button>
                    </form>

                    <!-- Lyen koneksyon -->
                    <p class="mt-4 sm:mt-6 text-center text-xs sm:text-sm text-gray-700">
                        Déjà un compte? 
                        <a href="login.php" class="text-blue-600 font-semibold hover:text-blue-800 hover:underline transition-colors">
                            Connectez-vous ici
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript pou validasyon -->
    <script>
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas!');
                return false;
            }
        });
    </script>
</body>
</html>