<?php

namespace App\Controller;

use App\Entity\MonitoredPage;
use App\Repository\MonitoredPageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
class MonitoredPageController extends AbstractController
{
    public function __construct(
        private readonly MonitoredPageRepository $monitoredPageRepository,
    ) {}

    #[Route('/monitored-pages', name: 'monitored_pages', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $pages = array_map(
            fn (MonitoredPage $page): array => [
                'id'     => $page->getId(),
                'url'    => $page->getUrl(),
                'hash'   => $page->getHash(),
                'userId' => $page->getUser()?->getId(),
            ],
            $this->monitoredPageRepository->findBy([], ['id' => 'ASC']),
        );

        return $this->json($pages);
    }
}
