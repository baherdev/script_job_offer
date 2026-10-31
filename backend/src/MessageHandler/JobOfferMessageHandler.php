<?php

namespace App\MessageHandler;

use App\Entity\Enterprise;
use App\Entity\JobOffer;
use App\Entity\SearchQuery;
use App\Message\JobApplicationMessage;
use App\Message\JobOfferMessage;
use App\Message\JobOfferUpdateMessage;
use App\Repository\EnterpriseRepository;
use App\Repository\JobOfferRepository;
use App\Service\EnterpriseLogoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class JobOfferMessageHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private JobOfferRepository $jobOfferRepository,
        private EnterpriseRepository $enterpriseRepository,
        private MessageBusInterface $bus,
        private EnterpriseLogoService $enterpriseLogoService,
    ) {}

    public function __invoke(JobOfferMessage $message): void
    {
        $url = $message->getUrl();
        $offer = is_string($url) && $url !== ''
            ? $this->jobOfferRepository->findOneBy(['url' => $url])
            : null;

        if ($offer instanceof JobOffer) {
            $offer->setProcessedAt(new \DateTimeImmutable());
            $this->em->flush();

            return;
        }

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

        $searchQuery = $this->attachSearchQuery($offer, $message);
        $this->em->flush();
        $this->queueJobApplication($offer, $searchQuery);
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
