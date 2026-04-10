<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\EmailService;
use App\Service\StripeService;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaymentController extends AbstractController
{
    private const AMOUNT_CENTS = 2000; // 20€ en cèntims
    private const CURRENCY     = 'eur';
    private const TEST_EMAIL   = 'david.domenech@urv.cat';

    public function __construct(
        private StripeService $stripeService,
        private EmailService  $emailService,
    ) {}

    #[Route('/payment', name: 'payment_checkout', methods: ['GET'])]
    public function checkout(): RedirectResponse
    {
        $successUrl = $this->generateUrl('payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $cancelUrl  = $this->generateUrl('payment_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $session = $this->stripeService->createCheckoutSession(
            amountCents: self::AMOUNT_CENTS,
            currency:    self::CURRENCY,
            successUrl:  $successUrl,
            cancelUrl:   $cancelUrl,
        );

        return new RedirectResponse($session->url, Response::HTTP_SEE_OTHER);
    }

    #[Route('/payment/success', name: 'payment_success', methods: ['GET'])]
    public function success(): Response
    {
        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="ca">
        <head>
            <meta charset="UTF-8">
            <title>Pagament completat</title>
            <style>
                body { font-family: sans-serif; text-align: center; padding: 60px; background: #f0fdf4; }
                h1   { color: #16a34a; }
                p    { color: #374151; font-size: 1.1rem; }
                a    { color: #2563eb; }
            </style>
        </head>
        <body>
            <h1>✅ Pagament completat!</h1>
            <p>Gràcies per la teva compra. Aviat rebràs un correu de confirmació.</p>
            <p><a href="/">Tornar a la botiga</a></p>
        </body>
        </html>
        HTML;

        return new Response($html, Response::HTTP_OK, ['Content-Type' => 'text/html']);
    }

    #[Route('/payment/cancel', name: 'payment_cancel', methods: ['GET'])]
    public function cancel(): Response
    {
        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="ca">
        <head>
            <meta charset="UTF-8">
            <title>Pagament cancel·lat</title>
            <style>
                body { font-family: sans-serif; text-align: center; padding: 60px; background: #fef2f2; }
                h1   { color: #dc2626; }
                p    { color: #374151; font-size: 1.1rem; }
                a    { color: #2563eb; }
            </style>
        </head>
        <body>
            <h1>❌ Pagament cancel·lat</h1>
            <p>Has cancel·lat el pagament. Pots tornar-ho a intentar quan vulguis.</p>
            <p><a href="/payment">Tornar al pagament</a></p>
        </body>
        </html>
        HTML;

        return new Response($html, Response::HTTP_OK, ['Content-Type' => 'text/html']);
    }

    #[Route('/payment/webhook', name: 'payment_webhook', methods: ['POST'])]
    public function webhook(Request $request): Response
    {
        $payload   = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');

        if (!$sigHeader) {
            return new Response('Missing Stripe-Signature header', Response::HTTP_BAD_REQUEST);
        }

        try {
            $event = $this->stripeService->constructWebhookEvent($payload, $sigHeader);
        } catch (SignatureVerificationException) {
            return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
        }

        if ($event->type === 'checkout.session.completed') {
            $this->emailService->sendPurchaseThankYou(self::TEST_EMAIL);
        }

        return new Response('', Response::HTTP_OK);
    }
}
