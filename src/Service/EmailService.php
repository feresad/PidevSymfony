<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendMeetingLink(
        string $to,
        string $clientName,
        string $game,
        \DateTimeInterface $date,
        string $duration
    ): void {
        $email = (new Email())
            ->from('votre.email@exemple.com') // Replace with your sender email
            ->to($to)
            ->subject('Votre lien Google Meet pour la session de coaching')
            ->html($this->generateMeetingLinkEmail($clientName, $game, $date, $duration));

        $this->mailer->send($email);
    }

    public function sendCancellationEmail(
        string $to,
        string $clientName,
        string $game,
        string $date,
        float $price
    ): void {
        $email = (new Email())
            ->from('votre.email@exemple.com') // Replace with your sender email
            ->to($to)
            ->subject('Annulation de réservation - Demande de remboursement')
            ->html($this->generateCancellationEmail($clientName, $game, $date, $price));

        $this->mailer->send($email);
    }

    public function sendRefusalEmail(
        string $to,
        string $clientName,
        string $game,
        \DateTimeInterface $date
    ): void {
        $email = (new Email())
            ->from('votre.email@exemple.com') // Replace with your sender email
            ->to($to)
            ->subject('Refus de votre session de coaching')
            ->html($this->generateRefusalEmail($clientName, $game, $date));

        $this->mailer->send($email);
    }

    public function sendAcceptanceEmail(
        string $to,
        string $clientName,
        string $game,
        \DateTimeInterface $date,
        float $price
    ): void {
        $email = (new Email())
            ->from('votre.email@exemple.com') // Replace with your sender email
            ->to($to)
            ->subject('Acceptation de votre demande de réservation')
            ->html($this->generateAcceptanceEmail($clientName, $game, $date, $price));

        $this->mailer->send($email);
    }

    private function generateMeetingLinkEmail(
        string $clientName,
        string $game,
        \DateTimeInterface $date,
        string $duration
    ): string {
        // Customize this template as needed
        return <<<HTML
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #dd163b;">Votre session de coaching</h2>
            <p>Cher(e) {$clientName},</p>
            <p>Votre session de coaching pour <strong>{$game}</strong> est confirmée :</p>
            <ul>
                <li><strong>Date :</strong> {$date->format('d/m/Y H:i')}</li>
                <li><strong>Durée :</strong> {$duration}</li>
                <li><strong>Lien Google Meet :</strong> <a href="https://meet.google.com/your-link">Rejoindre</a></li>
            </ul>
            <p>La session de coaching se déroule au 20h:00min.</p>
            <p>Nous vous remercions pour votre réservation.</p>
            <p>Cordialement,<br>L'équipe de support</p>
        </div>
        HTML;
    }

    private function generateCancellationEmail(
        string $clientName,
        string $game,
        string $date,
        float $price
    ): string {
        return <<<HTML
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #dd163b;">Annulation de réservation</h2>
            <p>Bonjour,</p>
            <p>Une réservation a été annulée. Voici les détails :</p>
            <ul>
                <li><strong>Client :</strong> {$clientName}</li>
                <li><strong>Jeu :</strong> {$game}</li>
                <li><strong>Date prévue :</strong> {$date}</li>
                <li><strong>Montant à rembourser :</strong> {$price} DT</li>
            </ul>
            <p>Le client demande un remboursement du montant payé. Merci de traiter cette demande dans les plus brefs délais.</p>
            <p>Nous vous prions de nous excuser pour tout désagrément causé.</p>
            <p>Cordialement,<br>L'équipe de support</p>
        </div>
        HTML;
    }

    private function generateRefusalEmail(
        string $clientName,
        string $game,
        \DateTimeInterface $date
    ): string {
        return <<<HTML
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #dd163b;">Refus de votre session de coaching</h2>
            <p>Cher(e) {$clientName},</p>
            <p>Nous sommes désolés de vous informer que votre session de coaching pour <strong>{$game}</strong> a été refusée par le coach.</p>
            <ul>
                <li><strong>Date prévue :</strong> {$date->format('d/m/Y H:i')}</li>
            </ul>
            <p>Nous vous prions de nous excuser pour ce désagrément. Vous pouvez réserver une autre session ou contacter notre support pour plus d'informations.</p>
            <p>Cordialement,<br>L'équipe de support</p>
        </div>
        HTML;
    }

    private function generateAcceptanceEmail(
        string $clientName,
        string $game,
        \DateTimeInterface $date,
        float $price
    ): string {
        return <<<HTML
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #dd163b;">Confirmation de votre réservation</h2>
            <p>Cher(e) {$clientName},</p>
            <p>Nous avons le plaisir de vous informer que votre demande de session de coaching pour <strong>{$game}</strong> a été acceptée.</p>
            <ul>
                  <li><strong>Prix :</strong> {$price} DT</li>
                  <li><strong>Lien Google Meet :</strong> <a href="https://meet.google.com/your-link">Rejoindre</a></li>
            </ul>
            <p>tu trouve votre reservation a la page "mes Réservation" .  Ne concentrer pas a la date établie juste fait le paiment .</p>
            <p>Je choisis une date convenable pour nous et je te l'envoi </p>
            <p>Nous vous remercions pour votre confiance et restons à votre disposition pour toute question.</p>
            <p>Cordialement,<br>L'équipe de support</p>
        </div>
        HTML;
    }
}