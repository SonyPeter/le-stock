<?php 
// require_once '../config/db.php';
require_once dirname(__DIR__) . '/includes/header.php';

$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = $_POST['firstname'];
    $lastname  = $_POST['lastname'];
    $email     = $_POST['email'];
    $phone     = $_POST['phone'];
    $password  = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Verifikasyon si modpas yo match
    if ($password !== $confirm_password) {
        $error = "De modpas yo pa menm. Tanpri tcheke yo byen.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
            $check->execute([$email, $phone]);
            
            if ($check->rowCount() > 0) {
                $error = "Imèl oswa nimewo telefòn sa a deja gen yon kont.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (firstname, lastname, email, phone, password) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$firstname, $lastname, $email, $phone, $hashed_password]);
                header("Location: login.php?success=Kont ou kreye! Konekte kounye a.");
                exit();
            }
        } catch (PDOException $e) {
            $error = "Gen yon erè nan sistèm nan. Tanpri reye ankò.";
        }
    }
}
?>

<div class="max-w-2xl mx-auto mt-10 mb-20 bg-white p-10 rounded-3xl shadow-2xl border border-gray-100">
    <div class="text-center mb-8">
        <h2 class="text-4xl font-black text-gray-800 italic uppercase">Gwolò <span class="text-blue-600">Tiraj</span></h2>
        <p class="text-gray-500 mt-2">Ranpli fòm nan pou w kòmanse genyen</p>
    </div>
    
    <?php if($error): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Nom</label>
            <input type="text" name="lastname" required class="w-full border-2 border-gray-100 p-3 rounded-xl focus:border-blue-500 outline-none transition bg-gray-50 focus:bg-white">
        </div>
        
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Prenom</label>
            <input type="text" name="firstname" required class="w-full border-2 border-gray-100 p-3 rounded-xl focus:border-blue-500 outline-none transition bg-gray-50 focus:bg-white">
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Email</label>
            <input type="email" name="email" required class="w-full border-2 border-gray-100 p-3 rounded-xl focus:border-blue-500 outline-none transition bg-gray-50 focus:bg-white">
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Nimewo Telefòn</label>
            <input type="text" name="phone" required class="w-full border-2 border-gray-100 p-3 rounded-xl focus:border-blue-500 outline-none transition bg-gray-50 focus:bg-white">
        </div>

        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Modpas</label>
            <input type="password" name="password" required class="w-full border-2 border-gray-100 p-3 rounded-xl focus:border-blue-500 outline-none transition bg-gray-50 focus:bg-white">
        </div>

        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Re-antre Modpas</label>
            <input type="password" name="confirm_password" required class="w-full border-2 border-gray-100 p-3 rounded-xl focus:border-blue-500 outline-none transition bg-gray-50 focus:bg-white">
        </div>

        <div class="md:col-span-2 mt-4">
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-4 rounded-xl shadow-lg transition transform hover:-translate-y-1">
                KREYE KONT MWEN
            </button>
        </div>
    </form>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>\