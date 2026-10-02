<?php

namespace App\Controller;

use App\Dto\ContactInput;
use App\Entity\Contact;
use App\Service\ContactService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/contacts')]
class ContactController extends AbstractController
{
    public function __construct(
        private readonly ContactService $contacts,
    ) {
    }

    #[Route('', name: 'contact_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json(array_map(
            $this->present(...),
            $this->contacts->findAll(),
        ));
    }

    #[Route('/{id}', name: 'contact_get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(int $id): JsonResponse
    {
        try {
            $contact = $this->contacts->get($id);
        } catch (\RuntimeException) {
            return $this->notFound($id);
        }

        return $this->json($this->present($contact));
    }

    #[Route('', name: 'contact_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] ContactInput $input): JsonResponse
    {
        $contact = $this->contacts->create(
            $input->name,
            $input->address,
            $input->email,
        );

        return $this->json($this->present($contact), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'contact_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, #[MapRequestPayload] ContactInput $input): JsonResponse
    {
        try {
            $contact = $this->contacts->get($id);
        } catch (\RuntimeException) {
            return $this->notFound($id);
        }

        $contact = $this->contacts->update(
            $contact,
            $input->name,
            $input->address,
            $input->email,
        );

        return $this->json($this->present($contact));
    }

    #[Route('/{id}', name: 'contact_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $contact = $this->contacts->get($id);
        } catch (\RuntimeException) {
            return $this->notFound($id);
        }

        $this->contacts->delete($contact);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    private function notFound(int $id): JsonResponse
    {
        return $this->json(
            ['error' => sprintf('Contact %d was not found.', $id)],
            Response::HTTP_NOT_FOUND,
        );
    }

    /**
     * @return array{id: int|null, name: string|null, address: string|null, email: string|null}
     */
    private function present(Contact $contact): array
    {
        return [
            'id' => $contact->getId(),
            'name' => $contact->getName(),
            'address' => $contact->getAddress(),
            'email' => $contact->getEmail(),
        ];
    }
}
