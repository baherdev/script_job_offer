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
            $previousHash = $page->getHash();
            if ($previousHash === $message->getHash()) {
                return;
            }

            $page->setHash($message->getHash());
            if ($previousHash !== null && $previousHash !== '') {
                $this->addFlashMessage($user, $page);
            }
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
        $url = htmlspecialchars((string) $page->getUrl(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $flashMessage = new UserFlashMessage();
        $flashMessage->setUser($user);
        $flashMessage->setMessage(sprintf(
            'A monitored page has changed. Please check <a href="%s" target="_blank" rel="noopener noreferrer">%s</a>.',
            $url,
            $url,
        ));

        $this->em->persist($flashMessage);
    }
}
