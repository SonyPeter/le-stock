<?php
// 1. Koneksyon ak Database la
require_once dirname(__DIR__) . '/config/db.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname  = trim($_POST['lastname'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $role      = $_POST['role'] ?? 'user'; // 'user' oswa 'merchant'
    $password  = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validasyon de baz
    if (empty($firstname) || empty($lastname) || empty($email) || empty($password)) {
        $error = "Tanpri ranpli tout chan obligatwa yo.";
    } elseif ($password !== $confirm_password) {
        $error = "De modpas yo pa menm.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Imèl sa a pa valide.";
    } else {
        try {
            // Tcheke si imèl la deja egziste
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);

            if ($check->rowCount() > 0) {
                $error = "Imèl sa a deja itilize.";
            } else {
                $proof_filename = null;
                $status = ($role === 'merchant') ? 'pending' : 'active';

                // SI SE YON MACHANN, JERE UPLOAD PRÈV LA
                if ($role === 'merchant') {
                    if (isset($_FILES['proof']) && $_FILES['proof']['error'] === 0) {
                        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
                        $filename = $_FILES['proof']['name'];
                        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                        if (in_array($ext, $allowed)) {
                            // Kreye yon non inik pou dosye a
                            $proof_filename = "proof_" . time() . "_" . uniqid() . "." . $ext;
                            $upload_path = dirname(__DIR__) . '/assets/img/' . $proof_filename;

                            if (!move_uploaded_file($_FILES['proof']['tmp_name'], $upload_path)) {
                                $error = "Erè pandan n t ap sove prèv peman an.";
                            }
                        } else {
                            $error = "Fòma fichye a dwe JPG, PNG oswa PDF.";
                        }
                    } else {
                        $error = "Kòm machann, ou dwe voye yon prèv peman.";
                    }
                }

                // SI PA GEN ERÈ UPLOAD, ANREJISTRE NAN DB
                if (empty($error)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (nom, prenom, email, password, role, status, proof_payment) VALUES (?, ?, ?, ?, ?, ?, ?)");

                    if ($stmt->execute([$lastname, $firstname, $email, $hashed_password, $role, $status, $proof_filename])) {
                        header("Location: login.php?success=registered");
                        exit();
                    }
                }
            }
        } catch (PDOException $e) {
            $error = "Erè nan baz de done: " . $e->getMessage();
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
                <p class="text-gray-500">Chwazi tip kont ou epi ranpli fòm nan.</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 mb-6 rounded text-sm">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data" class="space-y-4">

                <div class="flex gap-4 mb-6">
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="role" value="user" class="hidden peer" checked onchange="toggleMerchantFields(false)">
                        <div class="p-3 text-center border-2 rounded-xl peer-checked:border-indigo-600 peer-checked:bg-indigo-50 transition-all text-sm font-semibold">
                            Senp Kliyan
                        </div>
                    </label>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="role" value="merchant" class="hidden peer" onchange="toggleMerchantFields(true)">
                        <div class="p-3 text-center border-2 rounded-xl peer-checked:border-indigo-600 peer-checked:bg-indigo-50 transition-all text-sm font-semibold">
                            Machann
                        </div>
                    </label>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <input type="text" name="firstname" placeholder="Prénom" required class="w-full p-3 border rounded-xl focus:ring-2 focus:ring-indigo-400 outline-none">
                    <input type="text" name="lastname" placeholder="Nom" required class="w-full p-3 border rounded-xl focus:ring-2 focus:ring-indigo-400 outline-none">
                </div>

                <input type="email" name="email" placeholder="Email" required class="w-full p-3 border rounded-xl focus:ring-2 focus:ring-indigo-400 outline-none">

                <div class="grid grid-cols-2 gap-4">
                    <input type="password" name="password" placeholder="Mot de passe" required class="w-full p-3 border rounded-xl focus:ring-2 focus:ring-indigo-400 outline-none">
                    <input type="password" name="confirm_password" placeholder="Confirmer" required class="w-full p-3 border rounded-xl focus:ring-2 focus:ring-indigo-400 outline-none">
                </div>

                <div id="merchant-upload" class="hidden bg-yellow-50 p-4 rounded-xl border border-yellow-200">
                    <label class="block text-sm font-bold text-yellow-800 mb-2">Prèv Peman (PDF, JPG, PNG)</label>
                    <input type="file" name="proof" class="text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-yellow-100 file:text-yellow-700 hover:file:bg-yellow-200">
                </div>

                <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200">
                    Enskri Kounye a
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-gray-600">
                Ou gen yon kont deja? <a href="login.php" class="text-indigo-600 font-bold">Konekte w</a>
            </p>
        </div>
    </div>
</div>

<script>
    function toggleMerchantFields(isMerchant) {
        const uploadDiv = document.getElementById('merchant-upload');
        if (isMerchant) {
            uploadDiv.classList.remove('hidden');
        } else {
            uploadDiv.classList.add('hidden');
        }
    }
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>