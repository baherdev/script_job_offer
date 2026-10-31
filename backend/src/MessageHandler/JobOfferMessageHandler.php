<?php

namespace App\MessageHandler;

use App\Entity\Enterprise;
use App\Entity\JobOffer;
use App\Message\JobOfferMessage;
use App\Message\JobOfferUpdateMessage;
use App\Repository\EnterpriseRepository;
use App\Repository\JobOfferRepository;
use App\Service\EnterpriseLogoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
class JobOfferMessageHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private EnterpriseRepository $enterpriseRepository,
        private JobOfferRepository $jobOfferRepository,
        private EnterpriseLogoService $enterpriseLogoService,
    ) {}

    public function __invoke(JobOfferMessage $message): void
    {
        $offer = new JobOffer();
        $offer->setTitle($message->getTitle());
        $offer->setLocation($message->getLocation());
        $offer->setDescription($message->getDescription());
        $offer->setUrl($message->getUrl());
        $offer->setStatus('processed');
        $offer->setCreatedAt(new \DateTimeImmutable());
        $offer->setProcessedAt(new \DateTimeImmutable());
        $offer->setEnterprise($this->findOrCreateEnterprise($message->getCompany(), $message->getLocation()));

        $this->em->persist($offer);
        $this->em->flush();
    }

    #[AsMessageHandler]
    public function updateDescription(JobOfferUpdateMessage $message): void
    {
        $offer = $this->jobOfferRepository->find($message->getId());
        if (!$offer instanceof JobOffer) {
            throw new UnrecoverableMessageHandlingException(sprintf('Job offer %d was not found.', $message->getId()));
        }

        $logoUrl = $message->getLogoUrl();
        if (is_string($logoUrl) && $logoUrl !== '') {
            $enterprise = $offer->getEnterprise();
            if (!$enterprise instanceof Enterprise) {
                throw new UnrecoverableMessageHandlingException(sprintf('Job offer %d has no enterprise.', $message->getId()));
            }

            $this->enterpriseLogoService->assignFromUrl($enterprise, $logoUrl);
        }

        $offer->setDescription($message->getDescription());
        $offer->setProcessedAt(new \DateTimeImmutable());
        $this->em->flush();
    }

    private function findOrCreateEnterprise(string $company, ?string $location): Enterprise
    {
        $label = trim($company);
        $enterprise = $this->enterpriseRepository->findOneBy(['label' => $label]);
        if ($enterprise instanceof Enterprise) {
            return $enterprise;
        }

        $city = trim((string) $location);
        if (mb_strlen($city) > 255) {
            $city = mb_substr($city, 0, 255);
        }

        $enterprise = new Enterprise();
        $enterprise->setLabel($label);
        $enterprise->setWebsite('');
        $enterprise->setStreet('');
        $enterprise->setZipCode('');
        $enterprise->setCity($city);
        $enterprise->setCountry('');
        $this->em->persist($enterprise);

        return $enterprise;
    }
}