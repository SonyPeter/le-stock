<?php
// 1. Koneksyon ak Database la
require_once dirname(__DIR__) . '/config/db.php';

$error = "";
$success = "";
// Nou prepare yon tablo pou kenbe done yo si gen erè (UX)
$data = ['firstname' => '', 'lastname' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data['firstname'] = trim($_POST['firstname'] ?? '');
    $data['lastname']  = trim($_POST['lastname'] ?? '');
    $data['email']     = trim($_POST['email'] ?? '');
    $password          = $_POST['password'] ?? '';
    $confirm_password  = $_POST['confirm_password'] ?? '';

    // Validasyon de baz
    if (empty($data['firstname']) || empty($data['lastname']) || empty($data['email']) || empty($password)) {
        $error = "Tanpri ranpli tout chan obligatwa yo.";
    } elseif ($password !== $confirm_password) {
        $error = "De modpas yo pa menm.";
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $error = "Imèl sa a pa valide.";
    } else {
        try {
            // Tcheke si imèl la deja egziste
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$data['email']]);

            if ($check->rowCount() > 0) {
                $error = "Imèl sa a deja itilize.";
            } else {
                // Tout moun enskri kòm 'user' pa defo
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (nom, prenom, email, password, role) VALUES (?, ?, ?, ?, 'user')");

                if ($stmt->execute([$data['lastname'], $data['firstname'], $data['email'], $hashed_password])) {
                    header("Location: login.php?success=registered");
                    exit();
                }
            }
        } catch (PDOException $e) {
            $error = "Erè nan sistèm nan. Tanpri eseye pita.";
        }
    }
}

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 p-4">
    <div class="w-full max-w-5xl bg-white rounded-3xl shadow-xl overflow-hidden flex flex-col lg:flex-row">

        <div class="lg:w-1/2 bg-gradient-to-br from-indigo-600 to-purple-700 p-12 text-white flex flex-col justify-center items-center text-center">
            <h2 class="text-4xl font-bold mb-4">Le Stock Entreprise</h2>
            <p class="text-indigo-100 mb-8 text-lg">Rejwenn pi gwo rezo distribisyon an epi kòmanse akimile pwen.</p>
            <img src="../assets/img/anscrit.png" alt="Welcome" class="w-64 h-auto drop-shadow-2xl">
        </div>

        <div class="lg:w-1/2 p-8 sm:p-12">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Kreye yon kont</h1>
                <p class="text-gray-500">Antre enfòmasyon ou yo pou w kòmanse.</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 mb-6 rounded text-sm">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-4">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1 ml-1">Prénom</label>
                        <input type="text" name="firstname" value="<?= htmlspecialchars($data['firstname']) ?>" placeholder="Prénom" required
                            class="w-full p-3 border rounded-xl focus:ring-2 focus:ring-indigo-400 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1 ml-1">Nom</label>
                        <input type="text" name="lastname" value="<?= htmlspecialchars($data['lastname']) ?>" placeholder="Nom" required
                            class="w-full p-3 border rounded-xl focus:ring-2 focus:ring-indigo-400 outline-none transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1 ml-1">Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($data['email']) ?>" placeholder="Email" required
                        class="w-full p-3 border rounded-xl focus:ring-2 focus:ring-indigo-400 outline-none transition-all">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1 ml-1">Mot de passe</label>
                        <input type="password" name="password" placeholder="••••••••" required
                            class="w-full p-3 border rounded-xl focus:ring-2 focus:ring-indigo-400 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1 ml-1">Confirmer</label>
                        <input type="password" name="confirm_password" placeholder="••••••••" required
                            class="w-full p-3 border rounded-xl focus:ring-2 focus:ring-indigo-400 outline-none transition-all">
                    </div>
                </div>

                <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200 mt-4">
                    Enskri Kounye a
                </button>
            </form>

            <p class="mt-8 text-center text-sm text-gray-600">
                Ou gen yon kont deja? <a href="login.php" class="text-indigo-600 font-bold hover:underline">Konekte w</a>
            </p>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>