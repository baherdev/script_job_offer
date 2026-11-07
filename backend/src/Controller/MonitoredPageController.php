<?php

namespace App\Controller;

use App\Entity\MonitoredPage;
use App\Message\MonitoredPageMessage;
use App\Repository\MonitoredPageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
class MonitoredPageController extends AbstractController
{
    public function __construct(
        private readonly MonitoredPageRepository $monitoredPageRepository,
        private readonly MessageBusInterface $bus,
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

    #[Route('/monitored-pages', name: 'monitored_pages_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['monitoredPages']) || !is_array($data['monitoredPages'])) {
            return $this->json(['error' => 'Field "monitoredPages" is required'], Response::HTTP_BAD_REQUEST);
        }

        $errors = [];
        foreach ($data['monitoredPages'] as $i => $page) {
            if (!is_array($page)) {
                $errors[] = "monitoredPages[$i] must be an object";
                continue;
            }
            if (!isset($page['url']) || !is_string($page['url']) || trim($page['url']) === '') {
                $errors[] = "monitoredPages[$i].url is required";
            } elseif (mb_strlen($page['url']) > 2048) {
                $errors[] = "monitoredPages[$i].url must be at most 2048 characters";
            }
            if (!isset($page['hash']) || !is_string($page['hash']) || $page['hash'] === '') {
                $errors[] = "monitoredPages[$i].hash is required";
            } elseif (mb_strlen($page['hash']) > 255) {
                $errors[] = "monitoredPages[$i].hash must be at most 255 characters";
            }
            if (!isset($page['userId']) || !is_numeric($page['userId'])) {
                $errors[] = "monitoredPages[$i].userId must be numeric";
            }
        }

        if ($errors !== []) {
            return $this->json(['error' => 'Validation failed', 'details' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        foreach ($data['monitoredPages'] as $page) {
            $this->bus->dispatch(new MonitoredPageMessage(
                url: $page['url'],
                hash: $page['hash'],
                userId: (int) $page['userId'],
            ));
        }

        $count = count($data['monitoredPages']);

        return $this->json([
            'message'    => sprintf('%d monitored page(s) dispatched to the queue.', $count),
            'dispatched' => $count,
        ], Response::HTTP_ACCEPTED);
    }
}
