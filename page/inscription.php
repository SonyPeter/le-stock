<?php
// 1. Rele koneksyon an (db.php dwe gen $pdo ladan l)
require_once dirname(__DIR__) . '/config/db.php';

$error = "";

// Lojik pou trete fòm nan lè moun nan klike sou bouton an
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $firstname = trim($_POST['firstname'] ?? '');
    $lastname  = trim($_POST['lastname'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $adresse   = trim($_POST['adresse'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // VALIDASYON YO
    if (empty($firstname) || empty($lastname) || empty($email) || empty($phone) || empty($password) || empty($adresse)) {
        $error = "Tanpri ranpli tout chan yo.";
    } elseif ($password !== $confirm_password) {
        $error = "De modpas yo pa menm.";
    } elseif (strlen($password) < 8) {
        $error = "Modpas la dwe gen omwen 8 karaktè.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Tanpri antre yon imèl valide.";
    } else {
        // SI TOUT BAGAY OK, N AP ANREJISTRE
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Nou tcheke si imèl oswa telefòn nan tablo 'clients' la deja egziste
            $check = $pdo->prepare("SELECT id FROM clients WHERE email = ? OR telefòn = ?");
            $check->execute([$email, $phone]);

            if ($check->rowCount() > 0) {
                $error = "Imèl oswa telefòn sa deja egziste nan sistèm nan.";
            } else {
                // INSERT nan tablo 'clients' ak bon non kolòn yo jan nou wè nan phpMyAdmin ou a
                $stmt = $pdo->prepare("INSERT INTO clients 
                    (nom, prenom, email, telefòn, adresse, mot_de_passe, dat_enskripsyon)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())");

                $resultat = $stmt->execute([
                    $lastname,         // prale nan kolòn 'nom'
                    $firstname,        // prale nan kolòn 'prenom'
                    $email,            // prale nan kolòn 'email'
                    $phone,            // prale nan kolòn 'telefòn'
                    $adresse,          // prale nan kolòn 'adresse'
                    $hashed_password   // prale nan kolòn 'mot_de_passe'
                ]);

                if ($resultat) {
                    header("Location: login.php?success=registered");
                    exit();
                } else {
                    $error = "Enskripsyon an echwe. Eseye ankò.";
                }
            }
        } catch (PDOException $e) {
            $error = "Erè nan baz de done: " . $e->getMessage();
        }
    }
}

// Rele header a sèlman SI pa gen redireksyon ki fèt (evite erè Headers already sent)
require_once dirname(__DIR__) . '/includes/header.php';
?>

<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    primary: '#667eea',
                    secondary: '#764ba2',
                }
            }
        }
    }
</script>

<div class="min-h-screen flex items-center justify-center bg-gray-100 p-4 sm:p-6">

    <div class="w-full max-w-6xl bg-white rounded-2xl shadow-2xl overflow-hidden grid grid-cols-1 lg:grid-cols-2">

        <div class="bg-gradient-to-br from-indigo-500 to-purple-600 flex flex-col items-center justify-center p-8 sm:p-12 lg:p-16 min-h-[400px] lg:min-h-[700px] text-center relative">
            <div class="mb-6 sm:mb-8">
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-white mb-2">
                    Envie de Shopping
                </h2>
                <div class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-yellow-400">
                    Inscrit-toi Maintenant
                </div>
            </div>
            <img
                src="/le-stock/assets/img/anscrit.png"
                alt="Shopping"
                class="w-full max-w-md lg:max-w-lg xl:max-w-xl h-auto object-contain drop-shadow-2xl">
        </div>

        <div class="p-6 sm:p-10 lg:p-16 flex flex-col justify-center">

            <div class="text-center mb-6 sm:mb-8">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 mb-2">
                    Créez votre compte
                </h1>
                <p class="text-gray-500 text-sm sm:text-base">
                    Remplissez les informations pour commencer
                </p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-800 px-4 py-3 rounded-lg mb-5 text-sm">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-4 sm:space-y-5">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nom</label>
                        <input type="text" name="lastname" value="<?= htmlspecialchars($lastname ?? '') ?>" required
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Prénom</label>
                        <input type="text" name="firstname" value="<?= htmlspecialchars($firstname ?? '') ?>" required
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition-all">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Adresse</label>
                    <input type="text" name="adresse" value="<?= htmlspecialchars($adresse ?? '') ?>" required
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition-all">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Téléphone</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($phone ?? '') ?>" required
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition-all">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Mot de passe</label>
                        <input type="password" name="password" required
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Confirmer</label>
                        <input type="password" name="confirm_password" required
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition-all">
                    </div>
                </div>

                <button type="submit"
                    class="w-full py-4 bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-bold rounded-xl hover:-translate-y-0.5 hover:shadow-xl hover:shadow-indigo-500/30 transition-all duration-300 mt-2">
                    Créer Mon Compte
                </button>

            </form>

            <div class="text-center mt-6 text-sm text-gray-600">
                Déjà un compte?
                <a href="login.php" class="text-indigo-600 font-bold hover:text-indigo-800 hover:underline transition-colors">
                    Connectez-vous ici
                </a>
            </div>

        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>