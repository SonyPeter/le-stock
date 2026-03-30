<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Mon E-commerce'; ?></title>

    <!-- Votre CSS Tailwind compilé -->
    <link rel="stylesheet" href="/le-stock/css/style.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50 font-sans">



    <!-- Header principal -->
    <header class="bg-white shadow-lg sticky top-0 bottom-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-25 gap-4">

                <!-- Bouton menu mobile -->
                <button id="mobile-menu-btn" class="lg:hidden p-2 hover:bg-gray-100 rounded-lg transition">
                    <i class="fas fa-bars text-2xl text-gray-700"></i>
                </button>

                <!-- VOTRE LOGO ICI -->
                <a href="../index.php" class="flex items-center gap-3 shrink-0 group">

                    <!-- Logo image avec effet hover -->
                    <div class=" flex items-center justify-center group-hover:shadow-lg transition-all duration-300">


                        <img src="/le-stock/assets/img/le stock entreprise copy.png"
                            alt="MaBoutique"
                            class="w-25 h-20 p-1 object-contain group-hover:scale-110 transition-transform duration-300">
                    </div>

                    <!-- Texte du logo (optionnel) -->
                    <div class="hidden sm:block">
                        <span class="text-xl font-bold text-slate-800 block leading-tight group-hover:text-blue-600 transition-colors">MaBoutique</span>

                    </div>

                </a>

                <!-- Navigation desktop -->
                <nav class="hidden lg:flex items-center gap-8">
                    <a href="page/acceuil.php" class="text-gray-700 hover:text-blue-600 font-medium transition relative group">
                        Accueil
                        <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-blue-600 transition-all group-hover:w-full"></span>
                    </a>

                    <nav class="hidden lg:flex items-center gap-8">
                        <a href="page/hot_deal.php" class="text-gray-700 hover:text-blue-600 font-medium transition relative group">
                            Deal
                            <span class="absolute -top-2 -right-6 bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">Hot</span>
                        </a>

                        <a href="page/promotion.php" class="text-gray-700 hover:text-blue-600 font-medium transition relative">
                            Promotions

                        </a>
                        <a href="page/affiliation.php" class="text-gray-700 hover:text-blue-600 font-medium transition">Affiliations</a>
                    </nav>

                    <!-- Barre de recherche -->
                    <div class="hidden md:flex flex-1 max-w-lg mx-4">
                        <form action="page/acceuil.php" method="GET" class="relative w-full group">
                            <input type="text"
                                name="search"
                                placeholder="Rechercher un produit..."
                                value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                                class="w-full pl-12 pr-24 py-2.5 rounded-full border-2 border-gray-200 focus:border-blue-600 focus:outline-none bg-gray-50 focus:bg-white transition shadow-sm focus:shadow-md">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-blue-600 transition"></i>
                            <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 bg-blue-600 text-white px-6 py-1.5 rounded-full hover:bg-blue-700 transition text-sm font-medium">
                                OK
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
                        <a href="page/panier/panier.php" class="p-2 hover:bg-gray-100 rounded-full transition relative group">
                            <i class="fas fa-shopping-cart text-xl text-gray-700 group-hover:text-blue-600 transition"></i>
                            <span id="cart-count" class="cart-count absolute -top-1 -right-1 bg-blue-600 text-white text-xs w-5 h-5 flex items-center justify-center rounded-full font-bold">
                                0
                            </span>
                        </a>

                        <!-- Compte utilisateur -->
                        <div class="relative group">
                            <button class="flex items-center gap-2 p-2 hover:bg-gray-100 rounded-full transition">
                                <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-bold">
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
                                        <a href="page/profile.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-50 text-gray-700 transition">
                                            <i class="fas fa-user text-blue-600"></i>Mon profil
                                        </a>
                                        <a href="page/panier/commande.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-50 text-gray-700 transition">
                                            <i class="fas fa-box text-blue-600"></i>Mes commandes
                                        </a>
                                        <a href="page/profile.php/?tab=settings" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-50 text-gray-700 transition">
                                            <i class="fas fa-cog text-blue-600"></i>Paramètres
                                        </a>
                                        <hr class="my-2 border-gray-100">
                                        <a href="page/logout.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-red-50 text-red-600 transition">
                                            <i class="fas fa-sign-out-alt"></i>Déconnexion
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="p-4 text-center">
                                        <p class="text-gray-600 mb-3 text-sm">Connectez-vous pour accéder à votre compte</p>
                                        <a href="page/login.php" class="block w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition mb-2">Se connecter</a>
                                        <a href="page/inscription.php" class="block w-full border border-gray-300 text-gray-700 py-2 rounded-lg hover:bg-gray-50 transition text-sm">Créer un compte</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
            </div>
        </div>

        <!-- Barre de recherche mobile -->
        <div id="mobile-search" class="hidden md:hidden border-t border-gray-100 px-4 py-3 bg-white">
            <form action="page/acceuil.php" method="GET" class="relative flex gap-2">
                <div class="relative flex-1">
                    <input type="text"
                        name="q"
                        placeholder="Rechercher..."
                        class="w-full pl-10 pr-4 py-3 rounded-lg border-2 border-gray-200 focus:border-blue-600 focus:outline-none bg-gray-50">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-4 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
        </div>

        <!-- Menu mobile -->
        <div id="mobile-menu" class="hidden lg:hidden bg-white border-t border-gray-100">
            <div class="px-4 py-2 space-y-1">
                <a href="index.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-50 text-gray-700 font-medium">
                    <i class="fas fa-home text-blue-600 w-5"></i>Accueil
                </a>
                <a href="pages/categories.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-50 text-gray-700 font-medium">
                    <i class="fas fa-th-large text-blue-600 w-5"></i>Catégories
                </a>
                <a href="pages/promotions.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-50 text-gray-700 font-medium">
                    <i class="fas fa-fire text-blue-600 w-5"></i>Promotions
                </a>
                <a href="pages/nouveautes.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-50 text-gray-700 font-medium">
                    <i class="fas fa-star text-blue-600 w-5"></i>Nouveautés
                </a>
                <a href="pages/favoris.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-50 text-gray-700 font-medium sm:hidden">
                    <i class="fas fa-heart text-blue-600 w-5"></i>Favoris
                    <span class="ml-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">3</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Espace pour le header fixe -->
    <!-- <div class="h-20"></div> -->

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


        // Fonksyon pou mete ajou kantite panier a
        function updateCartCount() {
            const cartCount = document.getElementById('cart-count');
            if (cartCount) {
                fetch('/le-stock/page/panier/get_cart_count.php')
                    .then(response => response.json())
                    .then(data => {
                        cartCount.textContent = data.count || 0;
                        if (data.count > 0) {
                            cartCount.classList.add('badge-bounce');
                            setTimeout(() => cartCount.classList.remove('badge-bounce'), 500);
                        }
                    })
                    .catch(error => console.error('Erè:', error));
            }
        }

        // Rele fonksyon an lè paj la chaje
        document.addEventListener('DOMContentLoaded', updateCartCount);

        // Fonksyon pou ajoute nan panier (si ou bezwen li nan header)
        function addToCart(productId) {
            fetch('/le-stock/page/panier/add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'product_id=' + productId + '&qty=1'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateCartCount();
                    }
                });
        }
    </script>