<?php

namespace App\Controller;

use App\Dto\EntrepriseInput;
use App\Entity\Entreprise;
use App\Service\EntrepriseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/entreprises')]
class EntrepriseController extends AbstractController
{
    public function __construct(
        private readonly EntrepriseService $entreprises,
    ) {
    }

    #[Route('', name: 'entreprise_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json(array_map(
            $this->present(...),
            $this->entreprises->findAll(),
        ));
    }

    #[Route('/{id}', name: 'entreprise_get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(int $id): JsonResponse
    {
        try {
            $entreprise = $this->entreprises->get($id);
        } catch (\RuntimeException) {
            return $this->notFound($id);
        }

        return $this->json($this->present($entreprise));
    }

    #[Route('', name: 'entreprise_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] EntrepriseInput $input): JsonResponse
    {
        $entreprise = $this->entreprises->create(
            $input->label,
            $input->address,
            $input->website,
            $input->isBlacklisted,
        );

        return $this->json($this->present($entreprise), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'entreprise_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, #[MapRequestPayload] EntrepriseInput $input): JsonResponse
    {
        try {
            $entreprise = $this->entreprises->get($id);
        } catch (\RuntimeException) {
            return $this->notFound($id);
        }

        $entreprise = $this->entreprises->update(
            $entreprise,
            $input->label,
            $input->address,
            $input->website,
            $input->isBlacklisted,
        );

        return $this->json($this->present($entreprise));
    }

    #[Route('/{id}', name: 'entreprise_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $entreprise = $this->entreprises->get($id);
        } catch (\RuntimeException) {
            return $this->notFound($id);
        }

        try {
            $this->entreprises->delete($entreprise);
        } catch (\RuntimeException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_CONFLICT);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    private function notFound(int $id): JsonResponse
    {
        return $this->json(
            ['error' => sprintf('Entreprise %d was not found.', $id)],
            Response::HTTP_NOT_FOUND,
        );
    }

    /**
     * @return array{id: int|null, label: string|null, address: string|null, website: string|null, isBlacklisted: bool}
     */
    private function present(Entreprise $entreprise): array
    {
        return [
            'id' => $entreprise->getId(),
            'label' => $entreprise->getLabel(),
            'address' => $entreprise->getAddress(),
            'website' => $entreprise->getWebsite(),
            'isBlacklisted' => $entreprise->isBlacklisted(),
        ];
    }
}
