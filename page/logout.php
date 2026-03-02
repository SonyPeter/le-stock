<?php
// 1. Kòmanse sesyon an pou nou ka gen aksè ak sa ki la deja
session_start();

// 2. Vide tout varyab sesyon yo
$_SESSION = array();

// 3. Si ou vle detwi kout pye (cookies) sesyon an tou (opsyonèl men rekòmande)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 4. Detwi sesyon an nèt nan sèvè a
session_destroy();

// 5. Redirije itilizatè a sou paj login nan
// Piske logout.php nan katab /page/, nou jis rele login.php ki nan menm kote a
header("Location: login.php");
exit();
