<?php

namespace App\MessageHandler;

use App\Entity\JobOffer;
use App\Entity\SearchQuery;
use App\Message\JobApplicationMessage;
use App\Message\JobOfferMessage;
use App\Repository\JobOfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class JobOfferMessageHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private JobOfferRepository $jobOfferRepository,
        private MessageBusInterface $bus,
    ) {}

    public function __invoke(JobOfferMessage $message): void
    {
        $url = $message->getUrl();
        $offer = is_string($url) && $url !== ''
            ? $this->jobOfferRepository->findOneBy(['url' => $url])
            : null;
        $created = !$offer instanceof JobOffer;

        if ($created) {
            $offer = new JobOffer();
            $offer->setTitle($message->getTitle());
            $offer->setCompany($message->getCompany());
            $offer->setLocation($message->getLocation());
            $offer->setDescription($message->getDescription());
            $offer->setUrl($message->getUrl());
            $offer->setStatus('processed');
            $offer->setCreatedAt(new \DateTimeImmutable());
            $offer->setProcessedAt(new \DateTimeImmutable());
            $this->em->persist($offer);
        }

        $searchQuery = $this->attachSearchQuery($offer, $message);
        $this->em->flush();

        if ($created) {
            $this->queueJobApplication($offer, $searchQuery);
        }
    }

    private function attachSearchQuery(JobOffer $offer, JobOfferMessage $message): ?SearchQuery
    {
        $searchQueryId = $message->getSearchQueryId();
        if ($searchQueryId === null) {
            return null;
        }

        $searchQuery = $this->em->find(SearchQuery::class, $searchQueryId);
        if (!$searchQuery instanceof SearchQuery) {
            return null;
        }

        $offer->addSearchQuery($searchQuery);

        return $searchQuery;
    }

    private function queueJobApplication(JobOffer $offer, ?SearchQuery $searchQuery): void
    {
        $user = $searchQuery?->getCreatedBy();
        if ($offer->getId() === null || $user?->getId() === null) {
            return;
        }

        $this->bus->dispatch(new JobApplicationMessage(
            jobOfferId: $offer->getId(),
            userId: $user->getId(),
        ));
    }
}