<?php
include 'includes/header.php';

?>

<body class="bg-gray-50 overflow-x-hidden">
  <!-- HERO SECTION -->
  <section class="relative min-h-[600px] lg:min-h-screen flex items-center justify-center overflow-hidden py-20 lg:py-0 w-full">

    <!-- Background image avec max-width 100% -->
    <div class="absolute inset-0 z-0 w-full h-full">
      <img src="assets/img/E-COMMERCE.jpeg" alt="Antrepriz Stock"
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
          <img src="assets/img/SHOP.png" alt="Shop"
            class="w-full h-auto object-contain max-w-full">
          <div class="absolute -z-10 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[80%] h-[80%] bg-blue-500/20 blur-[80px] rounded-full"></div>
        </div>
      </div>
    </div>

    <div class="absolute bottom-0 left-0 w-full h-[15%] bg-gradient-to-t from-gray-50 to-transparent pointer-events-none"></div>
  </section>

  <!-- SECTION À PROPOS -->
  <section class="py-12 sm:py-16 bg-gray-50 w-full overflow-hidden">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 w-full max-w-full">
      <div class="flex flex-col md:flex-row items-center gap-8 md:gap-12 w-full">

        <!-- Image -->
        <div class="w-full md:w-1/2 order-1 px-2 sm:px-0">
          <div class="relative w-full max-w-md mx-auto md:max-w-none">
            <img src="assets/E-COMMERCE.gif" alt="À propos de nous"
              onerror="this.src='assets/img/E-COMMERCE.jpeg'; this.onerror=null;"
              class="rounded-lg shadow-2xl object-cover w-full h-auto max-h-[300px] sm:max-h-[400px] md:max-h-[450px] transform hover:scale-105 transition duration-500 max-w-full">
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
  </style>
  <?php
  include 'includes/footer.php';
  ?>

</body>

</html>