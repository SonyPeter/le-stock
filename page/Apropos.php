<?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>À Propos & Conditions | LE-STOCK</title>
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
                        'whatsapp-pulse': 'whatsappPulse 2s ease-in-out infinite',
                        'fade-in-up': 'fadeInUp 0.7s ease-out both',
                        'fade-in-left': 'fadeInLeft 0.7s ease-out both',
                        'fade-in-right': 'fadeInRight 0.7s ease-out both',
                        'scale-in': 'scaleIn 0.6s ease-out both',
                        'count-up': 'countPulse 0.4s ease-out',
                    },
                    keyframes: {
                        floatUp: {
                            '0%': { transform: 'translateY(100%) scale(0)', opacity: '0' },
                            '20%': { opacity: '1' },
                            '100%': { transform: 'translateY(-100vh) scale(1)', opacity: '0' },
                        },
                        whatsappPulse: {
                            '0%, 100%': { boxShadow: '0 0 0 0 rgba(37, 211, 102, 0.4)' },
                            '50%': { boxShadow: '0 0 0 10px rgba(37, 211, 102, 0)' },
                        },
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(40px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        fadeInLeft: {
                            '0%': { opacity: '0', transform: 'translateX(-40px)' },
                            '100%': { opacity: '1', transform: 'translateX(0)' },
                        },
                        fadeInRight: {
                            '0%': { opacity: '0', transform: 'translateX(40px)' },
                            '100%': { opacity: '1', transform: 'translateX(0)' },
                        },
                        scaleIn: {
                            '0%': { opacity: '0', transform: 'scale(0.85)' },
                            '100%': { opacity: '1', transform: 'scale(1)' },
                        },
                        countPulse: {
                            '0%': { transform: 'scale(1)' },
                            '50%': { transform: 'scale(1.08)' },
                            '100%': { transform: 'scale(1)' },
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
        .whatsapp-btn:active { transform: translateY(0); }

        .timeline-line {
            background: linear-gradient(to bottom, #3b82f6, #2563eb, #1d4ed8, #1e40af, transparent);
        }

        .value-card { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .value-card:hover { transform: translateY(-8px); }

        .stat-card { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .stat-card:hover { transform: translateY(-6px) scale(1.02); }

        .section-visible .anim-item { animation-play-state: running; }
        .about-section:not(.section-visible) .anim-item { animation-play-state: paused; opacity: 0; }

        .gradient-text {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 40%, #60a5fa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .pattern-dots {
            background-image: radial-gradient(circle, rgba(59, 130, 246, 0.08) 1px, transparent 1px);
            background-size: 24px 24px;
        }

        /* Terms specific */
        .toc-link {
            transition: all 0.2s ease;
        }
        .toc-link:hover {
            background: #eff6ff;
            color: #2563eb;
            padding-left: 1.25rem;
        }
        .toc-link.active-toc {
            background: #eff6ff;
            color: #2563eb;
            border-left-color: #2563eb;
            font-weight: 600;
        }

        .term-block {
            transition: all 0.3s ease;
        }
        .term-block:hover {
            background: #fafbfc;
        }

        /* Sticky TOC sidebar */
        @media (min-width: 1024px) {
            .toc-sidebar {
                position: sticky;
                top: calc(68px + 1.5rem);
                max-height: calc(100vh - 68px - 3rem);
                overflow-y: auto;
            }
            .toc-sidebar::-webkit-scrollbar { width: 3px; }
            .toc-sidebar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
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
                      <a href="acceuil.php" class="nav-link-underline active-link relative text-white no-underline text-sm font-medium tracking-tight">Galerie</a>
                    <a href="promotion.php" class="nav-link-underline relative text-blue-200/85 no-underline text-sm font-medium tracking-tight transition-colors hover:text-white">Promotions</a>
                    <a href="Affiliation" class="nav-link-underline relative text-blue-200/85 no-underline text-sm font-medium tracking-tight transition-colors hover:text-white">Affiliations</a>
                    <a href="hot_deal.php" class="nav-link-underline relative text-blue-200/85 no-underline text-sm font-medium tracking-tight transition-colors hover:text-white">Hot Deals</a>
                     <a href="Contacte.php" class="nav-link-underline relative text-blue-200/85 no-underline text-sm font-medium tracking-tight transition-colors hover:text-white">Contactez-Nous</a>
                  
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
        <div class="absolute -top-1/2 -right-[20%] w-[600px] h-[600px] bg-[radial-gradient(circle,rgba(251,191,36,0.12)_0%,transparent_70%)] rounded-full"></div>
        <div class="absolute -bottom-[40%] -left-[10%] w-[500px] h-[500px] bg-[radial-gradient(circle,rgba(59,130,246,0.15)_0%,transparent_70%)] rounded-full"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-[radial-gradient(circle,rgba(255,255,255,0.03)_0%,transparent_60%)] rounded-full"></div>

        <div class="relative z-[2] text-center py-20 px-6 md:py-28 md:px-8">
            <div class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-white/10 backdrop-blur-sm border border-white/10 text-blue-200 text-xs font-semibold uppercase tracking-widest mb-6">
                <i class="fas fa-heart text-red-400"></i>
                Qui sommes-nous
            </div>
            <h1 class="text-[clamp(2.4rem,6vw,4.2rem)] font-black text-white leading-[1.08] mb-6 max-w-[700px] mx-auto">
                L'avenir du commerce<br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-300 via-amber-400 to-yellow-300">commence ici</span>
            </h1>
            <p class="text-[clamp(0.95rem,2vw,1.15rem)] text-blue-200/85 max-w-[600px] mx-auto leading-relaxed font-normal">
                Découvrez l'histoire, la mission et les valeurs qui animent Le Stock Entreprise — une plateforme née de la conviction que la technologie peut transformer le commerce local en Haïti.
            </p>
            <div class="flex flex-wrap items-center justify-center gap-3 mt-10">
                <a href="#mission" class="inline-flex items-center gap-2 px-8 py-3.5 bg-white text-blue-900 rounded-[14px] font-bold text-sm no-underline transition-all shadow-xl shadow-black/20 hover:-translate-y-0.5 hover:shadow-2xl">
                    <i class="fas fa-arrow-down"></i> Notre histoire
                </a>
                <a href="#termes" class="inline-flex items-center gap-2 px-8 py-3.5 bg-white/10 backdrop-blur-sm text-white border border-white/20 rounded-[14px] font-bold text-sm no-underline transition-all hover:bg-white/20 hover:-translate-y-0.5">
                    <i class="fas fa-file-contract"></i> Termes & Conditions
                </a>
                <a href="https://wa.me/50932732920?text=Bonjour%20LE-STOCK%2C%20je%20souhaite%20en%20savoir%20plus%20sur%20votre%20plateforme." target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-8 py-3.5 bg-gradient-to-br from-emerald-500 to-emerald-600 text-white rounded-[14px] font-bold text-sm no-underline transition-all shadow-lg shadow-emerald-600/30 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-emerald-600/40">
                    <i class="fab fa-whatsapp"></i> Contactez-nous
                </a>
            </div>
        </div>

        <div class="absolute bottom-0 left-0 right-0">
            <svg viewBox="0 0 1440 100" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full">
                <path d="M0 50L48 45C96 40 192 30 288 28C384 26 480 32 576 42C672 52 768 66 864 68C960 70 1056 60 1152 50C1248 40 1344 30 1392 25L1440 20V100H1392C1344 100 1248 100 1152 100C1056 100 960 100 864 100C768 100 672 100 576 100C480 100 384 100 288 100C192 100 96 100 48 100H0V50Z" fill="#f1f5f9"/>
            </svg>
        </div>
    </section>

    <!-- ===== NOTRE MISSION ===== -->
    <section id="mission" class="about-section max-w-site mx-auto px-6 py-16 md:py-24">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            <div class="anim-item animate-fade-in-left">
                <div class="relative">
                    <div class="absolute -inset-4 bg-gradient-to-br from-blue-200 to-blue-100 rounded-[2rem] -rotate-3 opacity-60"></div>
                    <div class="relative bg-gradient-to-br from-blue-900 via-blue-700 to-blue-600 rounded-[1.5rem] overflow-hidden aspect-[4/3] shadow-2xl shadow-blue-600/30">
                        <div class="absolute inset-0 pattern-dots opacity-30"></div>
                        <div class="absolute inset-0 flex flex-col items-center justify-center p-10 text-center">
                            <div class="w-24 h-24 bg-gradient-to-br from-amber-400 to-amber-500 rounded-2xl flex items-center justify-center text-white text-5xl font-black shadow-xl shadow-amber-500/40 mb-6 rotate-3">L</div>
                            <h3 class="text-white font-extrabold text-2xl mb-2">Le Stock</h3>
                            <p class="text-blue-200/80 text-sm font-medium">Entreprise</p>
                            <div class="mt-6 flex items-center gap-2">
                                <div class="w-12 h-[2px] bg-gradient-to-r from-transparent to-amber-400"></div>
                                <span class="text-amber-400 text-xs font-bold uppercase tracking-widest">Depuis 2024</span>
                                <div class="w-12 h-[2px] bg-gradient-to-l from-transparent to-amber-400"></div>
                            </div>
                        </div>
                    </div>
                    <div class="absolute -bottom-5 -right-3 md:-right-6 bg-white rounded-2xl p-4 shadow-xl shadow-slate-300/50 border border-slate-100 flex items-center gap-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-xl flex items-center justify-center text-white text-lg">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <div>
                            <div class="text-slate-900 font-extrabold text-lg leading-none">100%</div>
                            <div class="text-slate-500 text-xs font-medium">Haïtien</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="anim-item animate-fade-in-right" style="animation-delay: 0.15s;">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-blue-50 border border-blue-200 text-blue-600 text-xs font-bold uppercase tracking-widest mb-5">
                    <i class="fas fa-bullseye text-[0.7rem]"></i> Notre Mission
                </div>
                <h2 class="text-[clamp(1.6rem,3.5vw,2.4rem)] font-extrabold text-slate-900 leading-tight mb-6">
                    L'innovation technologique au service de<br>
                    <span class="gradient-text">l'inclusion économique</span>
                </h2>
                <p class="text-[0.95rem] text-slate-600 leading-[1.8] mb-6">
                    Le Stock Entreprise est né d'une conviction profonde : l'innovation technologique doit servir l'inclusion économique. Dans une région où l'accès aux marchés reste un défi majeur pour les petits entrepreneurs, nous avons bâti une passerelle numérique qui connecte les vendeurs locaux à une clientèle élargie, sans barrières ni complexités inutiles.
                </p>
                <p class="text-[0.95rem] text-slate-600 leading-[1.8] mb-8">
                    Nous croyons que chaque entrepreneur, quel que soit son point de départ, mérite accès aux mêmes outils que les grands acteurs du commerce. C'est cette équité que nous construisons chaque jour, ligne de code après ligne de code.
                </p>
                <div class="flex flex-wrap gap-3">
                    <span class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 rounded-xl text-sm font-semibold text-slate-700 border border-slate-200">
                        <i class="fas fa-check-circle text-emerald-500"></i> Inclusion numérique
                    </span>
                    <span class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 rounded-xl text-sm font-semibold text-slate-700 border border-slate-200">
                        <i class="fas fa-check-circle text-emerald-500"></i> Commerce local
                    </span>
                    <span class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 rounded-xl text-sm font-semibold text-slate-700 border border-slate-200">
                        <i class="fas fa-check-circle text-emerald-500"></i> Accessibilité totale
                    </span>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== NOTRE HISTOIRE ===== -->
    <section class="about-section bg-white border-y border-slate-200 py-16 md:py-24">
        <div class="max-w-site mx-auto px-6">
            <div class="text-center max-w-[650px] mx-auto mb-14 anim-item animate-fade-in-up">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-blue-50 border border-blue-200 text-blue-600 text-xs font-bold uppercase tracking-widest mb-5">
                    <i class="fas fa-book-open text-[0.7rem]"></i> Notre Histoire
                </div>
                <h2 class="text-[clamp(1.6rem,3.5vw,2.4rem)] font-extrabold text-slate-900 leading-tight mb-4">
                    D'une idée simple à un<br>
                    <span class="gradient-text">mouvement concret</span>
                </h2>
                <p class="text-[0.95rem] text-slate-500 leading-relaxed">
                    Fondée au cœur du Cap-Haïtien, notre aventure a débuté avec une idée simple mais ambitieuse : transformer le commerce local grâce aux outils numériques.
                </p>
            </div>

            <div class="relative max-w-[900px] mx-auto">
                <div class="absolute left-6 md:left-1/2 md:-translate-x-[0.5px] top-0 bottom-0 w-[3px] timeline-line rounded-full"></div>

                <div class="relative flex items-start gap-6 md:gap-0 mb-12 anim-item animate-fade-in-left">
                    <div class="md:w-1/2 md:pr-12 md:text-right hidden md:block">
                        <div class="bg-gradient-to-br from-blue-50 to-white rounded-2xl p-6 border border-blue-100 shadow-lg shadow-blue-600/5 inline-block text-left">
                            <h3 class="text-lg font-extrabold text-slate-900 mb-2">Le Constat</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">De nombreux talents entrepreneuriaux peinaient à trouver leur public faute d'infrastructure adaptée. Le commerce traditionnel atteignait ses limites géographiques et logistiques.</p>
                        </div>
                    </div>
                    <div class="absolute left-6 md:left-1/2 -translate-x-1/2 w-12 h-12 bg-gradient-to-br from-blue-600 to-blue-500 rounded-xl flex items-center justify-center text-white text-sm z-10 shadow-lg shadow-blue-600/30 border-4 border-white">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="ml-20 md:ml-0 md:w-1/2 md:pl-12">
                        <div class="md:hidden bg-gradient-to-br from-blue-50 to-white rounded-2xl p-6 border border-blue-100 shadow-lg shadow-blue-600/5">
                            <h3 class="text-lg font-extrabold text-slate-900 mb-2">Le Constat</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">De nombreux talents entrepreneuriaux peinaient à trouver leur public faute d'infrastructure adaptée. Le commerce traditionnel atteignait ses limites géographiques et logistiques.</p>
                        </div>
                        <span class="hidden md:block text-xs font-bold text-blue-600 uppercase tracking-widest mt-2">Étape 1</span>
                    </div>
                </div>

                <div class="relative flex items-start gap-6 md:gap-0 mb-12 anim-item animate-fade-in-right" style="animation-delay: 0.15s;">
                    <div class="md:w-1/2 md:pr-12 hidden md:block">
                        <span class="text-xs font-bold text-blue-600 uppercase tracking-widest mt-2 inline-block">Étape 2</span>
                    </div>
                    <div class="absolute left-6 md:left-1/2 -translate-x-1/2 w-12 h-12 bg-gradient-to-br from-amber-500 to-amber-400 rounded-xl flex items-center justify-center text-white text-sm z-10 shadow-lg shadow-amber-500/30 border-4 border-white">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <div class="ml-20 md:ml-0 md:w-1/2 md:pl-12">
                        <div class="bg-gradient-to-br from-amber-50 to-white rounded-2xl p-6 border border-amber-100 shadow-lg shadow-amber-600/5">
                            <h3 class="text-lg font-extrabold text-slate-900 mb-2">L'Idée</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">Développer une solution sur mesure, pensée par et pour la communauté haïtienne. Une plateforme qui comprend les réalités du terrain et s'y adapte, pas l'inverse.</p>
                        </div>
                    </div>
                </div>

                <div class="relative flex items-start gap-6 md:gap-0 mb-12 anim-item animate-fade-in-left" style="animation-delay: 0.3s;">
                    <div class="md:w-1/2 md:pr-12 md:text-right hidden md:block">
                        <div class="bg-gradient-to-br from-emerald-50 to-white rounded-2xl p-6 border border-emerald-100 shadow-lg shadow-emerald-600/5 inline-block text-left">
                            <h3 class="text-lg font-extrabold text-slate-900 mb-2">La Construction</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">Mois de développement, de tests sur le terrain, d'échanges avec les vendeurs du Cap-Haïtien. Chaque fonctionnalité a été validée par ceux qui l'utilisent au quotidien.</p>
                        </div>
                    </div>
                    <div class="absolute left-6 md:left-1/2 -translate-x-1/2 w-12 h-12 bg-gradient-to-br from-emerald-500 to-emerald-400 rounded-xl flex items-center justify-center text-white text-sm z-10 shadow-lg shadow-emerald-500/30 border-4 border-white">
                        <i class="fas fa-hammer"></i>
                    </div>
                    <div class="ml-20 md:ml-0 md:w-1/2 md:pl-12">
                        <div class="md:hidden bg-gradient-to-br from-emerald-50 to-white rounded-2xl p-6 border border-emerald-100 shadow-lg shadow-emerald-600/5">
                            <h3 class="text-lg font-extrabold text-slate-900 mb-2">La Construction</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">Mois de développement, de tests sur le terrain, d'échanges avec les vendeurs du Cap-Haïtien. Chaque fonctionnalité a été validée par ceux qui l'utilisent au quotidien.</p>
                        </div>
                        <span class="hidden md:block text-xs font-bold text-blue-600 uppercase tracking-widest mt-2">Étape 3</span>
                    </div>
                </div>

                <div class="relative flex items-start gap-6 md:gap-0 anim-item animate-fade-in-right" style="animation-delay: 0.45s;">
                    <div class="md:w-1/2 md:pr-12 hidden md:block">
                        <span class="text-xs font-bold text-blue-600 uppercase tracking-widest mt-2 inline-block">Étape 4</span>
                    </div>
                    <div class="absolute left-6 md:left-1/2 -translate-x-1/2 w-12 h-12 bg-gradient-to-br from-violet-500 to-violet-400 rounded-xl flex items-center justify-center text-white text-sm z-10 shadow-lg shadow-violet-500/30 border-4 border-white">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <div class="ml-20 md:ml-0 md:w-1/2 md:pl-12">
                        <div class="bg-gradient-to-br from-violet-50 to-white rounded-2xl p-6 border border-violet-100 shadow-lg shadow-violet-600/5">
                            <h3 class="text-lg font-extrabold text-slate-900 mb-2">Le Lancement</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">Le Stock Entreprise voit le jour — une plateforme e-commerce inclusive, avec des solutions logistiques adaptées, un support en créole et en français, et des modalités de paiement conformes aux habitudes locales.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== NOS VALEURS ===== -->
    <section class="about-section max-w-site mx-auto px-6 py-16 md:py-24">
        <div class="text-center max-w-[650px] mx-auto mb-14 anim-item animate-fade-in-up">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-blue-50 border border-blue-200 text-blue-600 text-xs font-bold uppercase tracking-widest mb-5">
                <i class="fas fa-gem text-[0.7rem]"></i> Nos Valeurs
            </div>
            <h2 class="text-[clamp(1.6rem,3.5vw,2.4rem)] font-extrabold text-slate-900 leading-tight mb-4">
                Valeurs<br>
                <span class="gradient-text">Fondamentales</span>
            </h2>
            <p class="text-[0.95rem] text-slate-500 leading-relaxed">
                Quatre piliers qui guident chacune de nos décisions et nourrissent notre engagement quotidien envers la communauté.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="value-card anim-item animate-fade-in-up bg-white rounded-2xl p-7 border border-slate-200 shadow-lg shadow-slate-200/40 hover:shadow-xl hover:shadow-blue-600/10 hover:border-blue-200 relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-bl from-blue-50 to-transparent rounded-bl-full opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center text-white text-xl mb-5 shadow-lg shadow-blue-500/30"><i class="fas fa-universal-access"></i></div>
                    <h3 class="text-lg font-extrabold text-slate-900 mb-2">Accessibilité</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">La technologie au service de tous, sans distinction de taille d'entreprise ou de capital initial.</p>
                </div>
            </div>
            <div class="value-card anim-item animate-fade-in-up bg-white rounded-2xl p-7 border border-slate-200 shadow-lg shadow-slate-200/40 hover:shadow-xl hover:shadow-emerald-600/10 hover:border-emerald-200 relative overflow-hidden group" style="animation-delay: 0.1s;">
                <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-bl from-emerald-50 to-transparent rounded-bl-full opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative">
                    <div class="w-14 h-14 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl flex items-center justify-center text-white text-xl mb-5 shadow-lg shadow-emerald-500/30"><i class="fas fa-handshake"></i></div>
                    <h3 class="text-lg font-extrabold text-slate-900 mb-2">Transparence</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">Des relations commerciales claires, basées sur la confiance mutuelle et l'honnêteté.</p>
                </div>
            </div>
            <div class="value-card anim-item animate-fade-in-up bg-white rounded-2xl p-7 border border-slate-200 shadow-lg shadow-slate-200/40 hover:shadow-xl hover:shadow-amber-600/10 hover:border-amber-200 relative overflow-hidden group" style="animation-delay: 0.2s;">
                <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-bl from-amber-50 to-transparent rounded-bl-full opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative">
                    <div class="w-14 h-14 bg-gradient-to-br from-amber-500 to-amber-600 rounded-2xl flex items-center justify-center text-white text-xl mb-5 shadow-lg shadow-amber-500/30"><i class="fas fa-seedling"></i></div>
                    <h3 class="text-lg font-extrabold text-slate-900 mb-2">Empowerment</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">Renforcer l'autonomie des entrepreneurs par la formation et l'accompagnement continu.</p>
                </div>
            </div>
            <div class="value-card anim-item animate-fade-in-up bg-white rounded-2xl p-7 border border-slate-200 shadow-lg shadow-slate-200/40 hover:shadow-xl hover:shadow-violet-600/10 hover:border-violet-200 relative overflow-hidden group" style="animation-delay: 0.3s;">
                <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-bl from-violet-50 to-transparent rounded-bl-full opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative">
                    <div class="w-14 h-14 bg-gradient-to-br from-violet-500 to-violet-600 rounded-2xl flex items-center justify-center text-white text-xl mb-5 shadow-lg shadow-violet-500/30"><i class="fas fa-map-pin"></i></div>
                    <h3 class="text-lg font-extrabold text-slate-900 mb-2">Impact local</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">Prioriser les acteurs économiques du territoire pour créer une croissance partagée.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== CE QUI NOUS DISTINGUE ===== -->
    <section class="about-section bg-gradient-to-br from-slate-900 via-blue-900 to-blue-700 py-16 md:py-24 relative overflow-hidden">
        <div class="absolute inset-0 pattern-dots opacity-20"></div>
        <div class="absolute -top-[30%] -right-[15%] w-[500px] h-[500px] bg-[radial-gradient(circle,rgba(59,130,246,0.2)_0%,transparent_70%)] rounded-full"></div>
        <div class="absolute -bottom-[30%] -left-[10%] w-[400px] h-[400px] bg-[radial-gradient(circle,rgba(251,191,36,0.1)_0%,transparent_70%)] rounded-full"></div>

        <div class="relative z-[2] max-w-site mx-auto px-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <div class="anim-item animate-fade-in-left">
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/10 backdrop-blur-sm border border-white/10 text-blue-200 text-xs font-bold uppercase tracking-widest mb-5">
                        <i class="fas fa-star text-amber-400 text-[0.7rem]"></i> Ce Qui Nous Distingue
                    </div>
                    <h2 class="text-[clamp(1.6rem,3.5vw,2.4rem)] font-extrabold text-white leading-tight mb-6">
                        Pas une plateforme de plus,<br>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-300 to-amber-400">la bonne plateforme</span>
                    </h2>
                    <p class="text-[0.95rem] text-blue-200/80 leading-[1.8] mb-8">
                        Contrairement aux plateformes généralistes, Le Stock Entreprise comprend les réalités spécifiques du contexte haïtien. Nous intégrons des solutions logistiques adaptées, un support en créole et en français, et des modalités de paiement conformes aux habitudes locales.
                    </p>

                    <div class="space-y-4">
                        <div class="flex items-start gap-4 p-4 rounded-xl bg-white/[0.06] border border-white/[0.08] backdrop-blur-sm">
                            <div class="w-10 h-10 bg-gradient-to-br from-amber-400 to-amber-500 rounded-lg flex items-center justify-center text-white text-sm shrink-0 mt-0.5 shadow-lg shadow-amber-500/30"><i class="fas fa-language"></i></div>
                            <div>
                                <h4 class="text-white font-bold text-sm mb-1">Support bilingue</h4>
                                <p class="text-blue-200/70 text-sm leading-relaxed">Créole et français — parce que la langue ne doit jamais être une barrière.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 p-4 rounded-xl bg-white/[0.06] border border-white/[0.08] backdrop-blur-sm">
                            <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-emerald-500 rounded-lg flex items-center justify-center text-white text-sm shrink-0 mt-0.5 shadow-lg shadow-emerald-500/30"><i class="fas fa-truck"></i></div>
                            <div>
                                <h4 class="text-white font-bold text-sm mb-1">Logistique adaptée</h4>
                                <p class="text-blue-200/70 text-sm leading-relaxed">Des solutions de livraison pensées pour les routes et réalités haïtiennes.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 p-4 rounded-xl bg-white/[0.06] border border-white/[0.08] backdrop-blur-sm">
                            <div class="w-10 h-10 bg-gradient-to-br from-violet-400 to-violet-500 rounded-lg flex items-center justify-center text-white text-sm shrink-0 mt-0.5 shadow-lg shadow-violet-500/30"><i class="fas fa-wallet"></i></div>
                            <div>
                                <h4 class="text-white font-bold text-sm mb-1">Paiement local</h4>
                                <p class="text-blue-200/70 text-sm leading-relaxed">MonCash, Natcom, espèces — payez comme vous le faites partout ailleurs.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 p-4 rounded-xl bg-white/[0.06] border border-white/[0.08] backdrop-blur-sm">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-500 rounded-lg flex items-center justify-center text-white text-sm shrink-0 mt-0.5 shadow-lg shadow-blue-500/30"><i class="fas fa-store"></i></div>
                            <div>
                                <h4 class="text-white font-bold text-sm mb-1">Approche hybride</h4>
                                <p class="text-blue-200/70 text-sm leading-relaxed">Présence digitale ET ancrage territorial — le meilleur des deux mondes.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="anim-item animate-scale-in" style="animation-delay: 0.2s;">
                    <div class="relative">
                        <div class="bg-gradient-to-br from-white/[0.08] to-white/[0.02] rounded-[2rem] p-8 border border-white/[0.08] backdrop-blur-sm">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-white/[0.06] rounded-2xl p-6 text-center border border-white/[0.06]">
                                    <div class="text-3xl font-black text-white mb-1">🇭🇹</div>
                                    <div class="text-xs text-blue-200/70 font-medium">Fait en Haïti</div>
                                </div>
                                <div class="bg-white/[0.06] rounded-2xl p-6 text-center border border-white/[0.06]">
                                    <div class="text-3xl font-black text-white mb-1">💬</div>
                                    <div class="text-xs text-blue-200/70 font-medium">Créole & Français</div>
                                </div>
                                <div class="bg-white/[0.06] rounded-2xl p-6 text-center border border-white/[0.06]">
                                    <div class="text-3xl font-black text-white mb-1">💵</div>
                                    <div class="text-xs text-blue-200/70 font-medium">Paiement Local</div>
                                </div>
                                <div class="bg-white/[0.06] rounded-2xl p-6 text-center border border-white/[0.06]">
                                    <div class="text-3xl font-black text-white mb-1">🚚</div>
                                    <div class="text-xs text-blue-200/70 font-medium">Livraison Adaptée</div>
                                </div>
                            </div>
                            <div class="mt-6 bg-gradient-to-r from-amber-500/20 to-amber-400/10 rounded-xl p-5 border border-amber-400/20 text-center">
                                <p class="text-amber-200 font-bold text-sm leading-relaxed">
                                    <i class="fas fa-quote-left text-amber-400/60 mr-1"></i>
                                    Notre approche hybride combine présence digitale et ancrage territorial pour un impact réel.
                                    <i class="fas fa-quote-right text-amber-400/60 ml-1"></i>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== NOTRE IMPACT ===== -->
    <section id="impact" class="about-section max-w-site mx-auto px-6 py-16 md:py-24">
        <div class="text-center max-w-[650px] mx-auto mb-14 anim-item animate-fade-in-up">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-blue-50 border border-blue-200 text-blue-600 text-xs font-bold uppercase tracking-widest mb-5">
                <i class="fas fa-chart-line text-[0.7rem]"></i> Notre Impact
            </div>
            <h2 class="text-[clamp(1.6rem,3.5vw,2.4rem)] font-extrabold text-slate-900 leading-tight mb-4">
                Des chiffres qui<br>
                <span class="gradient-text">parlent d'eux-mêmes</span>
            </h2>
            <p class="text-[0.95rem] text-slate-500 leading-relaxed">
                Aujourd'hui, nous sommes fiers des résultats concrets que notre plateforme permet d'atteindre chaque jour.
            </p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 md:gap-6">
            <div class="stat-card anim-item animate-scale-in bg-white rounded-2xl p-6 md:p-8 border border-slate-200 shadow-lg shadow-slate-200/40 text-center hover:shadow-xl hover:shadow-blue-600/10 hover:border-blue-200">
                <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center text-white text-xl mx-auto mb-4 shadow-lg shadow-blue-500/30"><i class="fas fa-users"></i></div>
                <div class="text-3xl md:text-4xl font-black text-slate-900 mb-1 counter" data-target="150">0</div>
                <div class="text-sm text-slate-500 font-medium">Entrepreneurs accompagnés</div>
                <div class="text-xs text-blue-600 font-bold mt-1">dans leur digitalisation</div>
            </div>
            <div class="stat-card anim-item animate-scale-in bg-white rounded-2xl p-6 md:p-8 border border-slate-200 shadow-lg shadow-slate-200/40 text-center hover:shadow-xl hover:shadow-emerald-600/10 hover:border-emerald-200" style="animation-delay: 0.1s;">
                <div class="w-14 h-14 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl flex items-center justify-center text-white text-xl mx-auto mb-4 shadow-lg shadow-emerald-500/30"><i class="fas fa-exchange-alt"></i></div>
                <div class="text-3xl md:text-4xl font-black text-slate-900 mb-1 counter" data-target="2500">0</div>
                <div class="text-sm text-slate-500 font-medium">Transactions sécurisées</div>
                <div class="text-xs text-emerald-600 font-bold mt-1">mensuellement</div>
            </div>
            <div class="stat-card anim-item animate-scale-in bg-white rounded-2xl p-6 md:p-8 border border-slate-200 shadow-lg shadow-slate-200/40 text-center hover:shadow-xl hover:shadow-amber-600/10 hover:border-amber-200" style="animation-delay: 0.2s;">
                <div class="w-14 h-14 bg-gradient-to-br from-amber-500 to-amber-600 rounded-2xl flex items-center justify-center text-white text-xl mx-auto mb-4 shadow-lg shadow-amber-500/30"><i class="fas fa-map-marked-alt"></i></div>
                <div class="text-3xl md:text-4xl font-black text-slate-900 mb-1 counter" data-target="15">0</div>
                <div class="text-sm text-slate-500 font-medium">Communes couvertes</div>
                <div class="text-xs text-amber-600 font-bold mt-1">au Nord d'Haïti</div>
            </div>
            <div class="stat-card anim-item animate-scale-in bg-white rounded-2xl p-6 md:p-8 border border-slate-200 shadow-lg shadow-slate-200/40 text-center hover:shadow-xl hover:shadow-violet-600/10 hover:border-violet-200" style="animation-delay: 0.3s;">
                <div class="w-14 h-14 bg-gradient-to-br from-violet-500 to-violet-600 rounded-2xl flex items-center justify-center text-white text-xl mx-auto mb-4 shadow-lg shadow-violet-500/30"><i class="fas fa-briefcase"></i></div>
                <div class="text-3xl md:text-4xl font-black text-slate-900 mb-1 counter" data-target="45">0</div>
                <div class="text-sm text-slate-500 font-medium">Emplois créés</div>
                <div class="text-xs text-violet-600 font-bold mt-1">directs et indirects</div>
            </div>
        </div>

        <div class="mt-12 anim-item animate-fade-in-up" style="animation-delay: 0.4s;">
            <div class="bg-gradient-to-br from-blue-50 to-white rounded-2xl p-8 md:p-10 border border-blue-100 shadow-lg shadow-blue-600/5">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 shrink-0"><i class="fas fa-graduation-cap"></i></div>
                        <div>
                            <h4 class="font-bold text-slate-900 text-sm mb-1">Formation continue</h4>
                            <p class="text-xs text-slate-500 leading-relaxed">Des ateliers réguliers pour aider les vendeurs à maîtriser les outils digitaux et optimiser leurs ventes en ligne.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center text-emerald-600 shrink-0"><i class="fas fa-hand-holding-heart"></i></div>
                        <div>
                            <h4 class="font-bold text-slate-900 text-sm mb-1">Accompagnement personnalisé</h4>
                            <p class="text-xs text-slate-500 leading-relaxed">Chaque entrepreneur bénéficie d'un suivi individualisé pour déployer sa boutique et atteindre ses objectifs.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600 shrink-0"><i class="fas fa-network-wired"></i></div>
                        <div>
                            <h4 class="font-bold text-slate-900 text-sm mb-1">Réseau solidaire</h4>
                            <p class="text-xs text-slate-500 leading-relaxed">Une communauté d'entrepreneurs qui s'entraident, partagent leurs expériences et co-construisent l'écosystème.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== NOTRE VISION ===== -->
    <section class="about-section bg-white border-y border-slate-200 py-16 md:py-24">
        <div class="max-w-site mx-auto px-6">
            <div class="max-w-[800px] mx-auto text-center anim-item animate-fade-in-up">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-blue-50 border border-blue-200 text-blue-600 text-xs font-bold uppercase tracking-widest mb-5">
                    <i class="fas fa-eye text-[0.7rem]"></i> Notre Vision
                </div>
                <h2 class="text-[clamp(1.6rem,3.5vw,2.4rem)] font-extrabold text-slate-900 leading-tight mb-8">
                    Le Cap-Haïtien, pôle d'excellence<br>
                    <span class="gradient-text">technologique de la Caraïbe</span>
                </h2>
                <p class="text-[1.05rem] text-slate-600 leading-[1.9] mb-8">
                    Nous aspirons à devenir le référent du e-commerce inclusif en Haïti, prouver que l'innovation et la solidarité économique peuvent avancer de concert, et démontrer que le Cap-Haïtien peut être un pôle d'excellence technologique pour toute la Caraïbe.
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-10">
                    <div class="bg-slate-50 rounded-2xl p-6 border border-slate-200 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-600/5 transition-all">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl flex items-center justify-center text-blue-600 text-lg mx-auto mb-4"><i class="fas fa-globe-americas"></i></div>
                        <h4 class="font-bold text-slate-900 text-sm mb-2">Référent national</h4>
                        <p class="text-xs text-slate-500 leading-relaxed">Le e-commerce inclusif haïtien, reconnu au-delà des frontières.</p>
                    </div>
                    <div class="bg-slate-50 rounded-2xl p-6 border border-slate-200 hover:border-emerald-200 hover:shadow-lg hover:shadow-emerald-600/5 transition-all">
                        <div class="w-12 h-12 bg-gradient-to-br from-emerald-100 to-emerald-50 rounded-xl flex items-center justify-center text-emerald-600 text-lg mx-auto mb-4"><i class="fas fa-link"></i></div>
                        <h4 class="font-bold text-slate-900 text-sm mb-2">Innovation + Solidarité</h4>
                        <p class="text-xs text-slate-500 leading-relaxed">Prouver que progrès technologique et équité sociale vont de pair.</p>
                    </div>
                    <div class="bg-slate-50 rounded-2xl p-6 border border-slate-200 hover:border-amber-200 hover:shadow-lg hover:shadow-amber-600/5 transition-all">
                        <div class="w-12 h-12 bg-gradient-to-br from-amber-100 to-amber-50 rounded-xl flex items-center justify-center text-amber-600 text-lg mx-auto mb-4"><i class="fas fa-crown"></i></div>
                        <h4 class="font-bold text-slate-900 text-sm mb-2">Excellence caraïbe</h4>
                        <p class="text-xs text-slate-500 leading-relaxed">Le Cap-Haïtien comme modèle de transformation digitale régionale.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== NOTRE ÉQUIPE ===== -->
    <section class="about-section max-w-site mx-auto px-6 py-16 md:py-24">
        <div class="text-center max-w-[650px] mx-auto mb-14 anim-item animate-fade-in-up">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-blue-50 border border-blue-200 text-blue-600 text-xs font-bold uppercase tracking-widest mb-5">
                <i class="fas fa-users-cog text-[0.7rem]"></i> Notre Équipe
            </div>
            <h2 class="text-[clamp(1.6rem,3.5vw,2.4rem)] font-extrabold text-slate-900 leading-tight mb-4">
                Les personnes derrière<br>
                <span class="gradient-text">la plateforme</span>
            </h2>
            <p class="text-[0.95rem] text-slate-500 leading-relaxed">
                Une équipe passionnée, locale et engagée, qui croit au potentiel immense du commerce haïtien.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 max-w-[900px] mx-auto">
            <div class="anim-item animate-fade-in-up bg-white rounded-2xl p-7 border border-slate-200 shadow-lg shadow-slate-200/40 text-center hover:shadow-xl hover:shadow-blue-600/10 hover:border-blue-200 transition-all hover:-translate-y-1">
                <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center text-white text-3xl mx-auto mb-4 shadow-lg shadow-blue-500/30"><i class="fas fa-user-tie"></i></div>
                <h3 class="text-lg font-extrabold text-slate-900 mb-1">Fondateur & CEO</h3>
                <p class="text-sm text-blue-600 font-semibold mb-3">Direction Générale</p>
                <p class="text-xs text-slate-500 leading-relaxed">Visionnaire du projet, il porte la conviction qu'Haïti peut se transformer par le numérique.</p>
            </div>
            <div class="anim-item animate-fade-in-up bg-white rounded-2xl p-7 border border-slate-200 shadow-lg shadow-slate-200/40 text-center hover:shadow-xl hover:shadow-emerald-600/10 hover:border-emerald-200 transition-all hover:-translate-y-1" style="animation-delay: 0.1s;">
                <div class="w-20 h-20 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl flex items-center justify-center text-white text-3xl mx-auto mb-4 shadow-lg shadow-emerald-500/30"><i class="fas fa-code"></i></div>
                <h3 class="text-lg font-extrabold text-slate-900 mb-1">Équipe Technique</h3>
                <p class="text-sm text-emerald-600 font-semibold mb-3">Développement & Design</p>
                <p class="text-xs text-slate-500 leading-relaxed">Développeurs et designers haïtiens qui construisent une plateforme robuste et intuitive.</p>
            </div>
            <div class="anim-item animate-fade-in-up bg-white rounded-2xl p-7 border border-slate-200 shadow-lg shadow-slate-200/40 text-center hover:shadow-xl hover:shadow-amber-600/10 hover:border-amber-200 transition-all hover:-translate-y-1" style="animation-delay: 0.2s;">
                <div class="w-20 h-20 bg-gradient-to-br from-amber-500 to-amber-600 rounded-2xl flex items-center justify-center text-white text-3xl mx-auto mb-4 shadow-lg shadow-amber-500/30"><i class="fas fa-headset"></i></div>
                <h3 class="text-lg font-extrabold text-slate-900 mb-1">Support Client</h3>
                <p class="text-sm text-amber-600 font-semibold mb-3">Accompagnement & Formation</p>
                <p class="text-xs text-slate-500 leading-relaxed">Toujours disponibles, en créole et en français, pour guider chaque utilisateur.</p>
            </div>
        </div>
    </section>

    <!-- ===== REJOIGNEZ L'AVENTURE ===== -->
    <section class="about-section bg-gradient-to-br from-slate-900 via-blue-900 to-blue-700 py-16 md:py-24 relative overflow-hidden">
        <div class="absolute inset-0 pattern-dots opacity-15"></div>
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[400px] bg-[radial-gradient(ellipse,rgba(251,191,36,0.12)_0%,transparent_70%)]"></div>

        <div class="relative z-[2] max-w-[750px] mx-auto px-6 text-center anim-item animate-scale-in">
            <div class="w-20 h-20 bg-gradient-to-br from-amber-400 to-amber-500 rounded-2xl flex items-center justify-center text-white text-4xl font-black mx-auto mb-8 shadow-xl shadow-amber-500/40 rotate-3">L</div>
            <h2 class="text-[clamp(1.8rem,4vw,2.8rem)] font-black text-white leading-tight mb-6">Rejoignez l'Aventure</h2>
            <p class="text-[1.05rem] text-blue-200/80 leading-[1.8] mb-10 max-w-[600px] mx-auto">
                Que vous soyez vendeur, acheteur ou partenaire, vous avez votre place dans cette transformation. Ensemble, construisons un écosystème commercial plus équitable, plus dynamique et plus connecté.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="https://wa.me/50932732920?text=Bonjour%20LE-STOCK%2C%20je%20souhaite%20rejoindre%20votre%20aventure%20en%20tant%20que%20vendeur." target="_blank" rel="noopener noreferrer" class="whatsapp-btn inline-flex items-center gap-3 px-10 py-4 text-white rounded-[16px] font-bold text-[0.95rem] no-underline shadow-xl shadow-emerald-600/30 w-full sm:w-auto justify-center">
                    <i class="fab fa-whatsapp text-xl"></i> Devenir Vendeur
                </a>
                <a href="inscription.php" class="inline-flex items-center gap-2 px-10 py-4 bg-white text-blue-900 rounded-[16px] font-bold text-[0.95rem] no-underline transition-all shadow-xl shadow-black/20 hover:-translate-y-0.5 hover:shadow-2xl w-full sm:w-auto justify-center">
                    <i class="fas fa-user-plus"></i> Créer un Compte
                </a>
            </div>
            <div class="mt-10 flex items-center justify-center gap-6 text-blue-200/60 text-sm">
                <span class="flex items-center gap-2"><i class="fas fa-check text-emerald-400"></i> Gratuit</span>
                <span class="flex items-center gap-2"><i class="fas fa-check text-emerald-400"></i> Sans engagement</span>
                <span class="flex items-center gap-2"><i class="fas fa-check text-emerald-400"></i> Accompagné</span>
            </div>
        </div>
    </section>

    <!-- ===== SIGNATURE ===== -->
    <section class="max-w-site mx-auto px-6 py-12">
        <div class="text-center anim-item animate-fade-in-up">
            <div class="inline-flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-400 rounded-xl flex items-center justify-center text-white font-black text-lg shadow-lg shadow-blue-500/30">L</div>
                <span class="text-slate-900 font-extrabold text-xl tracking-tight">Le Stock Entreprise</span>
            </div>
            <p class="text-slate-500 text-sm font-medium">L'avenir du commerce commence ici.</p>
            <div class="w-16 h-[3px] bg-gradient-to-r from-blue-500 to-blue-300 rounded-full mx-auto mt-4"></div>
        </div>
    </section>


    <!-- ╔══════════════════════════════════════════════════════════════╗ -->
    <!-- ║              TERMES ET CONDITIONS                            ║ -->
    <!-- ╚══════════════════════════════════════════════════════════════╝ -->
    <section id="termes" class="about-section bg-white border-t-2 border-slate-200 py-16 md:py-24">
        <div class="max-w-site mx-auto px-6">

            <!-- Header -->
            <div class="text-center max-w-[700px] mx-auto mb-12 anim-item animate-fade-in-up">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-slate-100 border border-slate-300 text-slate-600 text-xs font-bold uppercase tracking-widest mb-5">
                    <i class="fas fa-file-contract text-[0.7rem]"></i> Document Légal
                </div>
                <h2 class="text-[clamp(1.8rem,4vw,2.8rem)] font-black text-slate-900 leading-tight mb-4">
                    Termes et Conditions
                </h2>
                <p class="text-[0.95rem] text-slate-500 leading-relaxed">
                    Dernière mise à jour : <strong class="text-slate-700">1er janvier 2025</strong><br>
                    Veuillez lire attentivement ces termes avant d'utiliser la plateforme Le Stock Entreprise.
                </p>
            </div>

            <!-- Layout: Sidebar TOC + Content -->
            <div class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-8 lg:gap-12 anim-item animate-fade-in-up" style="animation-delay: 0.1s;">

                <!-- Sidebar TOC (desktop only) -->
                <aside class="toc-sidebar hidden lg:block">
                    <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 px-3">
                            <i class="fas fa-list mr-1.5"></i> Sommaire
                        </h4>
                        <nav class="flex flex-col gap-0.5" id="toc-nav">
                            <a href="#art-1" class="toc-link block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg border-l-[3px] border-transparent">1. Définitions</a>
                            <a href="#art-2" class="toc-link block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg border-l-[3px] border-transparent">2. Acceptation des termes</a>
                            <a href="#art-3" class="toc-link block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg border-l-[3px] border-transparent">3. Inscription & Compte</a>
                            <a href="#art-4" class="toc-link block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg border-l-[3px] border-transparent">4. Produits & Prix</a>
                            <a href="#art-5" class="toc-link block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg border-l-[3px] border-transparent">5. Commandes</a>
                            <a href="#art-6" class="toc-link block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg border-l-[3px] border-transparent">6. Paiement</a>
                            <a href="#art-7" class="toc-link block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg border-l-[3px] border-transparent">7. Livraison</a>
                            <a href="#art-8" class="toc-link block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg border-l-[3px] border-transparent">8. Retours & Remboursements</a>
                            <a href="#art-9" class="toc-link block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg border-l-[3px] border-transparent">9. Hot Deals</a>
                            <a href="#art-10" class="toc-link block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg border-l-[3px] border-transparent">10. Propriété intellectuelle</a>
                            <a href="#art-11" class="toc-link block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg border-l-[3px] border-transparent">11. Responsabilité</a>
                            <a href="#art-12" class="toc-link block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg border-l-[3px] border-transparent">12. Protection des données</a>
                            <a href="#art-13" class="toc-link block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg border-l-[3px] border-transparent">13. Comportement utilisateur</a>
                            <a href="#art-14" class="toc-link block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg border-l-[3px] border-transparent">14. Résiliation</a>
                            <a href="#art-15" class="toc-link block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg border-l-[3px] border-transparent">15. Modifications</a>
                            <a href="#art-16" class="toc-link block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg border-l-[3px] border-transparent">16. Loi applicable</a>
                            <a href="#art-17" class="toc-link block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg border-l-[3px] border-transparent">17. Contact</a>
                        </nav>
                    </div>
                </aside>

                <!-- Content -->
                <div class="min-w-0">

                    <!-- Intro Block -->
                    <div class="bg-gradient-to-br from-slate-900 to-blue-900 rounded-2xl p-7 md:p-9 mb-10 text-center">
                        <div class="w-14 h-14 bg-white/10 rounded-2xl flex items-center justify-center text-white text-2xl mx-auto mb-4">
                            <i class="fas fa-scale-balanced"></i>
                        </div>
                        <p class="text-blue-200/90 text-[0.95rem] leading-relaxed max-w-[550px] mx-auto">
                            En utilisant la plateforme <strong class="text-white">Le Stock Entreprise</strong>, vous reconnaissez avoir lu, compris et accepté d'être lié par les présents Termes et Conditions. Si vous n'acceptez pas ces termes, veuillez ne pas utiliser nos services.
                        </p>
                    </div>

                    <!-- Mobile TOC (visible only on mobile) -->
                    <div class="lg:hidden mb-8">
                        <details class="bg-slate-50 rounded-2xl border border-slate-200 overflow-hidden">
                            <summary class="px-5 py-4 text-sm font-bold text-slate-700 cursor-pointer flex items-center gap-2 hover:bg-slate-100 transition-colors">
                                <i class="fas fa-list text-blue-600"></i> Sommaire des articles
                                <i class="fas fa-chevron-down text-xs text-slate-400 ml-auto"></i>
                            </summary>
                            <div class="px-5 pb-4 flex flex-col gap-1">
                                <a href="#art-1" class="block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors">1. Définitions</a>
                                <a href="#art-2" class="block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors">2. Acceptation des termes</a>
                                <a href="#art-3" class="block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors">3. Inscription & Compte</a>
                                <a href="#art-4" class="block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors">4. Produits & Prix</a>
                                <a href="#art-5" class="block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors">5. Commandes</a>
                                <a href="#art-6" class="block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors">6. Paiement</a>
                                <a href="#art-7" class="block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors">7. Livraison</a>
                                <a href="#art-8" class="block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors">8. Retours & Remboursements</a>
                                <a href="#art-9" class="block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors">9. Hot Deals</a>
                                <a href="#art-10" class="block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors">10. Propriété intellectuelle</a>
                                <a href="#art-11" class="block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors">11. Responsabilité</a>
                                <a href="#art-12" class="block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors">12. Protection des données</a>
                                <a href="#art-13" class="block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors">13. Comportement utilisateur</a>
                                <a href="#art-14" class="block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors">14. Résiliation</a>
                                <a href="#art-15" class="block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors">15. Modifications</a>
                                <a href="#art-16" class="block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors">16. Loi applicable</a>
                                <a href="#art-17" class="block px-3 py-2 text-sm text-slate-600 no-underline rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors">17. Contact</a>
                            </div>
                        </details>
                    </div>

                    <!-- Articles -->
                    <div class="space-y-6">

                        <!-- Art 1 -->
                        <div id="art-1" class="term-block bg-slate-50 rounded-2xl p-6 md:p-8 border border-slate-200 scroll-mt-[90px]">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 text-sm font-black shrink-0">1</div>
                                <h3 class="text-lg font-extrabold text-slate-900">Définitions</h3>
                            </div>
                            <div class="text-sm text-slate-600 leading-[1.85] space-y-3">
                                <p>Dans les présents Termes et Conditions, les termes suivants auront les significations indiquées ci-après :</p>
                                <ul class="list-none space-y-2.5 pl-1">
                                    <li class="flex items-start gap-2.5"><span class="w-1.5 h-1.5 rounded-full bg-blue-500 mt-2 shrink-0"></span><strong class="text-slate-800">« Plateforme »</strong> : désigne le site web et l'application Le Stock Entreprise, accessible via leStock.com ou tout autre domaine affilié.</li>
                                    <li class="flex items-start gap-2.5"><span class="w-1.5 h-1.5 rounded-full bg-blue-500 mt-2 shrink-0"></span><strong class="text-slate-800">« Utilisateur »</strong> : toute personne physique ou morale qui accède à la Plateforme, qu'elle soit acheteuse ou vendeuse.</li>
                                    <li class="flex items-start gap-2.5"><span class="w-1.5 h-1.5 rounded-full bg-blue-500 mt-2 shrink-0"></span><strong class="text-slate-800">« Vendeur »</strong> : tout Utilisateur inscrit qui propose des produits ou services à la vente sur la Plateforme.</li>
                                    <li class="flex items-start gap-2.5"><span class="w-1.5 h-1.5 rounded-full bg-blue-500 mt-2 shrink-0"></span><strong class="text-slate-800">« Acheteur »</strong> : tout Utilisateur qui effectue un achat de produits ou services via la Plateforme.</li>
                                    <li class="flex items-start gap-2.5"><span class="w-1.5 h-1.5 rounded-full bg-blue-500 mt-2 shrink-0"></span><strong class="text-slate-800">« Hot Deal »</strong> : offre promotionnelle à durée limitée publiée sur la Plateforme avec des conditions spécifiques de prix et de disponibilité.</li>
                                    <li class="flex items-start gap-2.5"><span class="w-1.5 h-1.5 rounded-full bg-blue-500 mt-2 shrink-0"></span><strong class="text-slate-800">« HTG »</strong> : la Gourde haïtienne, monnaie officielle de la République d'Haïti.</li>
                                    <li class="flex items-start gap-2.5"><span class="w-1.5 h-1.5 rounded-full bg-blue-500 mt-2 shrink-0"></span><strong class="text-slate-800">« Nous / Notre »</strong> : fait référence à Le Stock Entreprise, l'exploitant de la Plateforme.</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Art 2 -->
                        <div id="art-2" class="term-block bg-slate-50 rounded-2xl p-6 md:p-8 border border-slate-200 scroll-mt-[90px]">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 text-sm font-black shrink-0">2</div>
                                <h3 class="text-lg font-extrabold text-slate-900">Acceptation des Termes</h3>
                            </div>
                            <div class="text-sm text-slate-600 leading-[1.85] space-y-3">
                                <p>En accédant à la Plateforme, en créant un compte ou en effectuant toute transaction, vous acceptez sans réserve les présents Termes et Conditions dans leur intégralité.</p>
                                <p>Si vous utilisez la Plateforme au nom d'une entité commerciale, vous déclarez avoir l'autorité nécessaire pour engager cette entité. Dans le cas contraire, vous êtes personnellement responsable.</p>
                                <p>Nous nous réservons le droit de modifier ces termes à tout moment. Les modifications prennent effet dès leur publication sur la Plateforme. Votre utilisation continue après publication vaut acceptation des nouvelles conditions.</p>
                            </div>
                        </div>

                        <!-- Art 3 -->
                        <div id="art-3" class="term-block bg-slate-50 rounded-2xl p-6 md:p-8 border border-slate-200 scroll-mt-[90px]">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 text-sm font-black shrink-0">3</div>
                                <h3 class="text-lg font-extrabold text-slate-900">Inscription & Compte</h3>
                            </div>
                            <div class="text-sm text-slate-600 leading-[1.85] space-y-3">
                                <p><strong class="text-slate-800">3.1</strong> Pour accéder à l'ensemble des fonctionnalités de la Plateforme, vous devez créer un compte en fournissant des informations exactes, complètes et à jour.</p>
                                <p><strong class="text-slate-800">3.2</strong> Vous êtes seul responsable de la confidentialité de vos identifiants de connexion. Toute activité réalisée sous votre compte est réputée effectuée par vous.</p>
                                <p><strong class="text-slate-800">3.3</strong> Vous vous engagez à nous informer immédiatement de toute utilisation non autorisée de votre compte ou de toute autre breach de sécurité.</p>
                                <p><strong class="text-slate-800">3.4</strong> Nous nous réservons le droit de suspendre ou de supprimer tout compte en cas de violation des présents termes, de fraude présumée, ou d'inactivité prolongée (plus de 12 mois consécutifs) après avis préalable.</p>
                                <p><strong class="text-slate-800">3.5</strong> L'inscription en tant que Vendeur peut être soumise à un processus de vérification supplémentaire incluant la présentation de documents justificatifs.</p>
                            </div>
                        </div>

                        <!-- Art 4 -->
                        <div id="art-4" class="term-block bg-slate-50 rounded-2xl p-6 md:p-8 border border-slate-200 scroll-mt-[90px]">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 text-sm font-black shrink-0">4</div>
                                <h3 class="text-lg font-extrabold text-slate-900">Produits & Prix</h3>
                            </div>
                            <div class="text-sm text-slate-600 leading-[1.85] space-y-3">
                                <p><strong class="text-slate-800">4.1</strong> Les produits affichés sur la Plateforme sont proposés par les Vendeurs. Le Stock Entreprise agit en tant qu'intermédiaire technique et ne garantit pas la disponibilité, l'exactitude des descriptions ou la conformité des produits.</p>
                                <p><strong class="text-slate-800">4.2</strong> Tous les prix sont indiqués en Gourdes haïtiennes (HTG), hors taxes éventuelles. Les prix sont susceptibles de changer sans préavis.</p>
                                <p><strong class="text-slate-800">4.3</strong> Malgré tout le soin apporté, des erreurs de prix peuvent survenir. En cas d'erreur manifeste, nous nous réservons le droit d'annuler la commande et de vous en informer dans les meilleurs délais.</p>
                                <p><strong class="text-slate-800">4.4</strong> Les images produits sont fournies à titre indicatif. De légères différences de couleur, de texture ou de présentation peuvent exister entre l'image et le produit réel.</p>
                                <p><strong class="text-slate-800">4.5</strong> Le Vendeur est seul responsable de la qualité, de la légalité et de la conformité des produits qu'il met en vente.</p>
                            </div>
                        </div>

                        <!-- Art 5 -->
                        <div id="art-5" class="term-block bg-slate-50 rounded-2xl p-6 md:p-8 border border-slate-200 scroll-mt-[90px]">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 text-sm font-black shrink-0">5</div>
                                <h3 class="text-lg font-extrabold text-slate-900">Commandes</h3>
                            </div>
                            <div class="text-sm text-slate-600 leading-[1.85] space-y-3">
                                <p><strong class="text-slate-800">5.1</strong> Toute commande passée sur la Plateforme constitue une offre d'achat ferme. Elle est confirmée par l'envoi d'une notification (email ou SMS) indiquant l'acceptation de la commande.</p>
                                <p><strong class="text-slate-800">5.2</strong> Nous nous réservons le droit de refuser ou d'annuler toute commande en cas de : stock insuffisant, informations incorrectes, suspicion de fraude, ou force majeure.</p>
                                <p><strong class="text-slate-800">5.3</strong> L'Acheteur dispose d'un délai de vérification raisonnable après réception du produit. Toute réclamation doit être formulée dans les <strong class="text-slate-800">48 heures</strong> suivant la réception, via le formulaire de contact ou par WhatsApp.</p>
                                <p><strong class="text-slate-800">5.4</strong> Le Stock Entreprise se réserve le droit de limiter les quantités achetées par commande ou par utilisateur pour les produits en promotion ou les Hot Deals.</p>
                            </div>
                        </div>

                        <!-- Art 6 -->
                        <div id="art-6" class="term-block bg-slate-50 rounded-2xl p-6 md:p-8 border border-slate-200 scroll-mt-[90px]">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-9 h-9 bg-emerald-100 rounded-lg flex items-center justify-center text-emerald-600 text-sm font-black shrink-0">6</div>
                                <h3 class="text-lg font-extrabold text-slate-900">Paiement</h3>
                            </div>
                            <div class="text-sm text-slate-600 leading-[1.85] space-y-3">
                                <p><strong class="text-slate-800">6.1</strong> Le paiement s'effectue en Gourdes haïtiennes (HTG) via les méthodes acceptées sur la Plateforme, notamment : MonCash, Natcom Money, virement bancaire, et paiement en espèces selon les modalités en vigueur.</p>
                                <p><strong class="text-slate-800">6.2</strong> Le paiement intégral est requis au moment de la commande. Aucune commande ne sera traitée avant réception du paiement confirmé.</p>
                                <p><strong class="text-slate-800">6.3</strong> Les transactions financières sont sécurisées. Le Stock Entreprise ne stocke aucune donnée bancaire sensible sur ses serveurs — les paiements sont traités par des prestataires certifiés et conformes aux normes PCI-DSS.</p>
                                <p><strong class="text-slate-800">6.4</strong> En cas de paiement en espèces, la commande sera traitée uniquement après remise effective du montant au livreur ou au point de retrait désigné.</p>
                                <p><strong class="text-slate-800">6.5</strong> Tout paiement frauduleux ou contesté entraînera la suspension immédiate du compte concerné et pourra faire l'objet de poursuites légales.</p>
                            </div>
                        </div>

                        <!-- Art 7 -->
                        <div id="art-7" class="term-block bg-slate-50 rounded-2xl p-6 md:p-8 border border-slate-200 scroll-mt-[90px]">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-9 h-9 bg-amber-100 rounded-lg flex items-center justify-center text-amber-600 text-sm font-black shrink-0">7</div>
                                <h3 class="text-lg font-extrabold text-slate-900">Livraison</h3>
                            </div>
                            <div class="text-sm text-slate-600 leading-[1.85] space-y-3">
                                <p><strong class="text-slate-800">7.1</strong> Les délais de livraison sont donnés à titre indicatif et peuvent varier en fonction de la destination, des conditions de transport et de la disponibilité des produits. Le Stock Entreprise ne peut être tenu responsable des retards indépendants de sa volonté.</p>
                                <p><strong class="text-slate-800">7.2</strong> La livraison est gratuite pour les commandes dont le montant total dépasse <strong class="text-slate-800">180 HTG</strong>. En dessous de ce seuil, des frais de livraison seront appliqués selon la zone géographique.</p>
                                <p><strong class="text-slate-800">7.3</strong> Les zones de livraison couvertes sont indiquées lors du processus de commande. Si votre adresse n'est pas couverte, vous serez notifié et la commande pourra être annulée ou orientée vers un point de retrait alternatif.</p>
                                <p><strong class="text-slate-800">7.4</strong> Le risque de perte ou de dommage est transféré à l'Acheteur dès la remise du produit au livreur ou au point de retrait. Nous recommandons de vérifier l'état du colis avant signature.</p>
                                <p><strong class="text-slate-800">7.5</strong> En cas de livraison échouée (absence du destinataire, adresse incorrecte, refus de réception), des frais de relance pourront être facturés à l'Acheteur.</p>
                            </div>
                        </div>

                        <!-- Art 8 -->
                        <div id="art-8" class="term-block bg-slate-50 rounded-2xl p-6 md:p-8 border border-slate-200 scroll-mt-[90px]">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-9 h-9 bg-red-100 rounded-lg flex items-center justify-center text-red-600 text-sm font-black shrink-0">8</div>
                                <h3 class="text-lg font-extrabold text-slate-900">Retours & Remboursements</h3>
                            </div>
                            <div class="text-sm text-slate-600 leading-[1.85] space-y-3">
                                <p><strong class="text-slate-800">8.1</strong> Le droit de retour s'exerce dans un délai de <strong class="text-slate-800">3 jours ouvrables</strong> après la réception du produit, sous réserve que le produit soit retourné dans son état d'origine, non utilisé, avec tous ses accessoires et emballages d'origine.</p>
                                <p><strong class="text-slate-800">8.2</strong> Sont exclus du droit de retour : les produits personnalisés, les produits périssables, les produits d'hygiène ouverts, les logiciels débridés, et les Hot Deals sauf indication contraire.</p>
                                <p><strong class="text-slate-800">8.3</strong> Les frais de retour sont à la charge de l'Acheteur, sauf si le produit est défectueux, non conforme à la commande, ou endommagé lors de la livraison.</p>
                                <p><strong class="text-slate-800">8.4</strong> Le remboursement sera effectué dans un délai de <strong class="text-slate-800">7 à 14 jours ouvrables</strong> après réception et vérification du produit retourné, par la même méthode de paiement que celle utilisée lors de l'achat.</p>
                                <p><strong class="text-slate-800">8.5</strong> En cas de litige, le Vendeur et l'Acheteur sont encouragés à trouver une solution amiable via le support client de la Plateforme avant toute procédure contentieuse.</p>
                            </div>
                        </div>

                        <!-- Art 9 -->
                        <div id="art-9" class="term-block bg-gradient-to-br from-red-50 to-orange-50 rounded-2xl p-6 md:p-8 border border-red-200/60 scroll-mt-[90px]">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-9 h-9 bg-gradient-to-br from-red-500 to-orange-500 rounded-lg flex items-center justify-center text-white text-sm font-black shrink-0">
                                    <i class="fas fa-fire text-xs"></i>
                                </div>
                                <h3 class="text-lg font-extrabold text-slate-900">Hot Deals — Conditions Spécifiques</h3>
                            </div>
                            <div class="text-sm text-slate-600 leading-[1.85] space-y-3">
                                <p><strong class="text-slate-800">9.1</strong> Les Hot Deals sont des offres promotionnelles à durée limitée. La disponibilité est soumise aux stocks existants et à la période de validité indiquée. Passé ce délai, l'offre est automatiquement retirée.</p>
                                <p><strong class="text-slate-800">9.2</strong> Les prix des Hot Deals ne sont valables que pendant la période indiquée. Aucun ajustement rétroactif ne sera effectué après l'expiration de l'offre.</p>
                                <p><strong class="text-slate-800">9.3</strong> Les quantités par acheteur peuvent être limitées. Le Stock Entreprise se réserve le droit d'annuler les commandes qui dépassent la limite autorisée par utilisateur.</p>
                                <p><strong class="text-slate-800">9.4</strong> Les Hot Deals ne sont pas cumulables avec d'autres codes promotionnels ou offres en cours, sauf mention expresse.</p>
                                <p><strong class="text-slate-800">9.5</strong> Les conditions de retour pour les Hot Deals peuvent différer de celles des produits standards. Chaque Hot Deal indique clairement ses conditions de retour applicables.</p>
                                <p><strong class="text-slate-800">9.6</strong> Le Stock Entreprise se réserve le droit de publier des Hot Deals proposés par des tiers (partenaires, affiliés). Dans ce cas, le Vendeur partenaire reste responsable de la qualité et de la livraison du produit.</p>
                            </div>
                        </div>

                        <!-- Art 10 -->
                        <div id="art-10" class="term-block bg-slate-50 rounded-2xl p-6 md:p-8 border border-slate-200 scroll-mt-[90px]">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-9 h-9 bg-violet-100 rounded-lg flex items-center justify-center text-violet-600 text-sm font-black shrink-0">10</div>
                                <h3 class="text-lg font-extrabold text-slate-900">Propriété Intellectuelle</h3>
                            </div>
                            <div class="text-sm text-slate-600 leading-[1.85] space-y-3">
                                <p><strong class="text-slate-800">10.1</strong> L'ensemble des éléments composant la Plateforme (marques, logos, design, textes, graphismes, code source, base de données) est la propriété exclusive de Le Stock Entreprise ou de ses partenaires, et est protégé par les lois haïtiennes et internationales relatives à la propriété intellectuelle.</p>
                                <p><strong class="text-slate-800">10.2</strong> Toute reproduction, représentation, modification, distribution ou exploitation, même partielle, de tout ou partie de la Plateforme, par quelque procédé que ce soit, sans l'autorisation écrite préalable de Le Stock Entreprise, est strictement interdite.</p>
                                <p><strong class="text-slate-800">10.3</strong> Le Vendeur conserve la propriété intellectuelle des contenus (photos, descriptions) qu'il publie sur la Plateforme, mais accorde à Le Stock Entreprise une licence non-exclusive, mondiale, gratuite pour l'utilisation, l'affichage et la promotion de ces contenus dans le cadre de la Plateforme.</p>
                            </div>
                        </div>

                        <!-- Art 11 -->
                        <div id="art-11" class="term-block bg-slate-50 rounded-2xl p-6 md:p-8 border border-slate-200 scroll-mt-[90px]">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-9 h-9 bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 text-sm font-black shrink-0">11</div>
                                <h3 class="text-lg font-extrabold text-slate-900">Limitation de Responsabilité</h3>
                            </div>
                            <div class="text-sm text-slate-600 leading-[1.85] space-y-3">
                                <p><strong class="text-slate-800">11.1</strong> Le Stock Entreprise fournit la Plateforme « en l'état », sans garantie expresse ou implicite quant à sa disponibilité continue, son absence d'erreurs ou son adéquation à un usage particulier.</p>
                                <p><strong class="text-slate-800">11.2</strong> Le Stock Entreprise ne saurait être tenu responsable des dommages directs, indirects, accessoires ou consécutifs résultant de : l'utilisation ou l'impossibilité d'utiliser la Plateforme, les transactions entre Vendeurs et Acheteurs, la qualité des produits vendus par les Vendeurs tiers, ou tout contenu tiers accessible via la Plateforme.</p>
                                <p><strong class="text-slate-800">11.3</strong> La responsabilité totale de Le Stock Entreprise, le cas échéant, ne pourra en aucun cas excéder le montant total payé par l'Acheteur pour la commande concernée.</p>
                                <p><strong class="text-slate-800">11.4</strong> Le Stock Entreprise n'est pas partie aux contrats de vente conclus entre Vendeurs et Acheteurs. Notre rôle se limite à la mise en relation et à la fourniture des outils techniques nécessaires.</p>
                            </div>
                        </div>

                        <!-- Art 12 -->
                        <div id="art-12" class="term-block bg-slate-50 rounded-2xl p-6 md:p-8 border border-slate-200 scroll-mt-[90px]">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 text-sm font-black shrink-0">12</div>
                                <h3 class="text-lg font-extrabold text-slate-900">Protection des Données Personnelles</h3>
                            </div>
                            <div class="text-sm text-slate-600 leading-[1.85] space-y-3">
                                <p><strong class="text-slate-800">12.1</strong> Le Stock Entreprise s'engage à respecter la confidentialité des données personnelles collectées dans le cadre de l'utilisation de la Plateforme, conformément à la législation haïtienne en vigueur.</p>
                                <p><strong class="text-slate-800">12.2</strong> Les données collectées (nom, adresse, téléphone, email, informations de paiement) sont utilisées exclusivement pour : le traitement des commandes, la communication liée aux services, l'amélioration de la Plateforme, et l'envoi de communications commerciales (avec consentement préalable).</p>
                                <p><strong class="text-slate-800">12.3</strong> Les données ne sont jamais vendues à des tiers. Elles peuvent être partagées avec des prestataires techniques (livraison, paiement) dans la stricte mesure nécessaire au bon fonctionnement des services.</p>
                                <p><strong class="text-slate-800">12.4</strong> Conformément à la législation, vous disposez d'un droit d'accès, de rectification, de suppression et de portabilité de vos données. Pour exercer ces droits, contactez-nous à <strong class="text-slate-800">contact@le-stock.com</strong>.</p>
                                <p><strong class="text-slate-800">12.5</strong> Nous mettons en œuvre des mesures de sécurité techniques et organisationnelles appropriées pour protéger vos données contre tout accès non autorisé, modification, divulgation ou destruction.</p>
                            </div>
                        </div>

                        <!-- Art 13 -->
                        <div id="art-13" class="term-block bg-slate-50 rounded-2xl p-6 md:p-8 border border-slate-200 scroll-mt-[90px]">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-9 h-9 bg-amber-100 rounded-lg flex items-center justify-center text-amber-600 text-sm font-black shrink-0">13</div>
                                <h3 class="text-lg font-extrabold text-slate-900">Comportement de l'Utilisateur</h3>
                            </div>
                            <div class="text-sm text-slate-600 leading-[1.85] space-y-3">
                                <p>En utilisant la Plateforme, vous vous engagez à ne pas :</p>
                                <ul class="list-none space-y-2 pl-1">
                                    <li class="flex items-start gap-2.5"><span class="w-5 h-5 bg-red-100 rounded flex items-center justify-center text-red-500 text-[0.6rem] shrink-0 mt-0.5"><i class="fas fa-times"></i></span> Publier des informations fausses, trompeuses ou diffamatoires.</li>
                                    <li class="flex items-start gap-2.5"><span class="w-5 h-5 bg-red-100 rounded flex items-center justify-center text-red-500 text-[0.6rem] shrink-0 mt-0.5"><i class="fas fa-times"></i></span> Vendre des produits illégaux, contrefaits, dangereux ou interdits par la loi haïtienne.</li>
                                    <li class="flex items-start gap-2.5"><span class="w-5 h-5 bg-red-100 rounded flex items-center justify-center text-red-500 text-[0.6rem] shrink-0 mt-0.5"><i class="fas fa-times"></i></span> Tenter de manipuler les prix, les avis, les classements ou le fonctionnement technique de la Plateforme.</li>
                                    <li class="flex items-start gap-2.5"><span class="w-5 h-5 bg-red-100 rounded flex items-center justify-center text-red-500 text-[0.6rem] shrink-0 mt-0.5"><i class="fas fa-times"></i></span> Utiliser des robots, scripts ou tout moyen automatisé pour accéder à la Plateforme de manière abusive.</li>
                                    <li class="flex items-start gap-2.5"><span class="w-5 h-5 bg-red-100 rounded flex items-center justify-center text-red-500 text-[0.6rem] shrink-0 mt-0.5"><i class="fas fa-times"></i></span> Harceler, menacer ou porter atteinte à d'autres Utilisateurs ou au personnel de la Plateforme.</li>
                                    <li class="flex items-start gap-2.5"><span class="w-5 h-5 bg-red-100 rounded flex items-center justify-center text-red-500 text-[0.6rem] shrink-0 mt-0.5"><i class="fas fa-times"></i></span> Créer plusieurs comptes frauduleusement pour profiter d'offres destinées aux nouveaux utilisateurs.</li>
                                </ul>
                                <p>Toute violation de ces règles entraînera la suspension ou la suppression immédiate du compte, sans préjudice des recours légaux éventuels.</p>
                            </div>
                        </div>

                        <!-- Art 14 -->
                        <div id="art-14" class="term-block bg-slate-50 rounded-2xl p-6 md:p-8 border border-slate-200 scroll-mt-[90px]">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-9 h-9 bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 text-sm font-black shrink-0">14</div>
                                <h3 class="text-lg font-extrabold text-slate-900">Résiliation</h3>
                            </div>
                            <div class="text-sm text-slate-600 leading-[1.85] space-y-3">
                                <p><strong class="text-slate-800">14.1</strong> Vous pouvez résilier votre compte à tout moment en contactant notre support client ou via les paramètres de votre compte. La résiliation n'affecte pas les commandes en cours.</p>
                                <p><strong class="text-slate-800">14.2</strong> Le Stock Entreprise peut résilier ou suspendre votre accès à la Plateforme : immédiatement en cas de violation grave des présents termes ; après un préavis de 30 jours pour tout autre motif légitime.</p>
                                <p><strong class="text-slate-800">14.3</strong> Les dispositions relatives à la propriété intellectuelle, la limitation de responsabilité et la protection des données survivent à la résiliation.</p>
                            </div>
                        </div>

                        <!-- Art 15 -->
                        <div id="art-15" class="term-block bg-slate-50 rounded-2xl p-6 md:p-8 border border-slate-200 scroll-mt-[90px]">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 text-sm font-black shrink-0">15</div>
                                <h3 class="text-lg font-extrabold text-slate-900">Modifications des Termes</h3>
                            </div>
                            <div class="text-sm text-slate-600 leading-[1.85] space-y-3">
                                <p>Le Stock Entreprise se réserve le droit de modifier les présents Termes et Conditions à tout moment. Les modifications seront publiées sur cette page avec la date de mise à jour.</p>
                                <p>En cas de modification substantielle, une notification sera envoyée aux Utilisateurs inscrits (email ou notification sur la Plateforme) au moins <strong class="text-slate-800">7 jours</strong> avant l'entrée en vigueur des nouvelles conditions.</p>
                                <p>L'utilisation continue de la Plateforme après la date d'entrée en vigueur des modifications vaut acceptation des nouveaux termes. Si vous n'acceptez pas les modifications, vous devez cesser d'utiliser la Plateforme et demander la résiliation de votre compte.</p>
                            </div>
                        </div>

                        <!-- Art 16 -->
                        <div id="art-16" class="term-block bg-slate-50 rounded-2xl p-6 md:p-8 border border-slate-200 scroll-mt-[90px]">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 text-sm font-black shrink-0">16</div>
                                <h3 class="text-lg font-extrabold text-slate-900">Loi Applicable & Juridiction Compétente</h3>
                            </div>
                            <div class="text-sm text-slate-600 leading-[1.85] space-y-3">
                                <p>Les présents Termes et Conditions sont régis par et interprétés conformément aux lois de la <strong class="text-slate-800">République d'Haïti</strong>, sans égard à ses règles de conflit de lois.</p>
                                <p>Tout litige découlant des présents termes ou de l'utilisation de la Plateforme sera soumis à la compétence exclusive des tribunaux du <strong class="text-slate-800">Cap-Haïtien, République d'Haïti</strong>.</p>
                                <p>Avant tout recours judiciaire, les parties s'engagent à rechercher une solution amiable dans un délai de <strong class="text-slate-800">30 jours</strong> à compter de la notification du litige.</p>
                            </div>
                        </div>

                        <!-- Art 17 -->
                        <div id="art-17" class="term-block bg-gradient-to-br from-blue-50 to-slate-50 rounded-2xl p-6 md:p-8 border border-blue-200/60 scroll-mt-[90px]">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-9 h-9 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center text-white text-sm font-black shrink-0">17</div>
                                <h3 class="text-lg font-extrabold text-slate-900">Contact</h3>
                            </div>
                            <div class="text-sm text-slate-600 leading-[1.85] space-y-3">
                                <p>Pour toute question relative aux présents Termes et Conditions, ou pour signaler un problème, vous pouvez nous contacter par :</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4">
                                    <div class="bg-white rounded-xl p-4 border border-slate-200 flex items-center gap-3">
                                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 shrink-0"><i class="fas fa-envelope"></i></div>
                                        <div>
                                            <div class="text-xs text-slate-400 font-medium">Email</div>
                                            <div class="text-sm font-bold text-slate-800">contact@le-stock.com</div>
                                        </div>
                                    </div>
                                    <div class="bg-white rounded-xl p-4 border border-slate-200 flex items-center gap-3">
                                        <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center text-emerald-600 shrink-0"><i class="fab fa-whatsapp"></i></div>
                                        <div>
                                            <div class="text-xs text-slate-400 font-medium">WhatsApp</div>
                                            <div class="text-sm font-bold text-slate-800">+509 32 73 29 20</div>
                                        </div>
                                    </div>
                                    <div class="bg-white rounded-xl p-4 border border-slate-200 flex items-center gap-3">
                                        <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center text-amber-600 shrink-0"><i class="fas fa-phone"></i></div>
                                        <div>
                                            <div class="text-xs text-slate-400 font-medium">Téléphone</div>
                                            <div class="text-sm font-bold text-slate-800">+509 32 73 29 20</div>
                                        </div>
                                    </div>
                                    <div class="bg-white rounded-xl p-4 border border-slate-200 flex items-center gap-3">
                                        <div class="w-10 h-10 bg-violet-100 rounded-lg flex items-center justify-center text-violet-600 shrink-0"><i class="fas fa-map-marker-alt"></i></div>
                                        <div>
                                            <div class="text-xs text-slate-400 font-medium">Adresse</div>
                                            <div class="text-sm font-bold text-slate-800">Cap-Haïtien, Haïti</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <!-- End Articles -->

                    <!-- Bottom Note -->
                    <div class="mt-10 bg-slate-900 rounded-2xl p-7 text-center">
                        <p class="text-blue-200/80 text-sm leading-relaxed mb-4">
                            En cochant la case d'acceptation lors de l'inscription ou en passant commande, vous confirmez avoir lu, compris et accepté l'intégralité des présents Termes et Conditions.
                        </p>
                        <div class="flex items-center justify-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-gradient-to-br from-amber-400 to-amber-500 rounded-xl flex items-center justify-center text-white font-black text-lg">L</div>
                            <span class="text-white font-extrabold text-lg tracking-tight">Le Stock Entreprise</span>
                        </div>
                        <p class="text-slate-500 text-xs">Document en vigueur depuis le 1er janvier 2025</p>
                    </div>

                </div>
            </div>
        </div>
    </section>
    <!-- ╔══════════════════════════════════════════════════════════════╗ -->
    <!-- ║              FIN TERMES ET CONDITIONS                        ║ -->
    <!-- ╚══════════════════════════════════════════════════════════════╝ -->


    <!-- ===== FEATURES ===== -->
    <section class="bg-white border-t border-slate-200 py-12">
        <div class="max-w-site mx-auto px-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
            <div class="flex items-center gap-4 px-6 py-5 rounded-2xl border border-slate-200 transition-all bg-white hover:border-blue-200 hover:shadow-lg hover:shadow-blue-600/10 hover:-translate-y-0.5">
                <div class="w-[50px] h-[50px] rounded-[14px] flex items-center justify-center shrink-0 text-xl bg-blue-50 text-blue-600"><i class="fas fa-truck-fast"></i></div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 mb-0.5">Livraison Gratuite</h3>
                    <p class="text-xs text-slate-500 leading-snug">Pour les commandes de plus de 180 $</p>
                </div>
            </div>
            <div class="flex items-center gap-4 px-6 py-5 rounded-2xl border border-slate-200 transition-all bg-white hover:border-blue-200 hover:shadow-lg hover:shadow-blue-600/10 hover:-translate-y-0.5">
                <div class="w-[50px] h-[50px] rounded-[14px] flex items-center justify-center shrink-0 text-xl bg-emerald-50 text-emerald-600"><i class="fas fa-shield-halved"></i></div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 mb-0.5">Paiement Sécurisé</h3>
                    <p class="text-xs text-slate-500 leading-snug">Plusieurs options de paiement fiables</p>
                </div>
            </div>
            <div class="flex items-center gap-4 px-6 py-5 rounded-2xl border border-slate-200 transition-all bg-white hover:border-blue-200 hover:shadow-lg hover:shadow-blue-600/10 hover:-translate-y-0.5">
                <div class="w-[50px] h-[50px] rounded-[14px] flex items-center justify-center shrink-0 text-xl bg-amber-50 text-amber-600"><i class="fas fa-headset"></i></div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 mb-0.5">Support 24/7</h3>
                    <p class="text-xs text-slate-500 leading-snug">Disponibles en ligne tous les jours</p>
                </div>
            </div>
            <a href="https://wa.me/50932732920?text=Bonjour%20LE-STOCK%2C%20je%20souhaite%20avoir%20plus%20d%27informations." target="_blank" rel="noopener noreferrer" class="flex items-center gap-4 px-6 py-5 rounded-2xl border-2 border-emerald-200 transition-all bg-gradient-to-br from-emerald-50/80 to-white hover:border-emerald-400 hover:shadow-lg hover:shadow-emerald-500/15 hover:-translate-y-0.5 no-underline group">
                <div class="w-[50px] h-[50px] rounded-[14px] flex items-center justify-center shrink-0 text-xl bg-gradient-to-br from-emerald-500 to-emerald-600 text-white animate-whatsapp-pulse shadow-lg shadow-emerald-500/30"><i class="fab fa-whatsapp text-2xl"></i></div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-bold text-slate-900 mb-0.5 flex items-center gap-1.5">
                        Contactez-nous
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md bg-emerald-100 text-emerald-700 text-[0.6rem] font-extrabold uppercase tracking-wider leading-none">WhatsApp</span>
                    </h3>
                    <p class="text-xs text-emerald-700 font-semibold leading-snug flex items-center gap-1.5">
                        <i class="fab fa-whatsapp text-[0.7rem]"></i> +509 32 73 29 20
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
            <div>
                <h4 class="footer-heading text-white font-bold text-sm mb-5 relative pb-2.5">Entreprise</h4>
                <div class="flex flex-col gap-1">
                    <a href="a_propos.php" class="text-white no-underline text-sm transition-all inline-block py-0.5 hover:text-blue-300 hover:translate-x-0.5">À Propos</a>
                    <a href="#termes" class="text-slate-400 no-underline text-sm transition-all inline-block py-0.5 hover:text-white hover:translate-x-0.5">Termes & Conditions</a>
                    <a href="Contacte" class="text-slate-400 no-underline text-sm transition-all inline-block py-0.5 hover:text-white hover:translate-x-0.5">Contactez-nous</a>
                </div>
            </div>
            <div>
                <h4 class="footer-heading text-white font-bold text-sm mb-5 relative pb-2.5">Service Client</h4>
                <div class="flex flex-col gap-1">
                    <a href="profile" class="text-slate-400 no-underline text-sm transition-all inline-block py-0.5 hover:text-white hover:translate-x-0.5">Mon Compte</a>
                    <a href="#" class="text-slate-400 no-underline text-sm transition-all inline-block py-0.5 hover:text-white hover:translate-x-0.5">Suivre ma Commande</a>
                    <a href="../index" class="text-slate-400 no-underline text-sm transition-all inline-block py-0.5 hover:text-white hover:translate-x-0.5">Retours</a>
                </div>
            </div>
            <div>
                <h4 class="footer-heading text-white font-bold text-sm mb-5 relative pb-2.5">Coordonnées</h4>
                <div>
                    <div class="text-slate-400 flex items-start gap-3 mb-3.5 text-sm">
                        <i class="fas fa-phone text-blue-500 mt-0.5 w-4 text-center"></i>
                        <span>+509 32 73 29 20</span>
                    </div>
                    <div class="text-slate-400 flex items-start gap-3 mb-3.5 text-sm">
                        <i class="fas fa-envelope text-blue-500 mt-0.5 w-4 text-center"></i>
                        <span>lestockentreprise@gmail.com</span>
                    </div>
                    <div class="text-slate-400 flex items-start gap-3 mb-3.5 text-sm">
                        <i class="fas fa-map-marker-alt text-blue-500 mt-0.5 w-4 text-center"></i>
                        <span>12 Rue 24-A<br>
                        Cap-Haïtien, Haïti</span>
                    </div>
                </div>
            </div>
        </div>
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

        // ===== SCROLL ANIMATIONS =====
        (function () {
            const sections = document.querySelectorAll('.about-section');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) entry.target.classList.add('section-visible');
                });
            }, { threshold: 0.05, rootMargin: '0px 0px -30px 0px' });
            sections.forEach(s => observer.observe(s));
        })();

        // ===== COUNTER ANIMATION =====
        (function () {
            const counters = document.querySelectorAll('.counter');
            let countersAnimated = new Set();
            function animateCounter(el) {
                const target = parseInt(el.dataset.target);
                const duration = 2000;
                const start = performance.now();
                function update(now) {
                    const elapsed = now - start;
                    const progress = Math.min(elapsed / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    el.textContent = Math.floor(eased * target).toLocaleString('fr-FR');
                    if (progress < 1) requestAnimationFrame(update);
                    else {
                        el.textContent = target.toLocaleString('fr-FR') + '+';
                        el.classList.add('animate-count-up');
                        setTimeout(() => el.classList.remove('animate-count-up'), 400);
                    }
                }
                requestAnimationFrame(update);
            }
            const counterObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !countersAnimated.has(entry.target)) {
                        countersAnimated.add(entry.target);
                        animateCounter(entry.target);
                    }
                });
            }, { threshold: 0.5 });
            counters.forEach(c => counterObserver.observe(c));
        })();

        // ===== STICKY TOC ACTIVE STATE =====
        (function () {
            const tocLinks = document.querySelectorAll('#toc-nav .toc-link');
            const articles = [];
            tocLinks.forEach(link => {
                const href = link.getAttribute('href');
                const target = document.querySelector(href);
                if (target) articles.push({ el: target, link: link });
            });

            function updateActiveToc() {
                let current = null;
                const scrollY = window.scrollY + 120;
                articles.forEach(item => {
                    if (item.el.offsetTop <= scrollY) current = item;
                });
                tocLinks.forEach(l => l.classList.remove('active-toc'));
                if (current) current.link.classList.add('active-toc');
            }

            window.addEventListener('scroll', updateActiveToc, { passive: true });
            updateActiveToc();
        })();

        // ===== CART BADGE =====
        function updateCartBadge() {
            fetch('panier/get_cart_count.php')
                .then(r => r.json())
                .then(d => {
                    const b = document.getElementById('cart-badge');
                    if (b) b.textContent = d.count || 0;
                })
                .catch(() => {});
        }
        document.addEventListener('DOMContentLoaded', updateCartBadge);

        // ===== SMOOTH SCROLL =====
        document.querySelectorAll('a[href^="#"]').forEach(a => {
            a.addEventListener('click', function (e) {
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    // Close mobile details if open
                    const details = this.closest('details');
                    if (details) details.removeAttribute('open');
                }
            });
        });
    </script>

</body>
</html>