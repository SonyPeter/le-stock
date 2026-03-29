<?php
// config/stripe.php

require_once __DIR__ . '/../vendor/autoload.php';

// Si .env la nan /le-stock/ (rasin pwojè a)
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

define('STRIPE_SECRET_KEY', $_ENV['STRIPE_SECRET_KEY'] ?? '');
define('STRIPE_PUBLISHABLE_KEY', $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? '');

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
