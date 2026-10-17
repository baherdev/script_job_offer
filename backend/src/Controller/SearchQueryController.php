<?php

namespace App\Controller;

use App\Entity\SearchQuery;
use App\Repository\SearchQueryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
class SearchQueryController extends AbstractController
{
    public function __construct(
        private readonly SearchQueryRepository $searchQueryRepository,
    ) {}

    #[Route('/search-queries/active', name: 'search_queries_active', methods: ['GET'])]
    public function listActive(): JsonResponse
    {
        $queries = array_map(
            fn (SearchQuery $query): array => [
                'id'          => $query->getId(),
                'keyword'     => $query->getKeyword(),
                'location'    => $query->getLocation(),
                'distance'    => $query->getDistance(),
                'isActive'    => $query->isActive(),
                'createdAt'   => $query->getCreatedAt()?->format(\DateTimeInterface::ATOM),
                'createdById' => $query->getCreatedBy()?->getId(),
            ],
            $this->searchQueryRepository->findActive(),
        );

        return $this->json($queries);
    }
}
