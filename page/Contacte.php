<?php

// Kontak Page - LE-STOCK
session_start();

// Jere soumisyon fòm la
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars($_POST['name'] ?? '');
    $email = htmlspecialchars($_POST['email'] ?? '');
    $subject = htmlspecialchars($_POST['subject'] ?? '');
    $message = htmlspecialchars($_POST['message'] ?? '');
    
    // Validasyon senp
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Veuillez entrer une adresse email valide.";
    } else {
        // Nan yon vèsyon reyèl, ou ta voye email la isit la
        $success = "Votre message a été envoyé avec succès! Nous vous répondrons dans les plus brefs délais.";
    }
}

// FAQ items
$faqs = [
    [
        'question' => 'Comment passer une commande ?',
        'answer' => 'Parcourez notre catalogue, ajoutez vos articles au panier et suivez le processus de paiement sécurisé.'
    ],
    [
        'question' => 'Quels sont les délais de livraison ?',
        'answer' => 'Nous livrons en 2-5 jours ouvrables dans toute Haïti. Livraison express disponible.'
    ],
    [
        'question' => 'Comment suivre ma commande ?',
        'answer' => 'Vous recevrez un numéro de suivi par email dès l\'expédition de votre commande.'
    ],
    [
        'question' => 'Puis-je retourner un produit ?',
        'answer' => 'Oui, retours gratuits sous 30 jours pour tout article non utilisé avec son emballage d\'origine.'
    ],
    [
        'question' => 'Quels modes de paiement acceptez-vous ?',
        'answer' => 'Nous acceptons les cartes de crédit, Moncash, PayPal et le paiement à la livraison.'
    ],
    [
        'question' => 'Livrez-vous à l\'international ?',
        'answer' => 'Actuellement, nous livrons uniquement en Haïti. Livraison internationale bientôt disponible.'
    ]
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - LE-STOCK</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2563eb',
                        secondary: '#7c3aed',
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50">

    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                <!-- Logo -->
                <a href="index.php" class="flex items-center gap-2 sm:gap-3">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-blue-600 to-purple-600 rounded-xl flex items-center justify-center">
                        <span class="text-white text-xl sm:text-2xl">📦</span>
                    </div>
                    <div class="hidden sm:block">
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">LE-STOCK</h1>
                        <p class="text-xs text-gray-500">Votre boutique en ligne</p>
                    </div>
                </a>

                <!-- Navigation Desktop -->
                <nav class="hidden md:flex items-center gap-6">
                    <a href="index.php" class="text-gray-600 hover:text-blue-600 transition-colors">Accueil</a>
                    <a href="boutique.php" class="text-gray-600 hover:text-blue-600 transition-colors">Produits</a>
                    <a href="apropos.php" class="text-gray-600 hover:text-blue-600 transition-colors">À propos</a>
                    <a href="contact.php" class="text-blue-600 font-medium">Contact</a>
                </nav>

                <!-- Mobile Menu Button -->
                <button class="md:hidden p-2 text-gray-600 hover:text-gray-900" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t">
            <div class="px-4 py-3 space-y-2">
                <a href="index.php" class="block py-2 text-gray-600 hover:text-blue-600">Accueil</a>
                <a href="boutique.php" class="block py-2 text-gray-600 hover:text-blue-600">Produits</a>
                <a href="apropos.php" class="block py-2 text-gray-600 hover:text-blue-600">À propos</a>
                <a href="contact.php" class="block py-2 text-blue-600 font-medium">Contact</a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="bg-gradient-to-br from-blue-600 to-purple-700 text-white py-12 sm:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto">
                <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold mb-3 sm:mb-4">Contactez-nous</h1>
                <p class="text-lg sm:text-xl text-white/90">
                    Notre équipe est là pour répondre à toutes vos questions
                </p>
            </div>
        </div>
    </section>

    <!-- Contenu principal -->
    <section class="py-12 sm:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
                
                <!-- Colonne gauche - Informations de contact -->
                <div class="lg:col-span-1 space-y-4 sm:space-y-6">
                    
                    <!-- Carte d'information -->
                    <div class="bg-white rounded-xl shadow-sm p-5 sm:p-6">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 sm:mb-6">Informations de Contact</h2>

                        <!-- Adresse -->
                        <div class="flex items-start gap-3 sm:gap-4 mb-5 sm:mb-6">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-map-marker-alt text-blue-600 text-lg sm:text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 mb-1">Adresse</h3>
                                <p class="text-gray-500 text-xs sm:text-sm leading-relaxed">
                                    123 Rue Commerce<br>
                                    Port-au-Prince, Haïti<br>
                                    HT-6110
                                </p>
                            </div>
                        </div>

                        <!-- Téléphone -->
                        <div class="flex items-start gap-3 sm:gap-4 mb-5 sm:mb-6">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-phone text-green-600 text-lg sm:text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 mb-1">Téléphone</h3>
                                <p class="text-gray-500 text-xs sm:text-sm leading-relaxed">
                                    +509 1234 5678<br>
                                    +509 9876 5432
                                </p>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="flex items-start gap-3 sm:gap-4 mb-5 sm:mb-6">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-envelope text-purple-600 text-lg sm:text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 mb-1">Email</h3>
                                <p class="text-gray-500 text-xs sm:text-sm leading-relaxed">
                                    contact@le-stock.com<br>
                                    support@le-stock.com
                                </p>
                            </div>
                        </div>

                        <!-- Horaires -->
                        <div class="flex items-start gap-3 sm:gap-4">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-clock text-orange-600 text-lg sm:text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 mb-1">Horaires d'ouverture</h3>
                                <p class="text-gray-500 text-xs sm:text-sm leading-relaxed">
                                    Lundi - Vendredi: 8h - 18h<br>
                                    Samedi: 9h - 15h<br>
                                    Dimanche: Fermé
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Réseaux sociaux -->
                    <div class="bg-white rounded-xl shadow-sm p-5 sm:p-6">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-3 sm:mb-4">Suivez-nous</h3>
                        <div class="flex gap-2 sm:gap-3">
                            <a href="#" class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-600 rounded-lg flex items-center justify-center text-white hover:bg-blue-700 transition-colors">
                                <i class="fab fa-facebook-f text-lg sm:text-xl"></i>
                            </a>
                            <a href="#" class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-purple-600 to-pink-600 rounded-lg flex items-center justify-center text-white hover:opacity-90 transition-opacity">
                                <i class="fab fa-instagram text-lg sm:text-xl"></i>
                            </a>
                            <a href="#" class="w-10 h-10 sm:w-12 sm:h-12 bg-sky-500 rounded-lg flex items-center justify-center text-white hover:bg-sky-600 transition-colors">
                                <i class="fab fa-twitter text-lg sm:text-xl"></i>
                            </a>
                            <a href="#" class="w-10 h-10 sm:w-12 sm:h-12 bg-green-500 rounded-lg flex items-center justify-center text-white hover:bg-green-600 transition-colors">
                                <i class="fab fa-whatsapp text-lg sm:text-xl"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Image -->
                    <div class="hidden lg:block bg-white rounded-xl shadow-sm overflow-hidden">
                        <img 
                            src="https://images.unsplash.com/photo-1553775282-20af80779df7?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxjdXN0b21lciUyMHNlcnZpY2UlMjBzdXBwb3J0fGVufDF8fHx8MTc3Mjk5NDY2MHww&ixlib=rb-4.1.0&q=80&w=1080" 
                            alt="Customer Service" 
                            class="w-full h-48 sm:h-64 object-cover"
                            onerror="this.src='/le-stock/assets/img/placeholder.jpg'"
                        >
                    </div>
                </div>

                <!-- Colonne droite - Formulaire de contact -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-sm p-5 sm:p-6 lg:p-8">
                        <div class="mb-4 sm:mb-6">
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2">Envoyez-nous un message</h2>
                            <p class="text-gray-500 text-sm sm:text-base">
                                Remplissez le formulaire ci-dessous et nous vous répondrons dans les plus brefs délais.
                            </p>
                        </div>

                        <!-- Messages -->
                        <?php if ($success): ?>
                            <div class="mb-4 sm:mb-6 p-3 sm:p-4 bg-green-50 border border-green-200 rounded-lg flex items-center gap-2 sm:gap-3 text-green-700">
                                <i class="fas fa-check-circle text-lg"></i>
                                <span class="text-sm font-medium"><?= $success ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="mb-4 sm:mb-6 p-3 sm:p-4 bg-red-50 border border-red-200 rounded-lg flex items-center gap-2 sm:gap-3 text-red-700">
                                <i class="fas fa-exclamation-circle text-lg"></i>
                                <span class="text-sm font-medium"><?= $error ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Formulaire -->
                        <form method="POST" action="" class="space-y-4 sm:space-y-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">
                                        Nom complet <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        id="name" 
                                        name="name" 
                                        required
                                        class="w-full px-3 sm:px-4 py-2.5 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm sm:text-base"
                                        placeholder="Jean Dupont"
                                    >
                                </div>
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">
                                        Email <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="email" 
                                        id="email" 
                                        name="email" 
                                        required
                                        class="w-full px-3 sm:px-4 py-2.5 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm sm:text-base"
                                        placeholder="jean@example.com"
                                    >
                                </div>
                            </div>

                            <div>
                                <label for="subject" class="block text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">
                                    Sujet <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="subject" 
                                    name="subject" 
                                    required
                                    class="w-full px-3 sm:px-4 py-2.5 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm sm:text-base"
                                    placeholder="Question sur ma commande"
                                >
                            </div>

                            <div>
                                <label for="message" class="block text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">
                                    Message <span class="text-red-500">*</span>
                                </label>
                                <textarea 
                                    id="message" 
                                    name="message" 
                                    rows="4" 
                                    required
                                    class="w-full px-3 sm:px-4 py-2.5 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-none text-sm sm:text-base"
                                    placeholder="Décrivez votre demande en détail..."
                                ></textarea>
                            </div>

                            <button 
                                type="submit" 
                                class="w-full sm:w-auto bg-blue-600 text-white px-6 sm:px-8 py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors flex items-center justify-center gap-2 text-sm sm:text-base"
                            >
                                <i class="fas fa-paper-plane"></i>
                                Envoyer le message
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section FAQ rapide -->
    <section class="bg-white py-12 sm:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-8 sm:mb-12">
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-3 sm:mb-4">Questions Fréquentes</h2>
                <p class="text-gray-500 max-w-2xl mx-auto text-sm sm:text-base">
                    Trouvez rapidement des réponses aux questions les plus courantes
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                <?php foreach ($faqs as $index => $faq): ?>
                    <div class="bg-gray-50 rounded-xl p-4 sm:p-6 hover:shadow-md transition-shadow">
                        <h3 class="text-sm sm:text-base font-semibold text-blue-600 mb-2 sm:mb-3"><?= htmlspecialchars($faq['question']) ?></h3>
                        <p class="text-xs sm:text-sm text-gray-500 leading-relaxed"><?= htmlspecialchars($faq['answer']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Carte (optionnelle) -->
    <section class="bg-gray-100 py-12 sm:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-6 sm:mb-8">
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">Notre Localisation</h2>
                <p class="text-gray-500 text-sm sm:text-base">Visitez notre magasin physique</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="w-full h-64 sm:h-80 lg:h-96 bg-gray-200 flex items-center justify-center">
                    <div class="text-center px-4">
                        <i class="fas fa-map-marker-alt text-4xl sm:text-5xl text-gray-400 mb-3 sm:mb-4"></i>
                        <p class="text-gray-500 font-medium text-sm sm:text-base">Carte Google Maps à intégrer ici</p>
                        <p class="text-xs sm:text-sm text-gray-400 mt-2">
                            123 Rue Commerce, Port-au-Prince, Haïti
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-10 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8 mb-8 sm:mb-12">
                
                <!-- À propos -->
                <div>
                    <h3 class="text-lg sm:text-xl font-bold mb-3 sm:mb-4">LE-STOCK</h3>
                    <p class="text-gray-400 text-xs sm:text-sm leading-relaxed">
                        Votre boutique en ligne de confiance pour tous vos besoins en Haïti.
                    </p>
                </div>

                <!-- Liens rapides -->
                <div>
                    <h4 class="text-base sm:text-lg font-semibold mb-3 sm:mb-4">Liens Rapides</h4>
                    <ul class="space-y-2 text-xs sm:text-sm">
                        <li><a href="index.php" class="text-gray-400 hover:text-white transition-colors">Accueil</a></li>
                        <li><a href="boutique.php" class="text-gray-400 hover:text-white transition-colors">Produits</a></li>
                        <li><a href="apropos.php" class="text-gray-400 hover:text-white transition-colors">À propos</a></li>
                        <li><a href="contact.php" class="text-gray-400 hover:text-white transition-colors">Contact</a></li>
                    </ul>
                </div>

                <!-- Service client -->
                <div>
                    <h4 class="text-base sm:text-lg font-semibold mb-3 sm:mb-4">Service Client</h4>
                    <ul class="space-y-2 text-xs sm:text-sm">
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">FAQ</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Livraison</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Retours</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">CGV</a></li>
                    </ul>
                </div>

                <!-- Newsletter -->
                <div>
                    <h4 class="text-base sm:text-lg font-semibold mb-3 sm:mb-4">Newsletter</h4>
                    <p class="text-gray-400 text-xs sm:text-sm mb-3">
                        Inscrivez-vous pour recevoir nos offres
                    </p>
                    <form class="flex gap-2">
                        <input 
                            type="email" 
                            placeholder="Votre email" 
                            class="flex-1 px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-xs sm:text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                        <button type="submit" class="bg-blue-600 text-white px-3 sm:px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-xs sm:text-sm font-medium">
                            OK
                        </button>
                    </form>
                </div>
            </div>

            <div class="border-t border-gray-800 pt-6 sm:pt-8 text-center text-xs sm:text-sm text-gray-400">
                <p>&copy; 2026 LE-STOCK. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

</body>
</html>