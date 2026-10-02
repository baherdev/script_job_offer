<?php

namespace App\MessageHandler;

use App\Entity\JobOffer;
use App\Message\JobOfferMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class JobOfferMessageHandler
{
    public function __construct(private EntityManagerInterface $em) {}

    public function __invoke(JobOfferMessage $message): void
    {
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