<?php
include 'includes/header.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Le Stock Entreprise</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Empêcher le débordement horizontal */
        html,
        body {
            overflow-x: hidden;
            max-width: 100vw;
        }

        /* Fix pour images */
        img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-15px);
            }
        }

        .animate-float {
            animation: float 4s ease-in-out infinite;
        }

        .text-glow {
            text-shadow: 0 0 15px rgba(59, 130, 246, 0.5);
        }

        /* Très petits écrans */
        @media (max-width: 400px) {
            .xs\:flex-row {
                flex-direction: row;
            }
        }

        /* Animation pour les cartes */
        .hover-lift {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .hover-lift:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        /* Style pour le conteneur GIF - Format large mais haut (3:4) */
        .video-gif-container {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 25px 80px rgba(0,0,0,0.4);
            background: #000;
            max-width: 420px;
            margin: 0 auto;
        }
        
        .video-gif-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 40px;
            background: linear-gradient(to bottom, rgba(255,255,255,0.15), transparent);
            z-index: 10;
            pointer-events: none;
        }

        .video-gif-container img {
            width: 100%;
            height: auto;
            object-fit: cover;
            aspect-ratio: 3/4;
        }

        /* Effet de reflet sous l'écran */
        .screen-reflection {
            position: absolute;
            bottom: -30px;
            left: 10%;
            right: 10%;
            height: 30px;
            background: linear-gradient(to bottom, rgba(0,0,0,0.3), transparent);
            filter: blur(12px);
            border-radius: 50%;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .video-gif-container {
                max-width: 360px;
            }
        }

        @media (max-width: 640px) {
            .video-gif-container {
                max-width: 300px;
            }
        }
    </style>
</head>

<body class="bg-gray-50 overflow-x-hidden">
  
  <!-- HERO SECTION -->
  <section class="relative min-h-[600px] lg:min-h-screen flex items-center justify-center overflow-hidden py-20 lg:py-0 w-full">

    <!-- Background image avec max-width 100% -->
    <div class="absolute inset-0 z-0 w-full h-full">
      <img src="assets/img/Emo.png" alt="Antrepriz Stock"
        class="w-full h-full object-cover max-w-full">
      <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/60 to-black/40"></div>
    </div>

    <div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 flex flex-col lg:flex-row items-center justify-between gap-8 lg:gap-12 w-full max-w-full">

      <!-- Texte -->
      <div class="w-full lg:w-[55%] text-center lg:text-left order-2 lg:order-1 px-2 sm:px-0">
        <span class="inline-block px-3 py-1 mb-4 text-xs sm:text-sm font-semibold tracking-wider text-blue-400 uppercase bg-blue-900/30 rounded-full border border-blue-500/30">
          Bienvenue chez
        </span>

        <h1 class="text-2xl sm:text-4xl md:text-5xl lg:text-6xl xl:text-7xl font-extrabold text-white mb-4 sm:mb-6 leading-tight drop-shadow-md break-words">
          Le Stock <span class="text-blue-500 text-glow">Entreprise</span>
        </h1>

        <p class="text-sm sm:text-lg md:text-xl text-gray-200 mb-6 sm:mb-10 max-w-2xl mx-auto lg:mx-0 leading-relaxed px-2 sm:px-0">
          "Une plateforme complète pour gérer vos vendeurs et votre stock en temps réel."
        </p>

        <!-- Boutons en colonne sur très petits écrans -->
        <div class="flex flex-col xs:flex-row gap-3 sm:gap-5 justify-center lg:justify-start w-full sm:w-auto px-4 sm:px-0">
          <a href="page/acceuil.php" class="group bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 sm:px-10 rounded-full transition-all duration-300 shadow-lg flex items-center justify-center gap-2 text-sm sm:text-base whitespace-nowrap">
            Visiter
            <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
          </a>
          <a href="inscription.php" class="bg-white/10 hover:bg-white/20 text-white backdrop-blur-md font-bold py-3 px-6 sm:px-10 rounded-full border border-white/30 transition-all duration-300 flex items-center justify-center text-sm sm:text-base whitespace-nowrap">
            Devni Machann
          </a>
        </div>
      </div>

      <!-- Image Hero -->
      <div class="w-full lg:w-[45%] relative flex justify-center items-center order-1 lg:order-2 mb-8 lg:mb-0 px-4 sm:px-0">
        <div class="relative w-full max-w-[300px] sm:max-w-[350px] lg:max-w-full animate-float">
          <img src="assets/img/plop12345.png" alt="Shop"
            class="w-full h-auto object-contain max-w-full">
          <div class="absolute -z-10 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[80%] h-[80%] bg-blue-500/20 blur-[80px] rounded-full"></div>
        </div>
      </div>
    </div>

    <div class="absolute bottom-0 left-0 w-full h-[15%] bg-gradient-to-t from-gray-50 to-transparent pointer-events-none"></div>
  </section>

  <!-- SECTION À PROPOS - AVEC GIF FORMAT LARGE 3:4 -->
  <section class="py-12 sm:py-16 bg-gray-50 w-full overflow-hidden">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 w-full max-w-full">
      <div class="flex flex-col md:flex-row items-center gap-8 md:gap-12 w-full">

        <!-- Image/GIF Container - Format 3:4 (plus large, toujours haut) -->
        <div class="w-full md:w-1/2 order-1 px-2 sm:px-0 flex justify-center">
          <div class="relative">
            <!-- Conteneur style téléphone/tablette -->
            <div class="video-gif-container">
              <img src="assets/olop.gif" alt="À propos de nous - Démonstration"
                onerror="this.src='assets/img/E-COMMERCE.jpeg'; this.onerror=null;"
                class="w-full h-auto object-cover">
            </div>
            <!-- Reflet sous l'écran -->
            <div class="screen-reflection"></div>
            
            <!-- Badge en bas à droite -->
            <
            <!-- Indicateur de swipe -->
            <div class="absolute top-1/2 right-3 transform -translate-y-1/2 text-white/80 animate-pulse">
              <i class="fas fa-chevron-right text-xl"></i>
            </div>
          </div>
        </div>

        <!-- Texte -->
        <div class="w-full md:w-1/2 space-y-4 sm:space-y-6 order-2 text-center md:text-left px-2 sm:px-0">
          <h2 class="text-xl sm:text-3xl md:text-4xl font-bold text-gray-800 break-words">
            À propos de <span class="text-blue-600">Notre Entreprise</span>
          </h2>

          <p class="text-gray-600 leading-relaxed text-sm sm:text-lg break-words">
            Bienvenue sur notre plateforme. Nous nous engageons à fournir des solutions innovantes
            pour simplifier votre quotidien. Notre expertise nous permet de vous accompagner
            dans chacun de vos projets avec passion et rigueur.
          </p>

          <p class="text-gray-600 leading-relaxed text-xs sm:text-base break-words">
            Que ce soit pour la gestion de stocks ou le développement de solutions sur mesure,
            notre équipe est là pour transformer vos idées en réalité numérique.
          </p>

          <a href="#contact" class="inline-block bg-blue-600 text-white px-6 py-2.5 rounded-md font-semibold hover:bg-blue-700 transition text-sm sm:text-base">
            En savoir plus
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- SECTION COMMENT ÇA MARCHE -->
  <section class="py-16 sm:py-20 bg-blue-500 overflow-hidden">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 w-full max-w-full">
      
      <!-- En-tête -->
      <div class="text-center mb-12 sm:mb-16">
        <span class="inline-block px-4 py-1 mb-4 text-xs font-semibold tracking-wider text-blue-600 uppercase bg-blue-50 rounded-full">
          Processus Simple
        </span>
        <h2 class="text-2xl sm:text-4xl md:text-5xl font-bold text-gray-900 mb-4">
          Comment Ça Marche ?
        </h2>
        <p class="text-white text-sm sm:text-lg max-w-2xl mx-auto">
          Trois étapes simples pour commencer à gagner avec notre programme d'affiliation.
        </p>
      </div>

      <!-- Les 3 étapes -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8">
        
        <!-- Étape 1 -->
        <div class="relative bg-white rounded-2xl p-6 sm:p-8 border border-gray-100 hover-lift">
          <div class="absolute -top-4 left-6 w-8 h-8 bg-blue-600 text-white rounded-lg flex items-center justify-center font-bold text-sm shadow-lg">
            01
          </div>
          <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mb-4 mt-2">
            <i class="fas fa-user-plus text-blue-600 text-xl"></i>
          </div>
          <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-3">Inscrivez-Vous</h3>
          <p class="text-gray-600 text-sm sm:text-base leading-relaxed">
            Créez votre compte gratuitement en quelques minutes. Aucune expérience requise.
          </p>
        </div>

        <!-- Étape 2 -->
        <div class="relative bg-white rounded-2xl p-6 sm:p-8 border border-gray-100 hover-lift">
          <div class="absolute -top-4 left-6 w-8 h-8 bg-blue-600 text-white rounded-lg flex items-center justify-center font-bold text-sm shadow-lg">
            02
          </div>
          <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mb-4 mt-2">
            <i class="fas fa-share-alt text-blue-600 text-xl"></i>
          </div>
          <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-3">Partagez les Produits</h3>
          <p class="text-gray-600 text-sm sm:text-base leading-relaxed">
            Utilisez vos liens d'affiliation uniques pour promouvoir nos produits sur vos canaux.
          </p>
        </div>

        <!-- Étape 3 -->
        <div class="relative bg-white rounded-2xl p-6 sm:p-8 border border-gray-100 hover-lift">
          <div class="absolute -top-4 left-6 w-8 h-8 bg-blue-600 text-white rounded-lg flex items-center justify-center font-bold text-sm shadow-lg">
            03
          </div>
          <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mb-4 mt-2">
            <i class="fas fa-wallet text-blue-600 text-xl"></i>
          </div>
          <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-3">Gagnez des Revenus</h3>
          <p class="text-gray-600 text-sm sm:text-base leading-relaxed">
            Recevez des commissions sur chaque vente générée grâce à vos recommandations.
          </p>
        </div>

      </div>
    </div>
  </section>

  <!-- SECTION AVANTAGES DU PROGRAMME -->
  <section class="py-16 sm:py-20 bg-gray-50 w-full overflow-hidden">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 w-full max-w-full">
      
      <!-- En-tête -->
      <div class="text-center mb-12 sm:mb-16">
        <span class="inline-block px-4 py-1 mb-4 text-xs font-semibold tracking-wider text-blue-600 uppercase bg-blue-50 rounded-full">
          Pourquoi Nous Choisir
        </span>
        <h2 class="text-2xl sm:text-4xl md:text-5xl font-bold text-gray-900 mb-4">
          Avantages du Programme
        </h2>
      </div>

      <!-- Grille des avantages -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
        
        <!-- Avantage 1: Support Prioritaire -->
        <div class="bg-white rounded-xl p-6 flex items-start gap-4 hover-lift border border-gray-100">
          <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0">
            <i class="fas fa-headset text-blue-600 text-xl"></i>
          </div>
          <div>
            <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-2">Support Prioritaire</h3>
            <p class="text-gray-600 text-sm leading-relaxed">
              Une équipe dédiée à votre succès, disponible 7j/7 pour répondre à vos questions.
            </p>
          </div>
        </div>

        <!-- Avantage 2: Paiement Rapide -->
        <div class="bg-white rounded-xl p-6 flex items-start gap-4 hover-lift border border-gray-100">
          <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0">
            <i class="fas fa-bolt text-blue-600 text-xl"></i>
          </div>
          <div>
            <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-2">Paiement Rapide</h3>
            <p class="text-gray-600 text-sm leading-relaxed">
              Retirez vos gains en moins de 48 heures via plusieurs méthodes de paiement.
            </p>
          </div>
        </div>

        <!-- Avantage 3: Bonus & Récompenses -->
        <div class="bg-white rounded-xl p-6 flex items-start gap-4 hover-lift border border-gray-100">
          <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0">
            <i class="fas fa-gift text-blue-600 text-xl"></i>
          </div>
          <div>
            <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-2">Bonus & Récompenses</h3>
            <p class="text-gray-600 text-sm leading-relaxed">
              Profitez de bonus mensuels et de récompenses exclusives pour les meilleurs affiliés.
            </p>
          </div>
        </div>

        <!-- Avantage 4: Tableau de Bord -->
        <div class="bg-white rounded-xl p-6 flex items-start gap-4 hover-lift border border-gray-100">
          <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0">
            <i class="fas fa-chart-line text-blue-600 text-xl"></i>
          </div>
          <div>
            <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-2">Tableau de Bord</h3>
            <p class="text-gray-600 text-sm leading-relaxed">
              Suivez vos performances en temps réel avec des statistiques détaillées et claires.
            </p>
          </div>
        </div>

        <!-- Avantage 5: Outils de Promotion -->
        <div class="bg-white rounded-xl p-6 flex items-start gap-4 hover-lift border border-gray-100">
          <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0">
            <i class="fas fa-bullhorn text-blue-600 text-xl"></i>
          </div>
          <div>
            <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-2">Outils de Promotion</h3>
            <p class="text-gray-600 text-sm leading-relaxed">
              Bannières, liens personnalisés et contenus prêts à l'emploi pour vos campagnes.
            </p>
          </div>
        </div>

        <!-- Avantage 6: Cookies 90 Jours -->
        <div class="bg-white rounded-xl p-6 flex items-start gap-4 hover-lift border border-gray-100">
          <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0">
            <i class="fas fa-shield-alt text-blue-600 text-xl"></i>
          </div>
          <div>
            <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-2">Cookies 90 Jours</h3>
            <p class="text-gray-600 text-sm leading-relaxed">
              Une durée de cookie étendue pour maximiser vos chances de conversion.
            </p>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- FOOTER SIMPLE -->
  <footer class="bg-gray-900 text-white py-8 w-full">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <p class="text-sm text-gray-400">
        © 2026 Le Stock Entreprise. Tous droits réservés.
      </p>
    </div>
  </footer>

</body>
</html>