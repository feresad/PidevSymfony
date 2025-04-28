<?php
namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 17)]
class LocaleListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $session = $request->getSession();
        $locale = $session->get('_locale', 'en');
        $request->setLocale($locale);
    }
}