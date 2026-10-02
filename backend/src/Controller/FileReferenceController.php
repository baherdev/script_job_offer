<?php

namespace App\Controller;

use App\Dto\FileReferenceInput;
use App\Entity\FileReference;
use App\Service\FileReferenceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/file-references')]
class FileReferenceController extends AbstractController
{
    public function __construct(
        private readonly FileReferenceService $fileReferences,
    ) {
    }

    #[Route('', name: 'file_reference_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json(array_map(
            $this->present(...),
            $this->fileReferences->findAll(),
        ));
    }

    #[Route('/{id}', name: 'file_reference_get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(int $id): JsonResponse
    {
        try {
            $fileReference = $this->fileReferences->get($id);
        } catch (\RuntimeException) {
            return $this->notFound($id);
        }

        return $this->json($this->present($fileReference));
    }

    #[Route('', name: 'file_reference_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] FileReferenceInput $input): JsonResponse
    {
        $fileReference = $this->fileReferences->create(
            $input->fileType,
            $input->fileName,
            $input->fileVersion,
            $input->filePath,
            $input->isActif,
        );

        return $this->json($this->present($fileReference), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'file_reference_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, #[MapRequestPayload] FileReferenceInput $input): JsonResponse
    {
        try {
            $fileReference = $this->fileReferences->get($id);
        } catch (\RuntimeException) {
            return $this->notFound($id);
        }

        $fileReference = $this->fileReferences->update(
            $fileReference,
            $input->fileType,
            $input->fileName,
            $input->fileVersion,
            $input->filePath,
            $input->isActif,
        );

        return $this->json($this->present($fileReference));
    }

    #[Route('/{id}', name: 'file_reference_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $fileReference = $this->fileReferences->get($id);
        } catch (\RuntimeException) {
            return $this->notFound($id);
        }

        $this->fileReferences->delete($fileReference);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    private function notFound(int $id): JsonResponse
    {
        return $this->json(
            ['error' => sprintf('File reference %d was not found.', $id)],
            Response::HTTP_NOT_FOUND,
        );
    }

    /**
     * @return array{id: int|null, fileType: int|null, fileName: string|null, fileVersion: string|null, filePath: string|null, creationDate: string, isActif: bool}
     */
    private function present(FileReference $fileReference): array
    {
        return [
            'id' => $fileReference->getId(),
            'fileType' => $fileReference->getFileType(),
            'fileName' => $fileReference->getFileName(),
            'fileVersion' => $fileReference->getFileVersion(),
            'filePath' => $fileReference->getFilePath(),
            'creationDate' => $fileReference->getCreationDate()->format(\DateTimeInterface::ATOM),
            'isActif' => $fileReference->isActif(),
        ];
    }
}
