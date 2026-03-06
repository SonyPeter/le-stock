<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Le Stock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .glass-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }

        .glass-image-zone {
            background: rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(8px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }

        .glass-form-zone {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(12px);
        }

        .glass-input {
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: all 0.3s ease;
        }

        .glass-input:focus {
            background: rgba(255, 255, 255, 0.95);
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        .glass-btn {
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .glass-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.4);
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-0 sm:p-4 relative overflow-x-hidden bg-slate-900">

    <div class="fixed inset-0 z-0">
        <img src="/le-stock/assets/img/stock6.png" alt="Background" class="w-full h-full object-cover opacity-40">
        <div class="absolute inset-0 bg-black/40"></div>
    </div>

    <div class="relative z-10 w-full max-w-6xl mx-auto">
        <div class="glass-container rounded-none sm:rounded-[2.5rem] overflow-hidden flex flex-col lg:flex-row min-h-screen sm:min-h-[700px]">

            <div class="lg:w-5/12 glass-image-zone relative flex flex-col justify-center items-center p-8 text-center">
                <div class="relative z-10">
                    <h2 class="text-3xl lg:text-5xl font-black text-white mb-2 italic tracking-tighter">LE STOCK</h2>
                    <h3 class="text-xl lg:text-2xl font-bold text-yellow-300 mb-8 uppercase tracking-widest">Inscris-toi</h3>
                    <img src="/le-stock/assets/img/anscrit.png" alt="Shopping" class="w-64 h-auto mx-auto drop-shadow-2xl">
                </div>
            </div>

            <div class="lg:w-7/12 glass-form-zone p-6 sm:p-12 flex flex-col justify-center">
                <div class="max-w-md mx-auto w-full">
                    <div class="text-center mb-8">
                        <h1 class="text-3xl font-black text-slate-800 mb-2">Bienvenue!</h1>
                        <p class="text-slate-600 text-sm font-medium">Crée ton compte en quelques secondes</p>
                    </div>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="bg-red-500/20 backdrop-blur-md border border-red-500/50 text-red-700 p-4 mb-6 rounded-2xl text-sm font-bold">
                            <i class="fas fa-exclamation-circle mr-2"></i><?= $_SESSION['error'];
                                                                            unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="traitement_inscription.php" class="space-y-4" id="signupForm">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Nom</label>
                                <input type="text" name="lastname" required class="w-full px-4 py-3.5 glass-input rounded-2xl text-sm outline-none font-bold text-slate-700" placeholder="Doe">
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Prénom</label>
                                <input type="text" name="firstname" required class="w-full px-4 py-3.5 glass-input rounded-2xl text-sm outline-none font-bold text-slate-700" placeholder="John">
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Email</label>
                            <input type="email" name="email" required class="w-full px-4 py-3.5 glass-input rounded-2xl text-sm outline-none font-bold text-slate-700" placeholder="john@example.com">
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Adresse Complète</label>
                            <input type="text" name="address" required class="w-full px-4 py-3.5 glass-input rounded-2xl text-sm outline-none font-bold text-slate-700" placeholder="No, Rue, Ville, Pays">
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Téléphone</label>
                            <input type="tel" name="phone" required class="w-full px-4 py-3.5 glass-input rounded-2xl text-sm outline-none font-bold text-slate-700" placeholder="+509 0000 0000">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Mot de passe</label>
                                <input type="password" name="password" id="password" required class="w-full px-4 py-3.5 glass-input rounded-2xl text-sm outline-none font-bold text-slate-700" placeholder="••••••••">
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Confirmer</label>
                                <input type="password" name="confirm_password" id="confirm_password" required class="w-full px-4 py-3.5 glass-input rounded-2xl text-sm outline-none font-bold text-slate-700" placeholder="••••••••">
                            </div>
                        </div>

                        <button type="submit" class="w-full glass-btn text-white py-4 rounded-2xl font-black text-sm uppercase tracking-[0.2em] mt-6 active:scale-95 transition-all shadow-xl">
                            S'inscrire Maintenant
                        </button>
                    </form>

                    <p class="mt-8 text-center text-sm font-medium text-slate-600">
                        Déjà un compte?
                        <a href="login.php" class="text-indigo-600 font-black hover:underline ml-1">Se connecter</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            const p = document.getElementById('password').value;
            const cp = document.getElementById('confirm_password').value;
            if (p !== cp) {
                e.preventDefault();
                alert('Attention: Les mots de passe ne sont pas identiques!');
            }
        });
    </script>
</body>

</html>