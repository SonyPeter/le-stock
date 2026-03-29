<?php
// 1. Konfigirasyon Inisyal
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
session_start();

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

// 2. Chaje .env
if (file_exists(dirname(__DIR__) . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->load();
}

// 3. Konfigirasyon Google (Vèsyon Konpatib)
$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID'] ?? '');
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET'] ?? '');
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URL'] ?? '');
$client->addScope("email");
$client->addScope("profile");

// Inyore SSL pou evite erè JSON sou Wamp
$default_opts = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]];
stream_context_set_default($default_opts);

$error = "";

// --- 4. LOJIK KONEKSYON AK IMÈL (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_traditional'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'] ?? '')) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = ($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '');
                $_SESSION['role'] = $user['role'];

                // Redireksyon otomatik
                if ($user['role'] === 'admin') header("Location: admin_dashboard.php");
                elseif ($user['role'] === 'merchant') header("Location: acceuil.php");
                else header("Location: ../index.php");
                exit();
            } else {
                $error = "Imèl oswa modpas pa kòrèk.";
            }
        } catch (PDOException $e) {
            $error = "Erè baz de done.";
        }
    }
}

// --- 5. LOJIK GOOGLE (GET) ---
if (isset($_GET['code'])) {
    try {
        // Metòd pou vye vèsyon bibliyotèk la
        $client->authenticate($_GET['code']);
        $token = $client->getAccessToken();

        if ($token) {
            $client->setAccessToken($token);
            $google_oauth = new Google_Service_Oauth2($client);
            $google_info = $google_oauth->userinfo->get();

            $email = $google_info->email;
            $google_id = $google_info->id;
            $nom = $google_info->familyName ?? '';
            $prenom = $google_info->givenName ?? '';

            // Tcheke si itilizatè a egziste
            $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ? OR email = ?");
            $stmt->execute([$google_id, $email]);
            $user = $stmt->fetch();

            if ($user) {
                // Si l te gen imèl sèlman, n ap ajoute Google ID a
                if (empty($user['google_id'])) {
                    $pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?")->execute([$google_id, $user['id']]);
                }
            } else {
                // Enskripsyon Otomatik
                $insert = $pdo->prepare("INSERT INTO users (nom, prenom, email, role, google_id) VALUES (?, ?, ?, 'user', ?)");
                $insert->execute([$nom, $prenom, $email, $google_id]);

                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$pdo->lastInsertId()]);
                $user = $stmt->fetch();
            }

            // Konekte otomatikman
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];
            $_SESSION['role'] = $user['role'];

            header("Location: acceuil.php");
            exit();
        }
    } catch (Exception $e) {
        $error = "Erè Google: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Le Stock</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .bg-image-side {
            background: linear-gradient(rgba(118, 75, 162, 0.85), rgba(102, 126, 234, 0.85)),
                url('/le-stock/assets/img/stock.png') center/cover no-repeat;
        }
    </style>
</head>

<body class="bg-white font-sans antialiased overflow-x-hidden">
    <div class="flex flex-col lg:flex-row w-full min-h-screen">

        <div class="w-full lg:w-1/2 flex items-center justify-center p-6 lg:p-16 order-2 lg:order-1">
            <div class="w-full max-w-md">
                <div class="flex justify-center mb-6">
                    <div class="w-16 h-16 bg-indigo-600 rounded-2xl flex items-center justify-center shadow-xl">
                        <i class="fas fa-shopping-bag text-white text-3xl"></i>
                    </div>
                </div>
                <h1 class="text-3xl font-black text-center text-gray-900 mb-2 italic uppercase tracking-tighter">LE STOCK</h1>
                <p class="text-center text-gray-500 mb-8 font-medium">Konekte pou w jere biznis ou</p>

                <div class="mb-6">
                    <a href="<?= $client->createAuthUrl(); ?>" class="w-full flex items-center justify-center gap-3 border border-gray-300 py-3.5 rounded-xl font-bold text-gray-700 hover:bg-gray-50 transition-all active:scale-95 shadow-sm">
                        <img src="https://www.svgrepo.com/show/355037/google.svg" class="w-5 h-5" alt="Google">
                        CONTINUER AVEC GOOGLE
                    </a>
                </div>

                <div class="relative mb-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200"></div>
                    </div>
                    <div class="relative flex justify-center text-sm"><span class="px-2 bg-white text-gray-400 font-bold uppercase text-[10px] tracking-widest">ou ak imèl ou</span></div>
                </div>

                <?php if ($error): ?>
                    <div class="bg-red-50 text-red-600 p-4 rounded-xl mb-6 border border-red-100 text-sm flex items-center gap-3 font-semibold">
                        <i class="fas fa-exclamation-circle text-lg"></i>
                        <span><?= $error ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5">
                    <input type="hidden" name="login_traditional" value="1">
                    <div>
                        <label class="block text-[11px] font-black uppercase text-gray-500 mb-1 ml-1 tracking-wider">Email</label>
                        <input type="email" name="email" required class="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none font-semibold transition-all shadow-inner">
                    </div>
                    <div>
                        <label class="block text-[11px] font-black uppercase text-gray-500 mb-1 ml-1 tracking-wider">Modpas</label>
                        <div class="relative">
                            <input type="password" name="password" id="pass" required class="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none font-semibold transition-all shadow-inner">
                            <button type="button" onclick="togglePassword()" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-indigo-600">
                                <i id="eye-icon" class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-indigo-700 shadow-lg active:scale-95 transition-all">Se konekte kounye a</button>
                </form>

                <p class="text-center mt-8 text-sm text-gray-500 font-medium">
                    Poko gen kont ? <a href="inscription.php" class="font-black text-indigo-600 hover:underline italic uppercase">Kreyé yon kont</a>
                </p>
            </div>
        </div>

        <div class="w-full lg:w-1/2 bg-image-side flex flex-col justify-center px-10 lg:px-20 text-white order-1 lg:order-2 py-12">
            <h2 class="text-4xl lg:text-6xl font-black mb-6 leading-tight uppercase italic tracking-tighter">Le Stock Entreprise.</h2>
            <p class="text-lg opacity-90 mb-12 font-medium italic tracking-wide">Jere stock ou, ogmante pwofi ou ak sistèm SonyPeter la.</p>
            <div class="grid grid-cols-3 gap-8 border-t border-white/20 pt-10">
                <div><span class="block text-3xl font-black">150+</span><span class="text-[10px] uppercase font-bold opacity-70 tracking-widest text-indigo-200">Kliyan</span></div>
                <div><span class="block text-3xl font-black">1.2k+</span><span class="text-[10px] uppercase font-bold opacity-70 tracking-widest text-indigo-200">Pwodwi</span></div>
                <div><span class="block text-3xl font-black italic">4.9</span><span class="text-[10px] uppercase font-bold opacity-70 tracking-widest text-indigo-200">Satisfe</span></div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('pass');
            const icon = document.getElementById('eye-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>

</html>