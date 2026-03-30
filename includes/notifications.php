<?php
// FICHYE: includes/notifications.php
// Fonksyon pou voye notifikasyon email (MODIFYE POU PHPMAILER)

require_once dirname(__DIR__) . '/config/db.php';
// Nou chaje depandans yo pou PHPMailer ka mache
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/mail.php';

/**
 * Voye notifikasyon bay admin yo
 * * @param string $type - Kalite notifikasyon (new_registration, new_order, etc.)
 * @param array $data - Done ki gen rapò ak evenman an
 * @return bool - Si email la voye avèk siksè oswa non
 */
function notifyAdmins($type, $data = [])
{
    global $pdo;

    // Rekipere tout admin yo nan baz de done a
    try {
        $stmt = $pdo->query("SELECT email, prenom, nom FROM users WHERE role = 'admin'");
        $admins = $stmt->fetchAll();

        if (empty($admins)) {
            error_log("[NOTIFICATION] Pa gen admin pou voye notifikasyon.");
            return false;
        }

        // Prepare kontni email la selon kalite notifikasyon an
        switch ($type) {
            case 'new_registration':
                $subject = "🎉 Nouvo Enskripsyon - " . htmlspecialchars(($data['prenom'] ?? '') . ' ' . ($data['nom'] ?? ''));
                $message_body = getNewRegistrationTemplate($data);
                break;

            case 'new_order':
                $subject = "📦 Nouvo Kòmand - " . htmlspecialchars($data['order_id'] ?? 'ID#' . rand(1000, 9999));
                $message_body = getNewOrderTemplate($data);
                break;

            case 'merchant_request':
                $subject = "🏪 Nouvo Demann Machann - " . htmlspecialchars(($data['prenom'] ?? '') . ' ' . ($data['nom'] ?? ''));
                $message_body = getMerchantRequestTemplate($data);
                break;

            default:
                $subject = "📢 Notifikasyon Sistèm LE STOCK";
                $message_body = getDefaultTemplate($data);
        }

        // Voye email bay chak admin
        $success_count = 0;
        foreach ($admins as $admin) {

            // Pèsonalize mesaj la pou chak admin
            $personalized_content = str_replace(
                ['{admin_prenom}', '{admin_nom}'],
                [htmlspecialchars($admin['prenom']), htmlspecialchars($admin['nom'])],
                $message_body
            );

            // Bati template HTML konplè a
            $full_html_email = buildEmailTemplate($subject, $personalized_content, $admin);

            // ITILIZE PHPMAILER OLYE DE mail()
            if (voyeImel($admin['email'], $subject, $full_html_email)) {
                $success_count++;
                error_log("[NOTIFICATION] Email voye bay " . $admin['email'] . " - Type: " . $type);
            } else {
                error_log("[NOTIFICATION] Echèk voye email bay " . $admin['email']);
            }

            // Ti pause pou evite bloke kont Gmail la
            usleep(100000);
        }

        return $success_count > 0;
    } catch (PDOException $e) {
        error_log("[NOTIFICATION] Erè DB: " . $e->getMessage());
        return false;
    }
}

/**
 * Template pou nouvo enskripsyon
 */
function getNewRegistrationTemplate($data)
{
    $prenom = $data['prenom'] ?? 'N/A';
    $nom = $data['nom'] ?? 'N/A';
    return "
    <div style='background: #f0fdf4; border-left: 4px solid #22c55e; padding: 20px; margin: 20px 0; border-radius: 8px;'>
        <h3 style='color: #15803d; margin-top: 0;'>🎉 Yon nouvo moun enskri!</h3>
        <table style='width: 100%; border-collapse: collapse;'>
            <tr>
                <td style='padding: 8px; border-bottom: 1px solid #e5e7eb; font-weight: bold; color: #6b7280;'>Non:</td>
                <td style='padding: 8px; border-bottom: 1px solid #e5e7eb;'>" . htmlspecialchars($prenom . ' ' . $nom) . "</td>
            </tr>
            <tr>
                <td style='padding: 8px; border-bottom: 1px solid #e5e7eb; font-weight: bold; color: #6b7280;'>Email:</td>
                <td style='padding: 8px; border-bottom: 1px solid #e5e7eb;'>" . htmlspecialchars($data['email'] ?? 'N/A') . "</td>
            </tr>
            <tr>
                <td style='padding: 8px; border-bottom: 1px solid #e5e7eb; font-weight: bold; color: #6b7280;'>Telefòn:</td>
                <td style='padding: 8px; border-bottom: 1px solid #e5e7eb;'>" . htmlspecialchars($data['telephone'] ?? 'N/A') . "</td>
            </tr>
            <tr>
                <td style='padding: 8px; font-weight: bold; color: #6b7280;'>Ròl:</td>
                <td style='padding: 8px;'>
                    <span style='background: #dbeafe; color: #1e40af; padding: 4px 12px; border-radius: 12px; font-size: 12px; text-transform: uppercase;'>
                        " . htmlspecialchars($data['role'] ?? 'customer') . "
                    </span>
                </td>
            </tr>
        </table>
    </div>";
}

/**
 * Template pou nouvo kòmand
 */
function getNewOrderTemplate($data)
{
    $items_html = '';
    if (isset($data['items']) && is_array($data['items'])) {
        foreach ($data['items'] as $item) {
            $items_html .= "
            <tr>
                <td style='padding: 8px; border-bottom: 1px solid #e5e7eb;'>" . htmlspecialchars($item['product_name'] ?? 'Pwodwi') . "</td>
                <td style='padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: center;'>" . ($item['quantity'] ?? 1) . "</td>
                <td style='padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right;'>" . number_format($item['price'] ?? 0, 2) . " HTG</td>
            </tr>";
        }
    }

    return "
    <div style='background: #fffbeb; border-left: 4px solid #f59e0b; padding: 20px; margin: 20px 0; border-radius: 8px;'>
        <h3 style='color: #b45309; margin-top: 0;'>📦 Nouvo Kòmand resevwa!</h3>
        <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
            <tr>
                <td style='padding: 8px; border-bottom: 1px solid #e5e7eb; font-weight: bold; color: #6b7280;'>ID Kòmand:</td>
                <td style='padding: 8px; border-bottom: 1px solid #e5e7eb;'>#" . htmlspecialchars($data['order_id'] ?? 'N/A') . "</td>
            </tr>
            <tr>
                <td style='padding: 8px; border-bottom: 1px solid #e5e7eb; font-weight: bold; color: #6b7280;'>Kliyan:</td>
                <td style='padding: 8px; border-bottom: 1px solid #e5e7eb;'>" . htmlspecialchars($data['customer_name'] ?? 'N/A') . "</td>
            </tr>
            <tr>
                <td style='padding: 8px; border-bottom: 1px solid #e5e7eb; font-weight: bold; color: #6b7280;'>Total:</td>
                <td style='padding: 8px; border-bottom: 1px solid #e5e7eb; font-size: 18px; font-weight: bold; color: #059669;'>
                    " . number_format($data['total_amount'] ?? 0, 2) . " HTG
                </td>
            </tr>
        </table>
        
        <h4 style='color: #374151; margin-bottom: 10px;'>📋 Detay Pwodwi yo:</h4>
        <table style='width: 100%; border-collapse: collapse; background: white; border-radius: 8px;'>
            <thead style='background: #f3f4f6;'>
                <tr>
                    <th style='padding: 10px; text-align: left;'>Pwodwi</th>
                    <th style='padding: 10px; text-align: center;'>Kantite</th>
                    <th style='padding: 10px; text-align: right;'>Pri</th>
                </tr>
            </thead>
            <tbody>" . ($items_html ?: "<tr><td colspan='3' style='text-align:center; padding:10px;'>Pa gen detay detay disponib</td></tr>") . "</tbody>
        </table>
    </div>";
}

/**
 * Template pou demann machann
 */
function getMerchantRequestTemplate($data)
{
    return "
    <div style='background: #eff6ff; border-left: 4px solid #3b82f6; padding: 20px; margin: 20px 0; border-radius: 8px;'>
        <h3 style='color: #1e40af; margin-top: 0;'>🏪 Nouvo Demann Machann!</h3>
        <table style='width: 100%; border-collapse: collapse;'>
            <tr>
                <td style='padding: 8px; border-bottom: 1px solid #e5e7eb; font-weight: bold; color: #6b7280;'>Non:</td>
                <td style='padding: 8px; border-bottom: 1px solid #e5e7eb;'>" . htmlspecialchars(($data['prenom'] ?? '') . ' ' . ($data['nom'] ?? '')) . "</td>
            </tr>
            <tr>
                <td style='padding: 8px; border-bottom: 1px solid #e5e7eb; font-weight: bold; color: #6b7280;'>Email:</td>
                <td style='padding: 8px; border-bottom: 1px solid #e5e7eb;'>" . htmlspecialchars($data['email'] ?? 'N/A') . "</td>
            </tr>
        </table>
    </div>";
}

/**
 * Template default
 */
function getDefaultTemplate($data)
{
    return "
    <div style='background: #f3f4f6; padding: 20px; margin: 20px 0; border-radius: 8px;'>
        <pre style='background: white; padding: 15px; border-radius: 6px; overflow-x: auto; font-size: 12px;'>" .
        htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) .
        "</pre>
    </div>";
}

/**
 * Bati template email konplè a
 */
function buildEmailTemplate($subject, $content, $admin)
{
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #374151; background-color: #f3f4f6; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
            .header { background: #1e293b; padding: 30px; text-align: center; color: white; }
            .content { padding: 30px; }
            .footer { background: #1f2937; color: #9ca3af; padding: 20px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin: 0; font-size: 24px;'>LE STOCK</h1>
                <p style='margin: 10px 0 0 0; color: #94a3b8;'>Notifikasyon Administratif</p>
            </div>
            <div class='content'>
                <p>Bonjou <strong>" . htmlspecialchars($admin['prenom']) . "</strong>,</p>
                " . $content . "
                <p style='font-size: 12px; color: #9ca3af; margin-top: 20px; border-top: 1px solid #eee; pt: 10px;'>
                    Sistèm otomatik Le Stock. Tanpri pa reponn email sa.
                </p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " LE STOCK ENTREPRISE | Cap-Haïtien, Haiti</p>
            </div>
        </div>
    </body>
    </html>";
}

// Fonksyon èd yo
function notifyNewUserRegistration($user_data)
{
    return notifyAdmins('new_registration', $user_data);
}
function notifyNewOrder($order_data)
{
    return notifyAdmins('new_order', $order_data);
}
function notifyMerchantRequest($merchant_data)
{
    return notifyAdmins('merchant_request', $merchant_data);
}
