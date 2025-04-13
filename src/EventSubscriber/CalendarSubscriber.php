<?php

namespace App\EventSubscriber;

use App\Repository\EvenementRepository;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\CalendarEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CalendarSubscriber implements EventSubscriberInterface
{
    private $evenementRepository;

    public function __construct(EvenementRepository $evenementRepository)
    {
        $this->evenementRepository = $evenementRepository;
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

        // Récupérer les événements dans l'intervalle de dates
        $evenements = $this->evenementRepository->findByDateRange($start, $end);

        foreach ($evenements as $evenement) {
            $event = new Event(
                $evenement->getNomEvent(),
                $evenement->getDateEvent(),
                null // Pas de date de fin définie dans votre entité, donc événement d'une journée
            );

            // Ajouter des options supplémentaires (facultatif)
            $event->setOptions([
                'backgroundColor' => '#3788d8', // Couleur de fond
                'borderColor' => '#3788d8',     // Couleur de bordure
                'textColor' => '#ffffff',       // Couleur du texte
                'url' => '/evenement/show/' . $evenement->getId(), // Lien vers les détails
            ]);

            $calendar->addEvent($event);
        }
    }
}