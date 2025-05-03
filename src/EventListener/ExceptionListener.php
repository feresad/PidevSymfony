<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

class ExceptionListener
{
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();
        if ($exception instanceof NotFoundHttpException) {
            var_dump('Listener triggered for 404'); // This will output to the debug toolbar or screen
            $response = new Response(
                $this->twig->render('bundles/TwigBundle/Exception/error404.html.twig'),
                Response::HTTP_NOT_FOUND
            );
            $event->setResponse($response);
        }
    }
}