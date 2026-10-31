<?php

namespace App\MessageHandler;

use App\Entity\Enterprise;
use App\Entity\JobOffer;
use App\Message\JobOfferMessage;
use App\Repository\EnterpriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class JobOfferMessageHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private EnterpriseRepository $enterpriseRepository,
    ) {}

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
        $offer->setEnterprise($this->findOrCreateEnterprise($message->getCompany()));

        $this->em->persist($offer);
        $this->em->flush();
    }

    private function findOrCreateEnterprise(string $company): Enterprise
    {
        $label = trim($company);
        $enterprise = $this->enterpriseRepository->findOneBy(['label' => $label]);
        if ($enterprise instanceof Enterprise) {
            return $enterprise;
        }

        $enterprise = new Enterprise();
        $enterprise->setLabel($label);
        $enterprise->setWebsite('');
        $enterprise->setStreet('');
        $enterprise->setZipCode('');
        $enterprise->setCity('');
        $enterprise->setCountry('');
        $this->em->persist($enterprise);

        return $enterprise;
    }
}