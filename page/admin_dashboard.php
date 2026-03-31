<?php
// ATTENTION: Pas d'espace ou de ligne vide avant <?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';

// Sécurité: Uniquement Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

 $msg = "";
 $error = "";
 $section = $_GET['section'] ?? 'dashboard';

// ==================== CONFIGURATION REDIRECTION ====================

// Détecter URL absolue pour admin.php
 $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
 $host = $_SERVER['HTTP_HOST'];
 $script_name = $_SERVER['SCRIPT_NAME'];
 $admin_url = $protocol . $host . $script_name;

// Fonction redirection améliorée
function redirect($section, $message = '', $error = '', $return_to_same = false)
{
    global $admin_url;
    $target_section = $return_to_same ? $section : 'dashboard';
    $url = $admin_url . "?section=" . $target_section;
    if ($message) $url .= "&msg=" . urlencode($message);
    if ($error) $url .= "&error=" . urlencode($error);
    header("Location: " . $url);
    exit();
}

// Fonction pour revenir sur la même page sans changer de section
function refreshWithMessage($section, $message = '', $error = '')
{
    global $admin_url;
    $url = $admin_url . "?section=" . $section;
    if ($message) $url .= "&msg=" . urlencode($message);
    if ($error) $url .= "&error=" . urlencode($error);
    header("Location: " . $url);
    exit();
}

// ==================== ACTIONS ====================

// 1. AJOUTER CATÉGORIE
if (isset($_POST['add_cat'])) {
    $nom_cat = trim(htmlspecialchars($_POST['nom_cat']));
    if (!empty($nom_cat)) {
        try {
            $check = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
            $check->execute([$nom_cat]);
            if ($check->rowCount() > 0) {
                refreshWithMessage('categories', "", "Cette catégorie existe déjà!");
            } else {
                $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                $stmt->execute([$nom_cat]);
                refreshWithMessage('categories', "Catégorie ajoutée avec succès!");
            }
        } catch (PDOException $e) {
            refreshWithMessage('categories', "", "Erreur: " . $e->getMessage());
        }
    } else {
        refreshWithMessage('categories', "", "Le nom de la catégorie est obligatoire!");
    }
}

// 2. AJOUTER PRODUIT
if (isset($_POST['add_product'])) {
    $nom = trim(htmlspecialchars($_POST['p_nom']));
    $prix_reg = $_POST['p_prix_reg'];
    $prix_promo = !empty($_POST['p_prix_promo']) ? $_POST['p_prix_promo'] : null;
    $qty = $_POST['p_qty'];
    $cat_id = $_POST['p_category'];
    $desc = htmlspecialchars($_POST['p_desc']);
    $carac = htmlspecialchars($_POST['p_carac']);
    $status = ($qty > 0) ? 'disponible' : 'indisponible';

    if (empty($nom) || empty($prix_reg) || empty($qty) || empty($cat_id)) {
        refreshWithMessage('add-product', "", "Tous les champs obligatoires doivent être remplis!");
    } elseif (!isset($_FILES['p_img'])) {
        refreshWithMessage('add-product', "", "Aucun fichier envoyé!");
    } elseif ($_FILES['p_img']['error'] === UPLOAD_ERR_NO_FILE) {
        refreshWithMessage('add-product', "", "Vous devez choisir une image!");
    } elseif ($_FILES['p_img']['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'Le fichier est trop gros',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier est trop gros',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a pas été transféré correctement',
            UPLOAD_ERR_NO_TMP_DIR => 'Pas de dossier temporaire',
            UPLOAD_ERR_CANT_WRITE => 'Impossible d\'écrire le fichier',
            UPLOAD_ERR_EXTENSION => 'Extension bloquée',
        ];
        $errorCode = $_FILES['p_img']['error'];
        $error_msg = "Erreur d'upload: " . ($uploadErrors[$errorCode] ?? "Code d'erreur: $errorCode");
        refreshWithMessage('add-product', "", $error_msg);
    } else {
        $upload_dir = dirname(dirname(__FILE__)) . "/uploads/products/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $img = time() . "_" . basename($_FILES['p_img']['name']);
        $target_file = $upload_dir . $img;

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = mime_content_type($_FILES['p_img']['tmp_name']);

        if (!in_array($file_type, $allowed_types)) {
            refreshWithMessage('add-product', "", "Seuls les fichiers image sont acceptés!");
        } elseif (move_uploaded_file($_FILES['p_img']['tmp_name'], $target_file)) {
            try {
                $sql = "INSERT INTO products (name, price, price_promo, stock_qty, category_id, description, caractéristiques, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $pdo->prepare($sql)->execute([$nom, $prix_reg, $prix_promo, $qty, $cat_id, $desc, $carac, $img, $status]);
                refreshWithMessage('products', "Produit ajouté avec succès!");
            } catch (PDOException $e) {
                $error_msg = "Erreur: " . $e->getMessage();
                if (file_exists($target_file)) {
                    unlink($target_file);
                }
                refreshWithMessage('add-product', "", $error_msg);
            }
        } else {
            refreshWithMessage('add-product', "", "Erreur pendant le transfert du fichier!");
        }
    }
}

// 3. APPROUVER MARCHAND
if (isset($_POST['approve_merchant'])) {
    $id_demande = $_POST['id_demande'];
    $user_id = $_POST['user_id'];

    try {
        $pdo->prepare("UPDATE users SET role = 'merchant', merchant_status = 'approved' WHERE id = ?")->execute([$user_id]);

        $demandes_file = (dirname(__DIR__)) . '/admin/demandes_marchands.txt';
        if (file_exists($demandes_file)) {
            $content = file_get_contents($demandes_file);
            $content = preg_replace(
                '/(ID_DEMANN: ' . preg_quote($id_demande) . '.*?STATUT: )pending/s',
                '${1}approved',
                $content
            );
            file_put_contents($demandes_file, $content, LOCK_EX);
        }

        refreshWithMessage('merchant_requests', "Marchand approuvé!");
    } catch (PDOException $e) {
        refreshWithMessage('merchant_requests', "", "Erreur: " . $e->getMessage());
    }
}

// 4. REJETER MARCHAND
if (isset($_POST['reject_merchant'])) {
    $id_demande = $_POST['id_demande'];
    $user_id = $_POST['user_id'];

    try {
        $pdo->prepare("UPDATE users SET merchant_status = 'rejected' WHERE id = ?")->execute([$user_id]);

        $demandes_file = (dirname(__DIR__)) . '/admin/demandes_marchands.txt';
        if (file_exists($demandes_file)) {
            $content = file_get_contents($demandes_file);
            $content = preg_replace(
                '/(ID_DEMANN: ' . preg_quote($id_demande) . '.*?STATUT: )pending/s',
                '${1}rejected',
                $content
            );
            file_put_contents($demandes_file, $content, LOCK_EX);
        }

        refreshWithMessage('merchant_requests', "Demande rejetée.");
    } catch (PDOException $e) {
        refreshWithMessage('merchant_requests', "", "Erreur: " . $e->getMessage());
    }
}

// 5. SUPPRIMER CATÉGORIE
if (isset($_POST['delete_cat'])) {
    $cat_id = $_POST['cat_id'];
    try {
        $check = $pdo->prepare("SELECT COUNT(*) as total FROM products WHERE category_id = ?");
        $check->execute([$cat_id]);
        $result = $check->fetch();

        if ($result['total'] > 0) {
            refreshWithMessage('categories', "", "Vous ne pouvez pas supprimer cette catégorie car il y a des produits liés!");
        } else {
            $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$cat_id]);
            refreshWithMessage('categories', "Catégorie supprimée!");
        }
    } catch (PDOException $e) {
        refreshWithMessage('categories', "", "Erreur: " . $e->getMessage());
    }
}

// 6. SUPPRIMER PRODUIT
if (isset($_POST['delete_product'])) {
    $p_id = $_POST['product_id'];
    try {
        $img_stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $img_stmt->execute([$p_id]);
        $img_data = $img_stmt->fetch();

        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$p_id]);

        if ($img_data && !empty($img_data['image'])) {
            $img_path = dirname(dirname(__FILE__)) . "/uploads/products/" . $img_data['image'];
            if (file_exists($img_path)) {
                unlink($img_path);
            }
        }

        refreshWithMessage('products', "Produit supprimé!");
    } catch (PDOException $e) {
        refreshWithMessage('products', "", "Erreur: " . $e->getMessage());
    }
}

// 7. MODIFIER PRODUIT
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
            $upload_dir = dirname(dirname(__FILE__)) . "/uploads/products/";
            $img = time() . "_" . basename($_FILES['edit_img']['name']);
            $target_file = $upload_dir . $img;

            $old_img_stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
            $old_img_stmt->execute([$p_id]);
            $old_img = $old_img_stmt->fetch()['image'] ?? null;

            if (move_uploaded_file($_FILES['edit_img']['tmp_name'], $target_file)) {
                $sql = "UPDATE products SET name = ?, price = ?, price_promo = ?, stock_qty = ?, category_id = ?, description = ?, image = ?, status = ? WHERE id = ?";
                $pdo->prepare($sql)->execute([$nom, $prix, $prix_promo, $qty, $cat_id, $desc, $img, $status, $p_id]);

                if ($old_img && !empty($old_img)) {
                    $old_path = $upload_dir . $old_img;
                    if (file_exists($old_path)) {
                        unlink($old_path);
                    }
                }
            }
        } else {
            $sql = "UPDATE products SET name = ?, price = ?, price_promo = ?, stock_qty = ?, category_id = ?, description = ?, status = ? WHERE id = ?";
            $pdo->prepare($sql)->execute([$nom, $prix, $prix_promo, $qty, $cat_id, $desc, $status, $p_id]);
        }
        refreshWithMessage('products', "Produit mis à jour!");
    } catch (PDOException $e) {
        refreshWithMessage('products', "", "Erreur: " . $e->getMessage());
    }
}

// 8. SUPPRIMER UTILISATEUR
if (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];

    if ($user_id == $_SESSION['user_id']) {
        refreshWithMessage('users', "", "Vous ne pouvez pas supprimer votre propre compte!");
    } else {
        try {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
            refreshWithMessage('users', "Utilisateur supprimé avec succès!");
        } catch (PDOException $e) {
            refreshWithMessage('users', "", "Erreur: " . $e->getMessage());
        }
    }
}

// ==================== HOT DEALS - AVEC CORRECTIONS ====================

// 9. AJOUTER HOT DEAL
if (isset($_POST['add_hot_deal'])) {
    $titre = trim(htmlspecialchars($_POST['deal_titre']));
    $description = htmlspecialchars($_POST['deal_desc']);
    $prix_original = $_POST['deal_prix_original'];
    $prix_deal = $_POST['deal_prix'];
    $date_fin = $_POST['deal_date_fin'];
    $en_stock = isset($_POST['deal_stock']) ? 1 : 0;

    if (empty($titre) || empty($prix_original) || empty($prix_deal)) {
        refreshWithMessage('add_hot_deal', "", "Tous les champs obligatoires doivent être remplis!");
    } elseif (!isset($_FILES['deal_images']) || empty($_FILES['deal_images']['name'][0])) {
        refreshWithMessage('add_hot_deal', "", "Vous devez choisir au moins une image!");
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO hot_deals (titre, description, prix_original, prix_deal, date_fin, en_stock, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$titre, $description, $prix_original, $prix_deal, $date_fin, $en_stock]);
            $deal_id = $pdo->lastInsertId();

            $upload_dir = dirname(dirname(__FILE__)) . "/uploads/hot_deals/";

            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    throw new Exception("Impossible de créer le dossier: " . $upload_dir);
                }
            }

            if (!is_writable($upload_dir)) {
                chmod($upload_dir, 0777);
            }

            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $uploaded_images = [];

            for ($i = 0; $i < count($_FILES['deal_images']['name']); $i++) {
                if ($_FILES['deal_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['deal_images']['tmp_name'][$i];
                    $original_name = $_FILES['deal_images']['name'][$i];
                    $file_type = mime_content_type($tmp_name);

                    if (in_array($file_type, $allowed_types)) {
                        $extension = pathinfo($original_name, PATHINFO_EXTENSION);
                        $img_name = "deal_" . $deal_id . "_" . time() . "_" . $i . "." . $extension;
                        $target_file = $upload_dir . $img_name;

                        if (move_uploaded_file($tmp_name, $target_file)) {
                            $is_primary = ($i === 0) ? 1 : 0;
                            $pdo->prepare("INSERT INTO hot_deal_images (deal_id, image_name, is_primary, ordre) VALUES (?, ?, ?, ?)")
                                ->execute([$deal_id, $img_name, $is_primary, $i]);
                            $uploaded_images[] = $img_name;
                        }
                    }
                }
            }

            if (count($uploaded_images) > 0) {
                refreshWithMessage('hot_deals', "Hot Deal ajouté avec " . count($uploaded_images) . " image(s)!");
            } else {
                $pdo->prepare("DELETE FROM hot_deals WHERE id = ?")->execute([$deal_id]);
                refreshWithMessage('add_hot_deal', "", "Aucune image n'a été uploadée correctement!");
            }
        } catch (Exception $e) {
            refreshWithMessage('add_hot_deal', "", "Erreur: " . $e->getMessage());
        } catch (PDOException $e) {
            refreshWithMessage('add_hot_deal', "", "Erreur base de données: " . $e->getMessage());
        }
    }
}

// 10. MODIFIER HOT DEAL
if (isset($_POST['update_hot_deal'])) {
    $deal_id = $_POST['deal_id'];
    $titre = trim(htmlspecialchars($_POST['edit_titre']));
    $description = htmlspecialchars($_POST['edit_desc']);
    $prix_original = $_POST['edit_prix_original'];
    $prix_deal = $_POST['edit_prix'];
    $date_fin = $_POST['edit_date_fin'];
    $en_stock = isset($_POST['edit_stock']) ? 1 : 0;

    try {
        $pdo->prepare("UPDATE hot_deals SET titre = ?, description = ?, prix_original = ?, prix_deal = ?, date_fin = ?, en_stock = ? WHERE id = ?")
            ->execute([$titre, $description, $prix_original, $prix_deal, $date_fin, $en_stock, $deal_id]);

        if (isset($_FILES['edit_new_images']) && !empty($_FILES['edit_new_images']['name'][0])) {
            $upload_dir = dirname(dirname(__FILE__)) . "/uploads/hot_deals/";
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            $max_ordre_stmt = $pdo->prepare("SELECT MAX(ordre) as max_ord FROM hot_deal_images WHERE deal_id = ?");
            $max_ordre_stmt->execute([$deal_id]);
            $max_ordre = $max_ordre_stmt->fetch()['max_ord'] ?? -1;

            for ($i = 0; $i < count($_FILES['edit_new_images']['name']); $i++) {
                if ($_FILES['edit_new_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $file_type = mime_content_type($_FILES['edit_new_images']['tmp_name'][$i]);
                    if (in_array($file_type, $allowed_types)) {
                        $extension = pathinfo($_FILES['edit_new_images']['name'][$i], PATHINFO_EXTENSION);
                        $img_name = "deal_" . $deal_id . "_" . time() . "_" . $i . "." . $extension;
                        $target_file = $upload_dir . $img_name;

                        if (move_uploaded_file($_FILES['edit_new_images']['tmp_name'][$i], $target_file)) {
                            $pdo->prepare("INSERT INTO hot_deal_images (deal_id, image_name, is_primary, ordre) VALUES (?, ?, 0, ?)")
                                ->execute([$deal_id, $img_name, $max_ordre + $i + 1]);
                        }
                    }
                }
            }
        }
        refreshWithMessage('hot_deals', "Hot Deal mis à jour!");
    } catch (PDOException $e) {
        refreshWithMessage('hot_deals', "", "Erreur: " . $e->getMessage());
    }
}

// 11. SUPPRIMER HOT DEAL
if (isset($_POST['delete_hot_deal'])) {
    $deal_id = $_POST['deal_id'];
    try {
        $images = $pdo->prepare("SELECT image_name FROM hot_deal_images WHERE deal_id = ?");
        $images->execute([$deal_id]);

        $upload_dir = dirname(dirname(__FILE__)) . "/uploads/hot_deals/";
        while ($img = $images->fetch()) {
            $file_path = $upload_dir . $img['image_name'];
            if (file_exists($file_path)) unlink($file_path);
        }

        $pdo->prepare("DELETE FROM hot_deal_images WHERE deal_id = ?")->execute([$deal_id]);
        $pdo->prepare("DELETE FROM hot_deals WHERE id = ?")->execute([$deal_id]);
        refreshWithMessage('hot_deals', "Hot Deal supprimé!");
    } catch (PDOException $e) {
        refreshWithMessage('hot_deals', "", "Erreur: " . $e->getMessage());
    }
}

// 12. SUPPRIMER IMAGE HOT DEAL
if (isset($_POST['delete_deal_image'])) {
    $image_id = $_POST['image_id'];
    $deal_id = $_POST['deal_id'];
    try {
        $img_stmt = $pdo->prepare("SELECT image_name FROM hot_deal_images WHERE id = ?");
        $img_stmt->execute([$image_id]);
        $img_data = $img_stmt->fetch();

        if ($img_data) {
            $upload_dir = dirname(dirname(__FILE__)) . "/uploads/hot_deals/";
            $file_path = $upload_dir . $img_data['image_name'];
            if (file_exists($file_path)) unlink($file_path);

            $pdo->prepare("DELETE FROM hot_deal_images WHERE id = ?")->execute([$image_id]);

            global $admin_url;
            $url = $admin_url . "?section=hot_deals&edit_deal=" . $deal_id . "&msg=" . urlencode("Image supprimée!");
            header("Location: " . $url);
            exit();
        }
    } catch (PDOException $e) {
        global $admin_url;
        $url = $admin_url . "?section=hot_deals&edit_deal=" . $deal_id . "&error=" . urlencode("Erreur: " . $e->getMessage());
        header("Location: " . $url);
        exit();
    }
}

// 13. DÉFINIR IMAGE COMME PRINCIPALE
if (isset($_POST['set_primary_image'])) {
    $image_id = $_POST['image_id'];
    $deal_id = $_POST['deal_id'];
    try {
        $pdo->prepare("UPDATE hot_deal_images SET is_primary = 0 WHERE deal_id = ?")->execute([$deal_id]);
        $pdo->prepare("UPDATE hot_deal_images SET is_primary = 1 WHERE id = ?")->execute([$image_id]);

        global $admin_url;
        $url = $admin_url . "?section=hot_deals&edit_deal=" . $deal_id . "&msg=" . urlencode("Image principale changée!");
        header("Location: " . $url);
        exit();
    } catch (PDOException $e) {
        global $admin_url;
        $url = $admin_url . "?section=hot_deals&edit_deal=" . $deal_id . "&error=" . urlencode("Erreur: " . $e->getMessage());
        header("Location: " . $url);
        exit();
    }
}

// ==================== RÉCUPÉRER LES MESSAGES ====================

if (isset($_GET['msg'])) $msg = htmlspecialchars($_GET['msg']);
if (isset($_GET['error'])) $error = htmlspecialchars($_GET['error']);

// ==================== RÉCUPÉRER LES DONNÉES ====================

 $all_cats = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

 $demandes_marchands = [];
 $demandes_file = (dirname(__DIR__)) . '/admin/demandes_marchands.txt';
if (file_exists($demandes_file) && filesize($demandes_file) > 0) {
    $content = file_get_contents($demandes_file);
    $blocks = explode('=== DEMANN MACHANN ===', $content);
    foreach ($blocks as $block) {
        if (trim($block) === '') continue;
        $demande = [];
        preg_match('/ID_DEMANN: (.+)/', $block, $m);
        $demande['id_demande'] = $m[1] ?? '';
        preg_match('/USER_ID: (.+)/', $block, $m);
        $demande['user_id'] = $m[1] ?? '';
        preg_match('/DAT: (.+)/', $block, $m);
        $demande['date'] = $m[1] ?? '';
        preg_match('/NON: (.+)/', $block, $m);
        $demande['nom'] = $m[1] ?? '';
        preg_match('/PRENOM: (.+)/', $block, $m);
        $demande['prenom'] = $m[1] ?? '';
        preg_match('/EMAIL: (.+)/', $block, $m);
        $demande['email'] = $m[1] ?? '';
        preg_match('/TELEFON: (.+)/', $block, $m);
        $demande['telephone'] = $m[1] ?? '';
        preg_match('/ADRES: (.+)/', $block, $m);
        $demande['adresse'] = $m[1] ?? '';
        preg_match('/NUMERO_TRANSFERT: (.+)/', $block, $m);
        $demande['numero_transfert'] = $m[1] ?? '';
        preg_match('/FOTO_PROFIL: (.+)/', $block, $m);
        $demande['foto_profil'] = $m[1] ?? '';
        preg_match('/PIECE_ID: (.+)/', $block, $m);
        $demande['piece_id'] = $m[1] ?? '';
        preg_match('/PREUVE_PAIEMENT: (.+)/', $block, $m);
        $demande['preuve_paiement'] = $m[1] ?? '';
        preg_match('/STATUT: (.+)/', $block, $m);
        $demande['statut'] = trim($m[1] ?? 'pending');
        $demandes_marchands[] = $demande;
    }
}

 $pending_merchants = array_filter($demandes_marchands, fn($d) => $d['statut'] === 'pending');

 $all_products = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC")->fetchAll();

 $all_users = $pdo->query("SELECT id, BIN_TO_UUID(uuid) as uuid_text, prenom, nom, email, role, merchant_status, created_at FROM users ORDER BY id DESC")->fetchAll();

 $hot_deals = [];
try {
    $deals_stmt = $pdo->query("SELECT * FROM hot_deals ORDER BY created_at DESC");
    while ($deal = $deals_stmt->fetch()) {
        $img_stmt = $pdo->prepare("SELECT * FROM hot_deal_images WHERE deal_id = ? ORDER BY is_primary DESC, ordre ASC");
        $img_stmt->execute([$deal['id']]);
        $deal['images'] = $img_stmt->fetchAll();
        $hot_deals[] = $deal;
    }
} catch (PDOException $e) {
    $hot_deals = [];
}

 $total_products = count($all_products);
 $total_categories = count($all_cats);
 $total_users = count($all_users);
 $total_merchants = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'merchant'")->fetch()['total'];
 $total_hot_deals = count($hot_deals);

// ==================== FONCTIONS D'AIDE ====================

function isActive($current, $section)
{
    return $current === $section ? 'active' : '';
}

function getStatusBadge($status)
{
    switch ($status) {
        case 'pending':
            return ['bg-yellow-100 text-yellow-800', 'En attente', 'fas fa-clock'];
        case 'approved':
            return ['bg-green-100 text-green-800', 'Approuvé', 'fas fa-check-circle'];
        case 'rejected':
            return ['bg-red-100 text-red-800', 'Rejeté', 'fas fa-times-circle'];
        default:
            return ['bg-gray-100 text-gray-800', 'Inconnu', 'fas fa-question'];
    }
}

 $uploads_base_path = dirname(dirname(__FILE__)) . "/uploads/";
 $img_base_url = '../uploads/';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Panel Admin | LE-STOCK</title>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    animation: {
                        'fade-in': 'fadeIn 0.3s ease-in',
                    },
                    keyframes: {
                        fadeIn: {
                            from: {
                                opacity: '0',
                                transform: 'translateY(10px)'
                            },
                            to: {
                                opacity: '1',
                                transform: 'translateY(0)'
                            },
                        },
                    },
                },
            },
        }
    </script>
</head>

<body class="bg-slate-100 flex min-h-screen italic font-bold">

    <!-- Overlay pour sidebar sur mobile -->
    <div class="sidebar-overlay fixed inset-0 bg-black/50 z-40 opacity-0 invisible transition-all duration-300" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <!-- Bouton Hamburger (visible seulement sur mobile) -->
    <button class="hamburger-btn fixed top-4 right-4 z-50 bg-slate-900 text-white p-3 rounded-xl shadow-lg hover:bg-slate-800 transition-colors md:hidden" onclick="toggleSidebar()">
        <i class="fas fa-bars text-lg" id="hamburgerIcon"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar w-72 bg-slate-900 text-white p-6 h-screen shadow-2xl overflow-y-auto flex-shrink-0 -translate-x-full transition-transform duration-300 fixed top-0 left-0 z-50 md:translate-x-0 md:static md:z-auto" id="sidebar">
        <div class="flex items-center justify-between mb-10">
            <h1 class="text-2xl font-black italic tracking-tighter text-center uppercase">
                LE STOCK <span class="text-blue-500">ADMIN</span>
            </h1>
            <button class="md:hidden text-white hover:text-slate-300 p-2" onclick="closeSidebar()">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div class="mb-6 pb-6 border-b border-slate-700">
            <p class="text-xs text-slate-400 uppercase mb-2">Connecté en tant que:</p>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div>
                    <p class="font-bold text-sm"><?= htmlspecialchars($_SESSION['prenom'] ?? 'Admin') ?></p>
                    <p class="text-xs text-slate-400">Administrateur</p>
                </div>
            </div>
        </div>

        <nav class="space-y-2">
            <a href="?section=dashboard" class="sidebar-btn w-full text-left p-4 rounded-xl flex items-center gap-3 transition-all bg-transparent border-none text-white cursor-pointer font-italic font-bold uppercase text-sm mb-2 hover:bg-slate-800 border-l-4 border-transparent <?= isActive($section, 'dashboard') ? '!bg-slate-800 !border-l-blue-500' : '' ?>" onclick="closeSidebarOnMobile()">
                <i class="fas fa-chart-line w-6"></i> Tableau de Bord
            </a>

            <a href="?section=merchant_requests" class="sidebar-btn w-full text-left p-4 rounded-xl flex items-center gap-3 transition-all bg-transparent border-none text-white cursor-pointer font-italic font-bold uppercase text-sm mb-2 hover:bg-slate-800 border-l-4 border-transparent <?= isActive($section, 'merchant_requests') ? '!bg-slate-800 !border-l-blue-500' : '' ?>" onclick="closeSidebarOnMobile()">
                <i class="fas fa-user-clock w-6"></i>
                Demandes Marchands
                <?php if (count($pending_merchants) > 0): ?>
                    <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full ml-auto animate-pulse"><?= count($pending_merchants) ?></span>
                <?php endif; ?>
            </a>

            <a href="admin-order.php" class="sidebar-btn w-full text-left p-4 rounded-xl flex items-center gap-3 transition-all bg-transparent border-none text-white cursor-pointer font-italic font-bold uppercase text-sm mb-2 hover:bg-slate-800 border-l-4 border-transparent <?= basename($_SERVER['PHP_SELF']) === 'admin-orders.php' ? '!bg-slate-800 !border-l-blue-500' : '' ?>">
                <i class="fas fa-clipboard-list w-6"></i>
                Gestion des Commandes
                <?php
                $pending_orders_count = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'")->fetch()['total'];
                if ($pending_orders_count > 0):
                ?>
                    <span class="bg-yellow-500 text-white text-xs px-2 py-1 rounded-full ml-auto animate-pulse"><?= $pending_orders_count ?></span>
                <?php endif; ?>
            </a>

            <a href="?section=hot_deals" class="sidebar-btn w-full text-left p-4 rounded-xl flex items-center gap-3 transition-all bg-transparent border-none text-white cursor-pointer font-italic font-bold uppercase text-sm mb-2 hover:bg-slate-800 border-l-4 border-transparent <?= (in_array($section, ['hot_deals', 'add_hot_deal'])) ? '!bg-slate-800 !border-l-blue-500' : '' ?>" onclick="closeSidebarOnMobile()">
                <i class="fas fa-fire w-6"></i>
                Hot Deals
                <?php if ($total_hot_deals > 0): ?>
                    <span class="bg-orange-500 text-white text-xs px-2 py-1 rounded-full ml-auto"><?= $total_hot_deals ?></span>
                <?php endif; ?>
            </a>

            <a href="?section=categories" class="sidebar-btn w-full text-left p-4 rounded-xl flex items-center gap-3 transition-all bg-transparent border-none text-white cursor-pointer font-italic font-bold uppercase text-sm mb-2 hover:bg-slate-800 border-l-4 border-transparent <?= isActive($section, 'categories') ? '!bg-slate-800 !border-l-blue-500' : '' ?>" onclick="closeSidebarOnMobile()">
                <i class="fas fa-tags w-6"></i> Gestion Catégories
            </a>
            <a href="?section=add-product" class="sidebar-btn w-full text-left p-4 rounded-xl flex items-center gap-3 transition-all bg-transparent border-none text-white cursor-pointer font-italic font-bold uppercase text-sm mb-2 hover:bg-slate-800 border-l-4 border-transparent <?= isActive($section, 'add-product') ? '!bg-slate-800 !border-l-blue-500' : '' ?>" onclick="closeSidebarOnMobile()">
                <i class="fas fa-box-open w-6"></i> Ajouter Produit
            </a>
            <a href="?section=products" class="sidebar-btn w-full text-left p-4 rounded-xl flex items-center gap-3 transition-all bg-transparent border-none text-white cursor-pointer font-italic font-bold uppercase text-sm mb-2 hover:bg-slate-800 border-l-4 border-transparent <?= isActive($section, 'products') ? '!bg-slate-800 !border-l-blue-500' : '' ?>" onclick="closeSidebarOnMobile()">
                <i class="fas fa-list w-6"></i> Liste des Produits
            </a>
            <a href="?section=users" class="sidebar-btn w-full text-left p-4 rounded-xl flex items-center gap-3 transition-all bg-transparent border-none text-white cursor-pointer font-italic font-bold uppercase text-sm mb-2 hover:bg-slate-800 border-l-4 border-transparent <?= isActive($section, 'users') ? '!bg-slate-800 !border-l-blue-500' : '' ?>" onclick="closeSidebarOnMobile()">
                <i class="fas fa-users w-6"></i> Utilisateurs
            </a>
            <a href="?section=promotions" class="sidebar-btn w-full text-left p-4 rounded-xl flex items-center gap-3 transition-all bg-transparent border-none text-white cursor-pointer font-italic font-bold uppercase text-sm mb-2 hover:bg-slate-800 border-l-4 border-transparent <?= isActive($section, 'promotions') ? '!bg-slate-800 !border-l-blue-500' : '' ?>" onclick="closeSidebarOnMobile()">
                <i class="fas fa-percentage w-6"></i> Promotions
            </a>

            <!-- ==================== LIEN ENVOYER EMAIL (NOUVEAU) ==================== -->
            <a href="admin_email.php" class="sidebar-btn w-full text-left p-4 rounded-xl flex items-center gap-3 transition-all bg-transparent border-none text-white cursor-pointer font-italic font-bold uppercase text-sm mb-2 hover:bg-slate-800 border-l-4 border-transparent <?= basename($_SERVER['PHP_SELF']) === 'admin_email.php' ? '!bg-slate-800 !border-l-blue-500' : '' ?>">
                <i class="fas fa-envelope w-6"></i> Envoyer Email
            </a>

            <a href="../index.php" class="sidebar-btn w-full text-left p-4 rounded-xl flex items-center gap-3 transition-all bg-transparent border-none text-slate-400 cursor-pointer font-italic font-bold uppercase text-sm mb-2 hover:bg-slate-800 hover:text-white border-l-4 border-transparent">
                <i class="fas fa-arrow-left w-6"></i> Retourner au site
            </a>
        </nav>

        <div class="mt-8 pt-6 border-t border-slate-700">
            <a href="logout.php" class="sidebar-btn w-full text-left p-4 rounded-xl flex items-center gap-3 transition-all bg-transparent border-none text-red-400 cursor-pointer font-italic font-bold uppercase text-sm mb-2 hover:bg-red-600 hover:text-white border-l-4 border-transparent">
                <i class="fas fa-sign-out-alt w-6"></i> Déconnexion
            </a>
        </div>
    </div>

    <!-- Contenu Principal -->
    <div class="main-content flex-1 p-6 md:p-10 overflow-y-auto min-h-screen md:pt-6 pt-[70px]">

        <!-- Alertes -->
        <?php if ($msg): ?>
            <div id="alertMsg" class="bg-green-600 text-white p-4 rounded-2xl mb-6 shadow-lg uppercase text-xs text-center flex items-center justify-center gap-2 transition-opacity duration-500">
                <i class="fas fa-check-circle"></i> <?= $msg ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div id="alertError" class="bg-red-600 text-white p-4 rounded-2xl mb-6 shadow-lg uppercase text-xs text-center flex items-center justify-center gap-2 transition-opacity duration-500">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if ($section === 'dashboard'): ?>
            <!-- TABLEAU DE BORD -->
            <h2 class="text-2xl md:text-3xl font-black mb-8 border-l-4 border-blue-600 pl-4 uppercase">Tableau de Bord</h2>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 md:gap-6 mb-8">
                <div class="bg-white p-4 md:p-6 rounded-2xl md:rounded-3xl shadow-sm border border-slate-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-slate-400 text-xs uppercase hidden sm:block">Total Produits</p>
                            <p class="text-slate-400 text-xs uppercase sm:hidden">Produits</p>
                            <h3 class="text-2xl md:text-3xl font-black text-blue-600"><?= $total_products ?></h3>
                        </div>
                        <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-lg md:text-xl">
                            <i class="fas fa-box"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-4 md:p-6 rounded-2xl md:rounded-3xl shadow-sm border border-slate-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-slate-400 text-xs uppercase">Catégories</p>
                            <h3 class="text-2xl md:text-3xl font-black text-purple-600"><?= $total_categories ?></h3>
                        </div>
                        <div class="w-10 h-10 md:w-12 md:h-12 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 text-lg md:text-xl">
                            <i class="fas fa-tags"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-4 md:p-6 rounded-2xl md:rounded-3xl shadow-sm border border-slate-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-slate-400 text-xs uppercase">Hot Deals</p>
                            <h3 class="text-2xl md:text-3xl font-black text-orange-600"><?= $total_hot_deals ?></h3>
                        </div>
                        <div class="w-10 h-10 md:w-12 md:h-12 bg-orange-100 rounded-full flex items-center justify-center text-orange-600 text-lg md:text-xl">
                            <i class="fas fa-fire"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-4 md:p-6 rounded-2xl md:rounded-3xl shadow-sm border border-slate-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-slate-400 text-xs uppercase">Utilisateurs</p>
                            <h3 class="text-2xl md:text-3xl font-black text-green-600"><?= $total_users ?></h3>
                        </div>
                        <div class="w-10 h-10 md:w-12 md:h-12 bg-green-100 rounded-full flex items-center justify-center text-green-600 text-lg md:text-xl">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-4 md:p-6 rounded-2xl md:rounded-3xl shadow-sm border border-slate-200 col-span-2 md:col-span-1">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-slate-400 text-xs uppercase">Marchands</p>
                            <h3 class="text-2xl md:text-3xl font-black text-amber-600"><?= $total_merchants ?></h3>
                        </div>
                        <div class="w-10 h-10 md:w-12 md:h-12 bg-amber-100 rounded-full flex items-center justify-center text-amber-600 text-lg md:text-xl">
                            <i class="fas fa-store"></i>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($section === 'merchant_requests'): ?>
            <!-- DEMANDES MARCHANDS -->
            <h2 class="text-2xl md:text-3xl font-black mb-8 border-l-4 border-yellow-500 pl-4 uppercase">Demandes des Marchands</h2>

            <?php if (empty($demandes_marchands)): ?>
                <div class="bg-white p-10 rounded-3xl text-center text-slate-400">
                    <i class="fas fa-user-clock text-6xl mb-4 text-yellow-200"></i>
                    <p class="text-xl">Pas de demande de marchand pour le moment</p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($demandes_marchands as $demande):
                        $badge = getStatusBadge($demande['statut']);
                        $uploads_dir = dirname(dirname(__FILE__)) . "/uploads/requests/";
                        $uploads_url = '../uploads/requests/';
                        $foto_profil_exists = !empty($demande['foto_profil']) && file_exists($uploads_dir . $demande['foto_profil']);
                        $piece_id_exists = !empty($demande['piece_id']) && file_exists($uploads_dir . $demande['piece_id']);
                        $preuve_exists = !empty($demande['preuve_paiement']) && file_exists($uploads_dir . $demande['preuve_paiement']);
                    ?>
                        <div class="bg-white p-4 md:p-6 rounded-3xl shadow-lg border border-slate-200 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl overflow-hidden">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6 pb-4 border-b border-slate-100">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 md:w-16 md:h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-xl md:text-2xl text-white font-bold flex-shrink-0">
                                        <?= strtoupper(substr($demande['prenom'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-lg md:text-xl text-slate-800"><?= htmlspecialchars($demande['prenom'] . ' ' . $demande['nom']) ?></h3>
                                        <p class="text-slate-500 text-sm"><i class="fas fa-envelope mr-1 text-blue-500"></i> <?= htmlspecialchars($demande['email']) ?></p>
                                        <p class="text-slate-500 text-sm"><i class="fas fa-phone mr-1 text-green-500"></i> <?= htmlspecialchars($demande['telephone']) ?></p>
                                        <span class="inline-block mt-2 px-3 py-1 rounded-full text-xs font-bold <?= $badge[0] ?>">
                                            <i class="<?= $badge[2] ?> mr-1"></i> <?= $badge[1] ?>
                                        </span>
                                    </div>
                                </div>

                                <?php if ($demande['statut'] === 'pending'): ?>
                                    <div class="flex gap-2 w-full md:w-auto">
                                        <form method="POST" class="flex-1 md:flex-none">
                                            <input type="hidden" name="id_demande" value="<?= $demande['id_demande'] ?>">
                                            <input type="hidden" name="user_id" value="<?= $demande['user_id'] ?>">
                                            <button type="submit" name="approve_merchant" class="w-full bg-green-600 text-white px-4 md:px-6 py-3 rounded-xl font-bold text-sm hover:bg-green-700 transition-all flex items-center justify-center gap-2">
                                                <i class="fas fa-check"></i> <span class="hidden md:inline">Approuver Marchand</span><span class="md:hidden">Approuver</span>
                                            </button>
                                        </form>
                                        <form method="POST" class="flex-1 md:flex-none">
                                            <input type="hidden" name="id_demande" value="<?= $demande['id_demande'] ?>">
                                            <input type="hidden" name="user_id" value="<?= $demande['user_id'] ?>">
                                            <button type="submit" name="reject_merchant" class="w-full bg-red-600 text-white px-4 md:px-6 py-3 rounded-xl font-bold text-sm hover:bg-red-700 transition-all flex items-center justify-center gap-2">
                                                <i class="fas fa-times"></i> <span class="hidden md:inline">Rejeter Demande</span><span class="md:hidden">Rejeter</span>
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div class="space-y-3">
                                    <div class="bg-slate-50 p-4 rounded-xl">
                                        <span class="text-xs text-slate-400 uppercase font-bold block mb-1">Adresse complète:</span>
                                        <p class="font-semibold text-slate-700"><i class="fas fa-map-marker-alt mr-2 text-red-500"></i><?= htmlspecialchars($demande['adresse']) ?></p>
                                    </div>

                                    <div class="bg-slate-50 p-4 rounded-xl">
                                        <span class="text-xs text-slate-400 uppercase font-bold block mb-1">Numéro qui a fait le transfert:</span>
                                        <p class="font-semibold text-slate-700 text-lg"><i class="fas fa-mobile-alt mr-2 text-blue-500"></i><?= htmlspecialchars($demande['numero_transfert']) ?></p>
                                    </div>

                                    <div class="bg-slate-50 p-4 rounded-xl">
                                        <span class="text-xs text-slate-400 uppercase font-bold block mb-1">Date de la demande:</span>
                                        <p class="font-semibold text-slate-700"><i class="fas fa-calendar-alt mr-2 text-purple-500"></i><?= htmlspecialchars($demande['date']) ?></p>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <h4 class="font-bold text-slate-700 uppercase text-sm mb-3"><i class="fas fa-folder-open mr-2"></i>Documents:</h4>

                                    <div class="grid grid-cols-1 gap-3">
                                        <div class="border-2 <?= $preuve_exists ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' ?> rounded-xl p-3 flex items-center gap-3">
                                            <div class="w-10 h-10 md:w-12 md:h-12 rounded-lg <?= $preuve_exists ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' ?> flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-receipt text-lg md:text-xl"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-bold text-sm <?= $preuve_exists ? 'text-green-800' : 'text-red-800' ?>">Preuve de Paiement</p>
                                                <?php if ($preuve_exists): ?>
                                                    <p class="text-xs text-green-600 truncate"><?= htmlspecialchars($demande['preuve_paiement']) ?></p>
                                                    <a href="<?= $uploads_url . htmlspecialchars($demande['preuve_paiement']) ?>" target="_blank" class="text-xs text-blue-600 hover:underline font-semibold">
                                                        <i class="fas fa-eye mr-1"></i>Voir le fichier
                                                    </a>
                                                <?php else: ?>
                                                    <p class="text-xs text-red-500">Fichier non disponible</p>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($preuve_exists): ?>
                                                <span class="text-green-500"><i class="fas fa-check-circle"></i></span>
                                            <?php else: ?>
                                                <span class="text-red-500"><i class="fas fa-exclamation-circle"></i></span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="border-2 <?= $piece_id_exists ? 'border-blue-200 bg-blue-50' : 'border-slate-200 bg-slate-50' ?> rounded-xl p-3 flex items-center gap-3">
                                            <div class="w-10 h-10 md:w-12 md:h-12 rounded-lg <?= $piece_id_exists ? 'bg-blue-100 text-blue-600' : 'bg-slate-200 text-slate-400' ?> flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-id-card text-lg md:text-xl"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-bold text-sm <?= $piece_id_exists ? 'text-blue-800' : 'text-slate-600' ?>">Pièce d'identité</p>
                                                <?php if ($piece_id_exists): ?>
                                                    <p class="text-xs text-blue-600 truncate"><?= htmlspecialchars($demande['piece_id']) ?></p>
                                                    <a href="<?= $uploads_url . htmlspecialchars($demande['piece_id']) ?>" target="_blank" class="text-xs text-blue-600 hover:underline font-semibold">
                                                        <i class="fas fa-eye mr-1"></i>Voir l'image
                                                    </a>
                                                <?php elseif (!empty($demande['piece_id'])): ?>
                                                    <p class="text-xs text-orange-500">Fichier introuvable sur le serveur</p>
                                                <?php else: ?>
                                                    <p class="text-xs text-slate-400">N'a pas été téléchargé</p>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($piece_id_exists): ?>
                                                <span class="text-green-500"><i class="fas fa-check-circle"></i></span>
                                            <?php elseif (!empty($demande['piece_id'])): ?>
                                                <span class="text-orange-500"><i class="fas fa-question-circle"></i></span>
                                            <?php else: ?>
                                                <span class="text-slate-400"><i class="fas fa-minus-circle"></i></span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="border-2 <?= $foto_profil_exists ? 'border-purple-200 bg-purple-50' : 'border-slate-200 bg-slate-50' ?> rounded-xl p-3 flex items-center gap-3">
                                            <div class="w-10 h-10 md:w-12 md:h-12 rounded-lg <?= $foto_profil_exists ? 'bg-purple-100 text-purple-600' : 'bg-slate-200 text-slate-400' ?> flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-user-circle text-lg md:text-xl"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-bold text-sm <?= $foto_profil_exists ? 'text-purple-800' : 'text-slate-600' ?>">Photo de Profil</p>
                                                <?php if ($foto_profil_exists): ?>
                                                    <p class="text-xs text-purple-600 truncate"><?= htmlspecialchars($demande['foto_profil']) ?></p>
                                                    <a href="<?= $uploads_url . htmlspecialchars($demande['foto_profil']) ?>" target="_blank" class="text-xs text-blue-600 hover:underline font-semibold">
                                                        <i class="fas fa-eye mr-1"></i>Voir l'image
                                                    </a>
                                                <?php elseif (!empty($demande['foto_profil'])): ?>
                                                    <p class="text-xs text-orange-500">Fichier introuvable sur le serveur</p>
                                                <?php else: ?>
                                                    <p class="text-xs text-slate-400">N'a pas été téléchargé</p>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($foto_profil_exists): ?>
                                                <span class="text-green-500"><i class="fas fa-check-circle"></i></span>
                                            <?php elseif (!empty($demande['foto_profil'])): ?>
                                                <span class="text-orange-500"><i class="fas fa-question-circle"></i></span>
                                            <?php else: ?>
                                                <span class="text-slate-400"><i class="fas fa-minus-circle"></i></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($foto_profil_exists || $piece_id_exists || $preuve_exists): ?>
                                <div class="border-t border-slate-100 pt-6">
                                    <h4 class="font-bold text-slate-700 uppercase text-sm mb-4"><i class="fas fa-images mr-2"></i>Visualisation des images:</h4>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        <?php if ($preuve_exists): ?>
                                            <div class="relative group">
                                                <div class="aspect-square rounded-2xl overflow-hidden border-2 border-green-200 bg-white shadow-sm">
                                                    <?php
                                                    $ext = strtolower(pathinfo($demande['preuve_paiement'], PATHINFO_EXTENSION));
                                                    if ($ext === 'pdf'): ?>
                                                        <div class="w-full h-full flex flex-col items-center justify-center bg-red-50 text-red-600 p-4">
                                                            <i class="fas fa-file-pdf text-5xl mb-2"></i>
                                                            <span class="text-xs font-bold text-center">Document PDF</span>
                                                        </div>
                                                    <?php else: ?>
                                                        <img src="<?= $uploads_url . htmlspecialchars($demande['preuve_paiement']) ?>"
                                                            alt="Preuve de Paiement"
                                                            class="w-full h-full object-cover">
                                                    <?php endif; ?>
                                                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-all flex items-center justify-center opacity-0 group-hover:opacity-100">
                                                        <a href="<?= $uploads_url . htmlspecialchars($demande['preuve_paiement']) ?>" target="_blank"
                                                            class="bg-white text-slate-800 px-4 py-2 rounded-lg font-bold text-sm shadow-lg scale-90 group-hover:scale-100 transition-transform">
                                                            <i class="fas fa-external-link-alt mr-1"></i>Voir
                                                        </a>
                                                    </div>
                                                </div>
                                                <p class="text-center text-xs font-bold text-green-700 mt-2">Preuve de Paiement</p>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($piece_id_exists): ?>
                                            <div class="relative group">
                                                <div class="aspect-square rounded-2xl overflow-hidden border-2 border-blue-200 bg-white shadow-sm">
                                                    <img src="<?= $uploads_url . htmlspecialchars($demande['piece_id']) ?>"
                                                        alt="Pièce d'identité"
                                                        class="w-full h-full object-cover">
                                                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-all flex items-center justify-center opacity-0 group-hover:opacity-100">
                                                        <a href="<?= $uploads_url . htmlspecialchars($demande['piece_id']) ?>" target="_blank"
                                                            class="bg-white text-slate-800 px-4 py-2 rounded-lg font-bold text-sm shadow-lg scale-90 group-hover:scale-100 transition-transform">
                                                            <i class="fas fa-external-link-alt mr-1"></i>Voir
                                                        </a>
                                                    </div>
                                                </div>
                                                <p class="text-center text-xs font-bold text-blue-700 mt-2">Pièce d'identité</p>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($foto_profil_exists): ?>
                                            <div class="relative group">
                                                <div class="aspect-square rounded-2xl overflow-hidden border-2 border-purple-200 bg-white shadow-sm">
                                                    <img src="<?= $uploads_url . htmlspecialchars($demande['foto_profil']) ?>"
                                                        alt="Photo de Profil"
                                                        class="w-full h-full object-cover">
                                                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-all flex items-center justify-center opacity-0 group-hover:opacity-100">
                                                        <a href="<?= $uploads_url . htmlspecialchars($demande['foto_profil']) ?>" target="_blank"
                                                            class="bg-white text-slate-800 px-4 py-2 rounded-lg font-bold text-sm shadow-lg scale-90 group-hover:scale-100 transition-transform">
                                                            <i class="fas fa-external-link-alt mr-1"></i>Voir
                                                        </a>
                                                    </div>
                                                </div>
                                                <p class="text-center text-xs font-bold text-purple-700 mt-2">Photo de Profil</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php elseif (in_array($section, ['hot_deals', 'add_hot_deal'])): ?>
            <!-- SECTION HOT DEALS -->
            <?php if ($section === 'add_hot_deal' || isset($_GET['edit_deal'])): ?>
                <?php
                $edit_mode = isset($_GET['edit_deal']);
                $deal_data = null;
                $deal_images = [];

                if ($edit_mode) {
                    $deal_id = $_GET['edit_deal'];
                    $deal_stmt = $pdo->prepare("SELECT * FROM hot_deals WHERE id = ?");
                    $deal_stmt->execute([$deal_id]);
                    $deal_data = $deal_stmt->fetch();

                    if ($deal_data) {
                        $img_stmt = $pdo->prepare("SELECT * FROM hot_deal_images WHERE deal_id = ? ORDER BY is_primary DESC, ordre ASC");
                        $img_stmt->execute([$deal_id]);
                        $deal_images = $img_stmt->fetchAll();
                    }
                }
                ?>

                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                    <h2 class="text-2xl md:text-3xl font-black border-l-4 border-orange-500 pl-4 uppercase">
                        <?= $edit_mode ? 'Modifier Hot Deal' : 'Nouveau Hot Deal' ?>
                    </h2>
                    <a href="?section=hot_deals" class="bg-slate-600 text-white px-6 py-3 rounded-2xl font-bold uppercase text-sm hover:bg-slate-700">
                        <i class="fas fa-arrow-left"></i> Retourner
                    </a>
                </div>

                <div class="bg-white p-6 md:p-10 rounded-3xl shadow-sm border border-slate-200 max-w-4xl">
                    <form method="POST" enctype="multipart/form-data" class="space-y-6">
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="deal_id" value="<?= $deal_data['id'] ?>">
                        <?php endif; ?>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-xs text-slate-500 mb-2 uppercase font-bold">Titre du Deal *</label>
                                <input type="text" name="<?= $edit_mode ? 'edit_titre' : 'deal_titre' ?>"
                                    value="<?= $edit_mode ? htmlspecialchars($deal_data['titre']) : '' ?>"
                                    required class="w-full p-4 bg-slate-50 rounded-2xl outline-none ring-1 ring-slate-200">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs text-slate-500 mb-2 uppercase font-bold">Description</label>
                                <textarea name="<?= $edit_mode ? 'edit_desc' : 'deal_desc' ?>" rows="3"
                                    class="w-full p-4 bg-slate-50 rounded-2xl outline-none ring-1 ring-slate-200"><?= $edit_mode ? htmlspecialchars($deal_data['description']) : '' ?></textarea>
                            </div>

                            <div>
                                <label class="block text-xs text-slate-500 mb-2 uppercase font-bold">Prix Original (HTG) *</label>
                                <input type="number" name="<?= $edit_mode ? 'edit_prix_original' : 'deal_prix_original' ?>"
                                    value="<?= $edit_mode ? $deal_data['prix_original'] : '' ?>"
                                    required class="w-full p-4 bg-slate-50 rounded-2xl outline-none ring-1 ring-slate-200">
                            </div>

                            <div>
                                <label class="block text-xs text-slate-500 mb-2 uppercase font-bold text-orange-600">Prix Deal (HTG) *</label>
                                <input type="number" name="<?= $edit_mode ? 'edit_prix' : 'deal_prix' ?>"
                                    value="<?= $edit_mode ? $deal_data['prix_deal'] : '' ?>"
                                    required class="w-full p-4 bg-slate-50 rounded-2xl outline-none ring-2 ring-orange-200 border-2 border-orange-100">
                            </div>

                            <div>
                                <label class="block text-xs text-slate-500 mb-2 uppercase font-bold">Date d'Expiration</label>
                                <input type="datetime-local" name="<?= $edit_mode ? 'edit_date_fin' : 'deal_date_fin' ?>"
                                    value="<?= $edit_mode && $deal_data['date_fin'] ? str_replace(' ', 'T', $deal_data['date_fin']) : '' ?>"
                                    class="w-full p-4 bg-slate-50 rounded-2xl outline-none ring-1 ring-slate-200">
                            </div>

                            <div class="flex items-center">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" name="<?= $edit_mode ? 'edit_stock' : 'deal_stock' ?>"
                                        <?= ($edit_mode && $deal_data['en_stock']) || (!$edit_mode) ? 'checked' : '' ?>
                                        class="w-5 h-5 text-orange-600 rounded">
                                    <span class="text-sm font-bold uppercase text-slate-700">En Stock</span>
                                </label>
                            </div>
                        </div>

                        <div class="border-t border-slate-200 pt-6">
                            <label class="block text-xs text-slate-500 mb-4 uppercase font-bold">
                                <?= $edit_mode ? 'Ajouter Nouvelles Images' : 'Images du Produit * (Vous pouvez en choisir plusieurs)' ?>
                            </label>

                            <input type="file" name="<?= $edit_mode ? 'edit_new_images[]' : 'deal_images[]' ?>"
                                accept="image/*" <?= $edit_mode ? '' : 'required' ?> multiple
                                class="w-full p-4 border-2 border-dashed rounded-2xl border-slate-300 hover:border-orange-400 transition-colors"
                                onchange="previewImages(this)">

                            <p class="text-xs text-slate-400 mt-2">
                                <i class="fas fa-info-circle"></i>
                                <?= $edit_mode ? 'Choisir de nouvelles images à ajouter' : 'La première image sera l\'image principale.' ?>
                            </p>

                            <div id="imagePreview" class="grid grid-cols-[repeat(auto-fill,minmax(100px,1fr))] gap-2.5 mt-4"></div>
                        </div>

                        <?php if ($edit_mode && !empty($deal_images)): ?>
                            <div class="border-t border-slate-200 pt-6">
                                <label class="block text-xs text-slate-500 mb-4 uppercase font-bold">Images existantes</label>
                                <div class="grid grid-cols-[repeat(auto-fill,minmax(100px,1fr))] gap-2.5">
                                    <?php foreach ($deal_images as $img):
                                        $img_path = $uploads_base_path . 'hot_deals/' . $img['image_name'];
                                        $img_exists = file_exists($img_path);
                                    ?>
                                        <div class="relative aspect-square rounded-lg overflow-hidden border-2 <?= $img['is_primary'] ? 'border-blue-500 shadow-[0_0_0_3px_rgba(59,130,246,0.3)]' : 'border-slate-200' ?> <?= !$img_exists ? 'opacity-50' : '' ?>">
                                            <?php if ($img_exists): ?>
                                                <img src="<?= $img_base_url ?>hot_deals/<?= htmlspecialchars($img['image_name']) ?>" alt="Image du deal" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <div class="w-full h-full bg-gray-200 flex items-center justify-center text-gray-400 text-xs">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="absolute top-0 right-0 flex gap-0.5 p-1">
                                                <?php if (!$img['is_primary']): ?>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="image_id" value="<?= $img['id'] ?>">
                                                        <input type="hidden" name="deal_id" value="<?= $deal_data['id'] ?>">
                                                        <button type="submit" name="set_primary_image" class="w-6 h-6 rounded bg-blue-500 text-white flex items-center justify-center text-[10px] cursor-pointer border-none" title="Rendre principale">
                                                            <i class="fas fa-star"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" class="inline" onsubmit="return confirm('Supprimer cette image?');">
                                                    <input type="hidden" name="image_id" value="<?= $img['id'] ?>">
                                                    <input type="hidden" name="deal_id" value="<?= $deal_data['id'] ?>">
                                                    <button type="submit" name="delete_deal_image" class="w-6 h-6 rounded bg-red-500 text-white flex items-center justify-center text-[10px] cursor-pointer border-none" title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                            <?php if ($img['is_primary']): ?>
                                                <div class="absolute bottom-0 left-0 right-0 bg-blue-500 text-white text-xs text-center py-1">Principale</div>
                                            <?php endif; ?>
                                            <?php if (!$img_exists): ?>
                                                <div class="absolute top-0 left-0 right-0 bg-red-500 text-white text-xs text-center py-1">Introuvable</div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="pt-6">
                            <button type="submit" name="<?= $edit_mode ? 'update_hot_deal' : 'add_hot_deal' ?>"
                                class="w-full py-5 bg-orange-600 text-white rounded-2xl font-black uppercase tracking-widest hover:bg-orange-700 transition-all">
                                <i class="fas fa-fire"></i> <?= $edit_mode ? 'Mettre à jour Hot Deal' : 'Créer Hot Deal' ?>
                            </button>
                        </div>
                    </form>
                </div>

            <?php else: ?>
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                    <h2 class="text-2xl md:text-3xl font-black border-l-4 border-orange-500 pl-4 uppercase">
                        <i class="fas fa-fire text-orange-500"></i> Hot Deals
                    </h2>
                    <a href="?section=add_hot_deal" class="bg-orange-600 text-white px-6 py-3 rounded-2xl font-bold uppercase text-sm hover:bg-orange-700">
                        <i class="fas fa-plus"></i> Nouveau Deal
                    </a>
                </div>

                <?php if (empty($hot_deals)): ?>
                    <div class="bg-white p-10 rounded-3xl text-center text-slate-400">
                        <i class="fas fa-fire text-6xl mb-4 text-orange-200"></i>
                        <p class="text-xl">Pas de Hot Deal pour le moment</p>
                        <a href="?section=add_hot_deal" class="inline-block mt-4 text-orange-600 hover:underline">
                            Créer votre premier Hot Deal
                        </a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <?php foreach ($hot_deals as $deal):
                            $pourcentage = round((($deal['prix_original'] - $deal['prix_deal']) / $deal['prix_original']) * 100);
                            $expired = $deal['date_fin'] && strtotime($deal['date_fin']) < time();

                            $display_images = [];
                            foreach ($deal['images'] as $img) {
                                $img_path = $uploads_base_path . 'hot_deals/' . $img['image_name'];
                                if (file_exists($img_path)) {
                                    $display_images[] = $img;
                                }
                            }
                            $has_images = !empty($display_images);
                        ?>
                            <div class="bg-white rounded-3xl shadow-lg border border-slate-200 overflow-hidden <?= $expired ? 'opacity-60' : '' ?>">
                                <div class="relative overflow-hidden rounded-3xl" id="slider-<?= $deal['id'] ?>">
                                    <?php if ($has_images): ?>
                                        <div class="flex transition-transform duration-500" data-slides>
                                            <?php foreach ($display_images as $index => $img): ?>
                                                <div class="min-w-full h-[300px]">
                                                    <img src="<?= $img_base_url ?>hot_deals/<?= htmlspecialchars($img['image_name']) ?>"
                                                        alt="<?= htmlspecialchars($deal['titre']) ?>"
                                                        class="w-full h-full object-cover"
                                                        onerror="this.parentElement.innerHTML='<div class=\'w-full h-full bg-gray-200 flex items-center justify-center text-gray-400\'><i class=\'fas fa-image\'></i></div>'">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <?php if (count($display_images) > 1): ?>
                                            <button class="absolute top-1/2 -translate-y-1/2 left-2.5 bg-black/50 text-white border-none p-4 cursor-pointer rounded-full transition-all hover:bg-black/80" onclick="moveSlide(<?= $deal['id'] ?>, -1)">
                                                <i class="fas fa-chevron-left"></i>
                                            </button>
                                            <button class="absolute top-1/2 -translate-y-1/2 right-2.5 bg-black/50 text-white border-none p-4 cursor-pointer rounded-full transition-all hover:bg-black/80" onclick="moveSlide(<?= $deal['id'] ?>, 1)">
                                                <i class="fas fa-chevron-right"></i>
                                            </button>
                                            <div class="absolute bottom-2.5 left-1/2 -translate-x-1/2 flex gap-2" data-dots>
                                                <?php foreach ($display_images as $index => $img): ?>
                                                    <span class="w-2.5 h-2.5 rounded-full bg-white/50 cursor-pointer transition-all <?= $index === 0 ? '!bg-white' : '' ?>" onclick="goToSlide(<?= $deal['id'] ?>, <?= $index ?>)"></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="min-w-full h-[300px] bg-gray-200 flex items-center justify-center text-gray-400">
                                            <div class="text-center">
                                                <i class="fas fa-image text-4xl mb-2"></i>
                                                <p>Pas d'image</p>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="absolute top-4 left-4 bg-red-500 text-white px-3 py-1 rounded-full font-bold text-sm">-<?= $pourcentage ?>%</div>

                                    <?php if (!$deal['en_stock']): ?>
                                        <div class="absolute top-4 right-4 bg-slate-800 text-white px-3 py-1 rounded-full font-bold text-xs">RUPTURE DE STOCK</div>
                                    <?php endif; ?>

                                    <?php if ($expired): ?>
                                        <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                            <span class="bg-red-500 text-white px-6 py-3 rounded-full font-bold text-xl -rotate-12">EXPIRÉ</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="p-6">
                                    <h3 class="font-black text-xl mb-2 uppercase"><?= htmlspecialchars($deal['titre']) ?></h3>
                                    <p class="text-slate-600 text-sm mb-4 line-clamp-2"><?= htmlspecialchars($deal['description']) ?></p>

                                    <div class="flex items-end gap-3 mb-4">
                                        <span class="text-2xl md:text-3xl font-black text-orange-600"><?= number_format($deal['prix_deal']) ?> HTG</span>
                                        <span class="text-lg text-slate-400 line-through mb-1"><?= number_format($deal['prix_original']) ?> HTG</span>
                                    </div>

                                    <?php if ($deal['date_fin']): ?>
                                        <div class="bg-slate-100 rounded-xl p-3 mb-4">
                                            <div class="flex items-center gap-2 text-xs text-slate-600 mb-1">
                                                <i class="fas fa-clock"></i>
                                                <span>Expire dans:</span>
                                            </div>
                                            <div class="font-bold text-slate-800 countdown" data-end="<?= $deal['date_fin'] ?>">Calcul en cours...</div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="flex gap-3 pt-4 border-t border-slate-100">
                                        <a href="?section=hot_deals&edit_deal=<?= $deal['id'] ?>"
                                            class="flex-1 bg-blue-600 text-white py-3 rounded-xl font-bold text-center hover:bg-blue-700 transition">
                                            <i class="fas fa-edit"></i> Modifier
                                        </a>
                                        <form method="POST" class="flex-1" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce Hot Deal?');">
                                            <input type="hidden" name="deal_id" value="<?= $deal['id'] ?>">
                                            <button type="submit" name="delete_hot_deal" class="w-full bg-red-600 text-white py-3 rounded-xl font-bold hover:bg-red-700 transition">
                                                <i class="fas fa-trash"></i> Supprimer
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

        <?php elseif ($section === 'categories'): ?>
            <h2 class="text-2xl md:text-3xl font-black mb-8 border-l-4 border-blue-600 pl-4 uppercase">Gestion des Catégories</h2>

            <div class="bg-white p-4 md:p-6 rounded-3xl shadow-sm border border-slate-200 mb-8">
                <form method="POST" class="flex flex-col sm:flex-row gap-4">
                    <input type="text" name="nom_cat" placeholder="Nom de la nouvelle catégorie" required
                        class="flex-1 p-4 bg-slate-50 rounded-2xl outline-none ring-1 ring-slate-200">
                    <button type="submit" name="add_cat" class="bg-blue-600 text-white px-8 py-4 rounded-2xl font-bold uppercase hover:bg-blue-700">
                        <i class="fas fa-plus"></i> Ajouter
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-x-auto">
                <table class="w-full min-w-[500px]">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left p-4 text-xs uppercase text-slate-500">ID</th>
                            <th class="text-left p-4 text-xs uppercase text-slate-500">Nom de la Catégorie</th>
                            <th class="text-right p-4 text-xs uppercase text-slate-500">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_cats as $cat): ?>
                            <tr class="border-t border-slate-100">
                                <td class="p-4"><?= $cat['id'] ?></td>
                                <td class="p-4 font-semibold"><?= htmlspecialchars($cat['name']) ?></td>
                                <td class="p-4 text-right">
                                    <form method="POST" class="inline" onsubmit="return confirm('Supprimer cette catégorie?');">
                                        <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
                                        <button type="submit" name="delete_cat" class="bg-red-100 text-red-600 p-2 rounded-lg hover:bg-red-200">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($section === 'add-product'): ?>
            <h2 class="text-2xl md:text-3xl font-black mb-8 border-l-4 border-orange-500 pl-4 uppercase">Nouveau Produit</h2>

            <div class="bg-white p-6 md:p-10 rounded-3xl shadow-sm border border-slate-200 max-w-4xl">
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-xs text-slate-500 mb-2 uppercase font-bold">Nom du Produit *</label>
                            <input type="text" name="p_nom" required class="w-full p-4 bg-slate-50 rounded-2xl outline-none ring-1 ring-slate-200">
                        </div>

                        <div>
                            <label class="block text-xs text-slate-500 mb-2 uppercase font-bold">Prix Régulier (HTG) *</label>
                            <input type="number" name="p_prix_reg" required class="w-full p-4 bg-slate-50 rounded-2xl outline-none ring-1 ring-slate-200">
                        </div>

                        <div>
                            <label class="block text-xs text-slate-500 mb-2 uppercase font-bold">Prix Promotionnel (HTG)</label>
                            <input type="number" name="p_prix_promo" class="w-full p-4 bg-slate-50 rounded-2xl outline-none ring-1 ring-slate-200">
                        </div>

                        <div>
                            <label class="block text-xs text-slate-500 mb-2 uppercase font-bold">Quantité en Stock *</label>
                            <input type="number" name="p_qty" required class="w-full p-4 bg-slate-50 rounded-2xl outline-none ring-1 ring-slate-200">
                        </div>

                        <div>
                            <label class="block text-xs text-slate-500 mb-2 uppercase font-bold">Catégorie *</label>
                            <select name="p_category" required class="w-full p-4 bg-slate-50 rounded-2xl outline-none ring-1 ring-slate-200">
                                <option value="">Choisir une catégorie</option>
                                <?php foreach ($all_cats as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs text-slate-500 mb-2 uppercase font-bold">Description</label>
                            <textarea name="p_desc" rows="3" class="w-full p-4 bg-slate-50 rounded-2xl outline-none ring-1 ring-slate-200"></textarea>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs text-slate-500 mb-2 uppercase font-bold">Caractéristiques</label>
                            <textarea name="p_carac" rows="3" class="w-full p-4 bg-slate-50 rounded-2xl outline-none ring-1 ring-slate-200"></textarea>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs text-slate-500 mb-2 uppercase font-bold">Image du Produit *</label>
                            <input type="file" name="p_img" accept="image/*" required
                                class="w-full p-4 border-2 border-dashed rounded-2xl border-slate-300 hover:border-orange-400 transition-colors">
                        </div>
                    </div>

                    <div class="pt-6">
                        <button type="submit" name="add_product" class="w-full py-5 bg-orange-600 text-white rounded-2xl font-black uppercase tracking-widest hover:bg-orange-700 transition-all">
                            <i class="fas fa-plus"></i> Ajouter Produit
                        </button>
                    </div>
                </form>
            </div>

        <?php elseif ($section === 'products'): ?>
            <h2 class="text-2xl md:text-3xl font-black mb-8 border-l-4 border-indigo-600 pl-4 uppercase">Liste des Produits</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($all_products as $product):
                    $img_url = $product['image'] ? $img_base_url . 'products/' . $product['image'] : '';
                ?>
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden transition-all duration-300 hover:-translate-y-1">
                        <div class="h-48 bg-slate-100 relative">
                            <?php if ($img_url): ?>
                                <img src="<?= $img_url ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-slate-400">
                                    <i class="fas fa-image text-4xl"></i>
                                </div>
                            <?php endif; ?>
                            <?php if ($product['price_promo']): ?>
                                <div class="absolute top-4 left-4 bg-red-500 text-white px-3 py-1 rounded-full text-xs font-bold">PROMO</div>
                            <?php endif; ?>
                        </div>

                        <div class="p-6">
                            <div class="text-xs text-slate-400 uppercase mb-1"><?= htmlspecialchars($product['category_name'] ?? 'Sans Catégorie') ?></div>
                            <h3 class="font-bold text-lg mb-2"><?= htmlspecialchars($product['name']) ?></h3>

                            <div class="flex items-center gap-2 mb-4">
                                <?php if ($product['price_promo']): ?>
                                    <span class="text-xl font-black text-orange-600"><?= number_format($product['price_promo']) ?> HTG</span>
                                    <span class="text-sm text-slate-400 line-through"><?= number_format($product['price']) ?> HTG</span>
                                <?php else: ?>
                                    <span class="text-xl font-black text-slate-800"><?= number_format($product['price']) ?> HTG</span>
                                <?php endif; ?>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-xs px-3 py-1 rounded-full <?= $product['status'] === 'disponible' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                    <?= $product['status'] ?>
                                </span>
                                <span class="text-xs text-slate-500">Stock: <?= $product['stock_qty'] ?></span>
                            </div>

                            <div class="flex gap-2 mt-4 pt-4 border-t border-slate-100">
                                <button onclick="editProduct(<?= $product['id'] ?>)" class="flex-1 bg-blue-600 text-white py-2 rounded-xl text-sm font-bold hover:bg-blue-700">
                                    <i class="fas fa-edit"></i> Modifier
                                </button>
                                <form method="POST" class="flex-1" onsubmit="return confirm('Supprimer ce produit?');">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <button type="submit" name="delete_product" class="w-full bg-red-600 text-white py-2 rounded-xl text-sm font-bold hover:bg-red-700">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($section === 'users'): ?>
            <h2 class="text-2xl md:text-3xl font-black mb-8 border-l-4 border-green-600 pl-4 uppercase">Gestion des Utilisateurs</h2>

            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-x-auto">
                <table class="w-full min-w-[700px]">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left p-4 text-xs uppercase text-slate-500">UUID</th>
                            <th class="text-left p-4 text-xs uppercase text-slate-500">Nom</th>
                            <th class="text-left p-4 text-xs uppercase text-slate-500">Email</th>
                            <th class="text-left p-4 text-xs uppercase text-slate-500">Rôle</th>
                            <th class="text-left p-4 text-xs uppercase text-slate-500">Date de Création</th>
                            <th class="text-right p-4 text-xs uppercase text-slate-500">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_users as $user): ?>
                            <tr class="border-t border-slate-100">
                                <td class="p-4">
                                    <div class="font-mono text-xs text-slate-500 max-w-[150px] overflow-hidden text-ellipsis whitespace-nowrap relative cursor-help group" data-uuid="<?= htmlspecialchars($user['uuid_text'] ?? 'N/A') ?>">
                                        <?php if (!empty($user['uuid_text'])): ?>
                                            <span class="font-mono text-xs bg-slate-100 px-2 py-1 rounded">
                                                <?= substr($user['uuid_text'], 0, 8) ?>...
                                            </span>
                                            <i class="fas fa-copy text-slate-400 ml-1 cursor-pointer hover:text-blue-500" onclick="copyToClipboard('<?= htmlspecialchars($user['uuid_text']) ?>')" title="Copier UUID"></i>
                                            <div class="absolute bottom-full left-0 bg-slate-800 text-white px-3 py-2 rounded-lg text-xs whitespace-nowrap z-[100] shadow-lg hidden group-hover:block">
                                                <?= htmlspecialchars($user['uuid_text']) ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-red-400 text-xs italic">Pas d'UUID</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="p-4 font-semibold"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></td>
                                <td class="p-4 text-slate-500"><?= htmlspecialchars($user['email']) ?></td>
                                <td class="p-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold 
                                        <?= $user['role'] === 'admin' ? 'bg-purple-100 text-purple-700' : ($user['role'] === 'merchant' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700') ?>">
                                        <?= $user['role'] ?>
                                    </span>
                                </td>
                                <td class="p-4 text-slate-500"><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                                <td class="p-4 text-right">
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('Supprimer cet utilisateur?');">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" name="delete_user" class="bg-red-100 text-red-600 p-2 rounded-lg hover:bg-red-200">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">Vous-même</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($section === 'promotions'): ?>
            <h2 class="text-2xl md:text-3xl font-black mb-8 border-l-4 border-red-500 pl-4 uppercase">Produits en Promotion</h2>

            <?php
            $promo_products = array_filter($all_products, fn($p) => !empty($p['price_promo']));
            ?>

            <?php if (empty($promo_products)): ?>
                <div class="bg-white p-10 rounded-3xl text-center text-slate-400">
                    <i class="fas fa-percentage text-6xl mb-4 text-red-200"></i>
                    <p class="text-xl">Pas de produit en promotion pour le moment</p>
                    <a href="?section=add-product" class="inline-block mt-4 text-red-600 hover:underline">
                        Ajouter un produit avec un prix promotionnel
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($promo_products as $product):
                        $discount = round((($product['price'] - $product['price_promo']) / $product['price']) * 100);
                    ?>
                        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden transition-all duration-300 hover:-translate-y-1">
                            <div class="h-48 bg-slate-100 relative">
                                <?php if ($product['image']): ?>
                                    <img src="<?= $img_base_url ?>products/<?= $product['image'] ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="w-full h-full object-cover">
                                <?php endif; ?>
                                <div class="absolute top-4 left-4 bg-red-500 text-white px-3 py-1 rounded-full font-bold">-<?= $discount ?>%</div>
                            </div>

                            <div class="p-6">
                                <h3 class="font-bold text-lg mb-2"><?= htmlspecialchars($product['name']) ?></h3>
                                <div class="flex items-center gap-2">
                                    <span class="text-xl font-black text-red-600"><?= number_format($product['price_promo']) ?> HTG</span>
                                    <span class="text-sm text-slate-400 line-through"><?= number_format($product['price']) ?> HTG</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>

    <script>
        // ===== GESTION SIDEBAR MOBILE =====
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const icon = document.getElementById('hamburgerIcon');

            sidebar.classList.toggle('-translate-x-full');
            sidebar.classList.toggle('translate-x-0');
            overlay.classList.toggle('opacity-0');
            overlay.classList.toggle('invisible');
            overlay.classList.toggle('opacity-100');
            overlay.classList.toggle('visible');

            if (sidebar.classList.contains('translate-x-0')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        }

        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const icon = document.getElementById('hamburgerIcon');

            sidebar.classList.add('-translate-x-full');
            sidebar.classList.remove('translate-x-0');
            overlay.classList.add('opacity-0', 'invisible');
            overlay.classList.remove('opacity-100', 'visible');
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
        }

        function closeSidebarOnMobile() {
            if (window.innerWidth < 768) {
                closeSidebar();
            }
        }

        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768) {
                closeSidebar();
            }
        });

        // ===== SLIDER HOT DEALS =====
        let slideIndices = {};

        function moveSlide(dealId, direction) {
            if (!slideIndices[dealId]) slideIndices[dealId] = 0;
            const slider = document.querySelector(`#slider-${dealId}`);
            if (!slider) return;
            const slidesContainer = slider.querySelector('[data-slides]');
            const dots = slider.querySelector('[data-dots]');
            const dotElements = dots ? dots.querySelectorAll('span') : [];
            const totalSlides = dotElements.length || 1;

            slideIndices[dealId] += direction;
            if (slideIndices[dealId] >= totalSlides) slideIndices[dealId] = 0;
            if (slideIndices[dealId] < 0) slideIndices[dealId] = totalSlides - 1;

            updateSlider(dealId);
        }

        function goToSlide(dealId, index) {
            slideIndices[dealId] = index;
            updateSlider(dealId);
        }

        function updateSlider(dealId) {
            const slider = document.querySelector(`#slider-${dealId}`);
            if (!slider) return;
            const slidesContainer = slider.querySelector('[data-slides]');
            const dots = slider.querySelector('[data-dots]');
            const dotElements = dots ? dots.querySelectorAll('span') : [];

            if (slidesContainer) {
                slidesContainer.style.transform = `translateX(-${slideIndices[dealId] * 100}%)`;
            }

            dotElements.forEach((dot, index) => {
                if (index === slideIndices[dealId]) {
                    dot.classList.add('!bg-white');
                } else {
                    dot.classList.remove('!bg-white');
                }
            });
        }

        document.querySelectorAll('[id^="slider-"]').forEach(slider => {
            const dealId = slider.id.replace('slider-', '');
            slideIndices[dealId] = 0;
        });

        // ===== APERÇU IMAGES =====
        function previewImages(input) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';

            if (input.files) {
                Array.from(input.files).forEach((file, index) => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'relative aspect-square rounded-lg overflow-hidden border-2 ' + (index === 0 ? 'border-blue-500 shadow-[0_0_0_3px_rgba(59,130,246,0.3)]' : 'border-slate-200');
                        div.innerHTML = `
                            <img src="${e.target.result}" alt="Aperçu" class="w-full h-full object-cover">
                            ${index === 0 ? '<div class="absolute bottom-0 left-0 right-0 bg-blue-500 text-white text-xs text-center py-1">Principale</div>' : ''}
                        `;
                        preview.appendChild(div);
                    }
                    reader.readAsDataURL(file);
                });
            }
        }

        // ===== COMPTE À REBOURS =====
        function updateCountdowns() {
            document.querySelectorAll('.countdown').forEach(el => {
                const endDate = new Date(el.dataset.end);
                const now = new Date();
                const diff = endDate - now;

                if (diff <= 0) {
                    el.textContent = 'Expiré!';
                    el.classList.add('text-red-600');
                    return;
                }

                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

                el.textContent = days > 0 ? `${days}j ${hours}h ${minutes}m` : `${String(hours).padStart(2,'0')}:${String(minutes).padStart(2,'0')}`;
            });
        }

        setInterval(updateCountdowns, 60000);
        updateCountdowns();

        // ===== MASQUAGE AUTOMATIQUE DES ALERTES =====
        setTimeout(function() {
            const alertMsg = document.getElementById('alertMsg');
            const alertError = document.getElementById('alertError');
            if (alertMsg) {
                alertMsg.style.opacity = '0';
                setTimeout(() => alertMsg.remove(), 500);
            }
            if (alertError) {
                alertError.style.opacity = '0';
                setTimeout(() => alertError.remove(), 500);
            }
        }, 5000);

        // ===== FONCTIONS D'AIDE =====
        function editProduct(productId) {
            alert('La fonction de modification du produit est en cours de développement...');
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('UUID copié dans le presse-papiers!');
            }, function(err) {
                console.error('Erreur lors de la copie:', err);
            });
        }
    </script>
</body>

</html>