<?php

namespace App\MessageHandler;

use App\Entity\MonitoredPage;
use App\Entity\User;
use App\Entity\UserFlashMessage;
use App\Message\MonitoredPageMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
class MonitoredPageMessageHandler
{
    public function __construct(private EntityManagerInterface $em) {}

    public function __invoke(MonitoredPageMessage $message): void
    {
        $user = $this->em->find(User::class, $message->getUserId());
        if (!$user instanceof User) {
            throw new UnrecoverableMessageHandlingException(sprintf('User %d was not found.', $message->getUserId()));
        }

        $page = $this->em->getRepository(MonitoredPage::class)->findOneBy([
            'url' => $message->getUrl(),
            'user' => $user,
        ]);
        if ($page instanceof MonitoredPage) {
            if ($page->getHash() === $message->getHash()) {
                return;
            }

            $page->setHash($message->getHash());
            $this->addFlashMessage($user, $page);
            $this->em->flush();

            return;
        }

        $page = new MonitoredPage();
        $page->setUrl($message->getUrl());
        $page->setHash($message->getHash());
        $page->setUser($user);

        $this->em->persist($page);
        $this->em->flush();
    }

    private function addFlashMessage(User $user, MonitoredPage $page): void
    {
        $flashMessage = new UserFlashMessage();
        $flashMessage->setUser($user);
        $flashMessage->setMessage(sprintf(
            'A monitored page has changed. Please check %s.',
            htmlspecialchars((string) $page->getUrl(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        ));

        $this->em->persist($flashMessage);
    }
}
