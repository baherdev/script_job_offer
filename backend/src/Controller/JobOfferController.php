<?php

namespace App\Controller;

use App\Dto\JobOfferInput;
use App\Entity\Entreprise;
use App\Entity\JobOffer;
use App\Service\EntrepriseService;
use App\Service\JobOfferService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/job-offers')]
class JobOfferController extends AbstractController
{
    public function __construct(
        private readonly JobOfferService $jobOffers,
        private readonly EntrepriseService $entreprises,
    ) {
    }

    #[Route('', name: 'job_offer_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json(array_map(
            $this->present(...),
            $this->jobOffers->findAll(),
        ));
    }

    #[Route('/{id}', name: 'job_offer_get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(int $id): JsonResponse
    {
        try {
            $jobOffer = $this->jobOffers->get($id);
        } catch (\RuntimeException) {
            return $this->notFound($id);
        }

        return $this->json($this->present($jobOffer));
    }

    #[Route('', name: 'job_offer_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] JobOfferInput $input): JsonResponse
    {
        $entreprise = $this->findEntreprise($input->entrepriseId);
        if ($entreprise instanceof JsonResponse) {
            return $entreprise;
        }

        $jobOffer = $this->jobOffers->create(
            $entreprise,
            $input->sourceId,
            $input->originalLink,
            $input->publicationDate,
            $input->minimumSalary,
            $input->maximumSalary,
            $input->location,
            $input->presenceMode,
            $input->positionTitle,
            $input->description,
            $input->applicationStatus,
            $input->applicationDate,
            $input->rejectionDate,
            $input->interviewDate1,
            $input->interviewDate2,
            $input->interviewDate3,
            $input->applicationValidationDate,
            $input->cancellationDate,
            $input->coverLetter,
            $input->desiredSalary,
            $input->availabilityDate,
        );

        return $this->json($this->present($jobOffer), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'job_offer_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, #[MapRequestPayload] JobOfferInput $input): JsonResponse
    {
        try {
            $jobOffer = $this->jobOffers->get($id);
        } catch (\RuntimeException) {
            return $this->notFound($id);
        }

        $entreprise = $this->findEntreprise($input->entrepriseId);
        if ($entreprise instanceof JsonResponse) {
            return $entreprise;
        }

        $jobOffer = $this->jobOffers->update(
            $jobOffer,
            $entreprise,
            $input->sourceId,
            $input->originalLink,
            $input->publicationDate,
            $input->minimumSalary,
            $input->maximumSalary,
            $input->location,
            $input->presenceMode,
            $input->positionTitle,
            $input->description,
            $input->applicationStatus,
            $input->applicationDate,
            $input->rejectionDate,
            $input->interviewDate1,
            $input->interviewDate2,
            $input->interviewDate3,
            $input->applicationValidationDate,
            $input->cancellationDate,
            $input->coverLetter,
            $input->desiredSalary,
            $input->availabilityDate,
        );

        return $this->json($this->present($jobOffer));
    }

    #[Route('/{id}', name: 'job_offer_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $jobOffer = $this->jobOffers->get($id);
        } catch (\RuntimeException) {
            return $this->notFound($id);
        }

        $this->jobOffers->delete($jobOffer);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    private function findEntreprise(?int $id): Entreprise|JsonResponse
    {
        if (null === $id) {
            return $this->json(['error' => 'Entreprise id is required.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            return $this->entreprises->get($id);
        } catch (\RuntimeException) {
            return $this->json(
                ['error' => sprintf('Entreprise %d was not found.', $id)],
                Response::HTTP_NOT_FOUND,
            );
        }
    }

    private function notFound(int $id): JsonResponse
    {
        return $this->json(
            ['error' => sprintf('Job offer %d was not found.', $id)],
            Response::HTTP_NOT_FOUND,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function present(JobOffer $jobOffer): array
    {
        return [
            'id' => $jobOffer->getId(),
            'entrepriseId' => $jobOffer->getEntreprise()?->getId(),
            'sourceId' => $jobOffer->getSourceId(),
            'originalLink' => $jobOffer->getOriginalLink(),
            'publicationDate' => $this->date($jobOffer->getPublicationDate()),
            'minimumSalary' => $jobOffer->getMinimumSalary(),
            'maximumSalary' => $jobOffer->getMaximumSalary(),
            'location' => $jobOffer->getLocation(),
            'presenceMode' => $jobOffer->getPresenceMode(),
            'positionTitle' => $jobOffer->getPositionTitle(),
            'description' => $jobOffer->getDescription(),
            'applicationStatus' => $jobOffer->getApplicationStatus(),
            'applicationDate' => $this->date($jobOffer->getApplicationDate()),
            'rejectionDate' => $this->date($jobOffer->getRejectionDate()),
            'interviewDate1' => $this->date($jobOffer->getInterviewDate1()),
            'interviewDate2' => $this->date($jobOffer->getInterviewDate2()),
            'interviewDate3' => $this->date($jobOffer->getInterviewDate3()),
            'applicationValidationDate' => $this->date($jobOffer->getApplicationValidationDate()),
            'cancellationDate' => $this->date($jobOffer->getCancellationDate()),
            'coverLetter' => $jobOffer->getCoverLetter(),
            'desiredSalary' => $jobOffer->getDesiredSalary(),
            'availabilityDate' => $this->date($jobOffer->getAvailabilityDate()),
        ];
    }

    private function date(?\DateTimeImmutable $date): ?string
    {
        return $date?->format('Y-m-d');
    }
}
