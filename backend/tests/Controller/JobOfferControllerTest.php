<?php

namespace App\Tests\Controller;

use App\Entity\Contact;
use App\Entity\FileReference;
use App\Entity\JobOffer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class JobOfferControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->createTestDatabase();

        $this->client = static::createClient();
        $this->resetSchema();
    }

    public function testCreateListGetUpdateAndDeleteJobOffer(): void
    {
        $entrepriseId = $this->createEntreprise('Acme');
        $otherEntrepriseId = $this->createEntreprise('Other');

        $this->request('POST', '/api/job-offers', $this->offerPayload($entrepriseId, [
            'applicationDate' => '2026-10-03',
            'coverLetter' => 'I am interested.',
            'desiredSalary' => 50000,
        ]));

        self::assertResponseStatusCodeSame(201);
        $created = $this->json();
        self::assertIsInt($created['id']);
        self::assertSame($entrepriseId, $created['entrepriseId']);
        self::assertSame(42, $created['sourceId']);
        self::assertSame('https://example.com/jobs/42', $created['originalLink']);
        self::assertSame('2026-10-02', $created['publicationDate']);
        self::assertSame(45000, $created['minimumSalary']);
        self::assertSame(55000, $created['maximumSalary']);
        self::assertSame('Lyon', $created['location']);
        self::assertSame(1, $created['presenceMode']);
        self::assertSame('Backend developer', $created['positionTitle']);
        self::assertSame('Symfony API work', $created['description']);
        self::assertSame(0, $created['applicationStatus']);
        self::assertSame('2026-10-03', $created['applicationDate']);
        self::assertNull($created['rejectionDate']);
        self::assertSame('I am interested.', $created['coverLetter']);
        self::assertSame(50000, $created['desiredSalary']);
        self::assertNull($created['availabilityDate']);

        $this->request('GET', '/api/job-offers');

        self::assertResponseIsSuccessful();
        self::assertSame([$created], $this->json());

        $this->request('GET', '/api/job-offers/'.$created['id']);

        self::assertResponseIsSuccessful();
        self::assertSame($created, $this->json());

        $this->request('PUT', '/api/job-offers/'.$created['id'], $this->offerPayload($otherEntrepriseId, [
            'sourceId' => 43,
            'originalLink' => 'https://example.com/jobs/43',
            'publicationDate' => '2026-11-01',
            'minimumSalary' => 48000,
            'maximumSalary' => 60000,
            'location' => 'Paris',
            'presenceMode' => 2,
            'positionTitle' => 'Senior backend developer',
            'description' => 'Lead the API work',
            'applicationStatus' => 1,
            'interviewDate1' => '2026-11-15',
        ]));

        self::assertResponseIsSuccessful();
        $updated = $this->json();
        self::assertSame($created['id'], $updated['id']);
        self::assertSame($otherEntrepriseId, $updated['entrepriseId']);
        self::assertSame(43, $updated['sourceId']);
        self::assertSame('Paris', $updated['location']);
        self::assertSame('Senior backend developer', $updated['positionTitle']);
        self::assertSame(1, $updated['applicationStatus']);
        self::assertNull($updated['applicationDate']);
        self::assertSame('2026-11-15', $updated['interviewDate1']);
        self::assertNull($updated['coverLetter']);
        self::assertNull($updated['desiredSalary']);

        $this->request('DELETE', '/api/job-offers/'.$created['id']);

        self::assertResponseStatusCodeSame(204);
        self::assertSame('', $this->client->getResponse()->getContent());

        $this->request('GET', '/api/job-offers/'.$created['id']);

        self::assertResponseStatusCodeSame(404);
        self::assertSame(
            ['error' => sprintf('Job offer %d was not found.', $created['id'])],
            $this->json(),
        );

        $this->request('GET', '/api/job-offers');

        self::assertResponseIsSuccessful();
        self::assertSame([], $this->json());

        $this->request('GET', '/api/entreprises/'.$entrepriseId);
        self::assertResponseIsSuccessful();
        $this->request('GET', '/api/entreprises/'.$otherEntrepriseId);
        self::assertResponseIsSuccessful();
    }

    public function testCreateRejectsABlankJobOffer(): void
    {
        $this->request('POST', '/api/job-offers', $this->offerPayload($this->createEntreprise('Acme'), [
            'positionTitle' => '',
        ]));

        self::assertResponseStatusCodeSame(422);
    }

    public function testCreateRejectsAnUnknownEntreprise(): void
    {
        $this->request('POST', '/api/job-offers', $this->offerPayload(999));

        self::assertResponseStatusCodeSame(404);
        self::assertSame(['error' => 'Entreprise 999 was not found.'], $this->json());
    }

    public function testMissingJobOfferReturnsNotFound(): void
    {
        $this->request('GET', '/api/job-offers/999');

        self::assertResponseStatusCodeSame(404);
        self::assertSame(['error' => 'Job offer 999 was not found.'], $this->json());

        $this->request('DELETE', '/api/job-offers/999');

        self::assertResponseStatusCodeSame(404);
        self::assertSame(['error' => 'Job offer 999 was not found.'], $this->json());
    }

    public function testDeleteDetachesContactsAndFileReferences(): void
    {
        $entrepriseId = $this->createEntreprise('Acme');
        $this->request('POST', '/api/job-offers', $this->offerPayload($entrepriseId));
        self::assertResponseStatusCodeSame(201);
        $created = $this->json();

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $jobOffer = $entityManager->find(JobOffer::class, $created['id']);
        self::assertInstanceOf(JobOffer::class, $jobOffer);

        $contact = (new Contact())
            ->setName('Ada Lovelace')
            ->setAddress('1 Main Street')
            ->setEmail('ada@example.com');
        $fileReference = (new FileReference())
            ->setFileType(1)
            ->setFileName('cv.pdf')
            ->setFileVersion('1')
            ->setFilePath('/files/cv.pdf');
        $jobOffer->addContact($contact);
        $jobOffer->addFileReference($fileReference);
        $entityManager->persist($contact);
        $entityManager->persist($fileReference);
        $entityManager->flush();
        $contactId = $contact->getId();
        $fileReferenceId = $fileReference->getId();

        $this->request('DELETE', '/api/job-offers/'.$created['id']);

        self::assertResponseStatusCodeSame(204);

        $entityManager->clear();
        $reloadedContact = $entityManager->find(Contact::class, $contactId);
        $reloadedFile = $entityManager->find(FileReference::class, $fileReferenceId);
        self::assertInstanceOf(Contact::class, $reloadedContact);
        self::assertInstanceOf(FileReference::class, $reloadedFile);
        self::assertCount(0, $reloadedContact->getJobOffers());
        self::assertCount(0, $reloadedFile->getJobOffers());

        $this->request('GET', '/api/entreprises/'.$entrepriseId);
        self::assertResponseIsSuccessful();
    }

    private function createEntreprise(string $label): int
    {
        $this->request('POST', '/api/entreprises', [
            'label' => $label,
            'address' => '1 Main Street',
            'website' => 'https://'.strtolower($label).'.example',
        ]);
        self::assertResponseStatusCodeSame(201);
        $created = $this->json();
        self::assertIsInt($created['id']);

        return $created['id'];
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function offerPayload(int $entrepriseId, array $overrides = []): array
    {
        return array_merge([
            'entrepriseId' => $entrepriseId,
            'sourceId' => 42,
            'originalLink' => 'https://example.com/jobs/42',
            'publicationDate' => '2026-10-02',
            'minimumSalary' => 45000,
            'maximumSalary' => 55000,
            'location' => 'Lyon',
            'presenceMode' => 1,
            'positionTitle' => 'Backend developer',
            'description' => 'Symfony API work',
            'applicationStatus' => 0,
        ], $overrides);
    }

    private function createTestDatabase(): void
    {
        $databaseUrl = (string) ($_SERVER['DATABASE_URL'] ?? '');
        if (!str_starts_with($databaseUrl, 'mysql:')) {
            self::fail('DATABASE_URL in .env.test must be a MySQL URL.');
        }

        $parts = parse_url($databaseUrl);
        if (!is_array($parts) || !isset($parts['host'], $parts['user'], $parts['path'])) {
            self::fail('DATABASE_URL in .env.test could not be parsed.');
        }

        $name = ltrim($parts['path'], '/').'_test'.($_SERVER['TEST_TOKEN'] ?? '');
        $port = $parts['port'] ?? 3306;
        $pdo = new \PDO(
            sprintf('mysql:host=%s;port=%d', $parts['host'], $port),
            $parts['user'],
            $parts['pass'] ?? '',
        );
        $pdo->exec(sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            str_replace('`', '``', $name),
        ));
    }

    private function resetSchema(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $connection = $entityManager->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($connection->createSchemaManager()->listTableNames() as $table) {
            $connection->executeStatement(sprintf('DROP TABLE IF EXISTS `%s`', str_replace('`', '``', $table)));
        }
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');

        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        (new SchemaTool($entityManager))->createSchema($metadata);
    }

    /**
     * @param array<string, mixed>|null $body
     */
    private function request(string $method, string $uri, ?array $body = null): void
    {
        $this->client->request(
            $method,
            $uri,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: null === $body ? null : json_encode($body, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function json(): array
    {
        $content = $this->client->getResponse()->getContent();
        self::assertIsString($content);

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }
}
