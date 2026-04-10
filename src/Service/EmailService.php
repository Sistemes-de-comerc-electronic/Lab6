<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
    ) {}

    public function sendPurchaseThankYou(string $to): void
    {
        $email = (new Email())
            ->from('noreply@botiga.cat')
            ->to($to)
            ->subject('Gràcies per la teva compra!')
            ->html(
                '<h1>Gràcies per la teva compra!</h1>'
                . '<p>Hem rebut el teu pagament de <strong>20€</strong> correctament.</p>'
                . '<p>Ens posarem en contacte amb tu aviat.</p>'
                . '<p>Salutacions,<br>L\'equip de la botiga</p>'
            );

        $this->mailer->send($email);
    }
}
