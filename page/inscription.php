<?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

// 1. Chaje varyab anviwònman yo (.env)
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// 2. Konfigirasyon Google Client
$clientID = $_ENV['GOOGLE_CLIENT_ID'];
$clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'];
$redirectUri = $_ENV['GOOGLE_REDIRECT_URL'];

$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);

// TRÈ ENPÒTAN: Ajoute pèmisyon yo isit la
$client->addScope("email");
$client->addScope("profile");

$error = "";
$success = "";

// --- 3. TRAITEMENT RETOUR GOOGLE (Lè itilizatè a fin otorize sou Google) ---
if (isset($_GET['code'])) {
    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

        if (!isset($token['error'])) {
            $client->setAccessToken($token['access_token']);
            $google_oauth = new Google_Service_Oauth2($client);
            $google_info = $google_oauth->userinfo->get();

            $email = $google_info->email;
            $nom = $google_info->familyName ?? 'Non-déterminé';
            $prenom = $google_info->givenName ?? 'Utilisateur';
            $google_id = $google_info->id;

            // Tcheke si imel sa egziste deja nan baz de done a
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Si kont lan egziste, nou mete ID Google la si l potko la
                if (empty($user['google_id'])) {
                    $update = $pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                    $update->execute([$google_id, $user['id']]);
                }
                $success = "Kont sa egziste deja. Koneksyon an kous...";
            } else {
                // Kreye nouvo kont lan si l pa egziste
                $insert = $pdo->prepare("INSERT INTO users (nom, prenom, email, role, google_id) VALUES (?, ?, ?, 'user', ?)");
                $insert->execute([$nom, $prenom, $email, $google_id]);

                // Rekipere itilizatè nou fenk kreye a
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$pdo->lastInsertId()]);
                $user = $stmt->fetch();
                $success = "Enskripsyon reyisi ak Google!";
            }

            // Mete enfòmasyon nan Session pou konekte l
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];
            $_SESSION['role'] = $user['role'];

            // Redireksyone apre 2 segonn
            header("refresh:2;url=../index.php");
        }
    } catch (Exception $e) {
        $error = "Erè Google: " . $e->getMessage();
    }
}

// --- 4. TRAITEMENT TRADISYONÈL (Lè l ranpli fòm nan) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_GET['code'])) {
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
            $error = "Erè baz de done: " . $e->getMessage();
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
    <script src="https://cdn.tailwindcss.com"></script>
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
            padding: 0.75rem 1rem;
        }

        .glass-btn {
            background: linear-gradient(135deg, #2563eb, #7c3aed);
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-0 sm:p-4 relative overflow-x-hidden">

    <div class="fixed inset-0 z-0">
        <img src="/le-stock/assets/img/stock6.png" alt="Background" class="w-full h-full object-cover opacity-40">
        <div class="absolute inset-0 bg-black/40"></div>
    </div>

    <div class="relative z-10 w-full max-w-6xl mx-auto">
        <div class="glass-container rounded-none sm:rounded-[2.5rem] overflow-hidden flex flex-col lg:flex-row min-h-screen">

            <div class="lg:w-5/12 relative flex flex-col justify-center items-center p-6 text-center border-r border-white/10 bg-gradient-to-br from-slate-800/50 to-slate-900/50">
                <h2 class="text-3xl lg:text-5xl font-black text-white mb-2 italic uppercase">LE STOCK</h2>
                <h3 class="text-xl font-bold text-yellow-300 mb-8 uppercase tracking-widest">Inscris-toi</h3>
                <img src="/le-stock/assets/img/anscrit.png" alt="Shopping" class="w-48 sm:w-64 h-auto mx-auto drop-shadow-2xl">
            </div>

            <div class="lg:w-7/12 glass-form-zone p-6 lg:p-12 flex flex-col justify-center">
                <div class="max-w-md mx-auto w-full">

                    <div class="mb-6">
                        <a href="<?= $client->createAuthUrl(); ?>" class="w-full flex items-center justify-center gap-3 bg-white/80 border border-white/50 py-3 rounded-xl font-bold text-slate-700 hover:bg-white transition-all shadow-md">
                            <img src="https://www.svgrepo.com/show/355037/google.svg" class="w-5 h-5" alt="Google">
                            S'INSCRIRE AVEC GOOGLE
                        </a>
                    </div>

                    <div class="relative mb-6">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-slate-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm"><span class="px-4 bg-transparent text-slate-600 font-bold uppercase tracking-tighter">ou par formulaire</span></div>
                    </div>

                    <?php if ($error): ?>
                        <div class="bg-red-500/20 border border-red-500/50 text-red-700 p-3 mb-6 rounded-xl text-xs font-bold uppercase flex items-center">
                            <i class="fas fa-exclamation-circle mr-2"></i> <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="bg-emerald-500/20 border border-emerald-500/50 text-emerald-700 p-3 mb-6 rounded-xl text-xs font-bold uppercase flex items-center justify-center">
                            <i class="fas fa-check-circle mr-2"></i> <?= $success ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="text-[10px] font-black uppercase text-slate-600 ml-1">Nom</label>
                                <input type="text" name="lastname" required class="w-full glass-input rounded-xl outline-none font-semibold text-slate-800" placeholder="Doe">
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-black uppercase text-slate-600 ml-1">Prénom</label>
                                <input type="text" name="firstname" required class="w-full glass-input rounded-xl outline-none font-semibold text-slate-800" placeholder="John">
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-slate-600 ml-1">Email</label>
                            <input type="email" name="email" required class="w-full glass-input rounded-xl outline-none font-semibold text-slate-800" placeholder="john@example.com">
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-slate-600 ml-1">Adresse</label>
                            <input type="text" name="address" required class="w-full glass-input rounded-xl outline-none font-semibold text-slate-800" placeholder="Ville, Pays">
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-slate-600 ml-1">Téléphone</label>
                            <input type="tel" name="phone" required class="w-full glass-input rounded-xl outline-none font-semibold text-slate-800" placeholder="+509...">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="text-[10px] font-black uppercase text-slate-600 ml-1">Mot de passe</label>
                                <input type="password" name="password" required class="w-full glass-input rounded-xl outline-none font-semibold text-slate-800" placeholder="••••••••">
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-black uppercase text-slate-600 ml-1">Confirmer</label>
                                <input type="password" name="confirm_password" required class="w-full glass-input rounded-xl outline-none font-semibold text-slate-800" placeholder="••••••••">
                            </div>
                        </div>

                        <button type="submit" class="w-full glass-btn text-white py-4 rounded-xl font-black text-xs uppercase tracking-widest mt-4 shadow-xl active:scale-95">
                            S'inscrire Maintenant
                        </button>
                    </form>

                    <p class="mt-6 text-center text-xs font-bold text-slate-600">
                        Déjà un compte? <a href="login.php" class="text-indigo-600 font-black hover:underline uppercase italic">Se connecter</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>