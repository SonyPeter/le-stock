<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LE - STOCK | Programme d'Affiliation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            DEFAULT: '#2563eb',
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                            950: '#172554',
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="font-sans bg-white text-gray-900 antialiased">

    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/90 backdrop-blur-md border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
            <a href="../index.php" class="flex items-center gap-2">
                <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center">
                    <span class="text-white font-bold text-sm">LE</span>
                </div>
                <span class="font-bold text-lg tracking-tight">STOCK</span>
            </a>
            <div class="hidden md:flex items-center gap-8 text-sm font-medium text-gray-600">
                <a href="../index.php" class="hover:text-brand-600 transition-colors">Acceuil</a>
                <a href="#avantages" class="hover:text-brand-600 transition-colors">Avantages</a>
                <a href="#comment" class="hover:text-brand-600 transition-colors">Comment ça marche</a>
                <a href="#programme" class="hover:text-brand-600 transition-colors">Programme</a>
                <a href="#rejoindre" class="hover:text-brand-600 transition-colors">Rejoindre</a>
            </div>
            <button id="menuBtn" class="md:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors">
                <i data-lucide="menu" class="w-5 h-5"></i>
            </button>
        </div>
        <!-- Mobile menu -->
        <div id="mobileMenu" class="hidden md:hidden border-t border-gray-100 bg-white">
            <div class="px-6 py-4 flex flex-col gap-3 text-sm font-medium text-gray-600">
                <a href="#avantages" class="py-2 hover:text-brand-600 transition-colors">Avantages</a>
                <a href="#comment" class="py-2 hover:text-brand-600 transition-colors">Comment ça marche</a>
                <a href="#programme" class="py-2 hover:text-brand-600 transition-colors">Programme</a>
                <a href="#rejoindre" class="py-2 hover:text-brand-600 transition-colors">Rejoindre</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative pt-32 pb-20 md:pt-44 md:pb-32 overflow-hidden">
        <!-- Background decorations -->
        <div class="absolute top-0 right-0 w-96 h-96 bg-brand-100 rounded-full blur-3xl opacity-50 -translate-y-1/2 translate-x-1/3"></div>
        <div class="absolute bottom-0 left-0 w-72 h-72 bg-brand-50 rounded-full blur-3xl opacity-60 translate-y-1/2 -translate-x-1/3"></div>

        <div class="relative max-w-7xl mx-auto px-6 text-center">
            <div class="inline-flex items-center gap-2 bg-brand-50 text-brand-700 text-xs font-semibold tracking-wider uppercase px-4 py-2 rounded-full mb-8">
                <i data-lucide="star" class="w-3.5 h-3.5"></i>
                Programme d'Affiliation
            </div>

            <h1 class="text-4xl md:text-6xl lg:text-7xl font-extrabold tracking-tight leading-[1.08] mb-6">
                Devenez Partenaire<br>
                <span class="text-brand-600">Développez Votre</span><br>
                Activité
            </h1>

            <p class="max-w-2xl mx-auto text-lg md:text-xl text-gray-500 font-light leading-relaxed mb-10">
                Rejoignez notre programme d'affiliation et gagnez des commissions attractives en promouvant nos produits auprès de votre audience.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="#rejoindre" class="bg-brand-600 hover:bg-brand-700 text-white font-semibold text-base px-8 py-3.5 rounded-xl transition-all duration-300 shadow-lg shadow-brand-600/25 hover:shadow-xl hover:shadow-brand-600/30">
                    Rejoindre le Programme
                </a>
                <a href="#comment" class="border border-gray-200 hover:border-brand-300 text-gray-700 hover:text-brand-600 font-medium text-base px-8 py-3.5 rounded-xl transition-all duration-300 bg-white">
                    En Savoir Plus
                </a>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section id="avantages" class="py-16 md:py-24 bg-brand-600 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-10 left-10 w-40 h-40 border border-white rounded-full"></div>
            <div class="absolute bottom-10 right-10 w-60 h-60 border border-white rounded-full"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-80 h-80 border border-white rounded-full"></div>
        </div>
        <div class="relative max-w-7xl mx-auto px-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 md:gap-12">
                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-extrabold text-white mb-2">500+</div>
                    <div class="text-brand-200 text-sm font-medium">Partenaires Actifs</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-extrabold text-white mb-2">25%</div>
                    <div class="text-brand-200 text-sm font-medium">Commission Moyenne</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-extrabold text-white mb-2">50K+</div>
                    <div class="text-brand-200 text-sm font-medium">Produits Disponibles</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-extrabold text-white mb-2">48h</div>
                    <div class="text-brand-200 text-sm font-medium">Paiement Rapide</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Comment Ça Marche -->
    <section id="comment" class="py-20 md:py-32 bg-gray-50">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <div class="inline-flex items-center gap-2 bg-brand-50 text-brand-700 text-xs font-semibold tracking-wider uppercase px-4 py-2 rounded-full mb-4">
                    Processus Simple
                </div>
                <h2 class="text-3xl md:text-5xl font-bold tracking-tight mb-4">Comment Ça Marche ?</h2>
                <p class="max-w-xl mx-auto text-gray-500 text-lg">Trois étapes simples pour commencer à gagner avec notre programme d'affiliation.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Step 1 -->
                <div class="relative bg-white rounded-2xl p-8 shadow-sm border border-gray-100 hover:shadow-lg hover:border-brand-100 transition-all duration-300 group">
                    <div class="absolute -top-4 -left-2 w-10 h-10 bg-brand-600 rounded-xl flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-brand-600/30">01</div>
                    <div class="w-14 h-14 bg-brand-50 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-brand-100 transition-colors">
                        <i data-lucide="user-plus" class="w-7 h-7 text-brand-600"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Inscrivez-Vous</h3>
                    <p class="text-gray-500 leading-relaxed">Créez votre compte gratuitement en quelques minutes. Aucune expérience requise.</p>
                </div>

                <!-- Step 2 -->
                <div class="relative bg-white rounded-2xl p-8 shadow-sm border border-gray-100 hover:shadow-lg hover:border-brand-100 transition-all duration-300 group">
                    <div class="absolute -top-4 -left-2 w-10 h-10 bg-brand-600 rounded-xl flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-brand-600/30">02</div>
                    <div class="w-14 h-14 bg-brand-50 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-brand-100 transition-colors">
                        <i data-lucide="share-2" class="w-7 h-7 text-brand-600"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Partagez les Produits</h3>
                    <p class="text-gray-500 leading-relaxed">Utilisez vos liens d'affiliation uniques pour promouvoir nos produits sur vos canaux.</p>
                </div>

                <!-- Step 3 -->
                <div class="relative bg-white rounded-2xl p-8 shadow-sm border border-gray-100 hover:shadow-lg hover:border-brand-100 transition-all duration-300 group">
                    <div class="absolute -top-4 -left-2 w-10 h-10 bg-brand-600 rounded-xl flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-brand-600/30">03</div>
                    <div class="w-14 h-14 bg-brand-50 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-brand-100 transition-colors">
                        <i data-lucide="wallet" class="w-7 h-7 text-brand-600"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Gagnez des Revenus</h3>
                    <p class="text-gray-500 leading-relaxed">Recevez des commissions sur chaque vente générée grâce à vos recommandations.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Avantages du Programme -->
    <section id="programme" class="py-20 md:py-32">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <div class="inline-flex items-center gap-2 bg-brand-50 text-brand-700 text-xs font-semibold tracking-wider uppercase px-4 py-2 rounded-full mb-4">
                    Pourquoi Nous Choisir
                </div>
                <h2 class="text-3xl md:text-5xl font-bold tracking-tight mb-4">Avantages du Programme</h2>
                <p class="max-w-xl mx-auto text-gray-500 text-lg">Tout ce dont vous avez besoin pour réussir en tant qu'affilié.</p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Avantage 1 -->
                <div class="flex items-start gap-4 p-6 rounded-2xl border border-gray-100 hover:border-brand-200 hover:bg-brand-50/50 transition-all duration-300 group">
                    <div class="w-12 h-12 bg-brand-100 rounded-xl flex items-center justify-center shrink-0 group-hover:bg-brand-200 transition-colors">
                        <i data-lucide="headphones" class="w-6 h-6 text-brand-600"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-base mb-1">Support Prioritaire</h3>
                        <p class="text-gray-500 text-sm leading-relaxed">Une équipe dédiée à votre succès, disponible 7j/7 pour répondre à vos questions.</p>
                    </div>
                </div>

                <!-- Avantage 2 -->
                <div class="flex items-start gap-4 p-6 rounded-2xl border border-gray-100 hover:border-brand-200 hover:bg-brand-50/50 transition-all duration-300 group">
                    <div class="w-12 h-12 bg-brand-100 rounded-xl flex items-center justify-center shrink-0 group-hover:bg-brand-200 transition-colors">
                        <i data-lucide="zap" class="w-6 h-6 text-brand-600"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-base mb-1">Paiement Rapide</h3>
                        <p class="text-gray-500 text-sm leading-relaxed">Retirez vos gains en moins de 48 heures via plusieurs méthodes de paiement.</p>
                    </div>
                </div>

                <!-- Avantage 3 -->
                <div class="flex items-start gap-4 p-6 rounded-2xl border border-gray-100 hover:border-brand-200 hover:bg-brand-50/50 transition-all duration-300 group">
                    <div class="w-12 h-12 bg-brand-100 rounded-xl flex items-center justify-center shrink-0 group-hover:bg-brand-200 transition-colors">
                        <i data-lucide="gift" class="w-6 h-6 text-brand-600"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-base mb-1">Bonus & Récompenses</h3>
                        <p class="text-gray-500 text-sm leading-relaxed">Profitez de bonus mensuels et de récompenses exclusives pour les meilleurs affiliés.</p>
                    </div>
                </div>

                <!-- Avantage 4 -->
                <div class="flex items-start gap-4 p-6 rounded-2xl border border-gray-100 hover:border-brand-200 hover:bg-brand-50/50 transition-all duration-300 group">
                    <div class="w-12 h-12 bg-brand-100 rounded-xl flex items-center justify-center shrink-0 group-hover:bg-brand-200 transition-colors">
                        <i data-lucide="bar-chart-3" class="w-6 h-6 text-brand-600"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-base mb-1">Tableau de Bord</h3>
                        <p class="text-gray-500 text-sm leading-relaxed">Suivez vos performances en temps réel avec des statistiques détaillées et claires.</p>
                    </div>
                </div>

                <!-- Avantage 5 -->
                <div class="flex items-start gap-4 p-6 rounded-2xl border border-gray-100 hover:border-brand-200 hover:bg-brand-50/50 transition-all duration-300 group">
                    <div class="w-12 h-12 bg-brand-100 rounded-xl flex items-center justify-center shrink-0 group-hover:bg-brand-200 transition-colors">
                        <i data-lucide="link" class="w-6 h-6 text-brand-600"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-base mb-1">Outils de Promotion</h3>
                        <p class="text-gray-500 text-sm leading-relaxed">Bannières, liens personnalisés et contenus prêts à l'emploi pour vos campagnes.</p>
                    </div>
                </div>

                <!-- Avantage 6 -->
                <div class="flex items-start gap-4 p-6 rounded-2xl border border-gray-100 hover:border-brand-200 hover:bg-brand-50/50 transition-all duration-300 group">
                    <div class="w-12 h-12 bg-brand-100 rounded-xl flex items-center justify-center shrink-0 group-hover:bg-brand-200 transition-colors">
                        <i data-lucide="shield-check" class="w-6 h-6 text-brand-600"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-base mb-1">Cookies 90 Jours</h3>
                        <p class="text-gray-500 text-sm leading-relaxed">Une durée de cookie étendue pour maximiser vos chances de conversion.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pourquoi Rejoindre -->
    <section class="py-20 md:py-32 bg-brand-950 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-brand-600 rounded-full blur-3xl opacity-10 translate-x-1/2 -translate-y-1/2"></div>
        <div class="absolute bottom-0 left-0 w-72 h-72 bg-brand-400 rounded-full blur-3xl opacity-10 -translate-x-1/2 translate-y-1/2"></div>

        <div class="relative max-w-7xl mx-auto px-6">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div>
                    <div class="inline-flex items-center gap-2 bg-white/10 text-brand-300 text-xs font-semibold tracking-wider uppercase px-4 py-2 rounded-full mb-6">
                        Faites le Bon Choix
                    </div>
                    <h2 class="text-3xl md:text-5xl font-bold tracking-tight mb-6 leading-tight">
                        Pourquoi Rejoindre<br>Notre Programme ?
                    </h2>
                    <p class="text-brand-200 text-lg leading-relaxed mb-10">
                        Nous offrons l'un des programmes d'affiliation les plus généreux du marché, conçu pour récompenser durablement vos efforts.
                    </p>

                    <div class="space-y-6">
                        <div class="flex items-start gap-4">
                            <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                                <i data-lucide="check" class="w-4 h-4 text-white"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold mb-1">Commissions Jusqu'à 30%</h4>
                                <p class="text-brand-300 text-sm">Les meilleures commissions du secteur avec des bonus de performance.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                                <i data-lucide="check" class="w-4 h-4 text-white"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold mb-1">Produits à Forte Demande</h4>
                                <p class="text-brand-300 text-sm">Un catalogue de plus de 50 000 produits qui se vendent facilement.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                                <i data-lucide="check" class="w-4 h-4 text-white"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold mb-1">Formation Complète</h4>
                                <p class="text-brand-300 text-sm">Accédez à des ressources et formations pour optimiser vos résultats.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                                <i data-lucide="check" class="w-4 h-4 text-white"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold mb-1">Communauté Active</h4>
                                <p class="text-brand-300 text-sm">Échangez avec d'autres affiliés et partagez vos meilleures stratégies.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-3xl p-8 md:p-10">
                        <div class="text-center mb-8">
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-brand-600 rounded-2xl mb-4 shadow-lg shadow-brand-600/30">
                                <i data-lucide="trending-up" class="w-8 h-8 text-white"></i>
                            </div>
                            <h3 class="text-xl font-bold">Revenus Potentiels</h3>
                            <p class="text-brand-300 text-sm mt-1">Estimation mensuelle</p>
                        </div>
                        <div class="space-y-5">
                            <div class="flex items-center justify-between py-3 border-b border-white/10">
                                <span class="text-brand-200 text-sm">10 ventes / mois</span>
                                <span class="font-bold text-lg">250 €</span>
                            </div>
                            <div class="flex items-center justify-between py-3 border-b border-white/10">
                                <span class="text-brand-200 text-sm">50 ventes / mois</span>
                                <span class="font-bold text-lg">1 250 €</span>
                            </div>
                            <div class="flex items-center justify-between py-3 border-b border-white/10">
                                <span class="text-brand-200 text-sm">100 ventes / mois</span>
                                <span class="font-bold text-lg text-brand-400">2 500 €</span>
                            </div>
                            <div class="flex items-center justify-between py-3">
                                <span class="text-brand-200 text-sm">500+ ventes / mois</span>
                                <span class="font-bold text-lg text-brand-400">12 500 €+</span>
                            </div>
                        </div>
                        <p class="text-brand-400/60 text-xs mt-6 text-center">* Basé sur une commission moyenne de 25 € par vente</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Lancement Bientôt / CTA -->
    <section id="rejoindre" class="py-20 md:py-32">
        <div class="max-w-7xl mx-auto px-6">
            <div class="relative bg-brand-600 rounded-3xl overflow-hidden">
                <div class="absolute inset-0">
                    <div class="absolute top-0 right-0 w-80 h-80 bg-brand-500 rounded-full blur-3xl opacity-50 translate-x-1/4 -translate-y-1/4"></div>
                    <div class="absolute bottom-0 left-0 w-64 h-64 bg-brand-700 rounded-full blur-3xl opacity-50 -translate-x-1/4 translate-y-1/4"></div>
                </div>
                <div class="relative px-8 py-16 md:px-16 md:py-24 text-center">
                    <div class="inline-flex items-center gap-2 bg-white/15 text-white text-xs font-semibold tracking-wider uppercase px-4 py-2 rounded-full mb-6 backdrop-blur-sm">
                        <i data-lucide="rocket" class="w-3.5 h-3.5"></i>
                        Nouveau
                    </div>

                    <h2 class="text-3xl md:text-5xl font-extrabold tracking-tight text-white mb-4">Lancement Bientôt</h2>
                    <p class="max-w-xl mx-auto text-brand-100 text-lg leading-relaxed mb-10">
                        Notre programme d'affiliation ouvre bientôt ses portes. Inscrivez-vous dès maintenant pour être parmi les premiers à en profiter.
                    </p>

                    <!-- Email form -->
                    <form id="signupForm" class="max-w-md mx-auto flex flex-col sm:flex-row gap-3 mb-8">
                        <input
                            type="email"
                            id="emailInput"
                            placeholder="Votre adresse email"
                            required
                            class="flex-1 px-5 py-3.5 rounded-xl bg-white text-gray-900 placeholder-gray-400 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-white/50 shadow-lg">
                        <button
                            type="submit"
                            class="px-7 py-3.5 bg-gray-900 hover:bg-gray-800 text-white font-semibold text-sm rounded-xl transition-all duration-300 shadow-lg whitespace-nowrap">
                            S'Inscrire
                        </button>
                    </form>

                    <!-- Success message -->
                    <div id="successMsg" class="hidden max-w-md mx-auto mb-8">
                        <div class="flex items-center justify-center gap-2 bg-white/15 backdrop-blur-sm text-white text-sm font-medium px-5 py-3 rounded-xl">
                            <i data-lucide="check-circle" class="w-5 h-5"></i>
                            Merci ! Vous serez notifié au lancement.
                        </div>
                    </div>

                    <p class="text-brand-200 text-sm">
                        Gratuit · Sans engagement · Accès prioritaire garanti
                    </p>

                    <!-- Countdown placeholder -->
                    <div class="flex items-center justify-center gap-4 mt-10">
                        <div class="text-center">
                            <div class="w-16 h-16 md:w-20 md:h-20 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center border border-white/10">
                                <span id="days" class="text-2xl md:text-3xl font-bold text-white">--</span>
                            </div>
                            <span class="text-brand-200 text-xs mt-2 block">Jours</span>
                        </div>
                        <span class="text-white/40 text-2xl font-light mt-[-16px]">:</span>
                        <div class="text-center">
                            <div class="w-16 h-16 md:w-20 md:h-20 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center border border-white/10">
                                <span id="hours" class="text-2xl md:text-3xl font-bold text-white">--</span>
                            </div>
                            <span class="text-brand-200 text-xs mt-2 block">Heures</span>
                        </div>
                        <span class="text-white/40 text-2xl font-light mt-[-16px]">:</span>
                        <div class="text-center">
                            <div class="w-16 h-16 md:w-20 md:h-20 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center border border-white/10">
                                <span id="minutes" class="text-2xl md:text-3xl font-bold text-white">--</span>
                            </div>
                            <span class="text-brand-200 text-xs mt-2 block">Minutes</span>
                        </div>
                        <span class="text-white/40 text-2xl font-light mt-[-16px]">:</span>
                        <div class="text-center">
                            <div class="w-16 h-16 md:w-20 md:h-20 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center border border-white/10">
                                <span id="seconds" class="text-2xl md:text-3xl font-bold text-white">--</span>
                            </div>
                            <span class="text-brand-200 text-xs mt-2 block">Secondes</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-950 text-gray-400 py-12 md:py-16">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid md:grid-cols-4 gap-10 mb-12">
                <div class="md:col-span-2">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-sm">LE</span>
                        </div>
                        <span class="font-bold text-lg text-white tracking-tight">LE - STOCK</span>
                    </div>
                    <p class="text-sm leading-relaxed max-w-sm">
                        Le programme d'affiliation le plus généreux pour développer vos revenus en ligne. Rejoignez une communauté de partenaires performants.
                    </p>
                </div>
                <div>
                    <h4 class="font-semibold text-white text-sm mb-4">Programme</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="#" class="hover:text-white transition-colors">Avantages</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Comment ça marche</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Commissions</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-white text-sm mb-4">Légal</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="#" class="hover:text-white transition-colors">Conditions d'utilisation</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Politique de confidentialité</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Mentions légales</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Contact</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-white/10 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-sm">&copy; <?php echo date('Y'); ?> LE - STOCK. Tous droits réservés.</p>
                <div class="flex items-center gap-4">
                    <a href="#" class="w-9 h-9 bg-white/5 hover:bg-white/10 rounded-lg flex items-center justify-center transition-colors">
                        <i data-lucide="twitter" class="w-4 h-4"></i>
                    </a>
                    <a href="#" class="w-9 h-9 bg-white/5 hover:bg-white/10 rounded-lg flex items-center justify-center transition-colors">
                        <i data-lucide="instagram" class="w-4 h-4"></i>
                    </a>
                    <a href="#" class="w-9 h-9 bg-white/5 hover:bg-white/10 rounded-lg flex items-center justify-center transition-colors">
                        <i data-lucide="linkedin" class="w-4 h-4"></i>
                    </a>
                    <a href="#" class="w-9 h-9 bg-white/5 hover:bg-white/10 rounded-lg flex items-center justify-center transition-colors">
                        <i data-lucide="facebook" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Mobile menu toggle
        const menuBtn = document.getElementById('menuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        menuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        // Close mobile menu on link click
        mobileMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.add('hidden');
            });
        });

        // Countdown timer - set to 30 days from now
        const launchDate = new Date();
        launchDate.setDate(launchDate.getDate() + 30);
        launchDate.setHours(12, 0, 0, 0);

        function updateCountdown() {
            const now = new Date();
            const diff = launchDate - now;

            if (diff <= 0) {
                document.getElementById('days').textContent = '00';
                document.getElementById('hours').textContent = '00';
                document.getElementById('minutes').textContent = '00';
                document.getElementById('seconds').textContent = '00';
                return;
            }

            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);

            document.getElementById('days').textContent = String(days).padStart(2, '0');
            document.getElementById('hours').textContent = String(hours).padStart(2, '0');
            document.getElementById('minutes').textContent = String(minutes).padStart(2, '0');
            document.getElementById('seconds').textContent = String(seconds).padStart(2, '0');
        }

        updateCountdown();
        setInterval(updateCountdown, 1000);

        // Signup form
        const signupForm = document.getElementById('signupForm');
        const successMsg = document.getElementById('successMsg');

        signupForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const email = document.getElementById('emailInput').value;
            if (email) {
                signupForm.classList.add('hidden');
                successMsg.classList.remove('hidden');
                lucide.createIcons();
            }
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>

</html>