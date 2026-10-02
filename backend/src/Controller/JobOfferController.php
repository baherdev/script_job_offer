<?php

namespace App\Controller;

use App\Service\JobOfferService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
class JobOfferController extends AbstractController
{
    public function __construct(
        private readonly JobOfferService $jobOfferService,
    ) {}

    #[Route('/job-offers', name: 'job_offers_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['offers']) || !is_array($data['offers'])) {
            return $this->json(['error' => 'Field "offers" is required'], Response::HTTP_BAD_REQUEST);
        }

        $errors = [];
        foreach ($data['offers'] as $i => $offer) {
            if (empty($offer['title']))   $errors[] = "offers[$i].title is required";
            if (empty($offer['company'])) $errors[] = "offers[$i].company is required";
        }

        if (!empty($errors)) {
            return $this->json(['error' => 'Validation failed', 'details' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $count = $this->jobOfferService->dispatchOffers($data['offers']);

        return $this->json([
            'message'    => sprintf('%d offer(s) dispatched to the queue.', $count),
            'dispatched' => $count,
        ], Response::HTTP_ACCEPTED);
    }
}