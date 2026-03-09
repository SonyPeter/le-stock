<?php
// ATANSYON: Pa gen espas oswa liy vid anvan <?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';

// Sekirite: Sèlman Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$msg = "";
$error = "";
$section = $_GET['section'] ?? 'dashboard';

// Fonksyon pou redireksyon ak mesaj - DWE ANVAN TOUT SÒTI
function redirect($section, $message = '', $error = '')
{
    $url = "admin.php?section=" . $section;
    if ($message) $url .= "&msg=" . urlencode($message);
    if ($error) $url .= "&error=" . urlencode($error);
    header("Location: " . $url);
    exit();
}

// 1. AJOUTE KATEGORI
if (isset($_POST['add_cat'])) {
    $nom_cat = trim(htmlspecialchars($_POST['nom_cat']));
    if (!empty($nom_cat)) {
        try {
            // VERIFYE SI KATEGORI A EGZISTE DEJA (pa non, pa id)
            $check = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
            $check->execute([$nom_cat]);
            if ($check->rowCount() > 0) {
                $error = "Kategori sa egziste deja!";
            } else {
                // AJOUTE KATEGORI (KOLÒN SE 'name', PA 'nom')
                $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                $stmt->execute([$nom_cat]);
                redirect('categories', "Kategori ajoute ak siksè!");
            }
        } catch (PDOException $e) {
            $error = "Erè: " . $e->getMessage();
        }
    } else {
        $error = "Non kategori a obligatwa!";
    }
}

// 2. AJOUTE PWODWI
if (isset($_POST['add_product'])) {
    $nom = trim(htmlspecialchars($_POST['p_nom']));
    $prix_reg = $_POST['p_prix_reg'];
    $prix_promo = !empty($_POST['p_prix_promo']) ? $_POST['p_prix_promo'] : null;
    $qty = $_POST['p_qty'];
    $cat_id = $_POST['p_category']; // ID kategori a, pa non li
    $desc = htmlspecialchars($_POST['p_desc']);
    $carac = htmlspecialchars($_POST['p_carac']);
    $status = ($qty > 0) ? 'disponible' : 'indisponible';

    if (empty($nom) || empty($prix_reg) || empty($qty) || empty($cat_id)) {
        $error = "Tout chan obligatwa yo dwe ranpli!";
    } elseif (!isset($_FILES['p_img']) || $_FILES['p_img']['error'] != 0) {
        $error = "Imaj la obligatwa!";
    } else {
        $upload_dir = "../uploads/products/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $img = time() . "_" . basename($_FILES['p_img']['name']);
        $target_file = $upload_dir . $img;

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = mime_content_type($_FILES['p_img']['tmp_name']);

        if (!in_array($file_type, $allowed_types)) {
            $error = "Se sèlman fichye imaj (JPG, PNG, GIF, WEBP) ki aksepte!";
        } elseif (move_uploaded_file($_FILES['p_img']['tmp_name'], $target_file)) {
            try {
                // KOLÒN YO: name, price, price_promo, stock_qty, category_id, description, caractéristiques, image, status
                $sql = "INSERT INTO products (name, price, price_promo, stock_qty, category_id, description, caractéristiques, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $pdo->prepare($sql)->execute([$nom, $prix_reg, $prix_promo, $qty, $cat_id, $desc, $carac, $img, $status]);
                redirect('products', "Pwodwi ajoute nan envantè a!");
            } catch (PDOException $e) {
                $error = "Erè: " . $e->getMessage();
                if (file_exists($target_file)) {
                    unlink($target_file);
                }
            }
        } else {
            $error = "Erè pandan transfè fichye a!";
        }
    }
}

// 3. APWOUVE MACHANN
if (isset($_POST['approve_merchant'])) {
    $u_id = $_POST['user_id'];
    try {
        $pdo->prepare("UPDATE users SET role = 'merchant', merchant_status = 'approved' WHERE id = ?")->execute([$u_id]);
        redirect('merchants', "Itilizatè a apwouve kòm Machann!");
    } catch (PDOException $e) {
        $error = "Erè: " . $e->getMessage();
    }
}

// 4. REJTE MACHANN
if (isset($_POST['reject_merchant'])) {
    $u_id = $_POST['user_id'];
    try {
        $pdo->prepare("UPDATE users SET merchant_status = 'rejected' WHERE id = ?")->execute([$u_id]);
        redirect('merchants', "Demann machann lan rejte!");
    } catch (PDOException $e) {
        $error = "Erè: " . $e->getMessage();
    }
}

// 5. EFASE KATEGORI
if (isset($_POST['delete_cat'])) {
    $cat_id = $_POST['cat_id'];
    try {
        // Verifye si gen pwodwi ki lye ak kategori sa
        $check = $pdo->prepare("SELECT COUNT(*) as total FROM products WHERE category_id = ?");
        $check->execute([$cat_id]);
        $result = $check->fetch();

        if ($result['total'] > 0) {
            $error = "Ou pa ka efase kategori sa paske gen pwodwi ki lye ak li!";
        } else {
            $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$cat_id]);
            redirect('categories', "Kategori efase!");
        }
    } catch (PDOException $e) {
        $error = "Erè: " . $e->getMessage();
    }
}

// 6. EFASE PWODWI
if (isset($_POST['delete_product'])) {
    $p_id = $_POST['product_id'];
    try {
        $img_stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $img_stmt->execute([$p_id]);
        $img_data = $img_stmt->fetch();

        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$p_id]);

        if ($img_data && file_exists("../uploads/products/" . $img_data['image'])) {
            unlink("../uploads/products/" . $img_data['image']);
        }

        redirect('products', "Pwodwi efase!");
    } catch (PDOException $e) {
        $error = "Erè: " . $e->getMessage();
    }
}

// 7. MODIFYE PWODWI
if (isset($_POST['update_product'])) {
    $p_id = $_POST['product_id'];
    $nom = trim(htmlspecialchars($_POST['edit_nom']));
    $prix = $_POST['edit_prix'];
    $prix_promo = !empty($_POST['edit_prix_promo']) ? $_POST['edit_prix_promo'] : null;
    $qty = $_POST['edit_qty'];
    $cat_id = $_POST['edit_category'];
    $desc = htmlspecialchars($_POST['edit_desc']);
    $status = ($qty > 0) ? 'disponible' : 'indisponible';

    try {
        if (isset($_FILES['edit_img']) && $_FILES['edit_img']['error'] == 0) {
            $upload_dir = "../uploads/products/";
            $img = time() . "_" . basename($_FILES['edit_img']['name']);
            $target_file = $upload_dir . $img;

            $old_img_stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
            $old_img_stmt->execute([$p_id]);
            $old_img = $old_img_stmt->fetch()['image'] ?? null;

            if (move_uploaded_file($_FILES['edit_img']['tmp_name'], $target_file)) {
                $sql = "UPDATE products SET name = ?, price = ?, price_promo = ?, stock_qty = ?, category_id = ?, description = ?, image = ?, status = ? WHERE id = ?";
                $pdo->prepare($sql)->execute([$nom, $prix, $prix_promo, $qty, $cat_id, $desc, $img, $status, $p_id]);

                if ($old_img && file_exists($upload_dir . $old_img)) {
                    unlink($upload_dir . $old_img);
                }
            }
        } else {
            $sql = "UPDATE products SET name = ?, price = ?, price_promo = ?, stock_qty = ?, category_id = ?, description = ?, status = ? WHERE id = ?";
            $pdo->prepare($sql)->execute([$nom, $prix, $prix_promo, $qty, $cat_id, $desc, $status, $p_id]);
        }
        redirect('products', "Pwodwi mete ajou!");
    } catch (PDOException $e) {
        $error = "Erè: " . $e->getMessage();
    }
}

// Rekipere mesaj soti nan URL
if (isset($_GET['msg'])) $msg = htmlspecialchars($_GET['msg']);
if (isset($_GET['error'])) $error = htmlspecialchars($_GET['error']);

// Rekipere done yo selon sektyon an
$all_cats = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$pending_merchants = $pdo->query("SELECT * FROM users WHERE merchant_status = 'pending'")->fetchAll();

// Pou pwodwi, mwen bezwen JOIN ak categories pou jwenn non kategori a
$all_products = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.id DESC
")->fetchAll();

$all_users = $pdo->query("SELECT id, prenom, nom, email, role, merchant_status, created_at FROM users ORDER BY id DESC")->fetchAll();

$total_products = count($all_products);
$total_categories = count($all_cats);
$total_users = count($all_users);
$total_merchants = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'merchant'")->fetch()['total'];

// Fonksyon pou verifye si seksyon an aktif
function isActive($current, $section)
{
    return $current === $section ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="ht">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Admin Panel | LE-STOCK</title>
    <style>
        /* TOUT STIL YO MENM JAN AK AVAN */
        .italic-bold {
            font-style: italic;
            font-weight: bold;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .sidebar-btn {
            width: 100%;
            text-align: left;
            padding: 1rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s;
            background: transparent;
            border: none;
            color: white;
            cursor: pointer;
            font-family: inherit;
            font-style: italic;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .sidebar-btn:hover {
            background-color: #1e293b;
        }

        .sidebar-btn.active {
            background-color: #1e293b;
            border-left: 4px solid #3b82f6;
        }

        .product-card:hover {
            transform: translateY(-5px);
            transition: all 0.3s ease;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body class="bg-slate-100 flex min-h-screen italic-bold">

    <!-- Sidebar -->
    <div class="w-72 bg-slate-900 text-white p-6 sticky top-0 h-screen shadow-2xl overflow-y-auto flex-shrink-0">
        <h1 class="text-2xl font-black italic tracking-tighter mb-10 text-center uppercase">
            LE STOCK <span class="text-blue-500">ADMIN</span>
        </h1>

        <div class="mb-6 pb-6 border-b border-slate-700">
            <p class="text-xs text-slate-400 uppercase mb-2">Konekte kòm:</p>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div>
                    <p class="font-bold text-sm"><?= htmlspecialchars($_SESSION['prenom'] ?? 'Admin') ?></p>
                    <p class="text-xs text-slate-400">Administratè</p>
                </div>
            </div>
        </div>

        <nav class="space-y-2">
            <a href="?section=dashboard" class="sidebar-btn <?= isActive($section, 'dashboard') ?>">
                <i class="fas fa-chart-line w-6"></i> Dashboard
            </a>
            <a href="?section=merchants" class="sidebar-btn <?= isActive($section, 'merchants') ?>">
                <i class="fas fa-user-clock w-6"></i>
                Demandes Machann
                <?php if (count($pending_merchants) > 0): ?>
                    <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full ml-auto"><?= count($pending_merchants) ?></span>
                <?php endif; ?>
            </a>
            <a href="?section=categories" class="sidebar-btn <?= isActive($section, 'categories') ?>">
                <i class="fas fa-tags w-6"></i> Gestion Catégories
            </a>
            <a href="?section=add-product" class="sidebar-btn <?= isActive($section, 'add-product') ?>">
                <i class="fas fa-box-open w-6"></i> Ajouter Produits
            </a>
            <a href="?section=products" class="sidebar-btn <?= isActive($section, 'products') ?>">
                <i class="fas fa-list w-6"></i> Lisyen Pwodwi
            </a>
            <a href="?section=users" class="sidebar-btn <?= isActive($section, 'users') ?>">
                <i class="fas fa-users w-6"></i> Itilizatè yo
            </a>
            <a href="?section=promotions" class="sidebar-btn <?= isActive($section, 'promotions') ?>">
                <i class="fas fa-percentage w-6"></i> Promotions
            </a>
        </nav>

        <div class="mt-8 pt-6 border-t border-slate-700">
            <a href="logout.php" class="sidebar-btn text-red-400 hover:text-white hover:bg-red-600">
                <i class="fas fa-sign-out-alt w-6"></i> Dekonekte
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 p-10 overflow-y-auto min-h-screen">

        <!-- Alerts -->
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

        <?php
        // SEKSYON DASHBOARD
        if ($section === 'dashboard'):
        ?>
            <h2 class="text-3xl font-black mb-8 border-l-4 border-blue-600 pl-4 uppercase">Tableau de Bord</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-slate-400 text-xs uppercase">Total Pwodwi</p>
                            <h3 class="text-3xl font-black text-blue-600"><?= $total_products ?></h3>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-xl">
                            <i class="fas fa-box"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-slate-400 text-xs uppercase">Kategori</p>
                            <h3 class="text-3xl font-black text-purple-600"><?= $total_categories ?></h3>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 text-xl">
                            <i class="fas fa-tags"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-slate-400 text-xs uppercase">Itilizatè</p>
                            <h3 class="text-3xl font-black text-green-600"><?= $total_users ?></h3>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center text-green-600 text-xl">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-slate-400 text-xs uppercase">Machann</p>
                            <h3 class="text-3xl font-black text-amber-600"><?= $total_merchants ?></h3>
                        </div>
                        <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center text-amber-600 text-xl">
                            <i class="fas fa-store"></i>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($section === 'merchants'): ?>
            <!-- KÒD POU MACHANN YO -->
            <h2 class="text-3xl font-black mb-8 border-l-4 border-yellow-500 pl-4 uppercase">Demandes Machann</h2>
            <?php if (empty($pending_merchants)): ?>
                <p class="text-slate-400">Pa gen demann pou kounye a.</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($pending_merchants as $m): ?>
                        <div class="bg-white p-6 rounded-2xl shadow-sm flex justify-between items-center border border-slate-200">
                            <div>
                                <h4 class="text-lg font-black"><?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></h4>
                                <p class="text-slate-500 text-sm"><?= htmlspecialchars($m['email']) ?></p>
                            </div>
                            <form method="POST" class="flex gap-2">
                                <input type="hidden" name="user_id" value="<?= $m['id'] ?>">
                                <button name="approve_merchant" class="bg-green-600 text-white px-4 py-2 rounded-xl text-xs uppercase font-black hover:bg-green-700">
                                    <i class="fas fa-check"></i> Apwouve
                                </button>
                                <button name="reject_merchant" class="bg-red-600 text-white px-4 py-2 rounded-xl text-xs uppercase font-black hover:bg-red-700">
                                    <i class="fas fa-times"></i> Rejte
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php elseif ($section === 'categories'): ?>
            <!-- KÒD POU KATEGORI YO -->
            <h2 class="text-3xl font-black mb-8 border-l-4 border-blue-600 pl-4 uppercase">Gestion des Catégories</h2>

            <div class="bg-white p-8 rounded-3xl shadow-sm mb-8 border border-slate-200">
                <h3 class="text-lg font-bold mb-4 uppercase">Ajoute Nouvo Kategori</h3>
                <form method="POST" class="flex gap-4">
                    <input type="text" name="nom_cat" placeholder="Nouvo non kategori" class="flex-1 p-4 bg-slate-50 rounded-2xl outline-none ring-1 ring-slate-200" required>
                    <button name="add_cat" class="bg-blue-600 text-white px-8 py-4 rounded-2xl font-black uppercase text-xs hover:bg-blue-700">
                        <i class="fas fa-plus"></i> Ajoute
                    </button>
                </form>
            </div>

            <h3 class="text-lg font-bold mb-4 uppercase">Kategori ki egziste yo</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <?php foreach ($all_cats as $c): ?>
                    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex justify-between items-center group">
                        <span class="uppercase font-bold text-slate-700"><?= htmlspecialchars($c['name']) ?></span>
                        <form method="POST" onsubmit="return confirm('Èske ou sèten?');">
                            <input type="hidden" name="cat_id" value="<?= $c['id'] ?>">
                            <button name="delete_cat" class="text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-all bg-transparent border-none cursor-pointer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($section === 'add-product'): ?>
            <!-- KÒD POU AJOUTE PWODWI -->
            <h2 class="text-3xl font-black mb-8 border-l-4 border-orange-500 pl-4 uppercase">Nouveau Produit</h2>

            <div class="bg-white p-10 rounded-3xl shadow-sm border border-slate-200 max-w-4xl">
                <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs text-slate-500 mb-2 uppercase font-bold">Non Pwodwi *</label>
                        <input type="text" name="p_nom" required class="w-full p-4 bg-slate-50 rounded-2xl outline-none ring-1 ring-slate-200">
                    </div>

                    <div>
                        <label class="block text-xs text-slate-500 mb-2 uppercase font-bold">Kategori *</label>
                        <select name="p_category" required class="w-full p-4 bg-slate-50 rounded-2xl outline-none ring-1 ring-slate-200">
                            <option value="">Chwazi yon kategori...</option>
                            <?php foreach ($all_cats as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs text-slate-500 mb-2 uppercase font-bold">Prix Normal (HTG) *</label>
                        <input type="number" name="p_prix_reg" required class="w-full p-4 bg-slate-50 rounded-2xl outline-none ring-1 ring-slate-200">
                    </div>

                    <div>
                        <label class="block text-xs text-slate-500 mb-2 uppercase font-bold">Prix Promo (HTG)</label>
                        <input type="number" name="p_prix_promo" placeholder="Pa obligatwa" class="w-full p-4 bg-slate-50 rounded-2xl outline-none ring-1 ring-orange-200 border-2 border-orange-100">
                    </div>

                    <div>
                        <label class="block text-xs text-slate-500 mb-2 uppercase font-bold">Kantite Stock *</label>
                        <input type="number" name="p_qty" required class="w-full p-4 bg-slate-50 rounded-2xl outline-none ring-1 ring-slate-200">
                    </div>

                    <div>
                        <label class="block text-xs text-slate-500 mb-2 uppercase font-bold">Imaj Pwodwi *</label>
                        <input type="file" name="p_img" accept="image/*" required class="w-full p-4 border-2 border-dashed rounded-2xl border-slate-300">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs text-slate-500 mb-2 uppercase font-bold">Deskripsyon</label>
                        <textarea name="p_desc" rows="3" class="w-full p-4 bg-slate-50 rounded-2xl outline-none ring-1 ring-slate-200"></textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs text-slate-500 mb-2 uppercase font-bold">Karakteristik</label>
                        <textarea name="p_carac" rows="2" class="w-full p-4 bg-slate-50 rounded-2xl outline-none ring-1 ring-slate-200"></textarea>
                    </div>

                    <div class="md:col-span-2">
                        <button type="submit" name="add_product" class="w-full py-5 bg-slate-900 text-white rounded-2xl font-black uppercase tracking-widest hover:bg-orange-600 transition-all">
                            <i class="fas fa-save"></i> Enregistrer le produit
                        </button>
                    </div>
                </form>
            </div>

        <?php elseif ($section === 'products'): ?>
            <!-- KÒD POU LISYEN PWODWI YO -->
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-3xl font-black border-l-4 border-indigo-600 pl-4 uppercase">Lisyen Pwodwi yo</h2>
                <a href="?section=add-product" class="bg-blue-600 text-white px-6 py-3 rounded-2xl font-bold uppercase text-sm hover:bg-blue-700">
                    <i class="fas fa-plus"></i> Nouvo Pwodwi
                </a>
            </div>

            <?php if (empty($all_products)): ?>
                <div class="bg-white p-10 rounded-3xl text-center text-slate-400">
                    <i class="fas fa-box-open text-4xl mb-4"></i>
                    <p>Pa gen pwodwi nan sistèm nan.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($all_products as $pr): ?>
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden product-card">
                            <div class="relative h-48">
                                <img src="../uploads/products/<?= $pr['image'] ?>" alt="<?= htmlspecialchars($pr['name']) ?>" class="w-full h-full object-cover" onerror="this.src='../assets/img/placeholder.png'">
                                <?php if ($pr['price_promo'] && $pr['price_promo'] > 0 && $pr['price_promo'] < $pr['price']): ?>
                                    <span class="absolute top-4 left-4 bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full">PROMO</span>
                                <?php endif; ?>
                            </div>
                            <div class="p-6">
                                <div class="text-xs text-blue-600 font-bold uppercase mb-2"><?= htmlspecialchars($pr['category_name'] ?? 'San kategori') ?></div>
                                <h3 class="font-bold text-lg mb-2"><?= htmlspecialchars($pr['name']) ?></h3>
                                <div class="flex justify-between items-center mb-4">
                                    <?php if ($pr['price_promo'] && $pr['price_promo'] > 0 && $pr['price_promo'] < $pr['price']): ?>
                                        <div>
                                            <span class="text-red-600 font-black text-xl"><?= number_format($pr['price_promo']) ?> HTG</span>
                                            <span class="text-slate-400 text-sm line-through ml-2"><?= number_format($pr['price']) ?> HTG</span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-slate-900 font-black text-xl"><?= number_format($pr['price']) ?> HTG</span>
                                    <?php endif; ?>
                                    <span class="text-sm text-slate-500">Stock: <?= $pr['stock_qty'] ?></span>
                                </div>
                                <div class="flex gap-2">
                                    <button onclick="editProduct(<?= $pr['id'] ?>)" class="flex-1 bg-blue-600 text-white py-2 rounded-xl text-sm font-bold hover:bg-blue-700">
                                        <i class="fas fa-edit"></i> Modifye
                                    </button>
                                    <form method="POST" class="flex-1" onsubmit="return confirm('Èske ou sèten?');">
                                        <input type="hidden" name="product_id" value="<?= $pr['id'] ?>">
                                        <button name="delete_product" class="w-full bg-red-600 text-white py-2 rounded-xl text-sm font-bold hover:bg-red-700">
                                            <i class="fas fa-trash"></i> Efase
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Modifye (simplifye pou kounye a) -->
                        <div id="modal-<?= $pr['id'] ?>" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 2rem;">
                            <div style="background: white; border-radius: 1rem; max-width: 600px; width: 100%; max-height: 90vh; overflow-y: auto; padding: 2rem;">
                                <h3 class="text-xl font-black mb-4 uppercase">Modifye Pwodwi</h3>
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="product_id" value="<?= $pr['id'] ?>">
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-xs font-bold uppercase mb-2">Non</label>
                                            <input type="text" name="edit_nom" value="<?= htmlspecialchars($pr['name']) ?>" class="w-full p-3 bg-slate-50 rounded-xl" required>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold uppercase mb-2">Kategori</label>
                                            <select name="edit_category" class="w-full p-3 bg-slate-50 rounded-xl">
                                                <?php foreach ($all_cats as $c): ?>
                                                    <option value="<?= $c['id'] ?>" <?= $c['id'] == $pr['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-xs font-bold uppercase mb-2">Prix</label>
                                                <input type="number" name="edit_prix" value="<?= $pr['price'] ?>" class="w-full p-3 bg-slate-50 rounded-xl" required>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold uppercase mb-2">Prix Promo</label>
                                                <input type="number" name="edit_prix_promo" value="<?= $pr['price_promo'] ?>" class="w-full p-3 bg-slate-50 rounded-xl">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold uppercase mb-2">Stock</label>
                                            <input type="number" name="edit_qty" value="<?= $pr['stock_qty'] ?>" class="w-full p-3 bg-slate-50 rounded-xl" required>
                                        </div>
                                        <div class="flex gap-2 pt-4">
                                            <button type="button" onclick="document.getElementById('modal-<?= $pr['id'] ?>').style.display='none'" class="flex-1 bg-slate-200 text-slate-700 py-3 rounded-xl font-bold">Anile</button>
                                            <button type="submit" name="update_product" class="flex-1 bg-blue-600 text-white py-3 rounded-xl font-bold">Sove</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php elseif ($section === 'users'): ?>
            <!-- KÒD POU ITILIZATÈ YO -->
            <h2 class="text-3xl font-black mb-8 border-l-4 border-green-600 pl-4 uppercase">Jesyon Itilizatè</h2>
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="p-4 text-left text-xs uppercase text-slate-500 font-bold">ID</th>
                            <th class="p-4 text-left text-xs uppercase text-slate-500 font-bold">Non</th>
                            <th class="p-4 text-left text-xs uppercase text-slate-500 font-bold">Imèl</th>
                            <th class="p-4 text-left text-xs uppercase text-slate-500 font-bold">Ròl</th>
                            <th class="p-4 text-left text-xs uppercase text-slate-500 font-bold">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_users as $u): ?>
                            <tr class="border-t border-slate-100">
                                <td class="p-4">#<?= $u['id'] ?></td>
                                <td class="p-4 font-bold"><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></td>
                                <td class="p-4 text-slate-600"><?= htmlspecialchars($u['email']) ?></td>
                                <td class="p-4">
                                    <?php if ($u['role'] == 'admin'): ?>
                                        <span class="bg-red-100 text-red-600 px-2 py-1 rounded text-xs font-bold">ADMIN</span>
                                    <?php elseif ($u['role'] == 'merchant'): ?>
                                        <span class="bg-amber-100 text-amber-600 px-2 py-1 rounded text-xs font-bold">MACHANN</span>
                                    <?php else: ?>
                                        <span class="bg-blue-100 text-blue-600 px-2 py-1 rounded text-xs font-bold">KLIYAN</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4">
                                    <?php if ($u['merchant_status'] == 'pending'): ?>
                                        <span class="bg-yellow-100 text-yellow-600 px-2 py-1 rounded text-xs font-bold">AN ATANT</span>
                                    <?php elseif ($u['merchant_status'] == 'rejected'): ?>
                                        <span class="bg-red-100 text-red-600 px-2 py-1 rounded text-xs font-bold">REJTE</span>
                                    <?php else: ?>
                                        <span class="bg-green-100 text-green-600 px-2 py-1 rounded text-xs font-bold">AKTIF</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($section === 'promotions'): ?>
            <!-- KÒD POU PROMOTIONS -->
            <h2 class="text-3xl font-black mb-8 border-l-4 border-red-500 pl-4 uppercase">Pwodwi an Promosyon</h2>
            <?php
            $promos = $pdo->query("
                SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.price_promo IS NOT NULL AND p.price_promo > 0 AND p.price_promo < p.price
                ORDER BY p.id DESC
            ")->fetchAll();
            ?>

            <?php if (empty($promos)): ?>
                <div class="bg-white p-10 rounded-3xl text-center text-slate-400">
                    <i class="fas fa-percentage text-4xl mb-4"></i>
                    <p>Pa gen pwodwi an promosyon pou kounye a.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($promos as $pr): ?>
                        <div class="bg-white rounded-2xl shadow-sm border-t-4 border-red-500 overflow-hidden">
                            <div class="h-48">
                                <img src="../uploads/products/<?= $pr['image'] ?>" class="w-full h-full object-cover" onerror="this.src='../assets/img/placeholder.png'">
                            </div>
                            <div class="p-6">
                                <h3 class="font-black text-lg mb-2 uppercase"><?= htmlspecialchars($pr['name']) ?></h3>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-400 line-through"><?= number_format($pr['price']) ?> HTG</span>
                                    <span class="text-red-600 font-black text-xl"><?= number_format($pr['price_promo']) ?> HTG</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        // Fonksyon pou modifye pwodwi
        function editProduct(productId) {
            document.getElementById('modal-' + productId).style.display = 'flex';
        }

        // Fè alert disparèt otomatikman
        setTimeout(function() {
            const alerts = document.querySelectorAll('.bg-green-600, .bg-red-600');
            alerts.forEach(alert => {
                if (alert.classList.contains('text-white') && alert.classList.contains('p-4')) {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s';
                    setTimeout(() => alert.remove(), 500);
                }
            });
        }, 5000);
    </script>
</body>

</html>