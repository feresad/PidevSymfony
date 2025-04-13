<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Stripe\Stripe;
use Psr\Log\LoggerInterface;

class PaymentController extends AbstractController
{
    private $stripeSecretKey;
    private $stripePublicKey;
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->stripeSecretKey = $_ENV['STRIPE_SECRET_KEY'] ?? throw new \RuntimeException('STRIPE_SECRET_KEY not defined in environment variables.');
$this->stripePublicKey = $_ENV['STRIPE_PUBLIC_KEY'] ?? throw new \RuntimeException('STRIPE_PUBLIC_KEY not defined in environment variables.');
       Stripe::setApiKey($this->stripeSecretKey);
    }

    #[Route('/payment/process', name: 'payment_process', methods: ['POST'])]
    public function processPayment(Request $request): JsonResponse
    {
        $this->logger->info('Payment processing started.');

        try {
            // Log the raw request content for debugging
            $rawContent = $request->getContent();
            $this->logger->debug('Raw request content: ' . $rawContent);

            // Parse JSON data from the request
            $data = json_decode($rawContent, true);
            if ($data === null) {
                $this->logger->error('Failed to decode JSON request body.');
                throw new \Exception('Invalid request data: JSON decoding failed.');
            }

            // Log the decoded data
            $this->logger->debug('Decoded request data: ', $data);

            // Validate required fields
            if (!isset($data['token']) || !isset($data['amount']) || !isset($data['reservation_id'])) {
                $this->logger->error('Missing required fields in request data.', [
                    'data' => $data,
                ]);
                throw new \Exception('Missing required fields: token, amount, or reservation_id.');
            }

            $token = $data['token'];
            $amount = (float) $data['amount'];
            $reservationId = (int) $data['reservation_id'];

            // Create a charge using Stripe
            $charge = \Stripe\Charge::create([
                'amount' => (int) ($amount * 100), // Amount in cents
                'currency' => 'usd', // TND is not supported by Stripe; use a supported currency
                'source' => $token,
                'description' => 'Reservation payment #' . $reservationId,
            ]);

            $this->logger->info('Payment successful for reservation ID: ' . $reservationId, [
                'charge_id' => $charge->id,
                'amount' => $amount,
            ]);

            return new JsonResponse(['success' => true, 'charge_id' => $charge->id]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $this->logger->error('Stripe API error: ' . $e->getMessage(), ['exception' => $e]);
            return new JsonResponse(['success' => false, 'error' => 'Stripe error: ' . $e->getMessage()]);
        } catch (\Exception $e) {
            $this->logger->error('General error during payment processing: ' . $e->getMessage(), ['exception' => $e]);
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function getPublicKey(): string
    {
        return $this->stripePublicKey;
    }
}