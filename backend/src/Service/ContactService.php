<?php

namespace App\Service;

use App\Entity\Contact;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;

class ContactService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ContactRepository $contacts,
    ) {
    }

    public function create(string $name, string $address, string $email): Contact
    {
        $contact = (new Contact())
            ->setName($name)
            ->setAddress($address)
            ->setEmail($email);

        $this->entityManager->persist($contact);
        $this->entityManager->flush();

        return $contact;
    }

    public function get(int $id): Contact
    {
        return $this->contacts->find($id)
            ?? throw new \RuntimeException(sprintf('Contact %d was not found.', $id));
    }

    /**
     * @return list<Contact>
     */
    public function findAll(): array
    {
        return $this->contacts->findBy([], ['id' => 'ASC']);
    }

    public function update(Contact $contact, string $name, string $address, string $email): Contact
    {
        $contact
            ->setName($name)
            ->setAddress($address)
            ->setEmail($email);

        $this->entityManager->flush();

        return $contact;
    }

    public function delete(Contact $contact): void
    {
        foreach ($contact->getJobOffers()->toArray() as $jobOffer) {
            $contact->removeJobOffer($jobOffer);
        }

        $this->entityManager->remove($contact);
        $this->entityManager->flush();
    }
}
