<?php

namespace App\Service;

use App\Entity\Utilisateur;
use App\Entity\Questions;
use App\Entity\Commentaire;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;

class TopicSubscriptionService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function subscribe(Utilisateur $user, Questions $question): void
    {
        if ($this->isSubscribed($user, $question)) {
            return;
        }

        $notification = new Notification();
        $notification->setUser($user)
                     ->setMessage("Vous suivez le sujet : {$question->getTitle()}")
                     ->setLink("/forum/topic/{$question->getQuestionId()}")
                     ->setIsRead(true); // Marqué comme lu pour ne pas apparaître dans les notifications
        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }

    public function unsubscribe(Utilisateur $user, Questions $question): void
    {
        $subscription = $this->entityManager->getRepository(Notification::class)
            ->findOneBy([
                'user' => $user,
                'message' => "Vous suivez le sujet : {$question->getTitle()}",
                'link' => "/forum/topic/{$question->getQuestionId()}"
            ]);

        if ($subscription) {
            $this->entityManager->remove($subscription);
            $this->entityManager->flush();
        }
    }

    public function isSubscribed(Utilisateur $user, Questions $question): bool
    {
        return null !== $this->entityManager->getRepository(Notification::class)
            ->findOneBy([
                'user' => $user,
                'message' => "Vous suivez le sujet : {$question->getTitle()}",
                'link' => "/forum/topic/{$question->getQuestionId()}"
            ]);
    }

    public function getSubscriptions(Utilisateur $user): array
    {
        return $this->entityManager->getRepository(Notification::class)
            ->createQueryBuilder('n')
            ->where('n.user = :user')
            ->andWhere('n.message LIKE :message')
            ->setParameter('user', $user)
            ->setParameter('message', 'Vous suivez le sujet : %')
            ->getQuery()
            ->getResult();
    }

    public function notifySubscribers(Questions $question, Commentaire $newComment, Utilisateur $commentAuthor): void
    {
        $subscriptions = $this->entityManager->getRepository(Notification::class)
            ->createQueryBuilder('n')
            ->where('n.message = :message')
            ->andWhere('n.link = :link')
            ->setParameter('message', "Vous suivez le sujet : {$question->getTitle()}")
            ->setParameter('link', "/forum/topic/{$question->getQuestionId()}")
            ->getQuery()
            ->getResult();

        foreach ($subscriptions as $subscription) {
            $subscriber = $subscription->getUser();
            if ($subscriber->getId() !== $commentAuthor->getId()) {
                $notification = new Notification();
                $notification->setUser($subscriber)
                             ->setMessage("Nouveau commentaire sur le sujet : {$question->getTitle()}")
                             ->setLink("/forum/topic/{$question->getQuestionId()}#comment-{$newComment->getCommentaireId()}");
                $this->entityManager->persist($notification);
            }
        }

        $this->entityManager->flush();
    }
}