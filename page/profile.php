<?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';

// Si itilizatè a pa konekte, voye l nan login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

// 1. Rekipere enfòmasyon itilizatè a
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// 2. Traitement Modifikasyon Profil
if (isset($_POST['update_profile'])) {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);

    if (!empty($nom) && !empty($prenom) && !empty($email)) {
        $update = $pdo->prepare("UPDATE users SET nom = ?, prenom = ?, email = ?, telephone = ? WHERE id = ?");
        if ($update->execute([$nom, $prenom, $email, $telephone, $user_id])) {
            $message = "Profil ou mete ajou ak siksè!";
            // Rafrechi done yo
            $user['nom'] = $nom;
            $user['prenom'] = $prenom;
            $user['email'] = $email;
        }
    } else {
        $error = "Tanpri ranpli tout chan yo.";
    }
}

// 3. Traitement Demann Machann (Upload Prèv)
if (isset($_POST['apply_merchant'])) {
    if (isset($_FILES['proof']) && $_FILES['proof']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $filename = $_FILES['proof']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_name = "proof_" . time() . "_" . $user_id . "." . $ext;
            $path = "assets/img/" . $new_name;

            if (move_uploaded_file($_FILES['proof']['tmp_name'], $path)) {
                $stmt = $pdo->prepare("UPDATE users SET merchant_status = 'pending', proof_payment = ? WHERE id = ?");
                $stmt->execute([$new_name, $user_id]);
                $message = "Demann ou an voye! Admin nan ap verifye sa.";
                $user['merchant_status'] = 'pending';
            }
        } else {
            $error = "Fòma sa a pa aksepte (JPG, PNG, PDF sèlman).";
        }
    } else {
        $error = "Ou dwe voye yon prèv peman.";
    }
}
?>

<!DOCTYPE html>
<html lang="ht">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Mwen - Le Stock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-100 font-sans">

    <div class="max-w-5xl mx-auto py-10 px-4">

        <div class="flex flex-col md:flex-row gap-8">

            <div class="w-full md:w-1/3 space-y-6">
                <div class="bg-white p-8 rounded-3xl shadow-sm text-center">
                    <div class="w-24 h-24 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl font-bold">
                        <?= strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1)) ?>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800"><?= $user['prenom'] . ' ' . $user['nom'] ?></h2>
                    <p class="text-gray-500 text-sm mb-4"><?= $user['email'] ?></p>
                    <span class="px-4 py-1 rounded-full text-xs font-bold uppercase tracking-widest 
                        <?= $user['role'] == 'merchant' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' ?>">
                        <?= $user['role'] ?>
                    </span>
                </div>

                <?php if ($user['role'] == 'merchant'): ?>
                    <div class="bg-indigo-600 p-6 rounded-3xl shadow-lg text-white">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-indigo-200 text-sm">Pwen Fidèl</span>
                            <i class="fas fa-coins text-yellow-400"></i>
                        </div>
                        <p class="text-3xl font-black"><?= $user['points'] ?> <span class="text-sm font-normal">pts</span></p>
                        <a href="merchant_dashboard.php" class="block text-center mt-4 bg-white/20 hover:bg-white/30 py-2 rounded-xl text-sm transition">Ale nan Dashboard</a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="flex-1 space-y-6">

                <?php if ($message): ?>
                    <div class="bg-green-500 text-white p-4 rounded-2xl shadow-lg flex items-center gap-3">
                        <i class="fas fa-check-circle"></i> <?= $message ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-red-500 text-white p-4 rounded-2xl shadow-lg flex items-center gap-3">
                        <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
                    </div>
                <?php endif; ?>

                <div class="bg-white p-8 rounded-3xl shadow-sm">
                    <h3 class="text-lg font-bold mb-6 flex items-center gap-2">
                        <i class="fas fa-user-edit text-indigo-600"></i> Modifye Enfòmasyon
                    </h3>
                    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-gray-400 ml-1">PRÉNOM</label>
                            <input type="text" name="prenom" value="<?= $user['prenom'] ?>" class="w-full p-3 bg-gray-50 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-gray-400 ml-1">NOM</label>
                            <input type="text" name="nom" value="<?= $user['nom'] ?>" class="w-full p-3 bg-gray-50 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="md:col-span-2 space-y-1">
                            <label class="text-xs font-bold text-gray-400 ml-1">EMAIL</label>
                            <input type="email" name="email" value="<?= $user['email'] ?>" class="w-full p-3 bg-gray-50 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="md:col-span-2 space-y-1">
                            <label class="text-xs font-bold text-gray-400 ml-1">TELEFÒN</label>
                            <div class="relative">
                                <i class="fas fa-phone absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 text-xs"></i>
                                <input type="text" name="telephone" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>"
                                    class="w-full pl-10 p-3 bg-gray-50 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>
                        </div>
                        <button type="submit" name="update_profile" class="md:col-span-2 bg-gray-900 text-white py-3 rounded-xl font-bold hover:bg-black transition mt-2">
                            Sove Chanjman
                        </button>
                    </form>
                </div>

                <?php if ($user['role'] == 'user'): ?>
                    <div class="bg-white p-8 rounded-3xl shadow-sm border-2 border-dashed border-indigo-200">
                        <h3 class="text-lg font-bold mb-2 flex items-center gap-2 text-indigo-700">
                            <i class="fas fa-store"></i> Vin yon Machann
                        </h3>
                        <p class="text-sm text-gray-500 mb-6">Achte pwodwi an gwo pou w revann epi akimile pwen kach.</p>

                        <?php if ($user['merchant_status'] == 'pending'): ?>
                            <div class="bg-orange-100 text-orange-700 p-4 rounded-xl font-bold text-center">
                                ⏳ Demann ou an ap verifye pa Admin nan...
                            </div>
                        <?php elseif ($user['merchant_status'] == 'rejected'): ?>
                            <div class="bg-red-100 text-red-700 p-4 rounded-xl font-bold text-center">
                                ❌ Demann ou an te rejte. Ou ka re-voye yon lòt prèv.
                            </div>
                        <?php else: ?>
                            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                                <div class="border-2 border-dashed border-gray-200 p-6 rounded-2xl text-center">
                                    <input type="file" name="proof" id="proof" class="hidden" required onchange="updateFileName()">
                                    <label for="proof" class="cursor-pointer">
                                        <i class="fas fa-cloud-upload-alt text-4xl text-indigo-400 mb-2"></i>
                                        <p id="file-name" class="text-sm text-gray-600 font-medium">Klike la pou voye prèv peman ou (500 HTG)</p>
                                    </label>
                                </div>
                                <button type="submit" name="apply_merchant" class="w-full bg-indigo-600 text-white py-3 rounded-xl font-bold hover:bg-indigo-700 transition">
                                    Voye Demann Machann nan
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <a href="logout.php" class="block text-center text-red-500 font-bold text-sm hover:underline">Dekonekte m</a>
            </div>
        </div>
    </div>

    <script>
        function updateFileName() {
            const input = document.getElementById('proof');
            const label = document.getElementById('file-name');
            if (input.files.length > 0) {
                label.innerText = "Fichye chwazi: " + input.files[0].name;
                label.classList.add('text-indigo-600');
            }
        }
    </script>
</body>

</html>