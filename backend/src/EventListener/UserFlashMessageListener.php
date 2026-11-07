<?php

namespace App\EventListener;

use App\Entity\User;
use App\Entity\UserFlashMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsEventListener(event: KernelEvents::REQUEST)]
class UserFlashMessageListener
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TokenStorageInterface $tokenStorage,
    ) {}

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $user = $this->tokenStorage->getToken()?->getUser();
        if (!$user instanceof User) {
            return;
        }

        $messages = $this->em->getRepository(UserFlashMessage::class)->findBy(['user' => $user], ['id' => 'ASC']);
        if ($messages === []) {
            return;
        }

        $flashBag = $request->getSession()->getFlashBag();
        foreach ($messages as $message) {
            $flashBag->add('warning', $message->getMessage());
            $this->em->remove($message);
        }

        $this->em->flush();
    }
}
