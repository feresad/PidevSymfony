<?php

namespace App\EventSubscriber;

use App\Repository\EvenementRepository;
use App\Repository\ClientEvenementRepository;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\CalendarEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Security;

class CalendarSubscriber implements EventSubscriberInterface
{
    private $evenementRepository;
    private $clientEvenementRepository;
    private $security;

    public function __construct(
        EvenementRepository $evenementRepository,
        ClientEvenementRepository $clientEvenementRepository,
        Security $security
    ) {
        $this->evenementRepository = $evenementRepository;
        $this->clientEvenementRepository = $clientEvenementRepository;
        $this->security = $security;
    }

    public static function getSubscribedEvents()
    {
        return [
            'calendar.set_data' => 'onCalendarSetData',
        ];
    }

    public function onCalendarSetData(CalendarEvent $calendar)
    {
        $start = $calendar->getStart();
        $end = $calendar->getEnd();
        $filters = $calendar->getFilters();

        $evenements = $this->evenementRepository->findByDateRange($start, $end);

        $user = $this->security->getUser();

        $userReservations = [];
        if ($user) {
            $reservations = $this->clientEvenementRepository->findBy(['client' => $user]);
            foreach ($reservations as $reservation) {
                $userReservations[$reservation->getEvenement()->getId()] = true;
            }
        }

        foreach ($evenements as $evenement) {
            $dateDebut = $evenement->getDateEvent();
            $dateFin = clone $dateDebut;
            $dateFin->modify('+2 hours');

            $event = new Event(
                $evenement->getNomEvent(),
                $dateDebut,
                $dateFin
            );

            $isReserved = $user && isset($userReservations[$evenement->getId()]);
            $event->setOptions([
                'backgroundColor' => $isReserved ? '#3788d8' : '#ff0000',
                'borderColor' => $isReserved ? '#3788d8' : '#ff0000',
                'textColor' => '#ffffff',
                'url' => '/evenement/show/' . $evenement->getId(),
            ]);

            $calendar->addEvent($event);
        }
    }
}