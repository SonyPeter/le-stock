<?php
session_start();

// Verifye si itilizatè a konekte
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Konekte ak baz done a
require_once dirname(__DIR__) . '/../config/db.php';

// LOJIK PHP POU TRETE FÒMILÈ A
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $nom = htmlspecialchars(trim($_POST['nom'] ?? ''));
    $prenom = htmlspecialchars(trim($_POST['prenom'] ?? ''));
    $email = htmlspecialchars(trim($_POST['email'] ?? ''));
    $tel = htmlspecialchars(trim($_POST['telephone'] ?? ''));
    $adr = htmlspecialchars(trim($_POST['adresse'] ?? ''));
    $num_trans = htmlspecialchars(trim($_POST['numero_transfert'] ?? ''));

    // Validasyon
    if (empty($nom) || empty($prenom)) {
        $errors[] = "Non ak prenom obligatwa";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Imèl pa valide";
    }
    if (empty($tel)) {
        $errors[] = "Nimewo telefòn obligatwa";
    }
    if (empty($adr)) {
        $errors[] = "Adrès obligatwa";
    }
    if (empty($num_trans)) {
        $errors[] = "Nimewo transfè a obligatwa";
    }

    // Verifye si itilizatè a deja fè yon demann
    try {
        $check_stmt = $pdo->prepare("SELECT merchant_status FROM users WHERE id = ?");
        $check_stmt->execute([$user_id]);
        $user_status = $check_stmt->fetchColumn();

        if ($user_status === 'pending') {
            $errors[] = "Ou gen yon demann deja an atant. Tanpri tann apwobasyon an.";
        } elseif ($user_status === 'approved') {
            $errors[] = "Ou deja se yon machann!";
        }
    } catch (PDOException $e) {
        // Si baz done pa disponib, kontinye kanmenm
    }

    if (empty($errors)) {
        // 1. Kreye dossier pou foto yo si l pa egziste (nan rasin pwojè a)
        $upload_dir = dirname(__DIR__) . '/uploads/requests/';
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                $errors[] = "Pa ka kreye dosye pou fichye yo";
            }
        }

        // 2. Jere foto yo
        $foto_profil = '';
        $piece_id = '';

        // Foto profil (opsyonel)
        if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = mime_content_type($_FILES['foto_profil']['tmp_name']);

            if (in_array($file_type, $allowed_types)) {
                $foto_profil = time() . "_profil_" . preg_replace('/[^a-zA-Z0-9.-]/', '_', basename($_FILES['foto_profil']['name']));
                if (!move_uploaded_file($_FILES['foto_profil']['tmp_name'], $upload_dir . $foto_profil)) {
                    $errors[] = "Erè pandan transfè foto profil la";
                    $foto_profil = '';
                }
            } else {
                $errors[] = "Foto profil la dwe yon imaj (JPG, PNG, GIF, WEBP)";
            }
        }

        // Pyès ID (opsyonel)
        if (isset($_FILES['pyes_id']) && $_FILES['pyes_id']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = mime_content_type($_FILES['pyes_id']['tmp_name']);

            if (in_array($file_type, $allowed_types)) {
                $piece_id = time() . "_id_" . preg_replace('/[^a-zA-Z0-9.-]/', '_', basename($_FILES['pyes_id']['name']));
                if (!move_uploaded_file($_FILES['pyes_id']['tmp_name'], $upload_dir . $piece_id)) {
                    $errors[] = "Erè pandan transfè pyès ID a";
                    $piece_id = '';
                }
            } else {
                $errors[] = "Pyès ID a dwe yon imaj (JPG, PNG, GIF, WEBP)";
            }
        }

        // 3. Jere prèv peman an (obligatwa)
        $preuve_paiement = '';
        if (isset($_FILES['prev_peman']) && $_FILES['prev_peman']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
            $file_type = mime_content_type($_FILES['prev_peman']['tmp_name']);

            if (in_array($file_type, $allowed_types)) {
                $preuve_paiement = time() . "_paie_" . preg_replace('/[^a-zA-Z0-9.-]/', '_', basename($_FILES['prev_peman']['name']));
                if (!move_uploaded_file($_FILES['prev_peman']['tmp_name'], $upload_dir . $preuve_paiement)) {
                    $errors[] = "Erè pandan transfè prèv peman an";
                    $preuve_paiement = '';
                }
            } else {
                $errors[] = "Prèv peman an dwe yon imaj (JPG, PNG, GIF, WEBP) oswa PDF";
            }
        } else {
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE => 'Fichye a twò gwo',
                UPLOAD_ERR_FORM_SIZE => 'Fichye a twò gwo',
                UPLOAD_ERR_PARTIAL => 'Fichye a pa transfere nèt',
                UPLOAD_ERR_NO_FILE => 'Ou dwe chwazi yon fichye',
                UPLOAD_ERR_NO_TMP_DIR => 'Erè serveur',
                UPLOAD_ERR_CANT_WRITE => 'Erè ekriti',
                UPLOAD_ERR_EXTENSION => 'Extension bloke'
            ];
            $err_code = $_FILES['prev_peman']['error'] ?? UPLOAD_ERR_NO_FILE;
            $errors[] = "Prèv peman an obligatwa. " . ($upload_errors[$err_code] ?? "Erè #$err_code");
        }

        if (empty($errors) && !empty($preuve_paiement)) {
            // 4. ANREJISTRE NAN FICHYE TÈKS LA
            $admin_dir = dirname(__DIR__) . '/admin/';
            $demandes_file = $admin_dir . 'demandes_marchands.txt';

            // Kreye dosye admin si l pa egziste
            if (!file_exists($admin_dir)) {
                mkdir($admin_dir, 0777, true);
            }

            // Kreye fichye a si l pa egziste
            if (!file_exists($demandes_file)) {
                touch($demandes_file);
                chmod($demandes_file, 0666);
            }

            // Kreye kontni demann nan
            $id_demann = uniqid('REQ_');
            $kontni = "=== DEMANN MACHANN ===\n";
            $kontni .= "ID_DEMANN: " . $id_demann . "\n";
            $kontni .= "USER_ID: " . $user_id . "\n";
            $kontni .= "DAT: " . date('d-m-Y H:i:s') . "\n";
            $kontni .= "NON: " . $nom . "\n";
            $kontni .= "PRENOM: " . $prenom . "\n";
            $kontni .= "EMAIL: " . $email . "\n";
            $kontni .= "TELEFON: " . $tel . "\n";
            $kontni .= "ADRES: " . $adr . "\n";
            $kontni .= "NUMERO_TRANSFERT: " . $num_trans . "\n";
            $kontni .= "FOTO_PROFIL: " . $foto_profil . "\n";
            $kontni .= "PIECE_ID: " . $piece_id . "\n";
            $kontni .= "PREUVE_PAIEMENT: " . $preuve_paiement . "\n";
            $kontni .= "STATUT: pending\n";
            $kontni .= "======================\n\n";

            // Ekri nan fichye a (ajoute nan fen)
            if (file_put_contents($demandes_file, $kontni, FILE_APPEND | LOCK_EX) === false) {
                $errors[] = "Pa ka anrejistre demann nan. Verifye pèmisyon fichye a.";
            } else {
                // Mete ajou sesyon itilizatè a pou make ke li fè yon demann
                try {
                    $pdo->prepare("UPDATE users SET merchant_status = 'pending' WHERE id = ?")->execute([$user_id]);
                } catch (PDOException $e) {
                    // Si baz done pa disponib, kontinye kanmenm
                    error_log("Erè UPDATE user: " . $e->getMessage());
                }

                $success = true;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ht">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/le-stock/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <title>Demann Machann - LE-STOCK</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
            background: #f5f5f5;
        }

        .bg-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            overflow: hidden;
        }

        .bg-image {
            width: 100%;
            height: 100%;
            background-image: url('/le-stock/assets/img/stock9.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            filter: blur(5px);
            -webkit-filter: blur(5px);
            transform: scale(1.03);
        }

        .bg-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.25);
            z-index: -1;
        }

        .header-ble {
            background: linear-gradient(135deg, #358aa0 0%, #216079 50%, #2b7e7a 100%);
            border-radius: 1rem;
            padding: 2rem 1.5rem;
            margin: 1rem;
            text-align: center;
            box-shadow: 0 10px 40px rgba(53, 138, 160, 0.4);
            position: relative;
            overflow: hidden;
        }

        .header-ble::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            pointer-events: none;
        }

        .header-content {
            position: relative;
            z-index: 1;
        }

        .logo-container {
            position: relative;
            display: inline-block;
            margin-bottom: 1.5rem;
        }

        .logo-main {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #358aa0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .logo-badge {
            position: absolute;
            bottom: -5px;
            right: -5px;
            width: 32px;
            height: 32px;
            background: #22c55e;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
            border: 3px solid white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .header-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            line-height: 1.2;
        }

        .header-subtitle {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.95);
            font-weight: 600;
            line-height: 1.5;
            max-width: 800px;
            margin: 0 auto;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
        }

        .glass-form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2);
        }

        .glass-input {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .glass-input:focus {
            background: rgba(255, 255, 255, 1);
            border-color: #358aa0;
            box-shadow: 0 0 0 4px rgba(53, 138, 160, 0.15), 0 8px 24px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .steps-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            max-width: 700px;
            margin: 0 auto;
        }

        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 0 0 auto;
        }

        .step-circle {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s ease;
            flex-shrink: 0;
            border: 2px solid rgba(255, 255, 255, 0.5);
        }

        .step-line {
            flex: 1;
            height: 3px;
            background: rgba(255, 255, 255, 0.3);
            margin: 0 15px;
            position: relative;
            top: -22px;
            min-width: 40px;
            max-width: 150px;
        }

        .step-line-fill {
            height: 100%;
            background: linear-gradient(90deg, #358aa0, #764ba2);
            width: 0%;
            transition: width 0.5s ease;
        }

        .step-label {
            margin-top: 8px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .upload-zone {
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
        }

        .upload-zone:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            background: rgba(255, 255, 255, 1);
        }

        .upload-zone.active {
            border-color: #10b981;
            background: rgba(16, 185, 129, 0.2);
        }

        .btn-primary {
            background: linear-gradient(135deg, #358aa0 0%, #216079 100%);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px rgba(53, 138, 160, 0.4);
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .animate-float {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-slide-up {
            animation: slideUp 0.5s ease-out forwards;
        }

        @media (max-width: 768px) {
            .bg-image {
                background-attachment: scroll;
                filter: blur(4px);
                -webkit-filter: blur(4px);
                transform: scale(1.05);
            }

            .header-ble {
                padding: 1.5rem 1rem;
                margin: 0.75rem;
                border-radius: 0.75rem;
            }

            .logo-main {
                width: 60px;
                height: 60px;
                font-size: 2rem;
                border-radius: 1rem;
            }

            .logo-badge {
                width: 26px;
                height: 26px;
                font-size: 0.75rem;
                border-width: 2px;
            }

            .header-title {
                font-size: 1.75rem;
            }

            .header-subtitle {
                font-size: 0.8rem;
            }
        }

        @media (max-width: 640px) {
            .step-circle {
                width: 36px;
                height: 36px;
                font-size: 13px;
            }

            .step-line {
                height: 2px;
                top: -18px;
                margin: 0 8px;
                min-width: 20px;
            }

            .step-label {
                font-size: 10px;
                margin-top: 6px;
            }
        }
    </style>
</head>

<body>
    <div class="bg-container">
        <div class="bg-image"></div>
    </div>
    <div class="bg-overlay"></div>

    <?php if ($success): ?>
        <div class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="glass-panel rounded-3xl p-8 md:p-10 max-w-lg w-full mx-4 text-center shadow-2xl animate-slide-up">
                <div class="w-24 h-24 rounded-full bg-green-100/90 flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-check-circle text-5xl text-green-500"></i>
                </div>
                <h3 class="text-3xl font-bold text-white mb-4">Demann Voye avèk Siksè!</h3>
                <p class="text-white/90 mb-8 text-lg">Nou resevwa demann ou. Ekip nou ap revize li.</p>
                <a href="profile.php?tab=settings" class="btn-primary inline-block px-10 py-4 rounded-xl text-white font-bold text-lg">
                    Retounen nan Pwofil
                </a>
            </div>
        </div>
    <?php endif; ?>

    <div class="max-w-6xl mx-auto relative z-10">

        <div class="header-ble animate-slide-up">
            <div class="header-content">
                <div class="logo-container animate-float">
                    <div class="logo-main">
                        <i class="fas fa-store"></i>
                    </div>
                    <div class="logo-badge">
                        <i class="fas fa-plus"></i>
                    </div>
                </div>
                <h1 class="header-title">Devni yon Machann</h1>
                <p class="header-subtitle">Kreye boutik ou pwòp ou epi kòmanse vann pwodwi ou yo sou pi gwo platfòm komès Ayiti a.</p>
            </div>
        </div>

        <div class="glass-panel rounded-2xl md:rounded-3xl p-4 md:p-6 lg:p-8 mx-4 md:mx-6 lg:mx-8 mb-6 md:mb-8 animate-slide-up">
            <div class="steps-container">
                <div class="step-item">
                    <div class="step-circle bg-blue-600 text-white" id="step-1">1</div>
                    <span class="step-label">Enfòmasyon</span>
                </div>
                <div class="step-line">
                    <div class="step-line-fill" id="line-1"></div>
                </div>
                <div class="step-item">
                    <div class="step-circle bg-white/20 text-white" id="step-2">2</div>
                    <span class="step-label">Peman</span>
                </div>
                <div class="step-line">
                    <div class="step-line-fill" id="line-2"></div>
                </div>
                <div class="step-item">
                    <div class="step-circle bg-white/20 text-white" id="step-3">3</div>
                    <span class="step-label">Verifikasyon</span>
                </div>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="glass-panel rounded-2xl md:rounded-3xl p-4 md:p-6 lg:p-8 mx-4 md:mx-6 lg:mx-8 mb-6 md:mb-8 border-l-4 border-red-500 animate-slide-up">
                <div class="flex items-center gap-3 md:gap-4 text-red-200 mb-3">
                    <i class="fas fa-exclamation-circle text-xl md:text-2xl"></i>
                    <h3 class="font-bold text-base md:text-xl text-white">Erè nan fòm nan:</h3>
                </div>
                <ul class="list-disc list-inside text-red-100 text-sm md:text-lg space-y-1 md:space-y-2">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="glass-form rounded-2xl md:rounded-3xl p-6 md:p-10 lg:p-16 mx-4 md:mx-6 lg:mx-8 shadow-2xl animate-slide-up">
            <form action="" method="POST" enctype="multipart/form-data" id="merchantForm" class="space-y-6 md:space-y-10">

                <div class="space-y-4 md:space-y-6">
                    <div class="flex items-center gap-3 md:gap-4 mb-4 md:mb-6">
                        <div class="w-10 h-10 md:w-12 md:h-12 rounded-2xl bg-blue-100 flex items-center justify-center text-blue-600 text-lg md:text-xl">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <h2 class="text-xl md:text-2xl lg:text-3xl font-bold text-slate-900">Enfòmasyon Pèsonèl</h2>
                            <p class="text-slate-600 text-sm md:text-base lg:text-lg">Ranpli detay sou tèt ou</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 lg:gap-8">
                        <div class="space-y-1.5 md:space-y-2">
                            <label class="text-xs md:text-sm font-bold text-slate-600 uppercase tracking-wide ml-1">Nom <span class="text-red-500">*</span></label>
                            <input type="text" name="nom" placeholder="Ex: Rose"
                                class="glass-input w-full p-4 md:p-5 lg:p-6 rounded-xl md:rounded-2xl lg:rounded-3xl outline-none focus:ring-2 focus:ring-blue-500 font-bold text-sm md:text-base lg:text-lg placeholder:text-slate-400 placeholder:font-medium"
                                value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required>
                        </div>
                        <div class="space-y-1.5 md:space-y-2">
                            <label class="text-xs md:text-sm font-bold text-slate-600 uppercase tracking-wide ml-1">Prenom <span class="text-red-500">*</span></label>
                            <input type="text" name="prenom" placeholder="Ex: Jeremy"
                                class="glass-input w-full p-4 md:p-5 lg:p-6 rounded-xl md:rounded-2xl lg:rounded-3xl outline-none focus:ring-2 focus:ring-blue-500 font-bold text-sm md:text-base lg:text-lg placeholder:text-slate-400 placeholder:font-medium"
                                value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 lg:gap-8">
                        <div class="space-y-1.5 md:space-y-2">
                            <label class="text-xs md:text-sm font-bold text-slate-600 uppercase tracking-wide ml-1">Email <span class="text-red-500">*</span></label>
                            <input type="email" name="email" placeholder="example@mail.com"
                                class="glass-input w-full p-4 md:p-5 lg:p-6 rounded-xl md:rounded-2xl lg:rounded-3xl outline-none focus:ring-2 focus:ring-blue-500 font-bold text-sm md:text-base lg:text-lg placeholder:text-slate-400 placeholder:font-medium"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                        <div class="space-y-1.5 md:space-y-2">
                            <label class="text-xs md:text-sm font-bold text-slate-600 uppercase tracking-wide ml-1">Telefòn <span class="text-red-500">*</span></label>
                            <input type="text" name="telephone" placeholder="+509 37 65 43 21"
                                class="glass-input w-full p-4 md:p-5 lg:p-6 rounded-xl md:rounded-2xl lg:rounded-3xl outline-none focus:ring-2 focus:ring-blue-500 font-bold text-sm md:text-base lg:text-lg placeholder:text-slate-400 placeholder:font-medium"
                                value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="space-y-1.5 md:space-y-2">
                        <label class="text-xs md:text-sm font-bold text-slate-600 uppercase tracking-wide ml-1">Adrès konplè <span class="text-red-500">*</span></label>
                        <input type="text" name="adresse" placeholder="Delmas 33, Rue Saint-Martin, #45"
                            class="glass-input w-full p-4 md:p-5 lg:p-6 rounded-xl md:rounded-2xl lg:rounded-3xl outline-none focus:ring-2 focus:ring-blue-500 font-bold text-sm md:text-base lg:text-lg placeholder:text-slate-400 placeholder:font-medium"
                            value="<?= htmlspecialchars($_POST['adresse'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="pt-6 md:pt-8 lg:pt-10 border-t border-slate-200/60 space-y-4 md:space-y-6">
                    <div class="flex items-center gap-3 md:gap-4 mb-4 md:mb-6">
                        <div class="w-10 h-10 md:w-12 md:h-12 rounded-2xl bg-amber-100 flex items-center justify-center text-amber-600 text-lg md:text-xl">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div>
                            <h2 class="text-xl md:text-2xl lg:text-3xl font-bold text-slate-900">Enfòmasyon Peman</h2>
                            <p class="text-slate-600 text-sm md:text-base lg:text-lg">Peze frè enskripsyon an pou kòmanse</p>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-amber-50/95 to-orange-50/95 backdrop-blur-sm rounded-xl md:rounded-2xl lg:rounded-3xl p-4 md:p-6 lg:p-8 border border-amber-200">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 md:gap-4 mb-3 md:mb-4">
                            <div>
                                <h4 class="font-bold text-slate-800 text-base md:text-lg lg:text-xl">Frè Enskripsyon</h4>
                                <p class="text-slate-600 text-xs md:text-sm lg:text-base">Peman yon sèl fwa pou ouvri boutik ou</p>
                            </div>
                            <div class="text-left md:text-right">
                                <div class="text-2xl md:text-3xl lg:text-4xl font-bold text-amber-600">500 Gdes</div>
                                <div class="text-xs md:text-sm text-slate-500">Non rembousab</div>
                            </div>
                        </div>
                        <div class="flex gap-3 md:gap-4 text-xs md:text-sm lg:text-base">
                            <span class="flex items-center gap-1.5 md:gap-2 text-slate-700"><i class="fas fa-check-circle text-green-500"></i> MonCash</span>
                            <span class="flex items-center gap-1.5 md:gap-2 text-slate-700"><i class="fas fa-check-circle text-green-500"></i> NatCash</span>
                        </div>
                    </div>

                    <div class="space-y-1.5 md:space-y-2">
                        <label class="text-xs md:text-sm font-bold text-blue-600 uppercase tracking-wide ml-1">Nimewo ki fè transfè a <span class="text-red-500">*</span></label>
                        <input type="text" name="numero_transfert" placeholder="Nimewo MonCash oswa NatCash ki fè peman an"
                            class="glass-input w-full p-4 md:p-5 lg:p-6 bg-blue-50/50 rounded-xl md:rounded-2xl lg:rounded-3xl outline-none focus:ring-2 focus:ring-blue-500 font-bold text-sm md:text-base lg:text-lg placeholder:text-slate-400 placeholder:font-medium"
                            value="<?= htmlspecialchars($_POST['numero_transfert'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="pt-6 md:pt-8 lg:pt-10 border-t border-slate-200/60 space-y-4 md:space-y-6">
                    <div class="flex items-center gap-3 md:gap-4 mb-4 md:mb-6">
                        <div class="w-10 h-10 md:w-12 md:h-12 rounded-2xl bg-green-100 flex items-center justify-center text-green-600 text-lg md:text-xl">
                            <i class="fas fa-file-upload"></i>
                        </div>
                        <div>
                            <h2 class="text-xl md:text-2xl lg:text-3xl font-bold text-slate-900">Dokiman yo</h2>
                            <p class="text-slate-600 text-sm md:text-base lg:text-lg">Telechaje dokiman nesesè yo</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 md:gap-4 lg:gap-6">
                        <div class="upload-zone relative overflow-hidden flex flex-col items-center p-4 md:p-6 lg:p-8 border-2 border-dashed border-slate-300 rounded-xl md:rounded-2xl lg:rounded-3xl cursor-pointer hover:border-blue-300 group" onclick="document.getElementById('foto_profil').click()">
                            <input type="file" name="foto_profil" id="foto_profil" class="hidden" accept="image/*" onchange="previewImage(this, 'preview-profil')">
                            <div id="preview-profil" class="hidden mb-2 md:mb-3">
                                <img src="" alt="" class="w-16 h-16 md:w-20 md:h-20 lg:w-24 lg:h-24 rounded-lg md:rounded-xl object-cover">
                            </div>
                            <i class="fas fa-user-circle text-3xl md:text-4xl lg:text-5xl text-slate-400 mb-2 md:mb-3 group-hover:text-blue-500 transition-colors"></i>
                            <span class="text-xs font-bold text-slate-600 uppercase text-center">Foto Profil</span>
                            <span class="text-xs text-slate-400">Opsyonel</span>
                        </div>

                        <div class="upload-zone relative overflow-hidden flex flex-col items-center p-4 md:p-6 lg:p-8 border-2 border-dashed border-slate-300 rounded-xl md:rounded-2xl lg:rounded-3xl cursor-pointer hover:border-blue-300 group" onclick="document.getElementById('pyes_id').click()">
                            <input type="file" name="pyes_id" id="pyes_id" class="hidden" accept="image/*" onchange="previewImage(this, 'preview-id')">
                            <div id="preview-id" class="hidden mb-2 md:mb-3">
                                <img src="" alt="" class="w-16 h-16 md:w-20 md:h-20 lg:w-24 lg:h-24 rounded-lg md:rounded-xl object-cover">
                            </div>
                            <i class="fas fa-id-card text-3xl md:text-4xl lg:text-5xl text-slate-400 mb-2 md:mb-3 group-hover:text-blue-500 transition-colors"></i>
                            <span class="text-xs font-bold text-slate-600 uppercase text-center">Pyès Idantite</span>
                            <span class="text-xs text-slate-400">Opsyonel</span>
                        </div>

                        <div class="upload-zone relative overflow-hidden flex flex-col items-center p-4 md:p-6 lg:p-8 bg-blue-50/50 border-2 border-dashed border-blue-300 rounded-xl md:rounded-2xl lg:rounded-3xl cursor-pointer hover:bg-blue-50 group" onclick="document.getElementById('prev_peman').click()">
                            <input type="file" name="prev_peman" id="prev_peman" class="hidden" accept="image/*,.pdf" required onchange="previewImage(this, 'preview-peman')">
                            <div id="preview-peman" class="hidden mb-2 md:mb-3">
                                <img src="" alt="" class="w-16 h-16 md:w-20 md:h-20 lg:w-24 lg:h-24 rounded-lg md:rounded-xl object-cover">
                            </div>
                            <i class="fas fa-receipt text-3xl md:text-4xl lg:text-5xl text-blue-600 mb-2 md:mb-3"></i>
                            <span class="text-xs font-bold text-blue-600 uppercase text-center">Prèv Peman</span>
                            <span class="text-xs text-slate-500">Obligatwa</span>
                        </div>
                    </div>
                </div>

                <div class="pt-4 md:pt-6 lg:pt-8">
                    <button type="submit" id="submitBtn" class="btn-primary w-full py-4 md:py-5 lg:py-6 rounded-xl md:rounded-2xl lg:rounded-3xl text-white font-black text-xs md:text-sm lg:text-base uppercase tracking-[0.15em] md:tracking-[0.2em] shadow-xl flex items-center justify-center gap-2 md:gap-3 group relative overflow-hidden">
                        <span class="relative z-10">Voye Demann lan</span>
                        <i class="fas fa-paper-plane relative z-10 group-hover:translate-x-1 transition-transform"></i>
                    </button>

                    <a href="profile.php" class="block text-center mt-4 md:mt-6 text-xs md:text-sm lg:text-base font-bold text-slate-600 uppercase tracking-widest hover:text-slate-800 transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-arrow-left"></i> Anile epi retounen nan pwofil
                    </a>
                </div>
            </form>
        </div>

        <div class="mt-6 md:mt-8 lg:mt-10 grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 lg:gap-6 text-center text-xs md:text-sm lg:text-base pb-6 mx-4 md:mx-6 lg:mx-8">
            <div class="glass-panel rounded-lg md:rounded-xl lg:rounded-2xl p-3 md:p-4 lg:p-6 hover:bg-white/20 transition-colors">
                <i class="fas fa-shield-alt text-xl md:text-2xl lg:text-3xl mb-1.5 md:mb-2 lg:mb-3 text-white drop-shadow-md"></i>
                <p class="font-bold text-white">Sekirite garanti</p>
            </div>
            <div class="glass-panel rounded-lg md:rounded-xl lg:rounded-2xl p-3 md:p-4 lg:p-6 hover:bg-white/20 transition-colors">
                <i class="fas fa-clock text-xl md:text-2xl lg:text-3xl mb-1.5 md:mb-2 lg:mb-3 text-white drop-shadow-md"></i>
                <p class="font-bold text-white">Apwobasyon 24-48h</p>
            </div>
            <div class="glass-panel rounded-lg md:rounded-xl lg:rounded-2xl p-3 md:p-4 lg:p-6 hover:bg-white/20 transition-colors">
                <i class="fas fa-headset text-xl md:text-2xl lg:text-3xl mb-1.5 md:mb-2 lg:mb-3 text-white drop-shadow-md"></i>
                <p class="font-bold text-white">Sipò 24/7</p>
            </div>
            <div class="glass-panel rounded-lg md:rounded-xl lg:rounded-2xl p-3 md:p-4 lg:p-6 hover:bg-white/20 transition-colors">
                <i class="fas fa-chart-line text-xl md:text-2xl lg:text-3xl mb-1.5 md:mb-2 lg:mb-3 text-white drop-shadow-md"></i>
                <p class="font-bold text-white">Kwasans rapid</p>
            </div>
        </div>
    </div>

    <script>
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const icon = input.parentElement.querySelector('i');
            const texts = input.parentElement.querySelectorAll('span');

            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.querySelector('img').src = e.target.result;
                    preview.classList.remove('hidden');
                    icon.classList.add('hidden');
                    texts.forEach(t => t.classList.add('hidden'));
                    input.parentElement.classList.add('active');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        const form = document.getElementById('merchantForm');
        const inputs = form.querySelectorAll('input[required]');
        const line1 = document.getElementById('line-1');
        const line2 = document.getElementById('line-2');
        const step2 = document.getElementById('step-2');
        const step3 = document.getElementById('step-3');

        function updateProgress() {
            let filled = 0;
            inputs.forEach(input => {
                if (input.value.trim() !== '') filled++;
            });

            const percent = (filled / inputs.length) * 100;

            if (percent >= 33) {
                line1.style.width = '100%';
                step2.classList.remove('bg-white/20');
                step2.classList.add('bg-green-500', 'text-white');
            } else {
                line1.style.width = percent * 3 + '%';
            }

            if (percent >= 66) {
                line2.style.width = '100%';
                step3.classList.remove('bg-white/20');
                step3.classList.add('bg-green-500', 'text-white');
            } else if (percent > 33) {
                line2.style.width = (percent - 33) * 3 + '%';
            }
        }

        inputs.forEach(input => {
            input.addEventListener('input', updateProgress);
        });

        form.addEventListener('submit', function(e) {
            const btn = document.getElementById('submitBtn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Anvwaye...';
            btn.disabled = true;
        });

        updateProgress();
    </script>
</body>

</html>