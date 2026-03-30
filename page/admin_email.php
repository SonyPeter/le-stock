<?php
// ATANSYON: Pa gen espas oswa liy vid anvan <?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';
// AJOUTE SA YO POU PHPMAILER TRAVAY
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/mail.php';

// Sekirite: Sèlman Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$msg = "";
$error = "";

// Fonksyon pou voye email (MODIFYE POU ITILIZE PHPMAILER)
function sendEmail($to, $subject, $message)
{
    // Nou rele fonksyon voyeImel ki nan config/mail.php la
    return voyeImel($to, $subject, $message);
}

// AKSYON: Voye Email
if (isset($_POST['send_email'])) {
    $recipients_type = $_POST['recipients_type'] ?? 'all';
    $subject = trim(htmlspecialchars($_POST['email_subject']));
    $message_content = $_POST['email_message'];
    $selected_users = $_POST['selected_users'] ?? [];

    if (empty($subject) || empty($message_content)) {
        $error = "Sijè ak mesaj yo obligatwa!";
    } else {
        try {
            $recipients = [];

            if ($recipients_type === 'all') {
                // Voye bay tout itilizatè yo
                $stmt = $pdo->query("SELECT email, prenom, nom FROM users WHERE role != 'admin'");
                $recipients = $stmt->fetchAll();
            } elseif ($recipients_type === 'customers') {
                // Voye sèlman bay kliyan yo
                $stmt = $pdo->query("SELECT email, prenom, nom FROM users WHERE role = 'customer'");
                $recipients = $stmt->fetchAll();
            } elseif ($recipients_type === 'merchants') {
                // Voye sèlman bay machann yo
                $stmt = $pdo->query("SELECT email, prenom, nom FROM users WHERE role = 'merchant'");
                $recipients = $stmt->fetchAll();
            } elseif ($recipients_type === 'selected' && !empty($selected_users)) {
                // Voye bay itilizatè seleksyone yo
                $placeholders = implode(',', array_fill(0, count($selected_users), '?'));
                $stmt = $pdo->prepare("SELECT email, prenom, nom FROM users WHERE id IN ($placeholders)");
                $stmt->execute($selected_users);
                $recipients = $stmt->fetchAll();
            }

            if (empty($recipients)) {
                $error = "Pa gen destinataè pou voye email la!";
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
                            <div style='background: linear-gradient(135deg, #3b82f6, #8b5cf6); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                                <h1 style='color: white; margin: 0; font-size: 24px;'>LE STOCK</h1>
                            </div>
                            <div style='background: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; border-top: none;'>
                                <h2 style='color: #1f2937; margin-top: 0;'>" . htmlspecialchars($subject) . "</h2>
                                <div style='color: #4b5563; font-size: 16px;'>
                                    " . nl2br($personalized_message) . "
                                </div>
                            </div>
                            <div style='background: #1f2937; color: #9ca3af; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; font-size: 12px;'>
                                <p>© " . date('Y') . " LE STOCK. Tout dwa rezève.</p>
                                <p>Si ou pa vle resevwa email sa yo ankò, <a href='#' style='color: #3b82f6;'>klike isit la</a>.</p>
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

                    // Ti delè pou evite spam
                    usleep(100000);
                }

                if ($success_count > 0) {
                    $msg = "Email voye ak siksè bay " . $success_count . " moun!";
                    if ($fail_count > 0) {
                        $msg .= " (" . $fail_count . " echwe)";
                    }
                } else {
                    $error = "Email la pa t' kapab voye. Tanpri eseye ankò.";
                }
            }
        } catch (PDOException $e) {
            $error = "Erè: " . $e->getMessage();
        }
    }
}

// Rekipere tout itilizatè yo pou seleksyon
$all_users = $pdo->query("SELECT id, prenom, nom, email, role FROM users ORDER BY prenom, nom ASC")->fetchAll();

// Konte itilizatè pa kategori
$total_customers = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'customer'")->fetch()['total'];
$total_merchants_only = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'merchant'")->fetch()['total'];
$total_all_users = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role != 'admin'")->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="ht">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Voye Email | Admin LE-STOCK</title>
    <style>
        .user-card.selected {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }

        .user-card.selected .check-icon {
            opacity: 1;
        }
    </style>
</head>

<body class="bg-slate-100 min-h-screen italic font-bold">

    <div class="bg-slate-900 text-white p-6 shadow-lg">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="admin_dashboard.php" class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-xl transition-all">
                    <i class="fas fa-arrow-left mr-2"></i> Retounen
                </a>
                <h1 class="text-2xl font-black uppercase tracking-tighter">
                    <i class="fas fa-envelope mr-2 text-blue-500"></i> Jesyon Email
                </h1>
            </div>
            <div class="text-sm text-slate-400">
                <i class="fas fa-user-shield mr-1"></i> Admin
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto p-6">

        <?php if ($msg): ?>
            <div class="bg-green-600 text-white p-4 rounded-2xl mb-6 shadow-lg uppercase text-xs text-center flex items-center justify-center gap-2">
                <i class="fas fa-check-circle"></i> <?= $msg ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-600 text-white p-4 rounded-2xl mb-6 shadow-lg uppercase text-xs text-center flex items-center justify-center gap-2">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 md:p-8">
                    <h2 class="text-xl font-black mb-6 border-l-4 border-blue-600 pl-4 uppercase">
                        Konpoze Email
                    </h2>

                    <form method="POST" id="emailForm" class="space-y-6">

                        <div>
                            <label class="block text-xs text-slate-500 mb-3 uppercase font-bold">
                                <i class="fas fa-users mr-1"></i> Chwazi Destinataè yo
                            </label>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                                <label class="cursor-pointer">
                                    <input type="radio" name="recipients_type" value="all" checked
                                        class="peer sr-only" onchange="toggleUserSelection()">
                                    <div class="p-4 rounded-2xl border-2 border-slate-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all text-center">
                                        <i class="fas fa-globe text-2xl mb-2 text-blue-500"></i>
                                        <p class="text-xs font-bold">Tout Moun</p>
                                        <span class="text-xs text-slate-500">(<?= $total_all_users ?>)</span>
                                    </div>
                                </label>

                                <label class="cursor-pointer">
                                    <input type="radio" name="recipients_type" value="customers"
                                        class="peer sr-only" onchange="toggleUserSelection()">
                                    <div class="p-4 rounded-2xl border-2 border-slate-200 peer-checked:border-green-500 peer-checked:bg-green-50 transition-all text-center">
                                        <i class="fas fa-shopping-bag text-2xl mb-2 text-green-500"></i>
                                        <p class="text-xs font-bold">Kliyan</p>
                                        <span class="text-xs text-slate-500">(<?= $total_customers ?>)</span>
                                    </div>
                                </label>

                                <label class="cursor-pointer">
                                    <input type="radio" name="recipients_type" value="merchants"
                                        class="peer sr-only" onchange="toggleUserSelection()">
                                    <div class="p-4 rounded-2xl border-2 border-slate-200 peer-checked:border-amber-500 peer-checked:bg-amber-50 transition-all text-center">
                                        <i class="fas fa-store text-2xl mb-2 text-amber-500"></i>
                                        <p class="text-xs font-bold">Machann</p>
                                        <span class="text-xs text-slate-500">(<?= $total_merchants_only ?>)</span>
                                    </div>
                                </label>

                                <label class="cursor-pointer">
                                    <input type="radio" name="recipients_type" value="selected"
                                        class="peer sr-only" onchange="toggleUserSelection()">
                                    <div class="p-4 rounded-2xl border-2 border-slate-200 peer-checked:border-purple-500 peer-checked:bg-purple-50 transition-all text-center">
                                        <i class="fas fa-user-check text-2xl mb-2 text-purple-500"></i>
                                        <p class="text-xs font-bold">Seleksyone</p>
                                        <span class="text-xs text-slate-500">(Custom)</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs text-slate-500 mb-2 uppercase font-bold">
                                <i class="fas fa-heading mr-1"></i> Sijè Email la *
                            </label>
                            <input type="text" name="email_subject" required
                                placeholder="Antre sijè email la..."
                                class="w-full p-4 bg-slate-50 rounded-2xl outline-none ring-1 ring-slate-200 focus:ring-2 focus:ring-blue-500 transition-all">
                        </div>

                        <div>
                            <label class="block text-xs text-slate-500 mb-2 uppercase font-bold">
                                <i class="fas fa-align-left mr-1"></i> Kontni Mesaj la *
                            </label>
                            <div class="bg-blue-50 p-3 rounded-xl mb-2 text-xs text-slate-600">
                                <i class="fas fa-info-circle mr-1 text-blue-500"></i>
                                Ou ka itilize varyab sa yo: <code>{prenom}</code>, <code>{nom}</code>, <code>{email}</code>
                            </div>
                            <textarea name="email_message" rows="8" required
                                placeholder="Antre mesaj ou a isit la...\n\nExanple:\nBonjou {prenom},\n\nNou gen yon nouvèl ofri pou ou!"
                                class="w-full p-4 bg-slate-50 rounded-2xl outline-none ring-1 ring-slate-200 focus:ring-2 focus:ring-blue-500 transition-all resize-none"></textarea>
                        </div>

                        <button type="submit" name="send_email"
                            class="w-full py-5 bg-blue-600 text-white rounded-2xl font-black uppercase tracking-widest hover:bg-blue-700 transition-all flex items-center justify-center gap-3">
                            <i class="fas fa-paper-plane text-xl"></i>
                            Voye Email la
                        </button>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-1" id="userSelectionPanel" style="display: none;">
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 sticky top-6 max-h-[calc(100vh-100px)] overflow-y-auto">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-black uppercase text-sm">
                            <i class="fas fa-user-friends mr-2 text-purple-500"></i> Seleksyone Itilizatè
                        </h3>
                        <div class="flex gap-2">
                            <button onclick="selectAllUsers()" class="text-xs bg-purple-100 text-purple-700 px-3 py-1 rounded-lg hover:bg-purple-200">
                                Tout
                            </button>
                            <button onclick="deselectAllUsers()" class="text-xs bg-slate-100 text-slate-700 px-3 py-1 rounded-lg hover:bg-slate-200">
                                Okenn
                            </button>
                        </div>
                    </div>

                    <input type="text" id="searchUsers" placeholder="Chèche itilizatè..."
                        class="w-full p-3 bg-slate-50 rounded-xl text-sm mb-4 outline-none ring-1 ring-slate-200"
                        onkeyup="filterUsers()">

                    <div class="space-y-2 max-h-[400px] overflow-y-auto" id="usersList">
                        <?php foreach ($all_users as $user):
                            $role_color = $user['role'] === 'admin' ? 'text-purple-600 bg-purple-100' : ($user['role'] === 'merchant' ? 'text-amber-600 bg-amber-100' : 'text-green-600 bg-green-100');
                        ?>
                            <label class="user-card flex items-center gap-3 p-3 rounded-xl border-2 border-slate-100 cursor-pointer transition-all hover:border-slate-300"
                                data-name="<?= strtolower(htmlspecialchars($user['prenom'] . ' ' . $user['nom'])) ?>"
                                data-email="<?= strtolower(htmlspecialchars($user['email'])) ?>">
                                <input type="checkbox" name="selected_users[]" value="<?= $user['id'] ?>"
                                    class="user-checkbox sr-only" onchange="toggleUserCard(this)">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                                    <?= strtoupper(substr($user['prenom'], 0, 1)) ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-sm truncate"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></p>
                                    <p class="text-xs text-slate-500 truncate"><?= htmlspecialchars($user['email']) ?></p>
                                </div>
                                <span class="text-xs px-2 py-1 rounded-full <?= $role_color ?>">
                                    <?= $user['role'] ?>
                                </span>
                                <div class="check-icon opacity-0 text-blue-500 transition-opacity">
                                    <i class="fas fa-check-circle text-lg"></i>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <p class="text-xs text-slate-500 text-center">
                            <span id="selectedCount">0</span> itilizatè seleksyone
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-black uppercase text-sm mb-4 border-l-4 border-slate-600 pl-4">
                <i class="fas fa-history mr-2"></i> Istwa Voye Email
            </h3>
            <div class="bg-slate-50 rounded-2xl p-8 text-center text-slate-400">
                <i class="fas fa-inbox text-4xl mb-3"></i>
                <p class="text-sm">Fonksyon istwa ap disponib nan pwochen vèsyon an.</p>
            </div>
        </div>
    </div>

    <script>
        function toggleUserSelection() {
            const selectedType = document.querySelector('input[name="recipients_type"]:checked').value;
            const panel = document.getElementById('userSelectionPanel');

            if (selectedType === 'selected') {
                panel.style.display = 'block';
                setTimeout(() => panel.scrollIntoView({
                    behavior: 'smooth'
                }), 100);
            } else {
                panel.style.display = 'none';
            }
        }

        function toggleUserCard(checkbox) {
            const card = checkbox.closest('.user-card');
            if (checkbox.checked) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
            updateSelectedCount();
        }

        function selectAllUsers() {
            document.querySelectorAll('.user-checkbox').forEach(cb => {
                cb.checked = true;
                cb.closest('.user-card').classList.add('selected');
            });
            updateSelectedCount();
        }

        function deselectAllUsers() {
            document.querySelectorAll('.user-checkbox').forEach(cb => {
                cb.checked = false;
                cb.closest('.user-card').classList.remove('selected');
            });
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const count = document.querySelectorAll('.user-checkbox:checked').length;
            document.getElementById('selectedCount').textContent = count;
        }

        function filterUsers() {
            const search = document.getElementById('searchUsers').value.toLowerCase();
            document.querySelectorAll('.user-card').forEach(card => {
                const name = card.dataset.name;
                const email = card.dataset.email;
                if (name.includes(search) || email.includes(search)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Verifye si gen itilizatè seleksyone anvan voye fòm nan
        document.getElementById('emailForm').addEventListener('submit', function(e) {
            const selectedType = document.querySelector('input[name="recipients_type"]:checked').value;
            if (selectedType === 'selected') {
                const selected = document.querySelectorAll('input[name="selected_users[]"]:checked');
                if (selected.length === 0) {
                    e.preventDefault();
                    alert('Tanpri seleksyone omwen yon itilizatè!');
                    return false;
                }
            }
            return true;
        });
    </script>
</body>

</html>