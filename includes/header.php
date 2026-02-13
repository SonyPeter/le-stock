<?php
// includes/header.php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Mon E-commerce'; ?></title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Configuration Tailwind inline -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3B82F6',
                        secondary: '#1E293B',
                        accent: '#F59E0B'
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 font-sans">

<!-- Barre supérieure -->
<div class="bg-secondary text-gray-300 text-sm py-2 hidden md:block">
    <div class="container mx-auto px-4 flex justify-between">
        <div class="flex space-x-6">
            <span><i class="fas fa-phone mr-2 text-accent"></i>+33 1 23 45 67 89</span>
            <span><i class="fas fa-envelope mr-2 text-accent"></i>contact@boutique.com</span>
        </div>
        <div class="flex space-x-4">
            <a href="#" class="hover:text-white transition">Suivi de commande</a>
            <a href="#" class="hover:text-white transition">Aide</a>
        </div>
    </div>
</div>

<!-- Header principal -->
<header class="bg-white shadow-lg sticky top-0 z-50">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-20 gap-4">
            
            <!-- Bouton menu mobile -->
            <button id="mobile-menu-btn" class="lg:hidden p-2 hover:bg-gray-100 rounded-lg transition">
                <i class="fas fa-bars text-2xl text-gray-700"></i>
            </button>

            <!-- Logo -->
            <a href="index.php" class="flex items-center gap-2 shrink-0 group">
                <div class="w-10 h-10 bg-gradient-to-br from-primary to-blue-600 rounded-xl flex items-center justify-center transform group-hover:rotate-12 transition duration-300">
                    <i class="fas fa-shopping-bag text-white text-xl"></i>
                </div>
                <span class="text-2xl font-bold bg-gradient-to-r from-primary to-blue-600 bg-clip-text text-transparent hidden sm:block">MaBoutique</span>
            </a>

            <!-- Navigation desktop -->
            <nav class="hidden lg:flex items-center gap-8">
                <a href="index.php" class="text-gray-700 hover:text-primary font-medium transition relative group">
                    Accueil
                    <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-primary transition-all group-hover:w-full"></span>
                </a>
                
                <!-- Menu déroulant Catégories -->
                <div class="relative group">
                    <button class="text-gray-700 hover:text-primary font-medium transition flex items-center gap-1 py-2">
                        Catégories
                        <i class="fas fa-chevron-down text-xs transition-transform group-hover:rotate-180"></i>
                    </button>
                    <div class="absolute top-full left-0 w-56 bg-white shadow-xl rounded-xl mt-1 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 border border-gray-100 transform translate-y-2 group-hover:translate-y-0">
                        <div class="p-2">
                            <a href="#" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition text-gray-700">
                                <i class="fas fa-laptop text-primary w-5"></i>
                                <span>Électronique</span>
                            </a>
                            <a href="#" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition text-gray-700">
                                <i class="fas fa-tshirt text-primary w-5"></i>
                                <span>Mode</span>
                            </a>
                            <a href="#" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition text-gray-700">
                                <i class="fas fa-home text-primary w-5"></i>
                                <span>Maison</span>
                            </a>
                            <a href="#" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition text-gray-700">
                                <i class="fas fa-futbol text-primary w-5"></i>
                                <span>Sport</span>
                            </a>
                        </div>
                    </div>
                </div>

                <a href="#" class="text-gray-700 hover:text-primary font-medium transition relative">
                    Promotions
                    <span class="absolute -top-2 -right-6 bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">Hot</span>
                </a>
                <a href="#" class="text-gray-700 hover:text-primary font-medium transition">Nouveautés</a>
            </nav>

            <!-- Barre de recherche -->
         <div class="hidden md:flex flex-1 max-w-md mx-4">
    <form action="pages/recherche.php" method="GET" class="relative w-full">
        <input type="text" 
               name="q"
               placeholder="Rechercher..." 
               class="w-full pl-10 pr-4 py-2 bg-transparent border-b-2 border-gray-300 focus:border-primary focus:outline-none transition placeholder-gray-400">
        <i class="fas fa-search absolute left-0 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <button type="submit" class="absolute right-0 top-1/2 -translate-y-1/2 text-primary hover:text-blue-600 transition">
            <i class="fas fa-arrow-right"></i>
        </button>
    </form>
</div>

            <!-- Actions droite -->
            <div class="flex items-center gap-2 sm:gap-4">
                
                <!-- Recherche mobile -->
                <button id="mobile-search-btn" class="md:hidden p-2 hover:bg-gray-100 rounded-full transition">
                    <i class="fas fa-search text-xl text-gray-700"></i>
                </button>

                <!-- Favoris -->
                <a href="pages/favoris.php" class="hidden sm:flex p-2 hover:bg-gray-100 rounded-full transition relative group">
                    <i class="far fa-heart text-xl text-gray-700 group-hover:text-red-500 transition"></i>
                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs w-5 h-5 flex items-center justify-center rounded-full font-bold">3</span>
                </a>

                <!-- Panier -->
                <a href="pages/panier.php" class="p-2 hover:bg-gray-100 rounded-full transition relative group">
                    <i class="fas fa-shopping-cart text-xl text-gray-700 group-hover:text-primary transition"></i>
                    <span id="cart-count" class="absolute -top-1 -right-1 bg-primary text-white text-xs w-5 h-5 flex items-center justify-center rounded-full font-bold">
                        <?php echo isset($_SESSION['cart_count']) ? $_SESSION['cart_count'] : '0'; ?>
                    </span>
                </a>

                <!-- Compte utilisateur -->
                <div class="relative group">
                    <button class="flex items-center gap-2 p-2 hover:bg-gray-100 rounded-full transition">
                        <div class="w-8 h-8 bg-gradient-to-br from-primary to-blue-600 rounded-full flex items-center justify-center text-white text-sm font-bold">
                            <?php 
                            if (isset($_SESSION['user_name'])) {
                                echo strtoupper(substr($_SESSION['user_name'], 0, 1));
                            } else {
                                echo '<i class="fas fa-user text-xs"></i>';
                            }
                            ?>
                        </div>
                        <span class="hidden lg:block text-sm font-medium text-gray-700">
                            <?php echo isset($_SESSION['user_name']) ? explode(' ', $_SESSION['user_name'])[0] : 'Compte'; ?>
                        </span>
                    </button>
                    
                    <!-- Dropdown compte -->
                    <div class="absolute right-0 top-full w-52 bg-white shadow-xl rounded-xl mt-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 border border-gray-100 transform translate-y-2 group-hover:translate-y-0">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="p-2">
                                <a href="pages/profil.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-50 text-gray-700 transition">
                                    <i class="fas fa-user text-primary"></i>Mon profil
                                </a>
                                <a href="pages/commandes.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-50 text-gray-700 transition">
                                    <i class="fas fa-box text-primary"></i>Mes commandes
                                </a>
                                <a href="pages/parametres.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-50 text-gray-700 transition">
                                    <i class="fas fa-cog text-primary"></i>Paramètres
                                </a>
                                <hr class="my-2 border-gray-100">
                                <a href="controller/deconnexion.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-red-50 text-red-600 transition">
                                    <i class="fas fa-sign-out-alt"></i>Déconnexion
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="p-4 text-center">
                                <p class="text-gray-600 mb-3 text-sm">Connectez-vous pour accéder à votre compte</p>
                                <a href="pages/connexion.php" class="block w-full bg-primary text-white py-2 rounded-lg hover:bg-blue-600 transition mb-2">Se connecter</a>
                                <a href="pages/inscription.php" class="block w-full border border-gray-300 text-gray-700 py-2 rounded-lg hover:bg-gray-50 transition text-sm">Créer un compte</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barre de recherche mobile -->
    <div id="mobile-search" class="hidden md:hidden border-t border-gray-100 px-4 py-3">
        <form action="pages/recherche.php" method="GET" class="relative">
            <input type="text" 
                   name="q"
                   placeholder="Rechercher..." 
                   class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-200 focus:border-primary focus:outline-none">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        </form>
    </div>

    <!-- Menu mobile -->
    <div id="mobile-menu" class="hidden lg:hidden bg-white border-t border-gray-100">
        <div class="px-4 py-2 space-y-1">
            <a href="index.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-50 text-gray-700 font-medium">
                <i class="fas fa-home text-primary w-5"></i>Accueil
            </a>
            <a href="pages/categories.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-50 text-gray-700 font-medium">
                <i class="fas fa-th-large text-primary w-5"></i>Catégories
            </a>
            <a href="pages/promotions.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-50 text-gray-700 font-medium">
                <i class="fas fa-fire text-primary w-5"></i>Promotions
            </a>
            <a href="pages/nouveautes.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-50 text-gray-700 font-medium">
                <i class="fas fa-star text-primary w-5"></i>Nouveautés
            </a>
            <a href="pages/favoris.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-50 text-gray-700 font-medium sm:hidden">
                <i class="fas fa-heart text-primary w-5"></i>Favoris
                <span class="ml-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">3</span>
            </a>
        </div>
    </div>
</header>

<!-- Espace pour le header fixe -->
<div class="h-20"></div>

<script>
    // Toggle menu mobile
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
            const icon = mobileMenuBtn.querySelector('i');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
        });
    }

    // Toggle recherche mobile
    const mobileSearchBtn = document.getElementById('mobile-search-btn');
    const mobileSearch = document.getElementById('mobile-search');
    
    if (mobileSearchBtn) {
        mobileSearchBtn.addEventListener('click', () => {
            mobileSearch.classList.toggle('hidden');
        });
    }

    // Fonction pour mettre à jour le compteur panier
    function updateCartCount(count) {
        const cartCount = document.getElementById('cart-count');
        if (cartCount) {
            cartCount.textContent = count;
            cartCount.classList.add('animate-bounce');
            setTimeout(() => cartCount.classList.remove('animate-bounce'), 1000);
        }
    }
</script>