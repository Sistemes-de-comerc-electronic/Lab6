<?php

declare(strict_types=1);

namespace App\Service;

use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeService
{
    public function __construct(
        private string $secretKey,
        private string $webhookSecret,
    ) {
        Stripe::setApiKey($this->secretKey);
    }

    public function createCheckoutSession(
        int    $amountCents,
        string $currency,
        string $successUrl,
        string $cancelUrl,
    ): Session {
        return Session::create([
            'payment_method_types' => ['card'],
            'line_items'           => [
                [
                    'price_data' => [
                        'currency'     => $currency,
                        'unit_amount'  => $amountCents,
                        'product_data' => [
                            'name' => 'Compra a la botiga',
                        ],
                    ],
                    'quantity' => 1,
                ],
            ],
            'mode'        => 'payment',
            'success_url' => $successUrl,
            'cancel_url'  => $cancelUrl,
        ]);
    }

    /**
     * @throws SignatureVerificationException
     */
    public function constructWebhookEvent(string $payload, string $sigHeader): Event
    {
        return Webhook::constructEvent($payload, $sigHeader, $this->webhookSecret);
    }
}
