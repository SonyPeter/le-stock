<?php
// config/stripe.php

require_once __DIR__ . '/../vendor/autoload.php';

// Sa a chaje varyab ki nan .env yo nan $_ENV
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Kounye a nou defini constant yo depi nan .env
define('STRIPE_SECRET_KEY', $_ENV['STRIPE_SECRET_KEY']);
define('STRIPE_PUBLISHABLE_KEY', $_ENV['STRIPE_PUBLISHABLE_KEY']);

// Konfigire Stripe ak kle a
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
