<?php
// page/panier/create-payment-intent.php

// On démarre la session pour vérifier l'utilisateur
session_start();

// Charger la config Stripe
require_once dirname(__DIR__, 2) . '/config/stripe.php';

// CORRECTION 1 : Le fichier s'appelle db.php et non database.php
require_once dirname(__DIR__, 2) . '/config/db.php';

header('Content-Type: application/json');

try {
    // Vérification de sécurité
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Utilisateur non connecté.');
    }

    // Récupérer les données envoyées par le JavaScript
    $input = json_decode(file_get_contents('php://input'), true);
    $amount = $input['amount'] ?? 0;

    if ($amount <= 0) {
        throw new Exception('Montant invalide.');
    }

    // CORRECTION 2 : Si tu n'as pas activé HTG dans ton compte Stripe, 
    // change 'htg' par 'usd' temporairement juste pour tester si ça marche.
    $currency = 'htg';

    // Créer le PaymentIntent sur Stripe
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => $amount, // Le montant en centimes (déjà envoyé par JS)
        'currency' => $currency,
        'automatic_payment_methods' => [
            'enabled' => true,
        ],
    ]);

    // Renvoyer le secret au client (JavaScript)
    echo json_encode([
        'clientSecret' => $paymentIntent->client_secret
    ]);
} catch (\Stripe\Exception\InvalidRequestException $e) {
    // Erè spécifique à Stripe (souvent la devise HTG qui n'est pas activée)
    http_response_code(400);
    echo json_encode(['error' => 'Erreur Stripe (Devise?): ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
