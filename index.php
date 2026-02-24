<?php
include 'includes/header.php';
?>

<body>
<section class="relative h-[calc(100vh-80px)] min-h-[500px] flex items-center justify-center overflow-hidden">
  <div class="absolute inset-0 z-0">
    <img src="assets/img/E-COMMERCE.jpeg" alt="Antrepriz Stock" class="w-full h-full object-cover">
    <div class="absolute inset-0 bg-gradient-to-r from-black/70 to-black/40"></div>
  </div>

  <div class="relative z-10 text-center px-4 max-w-5xl">
    <span class="inline-block px-4 py-1 mb-4 text-sm font-semibold tracking-wider text-blue-400 uppercase bg-blue-900/30 rounded-full border border-blue-500/30">
      Bienvenue chez 
    </span>
    <h1 class="text-4xl md:text-7xl font-extrabold text-white mb-6 leading-tight drop-shadow-md">
      Le Stock <span class="text-blue-500">Entreprise</span>
    </h1>
    <p class="text-lg md:text-2xl text-gray-200 mb-10 max-w-3xl mx-auto leading-relaxed">
      "Une plateforme complète pour gérer vos vendeurs et votre stock en temps réel."
    </p>
    <div class="flex flex-col sm:flex-row gap-5 justify-center">
      <a href="boutik.php" class="group bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-10 rounded-full transition-all duration-300 shadow-[0_0_20px_rgba(37,99,235,0.4)] flex items-center justify-center gap-2">
        Visiter 
        <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
      </a>
      <a href="inscription.php" class="bg-white/10 hover:bg-white/20 text-white backdrop-blur-md font-bold py-4 px-10 rounded-full border border-white/30 transition-all duration-300 flex items-center justify-center">
        Devni Machann
      </a>
    </div>
  </div>

  <div class="absolute bottom-0 left-0 w-full h-24 bg-gradient-to-t from-gray-50 to-transparent"></div>
  <div class="lg:w-1/2 relative flex justify-center">
      <div class="relative w-full max-w-md animate-float">
        <img src=".\assets\img\SHOP.png" alt="Produit"
             class="w-full h-auto drop-shadow-[0_20px_50px_rgba(59,130,246,0.5)]">
        
        <div class="absolute -z-10 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-blue-500/20 blur-[100px] rounded-full"></div>
      </div>
    </div>

</div>
<div class="absolute top-[19%] left-[59%] z-50 w-[35%] md:w-[25%] lg:w-[20%]">
  <div class="relative w-full animate-float">
    
    <img src="assets/img/vendeuse.png" alt="Produit"
         class="w-full h-auto drop-shadow-[0_20px_50px_rgba(59,130,246,0.5)]">
    
    <div class="absolute -z-10 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full h-full bg-blue-500/10 blur-[80px] rounded-full"></div>
    
  </div>
</div>
</section>

</body>

</html>