<?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';

$error = "";
$success = "";

// 1. LOJIK TRAITEMENT (PHP)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $lastname  = htmlspecialchars($_POST['lastname']);
    $firstname = htmlspecialchars($_POST['firstname']);
    $email     = htmlspecialchars($_POST['email']);
    $address   = htmlspecialchars($_POST['address']);
    $phone     = htmlspecialchars($_POST['phone']);
    $password  = $_POST['password'];
    $confirm   = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $error = "Modpas yo pa parèy!";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Imèl sa a deja gen yon kont!";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (nom, prenom, email, adresse, telephone, password, role) VALUES (?, ?, ?, ?, ?, ?, 'user')";
                $insert = $pdo->prepare($sql);

                if ($insert->execute([$lastname, $firstname, $email, $address, $phone, $hashed_password])) {
                    $success = "Enskripsyon reyisi! W ap redireksyone nan paj login...";
                    header("refresh:2;url=login.php");
                }
            }
        } catch (PDOException $e) {
            $error = "Erè: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Le Stock</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="\le-stock\css\style.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #0f172a;
        }

        .glass-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .glass-form-zone {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(12px);
        }

        .glass-input {
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: all 0.3s ease;
            font-size: 16px; /* Evite zoom sou iOS */
            min-height: 48px; /* Hautè minimòm pou boutwa */
        }

        .glass-input:focus {
            background: rgba(255, 255, 255, 0.95);
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .glass-input::placeholder {
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
        }

        .glass-btn {
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            transition: all 0.3s ease;
            min-height: 52px;
        }

        .glass-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.4);
        }

        /* Responsive amelyorasyon */
        @media (max-width: 640px) {
            .glass-form-zone {
                padding: 1.5rem;
            }
            
            .glass-input {
                font-size: 16px; /* Anpeche zoom sou iPhone */
                padding: 0.875rem 1rem;
            }
            
            .form-label {
                font-size: 12px;
            }
        }

        @media (min-width: 641px) {
            .glass-input {
                font-size: 15px;
                padding: 1rem 1.25rem;
            }
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-0 sm:p-4 relative overflow-x-hidden">

    <div class="fixed inset-0 z-0">
        <img src="/le-stock/assets/img/stock6.png" alt="Background" class="w-full h-full object-cover opacity-40">
        <div class="absolute inset-0 bg-black/40"></div>
    </div>

    <div class="relative z-10 w-full max-w-6xl mx-auto">
        <div class="glass-container rounded-none sm:rounded-[2.5rem] overflow-hidden flex flex-col lg:flex-row min-h-screen sm:min-h-[700px]">

            <!-- Seksyon Goch -->
            <div class="lg:w-5/12 relative flex flex-col justify-center items-center p-6 sm:p-8 text-center border-r border-white/10 bg-gradient-to-br from-slate-800/50 to-slate-900/50">
                <div class="relative z-10">
                    <h2 class="text-2xl sm:text-3xl lg:text-5xl font-black text-white mb-2 italic tracking-tighter uppercase">LE STOCK</h2>
                    <h3 class="text-lg sm:text-xl lg:text-2xl font-bold text-yellow-300 mb-6 sm:mb-8 uppercase tracking-widest">Inscris-toi</h3>
                    <img src="/le-stock/assets/img/anscrit.png" alt="Shopping" class="w-48 sm:w-64 h-auto mx-auto drop-shadow-2xl">
                </div>
            </div>

            <!-- Seksyon Dwat -->
            <div class="lg:w-7/12 glass-form-zone p-4 sm:p-8 lg:p-12 flex flex-col justify-center">
                <div class="max-w-md mx-auto w-full">
                    <div class="text-center mb-6 sm:mb-8">
                        <h1 class="text-2xl sm:text-3xl font-black text-slate-800 mb-2 italic uppercase">Bienvenue!</h1>
                        <p class="text-slate-600 text-sm sm:text-base font-medium">Crée ton compte en quelques secondes</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="bg-red-500/20 border border-red-500/50 text-red-700 p-4 mb-6 rounded-2xl text-sm font-bold italic uppercase flex items-center">
                            <i class="fas fa-exclamation-circle mr-2 text-lg"></i>
                            <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="bg-emerald-500/20 border border-emerald-500/50 text-emerald-700 p-4 mb-6 rounded-2xl text-sm font-bold italic uppercase text-center flex items-center justify-center">
                            <i class="fas fa-check-circle mr-2 text-lg"></i>
                            <?= $success ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="space-y-4 sm:space-y-5">
                        <!-- Non ak Prenon -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                            <div class="space-y-1.5 sm:space-y-2">
                                <label class="form-label block text-xs sm:text-sm font-black uppercase tracking-widest text-slate-600 ml-1">Nom</label>
                                <input type="text" name="lastname" required 
                                    class="w-full glass-input rounded-xl sm:rounded-2xl outline-none font-semibold text-slate-800 placeholder:font-medium" 
                                    placeholder="Ex: Doe">
                            </div>
                            <div class="space-y-1.5 sm:space-y-2">
                                <label class="form-label block text-xs sm:text-sm font-black uppercase tracking-widest text-slate-600 ml-1">Prénom</label>
                                <input type="text" name="firstname" required 
                                    class="w-full glass-input rounded-xl sm:rounded-2xl outline-none font-semibold text-slate-800 placeholder:font-medium" 
                                    placeholder="Ex: John">
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="space-y-1.5 sm:space-y-2">
                            <label class="form-label block text-xs sm:text-sm font-black uppercase tracking-widest text-slate-600 ml-1">Email</label>
                            <input type="email" name="email" required 
                                class="w-full glass-input rounded-xl sm:rounded-2xl outline-none font-semibold text-slate-800 placeholder:font-medium" 
                                placeholder="john@example.com">
                        </div>

                        <!-- Adrès -->
                        <div class="space-y-1.5 sm:space-y-2">
                            <label class="form-label block text-xs sm:text-sm font-black uppercase tracking-widest text-slate-600 ml-1">Adresse Complète</label>
                            <input type="text" name="address" required 
                                class="w-full glass-input rounded-xl sm:rounded-2xl outline-none font-semibold text-slate-800 placeholder:font-medium" 
                                placeholder="No, Rue, Ville, Pays">
                        </div>

                        <!-- Telefòn -->
                        <div class="space-y-1.5 sm:space-y-2">
                            <label class="form-label block text-xs sm:text-sm font-black uppercase tracking-widest text-slate-600 ml-1">Téléphone</label>
                            <input type="tel" name="phone" required 
                                class="w-full glass-input rounded-xl sm:rounded-2xl outline-none font-semibold text-slate-800 placeholder:font-medium" 
                                placeholder="+509 34 56 78 90">
                        </div>

                        <!-- Modpas -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                            <div class="space-y-1.5 sm:space-y-2">
                                <label class="form-label block text-xs sm:text-sm font-black uppercase tracking-widest text-slate-600 ml-1">Mot de passe</label>
                                <input type="password" name="password" required 
                                    class="w-full glass-input rounded-xl sm:rounded-2xl outline-none font-semibold text-slate-800 placeholder:font-medium" 
                                    placeholder="8 karaktè minimòm">
                            </div>
                            <div class="space-y-1.5 sm:space-y-2">
                                <label class="form-label block text-xs sm:text-sm font-black uppercase tracking-widest text-slate-600 ml-1">Confirmer</label>
                                <input type="password" name="confirm_password" required 
                                    class="w-full glass-input rounded-xl sm:rounded-2xl outline-none font-semibold text-slate-800 placeholder:font-medium" 
                                    placeholder="Re-tape modpas la">
                            </div>
                        </div>

                        <!-- Bouton -->
                        <button type="submit" class="w-full glass-btn text-white py-3.5 sm:py-4 rounded-xl sm:rounded-2xl font-black text-sm uppercase tracking-[0.15em] mt-6 sm:mt-8 active:scale-95 shadow-xl hover:shadow-2xl">
                            S'inscrire Maintenant
                        </button>
                    </form>

                    <p class="mt-6 sm:mt-8 text-center text-sm font-bold text-slate-600">
                        Déjà un compte?
                        <a href="login.php" class="text-indigo-600 font-black hover:underline ml-1 uppercase italic transition-colors hover:text-indigo-800">Se connecter</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

</body>

</html>