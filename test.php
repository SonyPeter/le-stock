<?php
require_once 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
require_once 'config/mail.php';

$test = voyeImel('mansonypierre2003@gmail.com', 'Tès Le Stock', 'Eske sa ap mache?');
echo ($test === true) ? "Siksè!" : "Erè: " . $test;
