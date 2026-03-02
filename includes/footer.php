<?php
// Verifye non paj la
$current_page = basename($_SERVER['PHP_SELF']);
$auth_pages = ['login.php', 'inscription.php', 'verifye.php'];

// Si nou PA sou yon paj auth, n ap afiche footer a
if (!in_array($current_page, $auth_pages)):
?>
    <link rel="stylesheet" href="/le-stock/css/style.css">

    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-info">
                <h2 class="logo">GWOLO<span>.</span></h2>
                <p>Pi bèl platfòm tiraj an liy an Ayiti. Sekirite ak transparans se priyorite nou.</p>
            </div>

            <div class="footer-links">
                <h4>Navigasyon</h4>
                <ul>
                    <li><a href="index.php">Akey</a></li>
                    <li><a href="dashboard.php">Tiraj an kou</a></li>
                    <li><a href="regleman.php">Regleman jwèt yo</a></li>
                </ul>
            </div>

            <div class="footer-contact">
                <h4>Kontak</h4>
                <p>📧 kontak@gwolo.ht</p>
                <p>📞 +509 3XXX-XXXX</p>
                <div class="social-icons">
                    <span>FB</span> <span>IG</span> <span>WA</span>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 Gwolo Platfòm. Tout dwa rezève.</p>
        </div>
    </footer>
<?php endif; ?>