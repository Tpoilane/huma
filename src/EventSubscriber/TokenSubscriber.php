<?php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Doctrine\ORM\EntityManagerInterface;

class TokenSubscriber implements EventSubscriberInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function onKernelController(ControllerEvent $event)
    {
        $controller = $event->getController();

        if (is_array($controller)) {
            $controller = $controller[0];
        }
        // Do not validate the token for the login route
        if ($controller instanceof \App\Controller\AuthController && $event->getRequest()->attributes->get('_route') === 'login') {
            return;
        }

        $token = $this->getTokenFromRequest($event->getRequest());

        if (!$token) {
            throw new AccessDeniedHttpException('No token provided');
        }

        $user = $this->entityManager->getRepository(\App\Entity\User::class)->findOneBy(['token' => $token]);

        if (!$user) {
            throw new AccessDeniedHttpException('Invalid token');
        }
    }

    private function getTokenFromRequest($request): ?string
    {
		$token = $request->get('token'); 
		if($token) return $token;
        return null;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}
