<?php
session_start();

// LOJIK PHP POU TRETE FÒMILÈ A
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'] ?? 'Anonyme';
    $nom = htmlspecialchars($_POST['nom']);
    $prenom = htmlspecialchars($_POST['prenom']);
    $email = htmlspecialchars($_POST['email']);
    $tel = htmlspecialchars($_POST['telephone']);
    $adr = htmlspecialchars($_POST['adresse']);
    $num_trans = htmlspecialchars($_POST['numero_transfert']);

    // 1. Kreye dossier pou foto yo si l pa egziste
    $upload_dir = 'uploads/requests/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // 2. Jere foto Prèv Peman an
    $foto_name = time() . "_" . basename($_FILES['prev_peman']['name']);
    $target_file = $upload_dir . $foto_name;

    move_uploaded_file($_FILES['prev_peman']['tmp_name'], $target_file);

    // 3. KONTNI FICHYE TÈKS LA (Pandan n ap tann yon baz de done konplè)
    $kontni = "--- NOUVO DEMANN MACHANN ---\n";
    $kontni .= "ID Itilizatè: $user_id\n";
    $kontni .= "Non Konplè: $prenom $nom\n";
    $kontni .= "Email: $email\n";
    $kontni .= "Telefòn: $tel\n";
    $kontni .= "Adrès: $adr\n";
    $kontni .= "Nimewo Transfè: $num_trans\n";
    $kontni .= "Foto Prèv Peman: $target_file\n";
    $kontni .= "Dat: " . date('d-m-Y H:i:s') . "\n";
    $kontni .= "---------------------------\n\n";

    // Anrejistre nan fichiye tèks la
    file_put_contents("admin_demandes.txt", $kontni, FILE_APPEND);

    // 4. Mesaj siksè ak Redireksyon
    echo "<script>
            alert('Demann ou voye byen jwenn Admin nan!'); 
            window.location.href='profile.php?tab=settings';
          </script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="ht">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <title>Demann Machann - LE-STOCK</title>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>

<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-2xl w-full bg-white p-8 md:p-12 rounded-[2.5rem] shadow-xl border border-slate-100">
        <div class="text-center mb-10">
            <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center text-white text-2xl mx-auto shadow-lg mb-4 shadow-blue-100">
                <i class="fas fa-store"></i>
            </div>
            <h1 class="text-3xl font-black italic tracking-tighter uppercase text-slate-900">Demann Machann</h1>
            <p class="text-slate-400 text-sm mt-2 font-medium">Ranpli fòm sa a pou w ka kòmanse vann sou platfòm nan.</p>
        </div>

        <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Nom</label>
                    <input type="text" name="nom" placeholder="Rose" class="w-full p-4 bg-slate-50 rounded-2xl border-0 ring-1 ring-slate-200 outline-none focus:ring-2 focus:ring-blue-500 font-bold" required>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Prenom</label>
                    <input type="text" name="prenom" placeholder="Jeremy" class="w-full p-4 bg-slate-50 rounded-2xl border-0 ring-1 ring-slate-200 outline-none focus:ring-2 focus:ring-blue-500 font-bold" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Email</label>
                    <input type="email" name="email" placeholder="example@mail.com" class="w-full p-4 bg-slate-50 rounded-2xl border-0 ring-1 ring-slate-200 outline-none focus:ring-2 focus:ring-blue-500 font-bold" required>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Telefòn</label>
                    <input type="text" name="telephone" placeholder="+509..." class="w-full p-4 bg-slate-50 rounded-2xl border-0 ring-1 ring-slate-200 outline-none focus:ring-2 focus:ring-blue-500 font-bold" required>
                </div>
            </div>

            <div class="space-y-1">
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Adrès konplè</label>
                <input type="text" name="adresse" placeholder="Delmas 33, lari..." class="w-full p-4 bg-slate-50 rounded-2xl border-0 ring-1 ring-slate-200 outline-none focus:ring-2 focus:ring-blue-500 font-bold" required>
            </div>

            <div class="space-y-1">
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 text-blue-600">Nimewo ki fè transfè a</label>
                <input type="text" name="numero_transfert" placeholder="Nimewo MonCash oswa Natcash" class="w-full p-4 bg-blue-50/50 rounded-2xl border-0 ring-1 ring-blue-100 outline-none focus:ring-2 focus:ring-blue-500 font-bold" required>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <label class="flex flex-col items-center p-5 bg-slate-50 border-2 border-dashed border-slate-200 rounded-3xl cursor-pointer hover:bg-blue-50 hover:border-blue-200 transition-all group">
                    <i class="fas fa-user-circle text-slate-400 mb-2 group-hover:text-blue-500"></i>
                    <span class="text-[9px] font-black uppercase text-slate-500">Foto Profil</span>
                    <input type="file" name="foto_profil" class="hidden">
                </label>

                <label class="flex flex-col items-center p-5 bg-slate-50 border-2 border-dashed border-slate-200 rounded-3xl cursor-pointer hover:bg-blue-50 hover:border-blue-200 transition-all group">
                    <i class="fas fa-id-card text-slate-400 mb-2 group-hover:text-blue-500"></i>
                    <span class="text-[9px] font-black uppercase text-slate-500">Pyès Idantite</span>
                    <input type="file" name="pyes_id" class="hidden">
                </label>

                <label class="flex flex-col items-center p-5 bg-blue-50 border-2 border-dashed border-blue-200 rounded-3xl cursor-pointer hover:bg-blue-600 hover:text-white transition-all group">
                    <i class="fas fa-receipt text-blue-600 mb-2 group-hover:text-white text-xl"></i>
                    <span class="text-[9px] font-black uppercase text-blue-600 group-hover:text-white">Prèv Peman</span>
                    <input type="file" name="prev_peman" class="hidden" required>
                </label>
            </div>

            <button type="submit" class="w-full py-5 bg-slate-900 text-white rounded-2xl font-black text-xs uppercase tracking-[0.2em] shadow-xl hover:bg-blue-600 transition-all italic">
                Voye Demann lan bay Admin
            </button>

            <a href="profile.php" class="block text-center text-[9px] font-black text-slate-400 uppercase tracking-widest hover:text-slate-600 transition-colors">
                <i class="fas fa-arrow-left mr-1"></i> Anile epi tounen
            </a>
        </form>
    </div>
</body>

</html>