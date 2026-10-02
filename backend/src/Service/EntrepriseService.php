<?php

namespace App\Service;

use App\Entity\Entreprise;
use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;

class EntrepriseService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EntrepriseRepository $entreprises,
    ) {
    }

    public function create(string $label, string $address, string $website, bool $isBlacklisted = false): Entreprise
    {
        $entreprise = (new Entreprise())
            ->setLabel($label)
            ->setAddress($address)
            ->setWebsite($website)
            ->setIsBlacklisted($isBlacklisted);

        $this->entityManager->persist($entreprise);
        $this->entityManager->flush();

        return $entreprise;
    }

    public function get(int $id): Entreprise
    {
        return $this->entreprises->find($id)
            ?? throw new \RuntimeException(sprintf('Entreprise %d was not found.', $id));
    }

    /**
     * @return list<Entreprise>
     */
    public function findAll(): array
    {
        return $this->entreprises->findBy([], ['id' => 'ASC']);
    }

    public function update(Entreprise $entreprise, string $label, string $address, string $website, bool $isBlacklisted): Entreprise
    {
        $entreprise
            ->setLabel($label)
            ->setAddress($address)
            ->setWebsite($website)
            ->setIsBlacklisted($isBlacklisted);

        $this->entityManager->flush();

        return $entreprise;
    }

    public function delete(Entreprise $entreprise): void
    {
        if (!$entreprise->getJobOffers()->isEmpty()) {
            throw new \RuntimeException(sprintf('Entreprise %d cannot be deleted while job offers still reference it.', $entreprise->getId()));
        }

        $this->entityManager->remove($entreprise);
        $this->entityManager->flush();
    }
}
