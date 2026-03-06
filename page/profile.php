<?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erè baz de done: " . $e->getMessage());
}

$activeTab = $_GET['tab'] ?? 'about';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_info'])) {
    $prenom = htmlspecialchars($_POST['prenom']);
    $nom = htmlspecialchars($_POST['nom']);
    $adresse = htmlspecialchars($_POST['adresse']);
    $phone = htmlspecialchars($_POST['phone']);

    $update = $pdo->prepare("UPDATE users SET prenom = ?, nom = ?, adresse = ?, telephone = ? WHERE id = ?");
    if ($update->execute([$prenom, $nom, $adresse, $phone, $user_id])) {
        header("Location: profile.php?tab=settings&success=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="ht">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pwofil - <?= htmlspecialchars($user['prenom']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #ffffff;
            color: #1e293b;
        }

        .nav-link.active {
            color: #2563eb;
            border-bottom: 3px solid #2563eb;
            font-weight: 800;
        }

        .initials-avatar {
            background: #f1f5f9;
            color: #64748b;
            border: 2px solid #e2e8f0;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col">

    <header class="flex items-center justify-between px-6 py-5 border-b border-slate-100 bg-white sticky top-0 z-50">
        <div class="flex items-center gap-2">
            <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                <i class="fas fa-bolt"></i>
            </div>
            <span class="text-xl font-black tracking-tighter italic">LE-STOCK</span>
        </div>
        <nav class="hidden lg:flex items-center gap-10 text-[11px] font-bold uppercase tracking-[0.15em] text-slate-500">
            <a href="panier.php" class="hover:text-blue-600 flex items-center gap-2 font-black italic"><i class="fas fa-shopping-bag"></i> Panier</a>
            <a href="commandes.php" class="hover:text-blue-600 flex items-center gap-2 font-black italic"><i class="fas fa-receipt"></i> Commandes</a>
            <a href="favoris.php" class="hover:text-blue-600 flex items-center gap-2 font-black italic"><i class="fas fa-heart"></i> Favoris</a>
            <div class="flex items-center gap-3 border-l pl-6">
                <span class="text-slate-900 font-bold italic"><?= htmlspecialchars($user['prenom']) ?></span>
                <div class="w-8 h-8 rounded-full initials-avatar flex items-center justify-center text-[10px] font-black italic">
                    <?= strtoupper(substr($user['prenom'] ?? '', 0, 1) . substr($user['nom'] ?? '', 0, 1)) ?>
                </div>
            </div>
        </nav>
    </header>

    <main class="flex-grow grid grid-cols-1 lg:grid-cols-12">
        <aside class="lg:col-span-3 p-8 border-r border-slate-50 bg-slate-50/40">
            <div class="w-32 h-32 mx-auto rounded-[2.5rem] initials-avatar flex items-center justify-center text-4xl font-black shadow-inner mb-8 bg-white italic">
                <?= strtoupper(substr($user['prenom'] ?? '', 0, 1) . substr($user['nom'] ?? '', 0, 1)) ?>
            </div>
            <div class="space-y-4">
                <?php if (strtolower($user['role'] ?? '') === 'merchant'): ?>
                    <div class="bg-blue-600 p-6 rounded-3xl text-white shadow-lg">
                        <p class="text-[10px] font-black uppercase opacity-70 mb-1">Pwen Fidelite</p>
                        <div class="text-3xl font-black italic"><?= number_format($user['points'] ?? 0) ?> pts</div>
                    </div>
                <?php endif; ?>
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-2 tracking-widest">Status Kont</p>
                    <span class="px-4 py-1.5 bg-blue-50 text-blue-600 text-[10px] font-black rounded-full border border-blue-200 uppercase">
                        <?= strtoupper($user['role'] ?? 'USER') ?>
                    </span>
                </div>
            </div>
        </aside>

        <div class="lg:col-span-9 p-8 lg:p-16">
            <div class="mb-12">
                <h1 class="text-5xl font-extrabold text-slate-900 tracking-tighter italic uppercase">
                    <?= htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?>
                </h1>
                <p class="text-slate-400 mt-2 font-medium italic">ID Itilizatè: #LS-<?= $user['id'] ?></p>
            </div>

            <div class="flex gap-12 border-b border-slate-100 mb-12">
                <a href="?tab=about" class="pb-5 nav-link <?= $activeTab === 'about' ? 'active' : '' ?> text-slate-400 text-xs font-black uppercase tracking-widest italic">Enfòmasyon</a>
                <a href="?tab=settings" class="pb-5 nav-link <?= $activeTab === 'settings' ? 'active' : '' ?> text-slate-400 text-xs font-black uppercase tracking-widest italic">Paramètre</a>
            </div>

            <?php if ($activeTab === 'about'): ?>
                <div class="grid md:grid-cols-2 gap-8 italic">
                    <div class="p-8 rounded-[2rem] bg-slate-50 border border-slate-100">
                        <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6">Kontak</h4>
                        <div class="space-y-6 text-sm">
                            <div>
                                <p class="text-slate-400 mb-1 font-bold uppercase text-[9px]">Telefòn</p>
                                <p class="font-black"><?= $user['telephone'] ?: '--' ?></p>
                            </div>
                            <div>
                                <p class="text-slate-400 mb-1 font-bold uppercase text-[9px]">Email</p>
                                <p class="font-black text-blue-600"><?= $user['email'] ?></p>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($activeTab === 'settings'): ?>
                <div class="max-w-xl">
                    <div class="space-y-12">
                        <div class="space-y-6">
                            <h3 class="text-xs font-black text-slate-800 uppercase italic tracking-widest">Mizajou Profil</h3>
                            <form method="POST" class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <input type="text" name="prenom" value="<?= $user['prenom'] ?>" class="w-full p-4 rounded-2xl bg-slate-50 border-0 ring-1 ring-slate-200 focus:ring-2 focus:ring-blue-500 font-bold text-sm">
                                    <input type="text" name="nom" value="<?= $user['nom'] ?>" class="w-full p-4 rounded-2xl bg-slate-50 border-0 ring-1 ring-slate-200 focus:ring-2 focus:ring-blue-500 font-bold text-sm">
                                </div>
                                <input type="text" name="phone" value="<?= $user['telephone'] ?>" placeholder="Telefòn" class="w-full p-4 rounded-2xl bg-slate-50 border-0 ring-1 ring-slate-200 focus:ring-2 focus:ring-blue-500 font-bold text-sm">
                                <input type="text" name="adresse" value="<?= $user['adresse'] ?>" placeholder="Adrès" class="w-full p-4 rounded-2xl bg-slate-50 border-0 ring-1 ring-slate-200 focus:ring-2 focus:ring-blue-500 font-bold text-sm">
                                <button type="submit" name="update_info" class="w-full py-5 bg-[#0a1128] text-white rounded-2xl font-black text-[10px] uppercase shadow-xl hover:bg-blue-600 transition-all italic">Sove Modifikasyon</button>
                            </form>
                        </div>

                        <div class="pt-8 border-t border-slate-100">
                            <?php
                            $ròl_kounye_a = strtolower($user['role'] ?? '');
                            $status_demann = strtolower($user['merchant_status'] ?? '');
                            ?>

                            <?php if ($ròl_kounye_a === 'merchant'): ?>
                                <div class="p-4 bg-emerald-50 text-emerald-600 rounded-2xl text-[10px] font-black uppercase text-center italic">Ou se yon Machann konfime</div>
                            <?php elseif ($status_demann === 'pending'): ?>
                                <div class="p-6 bg-amber-50 border border-amber-100 rounded-3xl flex items-center gap-4 italic">
                                    <i class="fas fa-hourglass-half text-amber-500 animate-pulse"></i>
                                    <div>
                                        <p class="text-[10px] font-black text-amber-900 uppercase">Demann an kou...</p>
                                        <p class="text-[9px] text-amber-600 font-bold">Admin ap verifye peman ou.</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <h3 class="text-xs font-black text-slate-800 uppercase italic tracking-widest">Upgrade Kont</h3>
                                    <a href="form_machann.php" class="inline-flex items-center gap-3 px-10 py-5 bg-blue-600 text-white rounded-2xl font-black text-[11px] uppercase tracking-[0.2em] hover:bg-yellow-400 hover:text-slate-900 transition-all shadow-xl shadow-blue-100 italic">
                                        <i class="fas fa-store"></i> Devni Machann
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>