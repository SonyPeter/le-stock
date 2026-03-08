<?php
session_start();

// Verifye si itilizatè a konekte
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

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

    if (empty($errors)) {
        // 1. Kreye dossier pou foto yo si l pa egziste
        $upload_dir = 'uploads/requests/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // 2. Jere foto Prèv Peman an
        $foto_name = '';
        if (isset($_FILES['prev_peman']) && $_FILES['prev_peman']['error'] == 0) {
            $foto_name = time() . "_" . basename($_FILES['prev_peman']['name']);
            $target_file = $upload_dir . $foto_name;
            move_uploaded_file($_FILES['prev_peman']['tmp_name'], $target_file);
        }

        // 3. KONTNI FICHYE TÈKS LA
        $kontni = "--- NOUVO DEMANN MACHANN ---\n";
        $kontni .= "ID Itilizatè: $user_id\n";
        $kontni .= "Non Konplè: $prenom $nom\n";
        $kontni .= "Email: $email\n";
        $kontni .= "Telefòn: $tel\n";
        $kontni .= "Adrès: $adr\n";
        $kontni .= "Nimewo Transfè: $num_trans\n";
        $kontni .= "Foto Prèv Peman: " . ($foto_name ? $target_file : 'N/A') . "\n";
        $kontni .= "Dat: " . date('d-m-Y H:i:s') . "\n";
        $kontni .= "---------------------------\n\n";

        file_put_contents("admin_demandes.txt", $kontni, FILE_APPEND);
        $success = true;
    }
}
?>

<!DOCTYPE html>
<html lang="ht">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Tailwind CSS CLI -->
    <link rel="stylesheet" href="/le-stock/css/style.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
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
        
        /* IMAJ BACKGROUND FLOU 5% */
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
            background-position: center center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            /* FLOU 5% */
            filter: blur(5px);
            -webkit-filter: blur(5px);
            transform: scale(1.03); /* Evite bò ki klè akoz flou a */
        }
        
        /* OVERLAY POU KONTRAS */
        .bg-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.25);
            z-index: -1;
        }
        
        /* KONTOU BLE POU HEADER LA SÈLMAN */
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
        
        /* Efè limyè sou kontou a */
        .header-ble::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            pointer-events: none;
        }
        
        /* Header content */
        .header-content {
            position: relative;
            z-index: 1;
        }
        
        /* Logo container */
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
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
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
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        /* Titre */
        .header-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            line-height: 1.2;
        }
        
        /* Sou-titre */
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
        
        /* Glass morphism pou reste paj la */
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
        
        /* GLASS INPUT - POU CHAMPS YO SÈLMAN */
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
        
        /* Steps container */
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
            border: 2px solid rgba(255,255,255,0.5);
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
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
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
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-slide-up {
            animation: slideUp 0.5s ease-out forwards;
        }
        
        /* RESPONSIVE POU BACKGROUND FLOU */
        @media (max-width: 768px) {
            .bg-image {
                background-attachment: scroll;
                filter: blur(4px);
                -webkit-filter: blur(4px);
                transform: scale(1.05);
            }
        }
        
        @media (max-width: 480px) {
            .bg-image {
                filter: blur(3px);
                -webkit-filter: blur(3px);
                transform: scale(1.08);
            }
        }
        
        /* RESPONSIVE POU HEADER BLE A */
        @media (max-width: 768px) {
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
        
        @media (max-width: 480px) {
            .header-ble {
                padding: 1.25rem 0.75rem;
                margin: 0.5rem;
                border-radius: 0.5rem;
            }
            
            .logo-main {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }
            
            .logo-badge {
                width: 22px;
                height: 22px;
                font-size: 0.65rem;
            }
            
            .header-title {
                font-size: 1.25rem;
                margin-bottom: 0.5rem;
            }
            
            .header-subtitle {
                font-size: 0.7rem;
                line-height: 1.4;
            }
        }
        
        /* RESPONSIVE POU STEPS */
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
        
        @media (max-width: 480px) {
            .step-circle {
                width: 30px;
                height: 30px;
                font-size: 11px;
                border-width: 1px;
            }
            
            .step-line {
                top: -15px;
                margin: 0 5px;
            }
            
            .step-label {
                font-size: 8px;
            }
        }
        
        /* RESPONSIVE GENERAL */
        @media (max-width: 768px) {
            body {
                padding: 0.5rem;
            }
            
            .glass-input {
                font-size: 16px; /* Evite zoom sou iOS */
            }
            
            .grid.grid-cols-3 {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            button, .btn-primary {
                min-height: 56px;
            }
        }
        
        @media (max-width: 480px) {
            .glass-form {
                padding: 1.25rem !important;
                border-radius: 1rem !important;
            }
        }
    </style>
</head>

<body>

    <!-- IMAJ BACKGROUND FLOU 5% -->
    <div class="bg-container">
        <div class="bg-image"></div>
    </div>
    <div class="bg-overlay"></div>

    <?php if ($success): ?>
    <!-- Modal Siksè -->
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
        
        <!-- HEADER BLE A -->
        <div class="header-ble animate-slide-up">
            <div class="header-content">
                <!-- Logo -->
                <div class="logo-container animate-float">
                    <div class="logo-main">
                        <i class="fas fa-store"></i>
                    </div>
                    <div class="logo-badge">
                        <i class="fas fa-plus"></i>
                    </div>
                </div>
                
                <!-- Titre -->
                <h1 class="header-title">Devni yon Machann</h1>
                
                <!-- Sou-titre -->
                <p class="header-subtitle">Kreye boutik ou pwòp ou epi kòmanse vann pwodwi ou yo sou pi gwo platfòm komès Ayiti a.</p>
            </div>
        </div>

        <!-- Progress Steps - DEYÒ KONTOU A -->
        <div class="glass-panel rounded-2xl md:rounded-3xl p-4 md:p-6 lg:p-8 mx-4 md:mx-6 lg:mx-8 mb-6 md:mb-8 animate-slide-up">
            <div class="steps-container">
                <!-- Step 1 -->
                <div class="step-item">
                    <div class="step-circle bg-blue-600 text-white" id="step-1">1</div>
                    <span class="step-label">Enfòmasyon</span>
                </div>
                
                <!-- Line 1 -->
                <div class="step-line">
                    <div class="step-line-fill" id="line-1"></div>
                </div>
                
                <!-- Step 2 -->
                <div class="step-item">
                    <div class="step-circle bg-white/20 text-white" id="step-2">2</div>
                    <span class="step-label">Peman</span>
                </div>
                
                <!-- Line 2 -->
                <div class="step-line">
                    <div class="step-line-fill" id="line-2"></div>
                </div>
                
                <!-- Step 3 -->
                <div class="step-item">
                    <div class="step-circle bg-white/20 text-white" id="step-3">3</div>
                    <span class="step-label">Verifikasyon</span>
                </div>
            </div>
        </div>

        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
        <div class="glass-panel rounded-2xl md:rounded-3xl p-4 md:p-6 lg:p-8 mx-4 md:mx-6 lg:mx-8 mb-6 md:mb-8 border-l-4 border-red-500 animate-slide-up">
            <div class="flex items-center gap-3 md:gap-4 text-red-200 mb-3">
                <i class="fas fa-exclamation-circle text-xl md:text-2xl"></i>
                <h3 class="font-bold text-base md:text-xl text-white">Erè nan fòm nan:</h3>
            </div>
            <ul class="list-disc list-inside text-red-100 text-sm md:text-lg space-y-1 md:space-y-2">
                <?php foreach ($errors as $error): ?>
                <li><?= $error ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Main Form -->
        <div class="glass-form rounded-2xl md:rounded-3xl p-6 md:p-10 lg:p-16 mx-4 md:mx-6 lg:mx-8 shadow-2xl animate-slide-up">
            <form action="" method="POST" enctype="multipart/form-data" id="merchantForm" class="space-y-6 md:space-y-10">
                
                <!-- Section 1: Personal Info -->
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
                            <!-- GLASS INPUT -->
                            <input type="text" name="nom" placeholder="Ex: Rose" 
                                class="glass-input w-full p-4 md:p-5 lg:p-6 rounded-xl md:rounded-2xl lg:rounded-3xl outline-none focus:ring-2 focus:ring-blue-500 font-bold text-sm md:text-base lg:text-lg placeholder:text-slate-400 placeholder:font-medium"
                                value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required>
                        </div>
                        <div class="space-y-1.5 md:space-y-2">
                            <label class="text-xs md:text-sm font-bold text-slate-600 uppercase tracking-wide ml-1">Prenom <span class="text-red-500">*</span></label>
                            <!-- GLASS INPUT -->
                            <input type="text" name="prenom" placeholder="Ex: Jeremy" 
                                class="glass-input w-full p-4 md:p-5 lg:p-6 rounded-xl md:rounded-2xl lg:rounded-3xl outline-none focus:ring-2 focus:ring-blue-500 font-bold text-sm md:text-base lg:text-lg placeholder:text-slate-400 placeholder:font-medium"
                                value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 lg:gap-8">
                        <div class="space-y-1.5 md:space-y-2">
                            <label class="text-xs md:text-sm font-bold text-slate-600 uppercase tracking-wide ml-1">Email <span class="text-red-500">*</span></label>
                            <!-- GLASS INPUT -->
                            <input type="email" name="email" placeholder="example@mail.com" 
                                class="glass-input w-full p-4 md:p-5 lg:p-6 rounded-xl md:rounded-2xl lg:rounded-3xl outline-none focus:ring-2 focus:ring-blue-500 font-bold text-sm md:text-base lg:text-lg placeholder:text-slate-400 placeholder:font-medium"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                        <div class="space-y-1.5 md:space-y-2">
                            <label class="text-xs md:text-sm font-bold text-slate-600 uppercase tracking-wide ml-1">Telefòn <span class="text-red-500">*</span></label>
                            <!-- GLASS INPUT -->
                            <input type="text" name="telephone" placeholder="+509 37 65 43 21" 
                                class="glass-input w-full p-4 md:p-5 lg:p-6 rounded-xl md:rounded-2xl lg:rounded-3xl outline-none focus:ring-2 focus:ring-blue-500 font-bold text-sm md:text-base lg:text-lg placeholder:text-slate-400 placeholder:font-medium"
                                value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="space-y-1.5 md:space-y-2">
                        <label class="text-xs md:text-sm font-bold text-slate-600 uppercase tracking-wide ml-1">Adrès konplè <span class="text-red-500">*</span></label>
                        <!-- GLASS INPUT -->
                        <input type="text" name="adresse" placeholder="Delmas 33, Rue Saint-Martin, #45" 
                            class="glass-input w-full p-4 md:p-5 lg:p-6 rounded-xl md:rounded-2xl lg:rounded-3xl outline-none focus:ring-2 focus:ring-blue-500 font-bold text-sm md:text-base lg:text-lg placeholder:text-slate-400 placeholder:font-medium"
                            value="<?= htmlspecialchars($_POST['adresse'] ?? '') ?>" required>
                    </div>
                </div>

                <!-- Section 2: Payment -->
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
                        <!-- GLASS INPUT -->
                        <input type="text" name="numero_transfert" placeholder="Nimewo MonCash oswa NatCash ki fè peman an" 
                            class="glass-input w-full p-4 md:p-5 lg:p-6 bg-blue-50/50 rounded-xl md:rounded-2xl lg:rounded-3xl outline-none focus:ring-2 focus:ring-blue-500 font-bold text-sm md:text-base lg:text-lg placeholder:text-slate-400 placeholder:font-medium"
                            value="<?= htmlspecialchars($_POST['numero_transfert'] ?? '') ?>" required>
                    </div>
                </div>

                <!-- Section 3: Documents -->
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
                        <!-- Photo Profil -->
                        <div class="upload-zone relative overflow-hidden flex flex-col items-center p-4 md:p-6 lg:p-8 border-2 border-dashed border-slate-300 rounded-xl md:rounded-2xl lg:rounded-3xl cursor-pointer hover:border-blue-300 group" onclick="document.getElementById('foto_profil').click()">
                            <input type="file" name="foto_profil" id="foto_profil" class="hidden" accept="image/*" onchange="previewImage(this, 'preview-profil')">
                            <div id="preview-profil" class="hidden mb-2 md:mb-3">
                                <img src="" alt="" class="w-16 h-16 md:w-20 md:h-20 lg:w-24 lg:h-24 rounded-lg md:rounded-xl object-cover">
                            </div>
                            <i class="fas fa-user-circle text-3xl md:text-4xl lg:text-5xl text-slate-400 mb-2 md:mb-3 group-hover:text-blue-500 transition-colors"></i>
                            <span class="text-xs font-bold text-slate-600 uppercase text-center">Foto Profil</span>
                            <span class="text-xs text-slate-400">Opsyonel</span>
                        </div>

                        <!-- ID -->
                        <div class="upload-zone relative overflow-hidden flex flex-col items-center p-4 md:p-6 lg:p-8 border-2 border-dashed border-slate-300 rounded-xl md:rounded-2xl lg:rounded-3xl cursor-pointer hover:border-blue-300 group" onclick="document.getElementById('pyes_id').click()">
                            <input type="file" name="pyes_id" id="pyes_id" class="hidden" accept="image/*" onchange="previewImage(this, 'preview-id')">
                            <div id="preview-id" class="hidden mb-2 md:mb-3">
                                <img src="" alt="" class="w-16 h-16 md:w-20 md:h-20 lg:w-24 lg:h-24 rounded-lg md:rounded-xl object-cover">
                            </div>
                            <i class="fas fa-id-card text-3xl md:text-4xl lg:text-5xl text-slate-400 mb-2 md:mb-3 group-hover:text-blue-500 transition-colors"></i>
                            <span class="text-xs font-bold text-slate-600 uppercase text-center">Pyès Idantite</span>
                            <span class="text-xs text-slate-400">Opsyonel</span>
                        </div>

                        <!-- Proof of Payment -->
                        <div class="upload-zone relative overflow-hidden flex flex-col items-center p-4 md:p-6 lg:p-8 bg-blue-50/50 border-2 border-dashed border-blue-300 rounded-xl md:rounded-2xl lg:rounded-3xl cursor-pointer hover:bg-blue-50 group" onclick="document.getElementById('prev_peman').click()">
                            <input type="file" name="prev_peman" id="prev_peman" class="hidden" accept="image/*" required onchange="previewImage(this, 'preview-peman')">
                            <div id="preview-peman" class="hidden mb-2 md:mb-3">
                                <img src="" alt="" class="w-16 h-16 md:w-20 md:h-20 lg:w-24 lg:h-24 rounded-lg md:rounded-xl object-cover">
                            </div>
                            <i class="fas fa-receipt text-3xl md:text-4xl lg:text-5xl text-blue-600 mb-2 md:mb-3"></i>
                            <span class="text-xs font-bold text-blue-600 uppercase text-center">Prèv Peman</span>
                            <span class="text-xs text-slate-500">Obligatwa</span>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
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

        <!-- Trust Badges -->
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
        // Image Preview Function
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

        // Form Validation & Progress
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
            
            // Update lines
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

        // Form submission loading state
        form.addEventListener('submit', function(e) {
            const btn = document.getElementById('submitBtn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Anvwaye...';
            btn.disabled = true;
        });

        // Initialize progress
        updateProgress();
    </script>
</body>

</html>