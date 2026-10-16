<?php

namespace App\MessageHandler;

use App\Entity\JobOffer;
use App\Message\JobOfferMessage;
use App\Repository\JobOfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class JobOfferMessageHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private JobOfferRepository $jobOfferRepository,
    ) {}

    public function __invoke(JobOfferMessage $message): void
    {
        $url = $message->getUrl();
        if (is_string($url) && $url !== '' && $this->jobOfferRepository->findOneBy(['url' => $url]) !== null) {
            return;
        }

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
        $this->em->flush();
    }
}