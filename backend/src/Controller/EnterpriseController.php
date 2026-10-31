<?php

namespace App\Controller;

use App\Entity\Enterprise;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
class EnterpriseController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    #[Route('/enterprises', name: 'enterprises_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (!is_array($data)) {
            return $this->json(['error' => 'JSON body must be an object'], Response::HTTP_BAD_REQUEST);
        }

        $errors = [];
        if (!isset($data['label']) || !is_string($data['label']) || trim($data['label']) === '') {
            $errors[] = 'label is required';
        } elseif (strlen($data['label']) > 255) {
            $errors[] = 'label must be at most 255 characters';
        }

        $optionalFields = ['website', 'street', 'zipCode', 'city', 'country'];
        foreach ($optionalFields as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
                $data[$field] = '';
                continue;
            }
            if (!is_string($data[$field])) {
                $errors[] = sprintf('%s must be a string', $field);
                continue;
            }
            if (strlen($data[$field]) > 255) {
                $errors[] = sprintf('%s must be at most 255 characters', $field);
            }
        }

        if ($errors !== []) {
            return $this->json(['error' => 'Validation failed', 'details' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $enterprise = new Enterprise();
        $enterprise->setLabel($data['label']);
        $enterprise->setWebsite($data['website']);
        $enterprise->setStreet($data['street']);
        $enterprise->setZipCode($data['zipCode']);
        $enterprise->setCity($data['city']);
        $enterprise->setCountry($data['country']);

        $this->entityManager->persist($enterprise);
        $this->entityManager->flush();

        return $this->json([
            'id'      => $enterprise->getId(),
            'label'   => $enterprise->getLabel(),
            'website' => $enterprise->getWebsite(),
            'street'  => $enterprise->getStreet(),
            'zipCode' => $enterprise->getZipCode(),
            'city'    => $enterprise->getCity(),
            'country' => $enterprise->getCountry(),
        ], Response::HTTP_CREATED);
    }
}
