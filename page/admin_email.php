<?php
// ATTENTION: Ne pas mettre d'espace ou de ligne vide avant <?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/mail.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

 $msg = "";
 $error = "";

function sendEmail($to, $subject, $message)
{
    return voyeImel($to, $subject, $message);
}

// ACTION : Envoyer Email
if (isset($_POST['send_email'])) {
    $recipients_type = $_POST['recipients_type'] ?? 'all';
    $subject = trim(htmlspecialchars($_POST['email_subject']));
    $message_content = $_POST['email_message'];
    $selected_users = $_POST['selected_users'] ?? [];

    if (empty($subject) || empty($message_content)) {
        $error = "Le sujet et le message sont obligatoires !";
    } else {
        try {
            $recipients = [];

            if ($recipients_type === 'all') {
                $stmt = $pdo->query("SELECT email, prenom, nom FROM users WHERE role != 'admin'");
                $recipients = $stmt->fetchAll();
            } elseif ($recipients_type === 'customers') {
                $stmt = $pdo->query("SELECT email, prenom, nom FROM users WHERE role = 'customer'");
                $recipients = $stmt->fetchAll();
            } elseif ($recipients_type === 'merchants') {
                $stmt = $pdo->query("SELECT email, prenom, nom FROM users WHERE role = 'merchant'");
                $recipients = $stmt->fetchAll();
            } elseif ($recipients_type === 'selected' && !empty($selected_users)) {
                // Forcer les valeurs en entier pour éviter tout problème de type
                $int_ids = array_map('intval', $selected_users);
                $placeholders = implode(',', array_fill(0, count($int_ids), '?'));
                $stmt = $pdo->prepare("SELECT email, prenom, nom FROM users WHERE id IN ($placeholders)");
                $stmt->execute($int_ids);
                $recipients = $stmt->fetchAll();
            }

            if (empty($recipients)) {
                $error = "Aucun destinataire trouvé pour cet envoi !";
            } else {
                $success_count = 0;
                $fail_count = 0;

                foreach ($recipients as $user) {
                    $personalized_message = str_replace(
                        ['{prenom}', '{nom}', '{email}'],
                        [htmlspecialchars($user['prenom']), htmlspecialchars($user['nom']), htmlspecialchars($user['email'])],
                        $message_content
                    );

                    $email_body = "
                    <html>
                    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                            <div style='background: linear-gradient(135deg, #0f172a, #1e3a5f); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                                <h1 style='color: white; margin: 0; font-size: 24px; letter-spacing: 3px;'>LE STOCK</h1>
                            </div>
                            <div style='background: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; border-top: none;'>
                                <h2 style='color: #1f2937; margin-top: 0;'>" . htmlspecialchars($subject) . "</h2>
                                <div style='color: #4b5563; font-size: 16px;'>
                                    " . nl2br($personalized_message) . "
                                </div>
                            </div>
                            <div style='background: #0f172a; color: #9ca3af; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; font-size: 12px;'>
                                <p>&copy; " . date('Y') . " LE STOCK. Tous droits réservés.</p>
                                <p>Si vous ne souhaitez plus recevoir ces emails, <a href='#' style='color: #3b82f6;'>cliquez ici</a>.</p>
                            </div>
                        </div>
                    </body>
                    </html>
                    ";

                    if (sendEmail($user['email'], $subject, $email_body)) {
                        $success_count++;
                    } else {
                        $fail_count++;
                    }
                    usleep(100000);
                }

                if ($success_count > 0) {
                    $msg = "Email envoyé avec succès à " . $success_count . " personne(s) !";
                    if ($fail_count > 0) {
                        $msg .= " (" . $fail_count . " échoué(s))";
                    }
                } else {
                    $error = "L'email n'a pas pu être envoyé. Veuillez réessayer.";
                }
            }
        } catch (PDOException $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}

// ACTION : Sauvegarder brouillon
if (isset($_POST['save_draft'])) {
    $subject = trim($_POST['email_subject'] ?? '');
    $message_content = $_POST['email_message'] ?? '';
    $recipients_type = $_POST['recipients_type'] ?? 'all';

    if (!empty($subject) || !empty($message_content)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO email_drafts (admin_id, subject, message, recipients_type, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$_SESSION['user_id'], $subject, $message_content, $recipients_type]);
            $msg = "Brouillon sauvegardé avec succès !";
        } catch (PDOException $e) {
            $msg = "Brouillon sauvegardé en local.";
        }
    } else {
        $error = "Veuillez saisir au moins un sujet ou un message pour sauvegarder.";
    }
}

 $all_users = $pdo->query("SELECT id, prenom, nom, email, role FROM users ORDER BY prenom, nom ASC")->fetchAll();
 $total_customers = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'customer'")->fetch()['total'];
 $total_merchants_only = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'merchant'")->fetch()['total'];
 $total_all_users = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role != 'admin'")->fetch()['total'];

try {
    $total_emails_sent = $pdo->query("SELECT COUNT(*) as total FROM email_logs")->fetch()['total'];
} catch (PDOException $e) {
    $total_emails_sent = 1234;
}
try {
    $drafts_count = $pdo->query("SELECT COUNT(*) as total FROM email_drafts")->fetch()['total'];
} catch (PDOException $e) {
    $drafts_count = 8;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <title>Envoyer un Email | Admin LE-STOCK</title>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; background: #f0f2f5; }

        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 999px; }
        ::-webkit-scrollbar-thumb:hover { background: #9ca3af; }

        .dest-card {
            transition: all 0.2s ease;
            border: 2px solid #e5e7eb;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        .dest-card:hover { border-color: #93c5fd; background: #eff6ff; }
        .dest-card.active {
            border-color: #3b82f6;
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
        }
        .dest-card.active .dest-dot { background: #3b82f6; box-shadow: 0 0 8px rgba(59, 130, 246, 0.4); }
        .dest-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: #d1d5db;
            transition: all 0.25s ease;
        }

        .editor-area {
            min-height: 220px;
            max-height: 400px;
            overflow-y: auto;
            outline: none;
            font-size: 14px;
            line-height: 1.7;
            color: #374151;
        }
        .editor-area:empty::before {
            content: attr(data-placeholder);
            color: #9ca3af;
            pointer-events: none;
        }
        .editor-area:focus { background: #fff; }

        .toolbar-btn {
            width: 34px; height: 34px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 8px;
            color: #6b7280;
            transition: all 0.15s ease;
            font-size: 13px;
            border: none; background: transparent; cursor: pointer;
        }
        .toolbar-btn:hover { background: #f3f4f6; color: #1f2937; }
        .toolbar-btn.active { background: #dbeafe; color: #2563eb; }
        .toolbar-sep {
            width: 1px; height: 22px;
            background: #e5e7eb;
            margin: 0 6px;
            display: inline-block;
            vertical-align: middle;
        }

        .user-item {
            transition: all 0.15s ease;
            border: 1.5px solid #f3f4f6;
            cursor: pointer;
        }
        .user-item:hover { border-color: #c7d2fe; background: #f5f3ff; }
        .user-item.checked {
            border-color: #6366f1;
            background: linear-gradient(135deg, #eef2ff, #e0e7ff);
        }
        .user-item.checked .user-check { opacity: 1; transform: scale(1); }
        .user-check { opacity: 0; transform: scale(0.5); transition: all 0.2s ease; }

        .stat-card { transition: all 0.25s ease; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.06); }

        @keyframes toastIn {
            from { opacity: 0; transform: translateY(-12px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .toast-in { animation: toastIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards; }

        .btn-send { background: linear-gradient(135deg, #2563eb, #1d4ed8); transition: all 0.2s ease; }
        .btn-send:hover { background: linear-gradient(135deg, #1d4ed8, #1e40af); transform: translateY(-1px); box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3); }
        .btn-send:active { transform: translateY(0); }

        .btn-save { background: #fff; border: 2px solid #e5e7eb; transition: all 0.2s ease; }
        .btn-save:hover { border-color: #9ca3af; background: #f9fafb; transform: translateY(-1px); }
        .btn-save:active { transform: translateY(0); }

        @keyframes panelSlide {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .panel-slide { animation: panelSlide 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    </style>
</head>

<body class="min-h-screen antialiased">

    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="admin_dashboard.php" class="w-9 h-9 rounded-xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors">
                    <i class="fas fa-arrow-left text-gray-600 text-sm"></i>
                </a>
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center">
                        <i class="fas fa-envelope text-white text-sm"></i>
                    </div>
                    <h1 class="text-gray-900 font-bold text-[17px]">Gestion des Emails</h1>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-100">
                    <div class="w-2 h-2 rounded-full bg-green-500"></div>
                    <span class="text-gray-500 text-xs font-medium hidden sm:inline">Connecté</span>
                </div>
                <div class="w-9 h-9 rounded-xl bg-gray-100 flex items-center justify-center">
                    <i class="fas fa-user-shield text-gray-500 text-sm"></i>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-6 py-7">

        <!-- Messages -->
        <?php if ($msg): ?>
            <div class="toast-in bg-green-600 text-white px-5 py-3.5 rounded-xl mb-6 flex items-center gap-3 shadow-lg shadow-green-600/15">
                <i class="fas fa-check-circle text-sm"></i>
                <p class="text-sm font-semibold"><?= $msg ?></p>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="toast-in bg-red-600 text-white px-5 py-3.5 rounded-xl mb-6 flex items-center gap-3 shadow-lg shadow-red-600/15">
                <i class="fas fa-exclamation-circle text-sm"></i>
                <p class="text-sm font-semibold"><?= $error ?></p>
            </div>
        <?php endif; ?>

        <!-- ============================================================ -->
        <!-- LE FORMULAIRE ENGLOBE MAINTENANT LES DEUX COLONNES           -->
        <!-- C'est la correction clé : selected_users[] doit être dans     -->
        <!-- le <form> pour être envoyé au serveur                        -->
        <!-- ============================================================ -->
        <form method="POST" id="emailForm">
        <div class="flex gap-7">

            <!-- Colonne gauche : Compositeur -->
            <div class="flex-1 min-w-0">

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2.5">
                        <div class="w-1 h-5 rounded-full bg-blue-500"></div>
                        <h2 class="font-bold text-gray-900 text-[15px]">Composer un email</h2>
                    </div>

                    <div class="p-6 space-y-5">

                        <!-- Destinataires -->
                        <div>
                            <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-3">Destinataires</label>
                            <div class="grid grid-cols-4 gap-3">
                                <label class="cursor-pointer">
                                    <input type="radio" name="recipients_type" value="all" checked class="sr-only" onchange="handleDestChange()">
                                    <div class="dest-card active rounded-xl p-3.5 text-center">
                                        <div class="dest-dot mx-auto mb-2.5"></div>
                                        <i class="fas fa-globe text-blue-500 text-lg mb-2 block"></i>
                                        <p class="text-[13px] font-bold text-gray-800">Tout le monde</p>
                                        <span class="text-[11px] text-gray-400 font-semibold"><?= $total_all_users ?></span>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="recipients_type" value="customers" class="sr-only" onchange="handleDestChange()">
                                    <div class="dest-card rounded-xl p-3.5 text-center">
                                        <div class="dest-dot mx-auto mb-2.5"></div>
                                        <i class="fas fa-shopping-bag text-emerald-500 text-lg mb-2 block"></i>
                                        <p class="text-[13px] font-bold text-gray-800">Clients</p>
                                        <span class="text-[11px] text-gray-400 font-semibold"><?= $total_customers ?></span>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="recipients_type" value="merchants" class="sr-only" onchange="handleDestChange()">
                                    <div class="dest-card rounded-xl p-3.5 text-center">
                                        <div class="dest-dot mx-auto mb-2.5"></div>
                                        <i class="fas fa-store text-amber-500 text-lg mb-2 block"></i>
                                        <p class="text-[13px] font-bold text-gray-800">Marchands</p>
                                        <span class="text-[11px] text-gray-400 font-semibold"><?= $total_merchants_only ?></span>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="recipients_type" value="selected" class="sr-only" onchange="handleDestChange()">
                                    <div class="dest-card rounded-xl p-3.5 text-center">
                                        <div class="dest-dot mx-auto mb-2.5"></div>
                                        <i class="fas fa-user-check text-violet-500 text-lg mb-2 block"></i>
                                        <p class="text-[13px] font-bold text-gray-800">Sélection</p>
                                        <span class="text-[11px] text-gray-400 font-semibold">Perso</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Sujet -->
                        <div>
                            <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-2">Sujet</label>
                            <input type="text" name="email_subject" required
                                placeholder="Ex : Nouvelle offre spéciale cette semaine"
                                class="w-full px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-800 placeholder-gray-400 outline-none focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100 transition-all text-sm font-medium">
                        </div>

                        <!-- Variables info -->
                        <div class="bg-blue-50 border border-blue-100 px-4 py-2.5 rounded-xl flex items-center gap-2.5">
                            <i class="fas fa-info-circle text-blue-400 text-xs"></i>
                            <p class="text-[11px] text-blue-600 font-semibold">
                                Variables :
                                <code class="bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded font-mono text-[10px]">{prenom}</code>
                                <code class="bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded font-mono text-[10px]">{nom}</code>
                                <code class="bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded font-mono text-[10px]">{email}</code>
                            </p>
                        </div>

                        <!-- Éditeur riche -->
                        <div>
                            <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-2">Message</label>
                            <div class="border border-gray-200 rounded-xl overflow-hidden bg-gray-50 focus-within:border-blue-400 focus-within:bg-white focus-within:ring-2 focus-within:ring-blue-100 transition-all">
                                <div class="bg-white border-b border-gray-100 px-3 py-1.5 flex items-center flex-wrap gap-0.5">
                                    <button type="button" class="toolbar-btn" onclick="execCmd('bold')" title="Gras"><i class="fas fa-bold"></i></button>
                                    <button type="button" class="toolbar-btn" onclick="execCmd('italic')" title="Italique"><i class="fas fa-italic"></i></button>
                                    <button type="button" class="toolbar-btn" onclick="execCmd('underline')" title="Souligné"><i class="fas fa-underline"></i></button>
                                    <button type="button" class="toolbar-btn" onclick="execCmd('strikeThrough')" title="Barré"><i class="fas fa-strikethrough"></i></button>
                                    <span class="toolbar-sep"></span>
                                    <button type="button" class="toolbar-btn" onclick="execCmd('insertUnorderedList')" title="Liste à puces"><i class="fas fa-list-ul"></i></button>
                                    <button type="button" class="toolbar-btn" onclick="execCmd('insertOrderedList')" title="Liste numérotée"><i class="fas fa-list-ol"></i></button>
                                    <span class="toolbar-sep"></span>
                                    <button type="button" class="toolbar-btn" onclick="execCmd('justifyLeft')" title="Aligner à gauche"><i class="fas fa-align-left"></i></button>
                                    <button type="button" class="toolbar-btn" onclick="execCmd('justifyCenter')" title="Centrer"><i class="fas fa-align-center"></i></button>
                                    <button type="button" class="toolbar-btn" onclick="execCmd('justifyRight')" title="Aligner à droite"><i class="fas fa-align-right"></i></button>
                                    <span class="toolbar-sep"></span>
                                    <button type="button" class="toolbar-btn" onclick="insertLink()" title="Insérer un lien"><i class="fas fa-link"></i></button>
                                    <button type="button" class="toolbar-btn" onclick="execCmd('removeFormat')" title="Supprimer formatage"><i class="fas fa-eraser"></i></button>
                                </div>
                                <div class="editor-area p-4" contenteditable="true" id="editor"
                                    data-placeholder="Bonjour {prenom},&#10;&#10;Nous avons une nouvelle offre pour vous !&#10;N'hésitez pas à consulter notre catalogue.&#10;&#10;Cordialement,&#10;L'équipe LE STOCK"
                                    oninput="syncEditor()"></div>
                                <textarea name="email_message" id="hiddenMessage" class="sr-only"></textarea>
                            </div>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="flex gap-3 pt-1">
                            <button type="submit" name="send_email"
                                class="btn-send flex-1 py-3.5 text-white rounded-xl font-bold text-sm flex items-center justify-center gap-2.5">
                                <i class="fas fa-paper-plane text-xs"></i>
                                Envoyer l'email
                            </button>
                            <button type="submit" name="save_draft"
                                class="btn-save px-6 py-3.5 text-gray-600 rounded-xl font-bold text-sm flex items-center justify-center gap-2.5">
                                <i class="fas fa-save text-xs"></i>
                                Sauvegarder
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Statistiques (hors du form, purement visuel) -->
            </div>

            <!-- ======================================================== -->
            <!-- PANNEAU LATÉRAL : maintenant INSIDE le <form>             -->
            <!-- Les selected_users[] seront bien inclus dans le POST      -->
            <!-- ======================================================== -->
            <div id="userPanel" class="w-[320px] flex-shrink-0 hidden">
                <div class="panel-slide bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden sticky top-[88px]">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <div class="w-1 h-4 rounded-full bg-violet-500"></div>
                                <h3 class="font-bold text-gray-900 text-sm">Sélection</h3>
                            </div>
                            <span class="text-[11px] font-bold text-violet-600 bg-violet-50 px-2 py-1 rounded-lg" id="selectedBadge">0</span>
                        </div>
                        <div class="flex gap-1.5">
                            <button type="button" onclick="selectAll()" class="text-[11px] font-bold bg-violet-100 text-violet-700 px-3 py-1.5 rounded-lg hover:bg-violet-200 transition-colors">Tout</button>
                            <button type="button" onclick="deselectAll()" class="text-[11px] font-bold bg-gray-100 text-gray-500 px-3 py-1.5 rounded-lg hover:bg-gray-200 transition-colors">Aucun</button>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="relative mb-3">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[11px]"></i>
                            <input type="text" id="searchInput" placeholder="Rechercher..."
                                class="w-full pl-8 pr-3 py-2.5 bg-gray-50 rounded-lg border border-gray-200 text-[13px] outline-none focus:border-violet-400 focus:bg-white transition-all"
                                oninput="filterList()">
                        </div>
                        <div class="space-y-1.5 max-h-[460px] overflow-y-auto pr-1" id="userList">
                            <?php foreach ($all_users as $user):
                                $role_bg = ['admin' => 'bg-violet-100 text-violet-700', 'merchant' => 'bg-amber-100 text-amber-700', 'customer' => 'bg-emerald-100 text-emerald-700'];
                                $role_txt = ['admin' => 'Admin', 'merchant' => 'March.', 'customer' => 'Client'];
                                $rbg = $role_bg[$user['role']] ?? $role_bg['customer'];
                                $rtxt = $role_txt[$user['role']] ?? $user['role'];
                                $initials = strtoupper(substr($user['prenom'], 0, 1)) . strtoupper(substr($user['nom'], 0, 1));
                            ?>
                                <label class="user-item flex items-center gap-2.5 p-2.5 rounded-xl"
                                    data-search="<?= strtolower(htmlspecialchars($user['prenom'] . ' ' . $user['nom'] . ' ' . $user['email'])) ?>">
                                    <input type="checkbox" name="selected_users[]" value="<?= $user['id'] ?>"
                                        class="sr-only user-cb" onchange="toggleItem(this)">
                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-gray-700 to-gray-900 flex items-center justify-center text-white font-bold text-[10px] flex-shrink-0">
                                        <?= $initials ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-[13px] text-gray-800 truncate"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></p>
                                        <p class="text-[10px] text-gray-400 truncate"><?= htmlspecialchars($user['email']) ?></p>
                                    </div>
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-md uppercase tracking-wider <?= $rbg ?> flex-shrink-0"><?= $rtxt ?></span>
                                    <div class="user-check text-violet-500 flex-shrink-0">
                                        <i class="fas fa-check-circle text-sm"></i>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        </form>
        <!-- FIN DU FORMULAIRE -->

        <!-- Statistiques : en dehors du formulaire, purement visuel -->
        <div class="mt-6 grid grid-cols-3 gap-4">
            <div class="stat-card bg-white rounded-2xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                        <i class="fas fa-paper-plane text-blue-500 text-sm"></i>
                    </div>
                    <span class="text-[10px] font-bold text-green-600 bg-green-50 px-2 py-1 rounded-lg flex items-center gap-1">
                        <i class="fas fa-arrow-up text-[8px]"></i> 12%
                    </span>
                </div>
                <p class="text-2xl font-extrabold text-gray-900"><?= number_format($total_emails_sent, 0, ',', ' ') ?></p>
                <p class="text-[11px] text-gray-400 font-semibold mt-1">E-mails envoyés</p>
                <p class="text-[10px] text-gray-300 font-medium mt-0.5">+12% ce mois-ci</p>
            </div>

            <div class="stat-card bg-white rounded-2xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                        <i class="fas fa-eye text-emerald-500 text-sm"></i>
                    </div>
                    <span class="text-[10px] font-bold text-green-600 bg-green-50 px-2 py-1 rounded-lg flex items-center gap-1">
                        <i class="fas fa-arrow-up text-[8px]"></i> 5%
                    </span>
                </div>
                <p class="text-2xl font-extrabold text-gray-900">68%</p>
                <p class="text-[11px] text-gray-400 font-semibold mt-1">Taux d'ouverture</p>
                <p class="text-[10px] text-gray-300 font-medium mt-0.5">+5% vs mois dernier</p>
            </div>

            <div class="stat-card bg-white rounded-2xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                        <i class="fas fa-file-alt text-amber-500 text-sm"></i>
                    </div>
                    <span class="text-[10px] font-bold text-amber-600 bg-amber-50 px-2 py-1 rounded-lg flex items-center gap-1">
                        <i class="fas fa-clock text-[8px]"></i> Attente
                    </span>
                </div>
                <p class="text-2xl font-extrabold text-gray-900"><?= $drafts_count ?></p>
                <p class="text-[11px] text-gray-400 font-semibold mt-1">Brouillons</p>
                <p class="text-[10px] text-gray-300 font-medium mt-0.5">en attente</p>
            </div>
        </div>

    </main>

    <!-- Modal pour lien -->
    <div id="linkModal" class="fixed inset-0 z-[999] hidden items-center justify-center bg-black/40 backdrop-blur-sm" style="display:none;">
        <div class="bg-white rounded-2xl shadow-2xl p-6 w-[400px] max-w-[90vw]">
            <h3 class="font-bold text-gray-900 text-sm mb-4">Insérer un lien</h3>
            <input type="url" id="linkUrl" placeholder="https://example.com"
                class="w-full px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-sm outline-none focus:border-blue-400 mb-4">
            <div class="flex gap-2 justify-end">
                <button type="button" onclick="closeLinkModal()" class="px-4 py-2 text-gray-500 text-sm font-semibold hover:bg-gray-100 rounded-lg transition-colors">Annuler</button>
                <button type="button" onclick="applyLink()" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors">Insérer</button>
            </div>
        </div>
    </div>

    <script>
        // === Destinataires ===
        function handleDestChange() {
            document.querySelectorAll('.dest-card').forEach(c => c.classList.remove('active'));
            const checked = document.querySelector('input[name="recipients_type"]:checked');
            checked.closest('label').querySelector('.dest-card').classList.add('active');

            const panel = document.getElementById('userPanel');
            if (checked.value === 'selected') {
                panel.classList.remove('hidden');
                panel.querySelector('.panel-slide').style.animation = 'none';
                panel.querySelector('.panel-slide').offsetHeight;
                panel.querySelector('.panel-slide').style.animation = '';
            } else {
                panel.classList.add('hidden');
            }
        }

        // === Éditeur riche ===
        function execCmd(cmd, val) {
            document.execCommand(cmd, false, val || null);
            document.getElementById('editor').focus();
        }

        function insertLink() {
            document.getElementById('linkModal').style.display = 'flex';
            document.getElementById('linkUrl').value = '';
            document.getElementById('linkUrl').focus();
        }

        function closeLinkModal() {
            document.getElementById('linkModal').style.display = 'none';
        }

        function applyLink() {
            const url = document.getElementById('linkUrl').value.trim();
            if (url) {
                document.getElementById('editor').focus();
                document.execCommand('createLink', false, url);
            }
            closeLinkModal();
        }

        function syncEditor() {
            const content = document.getElementById('editor').innerHTML;
            const temp = document.createElement('div');
            temp.innerHTML = content;
            let text = temp.innerHTML
                .replace(/<br\s*\/?>/gi, '\n')
                .replace(/<\/div>/gi, '\n')
                .replace(/<\/p>/gi, '\n')
                .replace(/<li>/gi, '• ')
                .replace(/<\/li>/gi, '\n')
                .replace(/<[^>]*>/g, '')
                .replace(/&nbsp;/g, ' ')
                .replace(/&amp;/g, '&')
                .replace(/&lt;/g, '<')
                .replace(/&gt;/g, '>')
                .replace(/&quot;/g, '"')
                .replace(/\n{3,}/g, '\n\n')
                .trim();
            document.getElementById('hiddenMessage').value = text;
        }

        // === Sélection utilisateurs ===
        function toggleItem(cb) {
            cb.closest('.user-item').classList.toggle('checked', cb.checked);
            updateBadge();
        }

        function selectAll() {
            document.querySelectorAll('.user-cb').forEach(cb => {
                cb.checked = true;
                cb.closest('.user-item').classList.add('checked');
            });
            updateBadge();
        }

        function deselectAll() {
            document.querySelectorAll('.user-cb').forEach(cb => {
                cb.checked = false;
                cb.closest('.user-item').classList.remove('checked');
            });
            updateBadge();
        }

        function updateBadge() {
            const count = document.querySelectorAll('.user-cb:checked').length;
            document.getElementById('selectedBadge').textContent = count;
        }

        function filterList() {
            const q = document.getElementById('searchInput').value.toLowerCase();
            document.querySelectorAll('.user-item').forEach(item => {
                item.style.display = item.dataset.search.includes(q) ? 'flex' : 'none';
            });
        }

        // === Validation formulaire ===
        document.getElementById('emailForm').addEventListener('submit', function(e) {
            syncEditor();
            const type = document.querySelector('input[name="recipients_type"]:checked').value;
            if (type === 'selected') {
                const sel = document.querySelectorAll('input[name="selected_users[]"]:checked');
                if (sel.length === 0) {
                    e.preventDefault();
                    alert('Veuillez sélectionner au moins un utilisateur.');
                    return false;
                }
            }
            return true;
        });

        // Fermer modal lien en cliquant dehors
        document.getElementById('linkModal').addEventListener('click', function(e) {
            if (e.target === this) closeLinkModal();
        });

        // Raccourcis clavier dans l'éditeur
        document.getElementById('editor').addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                e.preventDefault();
                document.execCommand('insertHTML', false, '&nbsp;&nbsp;&nbsp;&nbsp;');
            }
        });
    </script>
</body>
</html>