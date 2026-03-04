<?php
session_start();

// 1. Rele koneksyon baz de done a (Ajiste chemen an si l pa sa)
require_once dirname(__DIR__) . '/config/db.php';

$error = "";
$success = "";

// Si gen paramèt "success" nan URL la (lè moun nan sot enskri)
if (isset($_GET['success']) && $_GET['success'] === 'registered') {
    $success = "Kont kreye avèk siksè! Konekte kounye a.";
}

// ===== TRAITEMENT LOGIN =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Tanpri ranpli tout chan yo.";
    } else {
        try {
            // 2. Chanje 'users' pou 'clients'
            $stmt = $pdo->prepare("SELECT * FROM clients WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // 3. Chanje 'password' pou 'mot_de_passe' jan sa ye nan phpMyAdmin ou a
            if ($user && password_verify($password, $user['mot_de_passe'])) {

                // Kreye sesyon yo
                $_SESSION['user_id'] = $user['id'];
                // Nan baz de done w lan se 'nom' ak 'prenom'
                $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];

                // 4. Redireksyon sou paj akèy (index.php)
                header("Location: ../index.php");
                exit();
            } else {
                $error = "Imèl oswa modpas pa kòrèk.";
            }
        } catch (PDOException $e) {
            // Si gen yon erè teknik, n ap afiche l pou n ka debug
            $error = "Erè nan baz de done: " . $e->getMessage();
        }
    }
}
require_once dirname(__DIR__) . '/includes/header.php';
?>

<!DOCTYPE html>
<html lang="ht">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Le Stock</title>

    <link rel="stylesheet" href="/le-stock/css/style.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
        }

        /* Gradient la pou l kole nèt ak tout rebò yo */
        .bg-image-side {
            background: linear-gradient(rgba(118, 75, 162, 0.7), rgba(102, 126, 234, 0.7)),
                url('/le-stock/assets/img/stock.png') center/cover no-repeat;
        }

        /* Pou asire ke de pati yo toujou pran menm wotè */
        .full-height {
            min-height: 100vh;
        }
    </style>
</head>

<body class="bg-white overflow-x-hidden">

    <div class="flex flex-col lg:flex-row w-full full-height">

        <div class="w-full lg:w-1/2 flex items-center justify-center p-6 lg:p-12 order-2 lg:order-1">
            <div class="w-full max-w-md">

                <div class="flex justify-center mb-8">
                    <div class="w-16 h-16 bg-black rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-shopping-bag text-white text-3xl"></i>
                    </div>
                </div>

                <h1 class="text-3xl font-bold text-center text-gray-900 mb-2">Bienvenue !</h1>
                <p class="text-center text-gray-500 mb-10">Connectez-vous pour accéder à votre espace client</p>

                <form class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Adresse email</label>
                        <div class="relative">
                            <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="email" placeholder="jamesonolizard7@gmail.com"
                                class="w-full pl-12 pr-4 py-3.5 bg-blue-50/50 border-none rounded-xl focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Mot de passe</label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="password" id="pass" placeholder="••••••••"
                                class="w-full pl-12 pr-12 py-3.5 bg-blue-50/50 border-none rounded-xl focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                            <button type="button" onclick="togglePassword('pass', this)" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" class="rounded border-gray-300 text-black focus:ring-black">
                            <span class="text-gray-600">Se souvenir de moi</span>
                        </label>
                        <a href="#" class="font-bold text-black hover:underline">Mot de passe oublié ?</a>
                    </div>

                    <button class="w-full bg-black text-white py-4 rounded-xl font-bold hover:bg-gray-800 transition-all shadow-lg transform active:scale-[0.98]">
                        Se connecter
                    </button>
                </form>

                <div class="relative my-8 flex items-center justify-center">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-100"></div>
                    </div>
                    <span class="relative px-4 bg-white text-gray-400 text-xs uppercase tracking-widest">Ou</span>
                </div>

                <div class="space-y-3">
                    <button class="w-full flex items-center justify-center gap-3 py-3 border border-gray-200 rounded-xl hover:bg-gray-50 transition font-medium">
                        <i class="fab fa-google text-red-500"></i> <span class="text-sm">Continuer avec Google</span>
                    </button>
                    <button class="w-full flex items-center justify-center gap-3 py-3 border border-gray-200 rounded-xl hover:bg-gray-50 transition font-medium">
                        <i class="fab fa-facebook text-blue-600"></i> <span class="text-sm">Continuer avec Facebook</span>
                    </button>
                </div>

                <p class="text-center mt-8 text-sm text-gray-500">
                    Pas encore de compte ? <a href="inscription.php" class="font-bold text-black hover:underline">Créer un compte gratuitement</a>
                </p>
            </div>
        </div>

        <div class="w-full lg:w-1/2 bg-image-side flex flex-col justify-center px-10 lg:px-20 text-white order-1 lg:order-2 py-12">
            <div class="max-w-xl">
                <h2 class="text-4xl lg:text-5xl font-bold mb-6 leading-tight">Votre boutique en ligne de confiance</h2>
                <p class="text-lg opacity-90 mb-10">Des milliers de produits, des millions de clients satisfaits.</p>

                <div class="grid grid-cols-3 gap-6 mb-12">
                    <div>
                        <span class="block text-4xl font-bold">500K+</span>
                        <span class="text-xs uppercase tracking-widest opacity-70">Clients</span>
                    </div>
                    <div>
                        <span class="block text-4xl font-bold">50K+</span>
                        <span class="text-xs uppercase tracking-widest opacity-70">Produits</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-1">
                            <i class="fas fa-star text-yellow-400 text-2xl"></i>
                            <span class="text-4xl font-bold">4.9</span>
                        </div>
                        <span class="text-xs uppercase tracking-widest opacity-70 text-nowrap">Satisfaction</span>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-white/10 backdrop-blur-md rounded-xl flex items-center justify-center border border-white/20">
                            <i class="fas fa-shield-alt text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-bold">100% Sécurisé</h4>
                            <p class="text-sm opacity-80 leading-tight">Vos données sont protégées avec SSL</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-white/10 backdrop-blur-md rounded-xl flex items-center justify-center border border-white/20">
                            <i class="fas fa-truck text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-nowrap">Livraison Express</h4>
                            <p class="text-sm opacity-80 leading-tight">Recevez vos commandes en 24-48h</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-white/10 backdrop-blur-md rounded-xl flex items-center justify-center border border-white/20">
                            <i class="fas fa-credit-card text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-bold">Paiement Flexible</h4>
                            <p class="text-sm opacity-80 leading-tight">CB, PayPal, virement - 3x sans frais</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        function togglePassword(id, btn) {
            const input = document.getElementById(id);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
    </script>
</body>

</html>