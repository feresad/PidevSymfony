<?php

namespace App\Controller;
use App\Entity\Notification;
use App\Entity\Questions;
use App\Entity\Utilisateur;
use App\Service\TopicSubscriptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserSubscriptionController extends AbstractController
{
    #[Route('/ajax/subscribe/topic/{id}', name: 'ajax_subscribe_topic', methods: ['POST'])]
    public function subscribe(int $id, TopicSubscriptionService $subscriptionService, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Vous devez être connecté pour suivre un sujet.'], 401);
        }

        $question = $entityManager->getRepository(Questions::class)->find($id);
        if (!$question) {
            return new JsonResponse(['success' => false, 'message' => 'Sujet non trouvé.'], 404);
        }

        try {
            $subscriptionService->subscribe($user, $question);
            return new JsonResponse([
                'success' => true,
                'message' => 'Vous suivez maintenant ce sujet.',
                'isSubscribed' => true
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de l\'abonnement.'], 500);
        }
    }

    #[Route('/ajax/unsubscribe/topic/{id}', name: 'ajax_unsubscribe_topic', methods: ['POST'])]
    public function unsubscribe(int $id, TopicSubscriptionService $subscriptionService, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Vous devez être connecté pour vous désabonner.'], 401);
        }

        $question = $entityManager->getRepository(Questions::class)->find($id);
        if (!$question) {
            return new JsonResponse(['success' => false, 'message' => 'Sujet non trouvé.'], 404);
        }

        try {
            $subscriptionService->unsubscribe($user, $question);
            return new JsonResponse([
                'success' => true,
                'message' => 'Vous ne suivez plus ce sujet.',
                'isSubscribed' => false
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors du désabonnement.'], 500);
        }
    }

    #[Route('/profile/subscriptions', name: 'user_subscriptions', methods: ['GET'])]
    public function subscriptions(TopicSubscriptionService $subscriptionService): Response
    {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour voir vos abonnements.');
            return $this->redirectToRoute('app_login_page');
        }

        $subscriptions = $subscriptionService->getSubscriptions($user);
        $subscriptionData = array_map(function (Notification $subscription) {
            preg_match('/Vous suivez le sujet : (.+)/', $subscription->getMessage(), $matches);
            $title = $matches[1] ?? 'Titre inconnu';
            return [
                'id' => $subscription->getId(),
                'title' => $title,
                'link' => $subscription->getLink()
            ];
        }, $subscriptions);

        return $this->render('profile/subscriptions.html.twig', [
            'subscriptions' => $subscriptionData
        ]);
    }
}