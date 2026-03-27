<?php
// create-payment-intent.php
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/stripe.php';

// Jwenn done JSON yo
$json = file_get_contents('php://input');
$data = json_decode($json);

$amount = $data->amount ?? 0;
$currency = $data->currency ?? 'htg';

try {
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

    // Kreye yon PaymentIntent
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => $amount,
        'currency' => $currency,
        'automatic_payment_methods' => [
            'enabled' => true,
        ],
    ]);

    echo json_encode([
        'clientSecret' => $paymentIntent->client_secret
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
