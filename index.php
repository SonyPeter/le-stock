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
    html,
    body {
      overflow-x: hidden;
      max-width: 100vw;
    }

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
      animation: float 6s ease-in-out infinite;
    }

    .text-glow {
      text-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
    }

    @media (max-width: 400px) {
      .xs\:flex-row {
        flex-direction: row;
      }
    }

    .hover-lift {
      transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.4s ease;
    }

    .hover-lift:hover {
      transform: translateY(-8px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
    }

    .video-gif-container {
      position: relative;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
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
      height: 60px;
      background: linear-gradient(to bottom, rgba(255, 255, 255, 0.1), transparent);
      z-index: 10;
      pointer-events: none;
    }

    .video-gif-container img {
      width: 100%;
      height: auto;
      object-fit: cover;
      aspect-ratio: 3/4;
    }

    .screen-reflection {
      position: absolute;
      bottom: -40px;
      left: 15%;
      right: 15%;
      height: 40px;
      background: linear-gradient(to bottom, rgba(0, 0, 0, 0.2), transparent);
      filter: blur(20px);
      border-radius: 50%;
    }

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

    /* ========== ANIMASYON DOUS FLUID ========== */

    /* 1. Bienvenue chez - Gentle fade up with slight slide */
    @keyframes gentleFadeUp {
      0% {
        opacity: 0;
        transform: translateY(40px);
        filter: blur(4px);
      }

      100% {
        opacity: 1;
        transform: translateY(0);
        filter: blur(0);
      }
    }

    /* 2. Le Stock Entreprise - Elegant scale fade */
    @keyframes elegantScale {
      0% {
        opacity: 0;
        transform: scale(0.9) translateY(20px);
        filter: blur(8px);
      }

      60% {
        opacity: 0.8;
        transform: scale(1.02) translateY(-5px);
        filter: blur(2px);
      }

      100% {
        opacity: 1;
        transform: scale(1) translateY(0);
        filter: blur(0);
      }
    }

    /* 3. Paragraf - Soft blur dissolve */
    @keyframes softBlurIn {
      0% {
        opacity: 0;
        transform: translateY(30px);
        filter: blur(12px);
        letter-spacing: 0.05em;
      }

      100% {
        opacity: 1;
        transform: translateY(0);
        filter: blur(0);
        letter-spacing: normal;
      }
    }

    /* 4. Bouton yo - Gentle rise with subtle bounce */
    @keyframes gentleRise {
      0% {
        opacity: 0;
        transform: translateY(60px) scale(0.95);
        filter: blur(6px);
      }

      70% {
        opacity: 1;
        transform: translateY(-8px) scale(1.02);
        filter: blur(0);
      }

      100% {
        opacity: 1;
        transform: translateY(0) scale(1);
        filter: blur(0);
      }
    }

    /* 5. Subtle glow animation */
    @keyframes subtleGlow {

      0%,
      100% {
        box-shadow: 0 4px 20px rgba(37, 99, 235, 0.2);
      }

      50% {
        box-shadow: 0 8px 40px rgba(37, 99, 235, 0.35);
      }
    }

    /* Klas pou chak eleman - kòmansman invizib */
    .anim-welcome,
    .anim-title,
    .anim-text,
    .anim-buttons {
      opacity: 0;
    }

    /* Lè animasyon an aktive - timing dous */
    .animate-now .anim-welcome {
      animation: gentleFadeUp 1.2s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
    }

    .animate-now .anim-title {
      animation: elegantScale 1.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
      animation-delay: 0.4s;
    }

    .animate-now .anim-text {
      animation: softBlurIn 1.3s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
      animation-delay: 0.8s;
    }

    .animate-now .anim-buttons {
      animation: gentleRise 1.2s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
      animation-delay: 1.2s;
    }

    /* Delè pou chak bouton */
    .animate-now .anim-buttons a:nth-child(1) {
      animation: gentleRise 1.2s cubic-bezier(0.34, 1.56, 0.64, 1) forwards,
        subtleGlow 3s ease-in-out infinite;
      animation-delay: 1.2s, 2s;
    }

    .animate-now .anim-buttons a:nth-child(2) {
      animation: gentleRise 1.2s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
      animation-delay: 1.4s;
    }

    /* Hover efè dous pou bouton */
    .btn-primary {
      position: relative;
      overflow: hidden;
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .btn-primary::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
      transition: left 0.6s ease;
    }

    .btn-primary:hover::before {
      left: 100%;
    }

    .btn-primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 15px 35px rgba(37, 99, 235, 0.3);
    }

    .btn-secondary {
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      backdrop-filter: blur(10px);
    }

    .btn-secondary:hover {
      background: rgba(255, 255, 255, 0.15);
      transform: translateY(-3px);
      border-color: rgba(255, 255, 255, 0.5);
      box-shadow: 0 15px 35px rgba(255, 255, 255, 0.1);
    }

    /* Efè hover sou flech la - plis dous */
    .btn-primary:hover .fa-arrow-right {
      transform: translateX(8px);
      transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
  </style>
</head>

<body class="bg-gray-50 overflow-x-hidden">

  <!-- HERO SECTION -->
  <section class="relative min-h-[600px] lg:min-h-screen flex items-center justify-center overflow-hidden py-20 lg:py-0 w-full">

    <div class="absolute inset-0 z-0 w-full h-full">
      <img src="assets/img/Emo.png" alt="Antrepriz Stock" class="w-full h-full object-cover max-w-full">
      <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/60 to-black/40"></div>
    </div>

    <div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 flex flex-col lg:flex-row items-center justify-between gap-8 lg:gap-12 w-full max-w-full">

      <!-- Texte -->
      <div id="hero-content" class="w-full lg:w-[55%] text-center lg:text-left order-2 lg:order-1 px-2 sm:px-0">

        <!-- Bienvenue chez - Gentle fade up -->
        <span class="anim-welcome inline-block px-4 py-1.5 mb-6 text-xs sm:text-sm font-medium tracking-[0.2em] text-blue-300 uppercase bg-blue-900/20 rounded-full border border-blue-500/20 backdrop-blur-sm">
          Bienvenue chez
        </span>

        <!-- Le Stock Entreprise - Elegant scale -->
        <h1 class="anim-title text-2xl sm:text-4xl md:text-5xl lg:text-6xl xl:text-7xl font-bold text-white mb-6 sm:mb-8 leading-tight drop-shadow-lg break-words tracking-tight">
          Le Stock <span class="text-blue-400 text-glow font-extrabold">Entreprise</span>
        </h1>

        <!-- Paragraf - Soft blur dissolve -->
        <p class="anim-text text-sm sm:text-lg md:text-xl text-gray-300 mb-8 sm:mb-12 max-w-2xl mx-auto lg:mx-0 leading-relaxed px-2 sm:px-0 font-light tracking-wide">
          Une solution intégrale pour visualiser l'intégrité et la disponibilité de nos produits en temps réel.
        </p>

        <!-- Bouton yo - Gentle rise -->
        <div class="anim-buttons flex flex-col xs:flex-row gap-4 sm:gap-6 justify-center lg:justify-start w-full sm:w-auto px-4 sm:px-0">
          <a href="page/acceuil.php" class="btn-primary group bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 px-10 sm:px-14 rounded-full shadow-lg flex items-center justify-center gap-3 text-sm sm:text-base whitespace-nowrap tracking-wide">
            <span>Visiter</span>
            <i class="fas fa-arrow-right text-sm"></i>
          </a>
          <a href="inscription.php" class="btn-secondary bg-white/5 hover:bg-white/10 text-white font-semibold py-4 px-10 sm:px-14 rounded-full border border-white/30 flex items-center justify-center text-sm sm:text-base whitespace-nowrap tracking-wide">
            Devni Machann
          </a>
        </div>
      </div>

      <script>
        function triggerHeroAnimation() {
          const hero = document.getElementById('hero-content');
          hero.classList.remove('animate-now');
          void hero.offsetWidth;
          hero.classList.add('animate-now');
        }

        document.addEventListener('DOMContentLoaded', () => {
          setTimeout(triggerHeroAnimation, 100);
          setInterval(triggerHeroAnimation, 12000);
        });
      </script>

      <!-- Image Hero -->
      <div class="w-full lg:w-[45%] relative flex justify-center items-center order-1 lg:order-2 mb-8 lg:mb-0 px-4 sm:px-0">
        <div class="relative w-full max-w-[300px] sm:max-w-[350px] lg:max-w-full animate-float">
          <img src="assets/img/plop12345.png" alt="Shop" class="w-full h-auto object-contain max-w-full">
          <div class="absolute -z-10 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90%] h-[90%] bg-blue-500/15 blur-[100px] rounded-full"></div>
        </div>
      </div>
    </div>

    <div class="absolute bottom-0 left-0 w-full h-[20%] bg-gradient-to-t from-gray-50 to-transparent pointer-events-none"></div>
  </section>

  <!-- SECTION À PROPOS -->
  <section class="py-16 sm:py-20 bg-gray-50 w-full overflow-hidden">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 w-full max-w-full">
      <div class="flex flex-col md:flex-row items-center gap-12 md:gap-16 w-full">
        <div class="w-full md:w-1/2 order-1 px-2 sm:px-0 flex justify-center">
          <div class="relative">
            <div class="video-gif-container">
              <img src="assets/olop.gif" alt="À propos de nous - Démonstration"
                onerror="this.src='assets/img/E-COMMERCE.jpeg'; this.onerror=null;"
                class="w-full h-auto object-cover">
            </div>
            <div class="screen-reflection"></div>
            <!-- <div class="absolute bottom-4 right-4 bg-blue-600/90 text-white text-xs font-medium px-4 py-2 rounded-full shadow-lg backdrop-blur-sm">
              <i class="fas fa-play mr-2"></i> Demo
            </div> -->
          </div>
        </div>
        <div class="w-full md:w-1/2 space-y-6 sm:space-y-8 order-2 text-center md:text-left px-2 sm:px-0">
          <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-800 break-words leading-tight">
            À propos de <span class="text-blue-600">Notre Entreprise</span>
          </h2>
          <div class="space-y-4 text-gray-600 leading-relaxed text-sm sm:text-base">
            <p>
              Dans un paysage économique en pleine mutation, Le Stock Entreprise s'affirme comme le moteur
              de la transformation numérique au sein de la ville du Cap-Haïtien et au-delà.
            </p>
            <p>
              Plus qu'une simple plateforme de vente en ligne, notre projet est une réponse technologique concrète
              aux défis logistiques et organisationnels rencontrés par les entrepreneurs locaux.
            </p>
            <p class="text-gray-500 italic">
              Nous croyons fermement que la technologie ne doit pas être un luxe, mais un outil d'émancipation économique accessible à tous.
            </p>
          </div>
          <a href="#contact" class="inline-block bg-blue-600 text-white px-8 py-3 rounded-full font-medium hover:bg-blue-700 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
            En savoir plus
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- SECTION COMMENT ÇA MARCHE -->
  <section class="py-20 sm:py-24 bg-gray-900 overflow-hidden">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 w-full max-w-full">
      <div class="text-center mb-16 sm:mb-20">
        <span class="inline-block px-4 py-1.5 mb-4 text-xs font-medium tracking-wider text-blue-600 uppercase bg-white/90 rounded-full shadow-sm">
          Processus Simple
        </span>
        <h2 class="text-3xl sm:text-4xl md:text-5xl font-bold text-white mb-6">
          Comment Ça Marche ?
        </h2>
        <p class="text-blue-100 text-base sm:text-lg max-w-2xl mx-auto leading-relaxed">
          Trois étapes simples pour commencer à gagner avec notre programme d'affiliation.
        </p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 lg:gap-10">
        <div class="relative bg-white rounded-2xl p-8 sm:p-10 shadow-xl hover-lift">
          <div class="absolute -top-4 left-8 w-10 h-10 bg-blue-600 text-white rounded-xl flex items-center justify-center font-bold shadow-lg">01</div>
          <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center mb-6 mt-4">
            <i class="fas fa-user-plus text-blue-600 text-2xl"></i>
          </div>
          <h3 class="text-xl font-bold text-gray-900 mb-4">Inscrivez-Vous</h3>
          <p class="text-gray-600 leading-relaxed">
            Créez votre compte gratuitement en quelques minutes. Aucune expérience requise.
          </p>
        </div>
        <div class="relative bg-white rounded-2xl p-8 sm:p-10 shadow-xl hover-lift">
          <div class="absolute -top-4 left-8 w-10 h-10 bg-blue-600 text-white rounded-xl flex items-center justify-center font-bold shadow-lg">02</div>
          <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center mb-6 mt-4">
            <i class="fas fa-share-alt text-blue-600 text-2xl"></i>
          </div>
          <h3 class="text-xl font-bold text-gray-900 mb-4">Partagez les Produits</h3>
          <p class="text-gray-600 leading-relaxed">
            Utilisez vos liens d'affiliation uniques pour promouvoir nos produits sur vos canaux.
          </p>
        </div>
        <div class="relative bg-white rounded-2xl p-8 sm:p-10 shadow-xl hover-lift">
          <div class="absolute -top-4 left-8 w-10 h-10 bg-blue-600 text-white rounded-xl flex items-center justify-center font-bold shadow-lg">03</div>
          <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center mb-6 mt-4">
            <i class="fas fa-wallet text-blue-600 text-2xl"></i>
          </div>
          <h3 class="text-xl font-bold text-gray-900 mb-4">Gagnez des Revenus</h3>
          <p class="text-gray-600 leading-relaxed">
            Recevez des commissions sur chaque vente générée grâce à vos recommandations.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- SECTION AVANTAGES -->
  <section class="py-20 sm:py-24 bg-gray-50 w-full overflow-hidden">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 w-full max-w-full">
      <div class="text-center mb-16 sm:mb-20">
        <span class="inline-block px-4 py-1.5 mb-4 text-xs font-medium tracking-wider text-blue-600 uppercase bg-blue-50 rounded-full">
          Pourquoi Nous Choisir
        </span>
        <h2 class="text-3xl sm:text-4xl md:text-5xl font-bold text-gray-900 mb-6">Avantages du Programme</h2>
        <div class="w-24 h-1 bg-blue-500 mx-auto rounded-full"></div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 lg:gap-10">
        <div class="bg-white rounded-2xl p-8 flex items-start gap-5 hover-lift shadow-sm border border-gray-100">
          <div class="w-14 h-14 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0">
            <i class="fas fa-headset text-blue-600 text-xl"></i>
          </div>
          <div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Support Dédié</h3>
            <p class="text-gray-600 leading-relaxed text-sm">
              Une équipe à votre service, disponible 7j/7 pour répondre à vos questions.
            </p>
          </div>
        </div>
        <div class="bg-white rounded-2xl p-8 flex items-start gap-5 hover-lift shadow-sm border border-gray-100">
          <div class="w-14 h-14 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0">
            <i class="fas fa-bolt text-blue-600 text-xl"></i>
          </div>
          <div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Achat Rapide</h3>
            <p class="text-gray-600 leading-relaxed text-sm">
              Achetez vos produits en moins de 48 heures via plusieurs méthodes de paiement.
            </p>
          </div>
        </div>
        <div class="bg-white rounded-2xl p-8 flex items-start gap-5 hover-lift shadow-sm border border-gray-100">
          <div class="w-14 h-14 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0">
            <i class="fas fa-gift text-blue-600 text-xl"></i>
          </div>
          <div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Bonus & Récompenses</h3>
            <p class="text-gray-600 leading-relaxed text-sm">
              Profitez de bonus mensuels et de récompenses exclusives pour les meilleurs affiliés.
            </p>
          </div>
        </div>
        <div class="bg-white rounded-2xl p-8 flex items-start gap-5 hover-lift shadow-sm border border-gray-100">
          <div class="w-14 h-14 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0">
            <i class="fas fa-chart-line text-blue-600 text-xl"></i>
          </div>
          <div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Tableau de Bord</h3>
            <p class="text-gray-600 leading-relaxed text-sm">
              Suivez vos commandes en temps réel avec des sections détaillées et claires.
            </p>
          </div>
        </div>
        <div class="bg-white rounded-2xl p-8 flex items-start gap-5 hover-lift shadow-sm border border-gray-100">
          <div class="w-14 h-14 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0">
            <i class="fas fa-bullhorn text-blue-600 text-xl"></i>
          </div>
          <div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Outils de Promotion</h3>
            <p class="text-gray-600 leading-relaxed text-sm">
              Contactez-nous pour poster et promoter vos produits à un prix dérisoire.
            </p>
          </div>
        </div>
        <div class="bg-white rounded-2xl p-8 flex items-start gap-5 hover-lift shadow-sm border border-gray-100">
          <div class="w-14 h-14 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0">
            <i class="fas fa-shield-alt text-blue-600 text-xl"></i>
          </div>
          <div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Marchand & Affiliation</h3>
            <p class="text-gray-600 leading-relaxed text-sm">
              Devenez marchand ou affilié selon vos objectifs sur notre plateforme.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="bg-gray-900 text-white py-12 w-full">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <p class="text-gray-400 text-sm">
        © 2026 Le Stock Entreprise. Tous droits réservés.
      </p>
    </div>
  </footer>

</body>

</html>