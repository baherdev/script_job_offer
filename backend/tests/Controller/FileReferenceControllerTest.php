<?php

namespace App\Tests\Controller;

use App\Entity\Entreprise;
use App\Entity\FileReference;
use App\Entity\JobOffer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FileReferenceControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->createTestDatabase();

        $this->client = static::createClient();
        $this->resetSchema();
    }

    public function testCreateListGetUpdateAndDeleteFileReference(): void
    {
        $this->request('POST', '/api/file-references', [
            'fileType' => 1,
            'fileName' => 'cv.pdf',
            'fileVersion' => '1',
            'filePath' => '/files/cv.pdf',
            'isActif' => true,
        ]);

        self::assertResponseStatusCodeSame(201);
        $created = $this->json();
        self::assertSame(1, $created['fileType']);
        self::assertSame('cv.pdf', $created['fileName']);
        self::assertSame('1', $created['fileVersion']);
        self::assertSame('/files/cv.pdf', $created['filePath']);
        self::assertTrue($created['isActif']);
        self::assertIsInt($created['id']);
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T/', $created['creationDate']);

        $this->request('GET', '/api/file-references');

        self::assertResponseIsSuccessful();
        self::assertSame([$created], $this->json());

        $this->request('GET', '/api/file-references/'.$created['id']);

        self::assertResponseIsSuccessful();
        self::assertSame($created, $this->json());

        $this->request('PUT', '/api/file-references/'.$created['id'], [
            'fileType' => 2,
            'fileName' => 'cv-v2.pdf',
            'fileVersion' => '2',
            'filePath' => '/files/cv-v2.pdf',
            'isActif' => false,
        ]);

        self::assertResponseIsSuccessful();
        $updated = $this->json();
        self::assertSame($created['id'], $updated['id']);
        self::assertSame($created['creationDate'], $updated['creationDate']);
        self::assertSame(2, $updated['fileType']);
        self::assertSame('cv-v2.pdf', $updated['fileName']);
        self::assertSame('2', $updated['fileVersion']);
        self::assertSame('/files/cv-v2.pdf', $updated['filePath']);
        self::assertFalse($updated['isActif']);

        $this->request('DELETE', '/api/file-references/'.$created['id']);

        self::assertResponseStatusCodeSame(204);
        self::assertSame('', $this->client->getResponse()->getContent());

        $this->request('GET', '/api/file-references/'.$created['id']);

        self::assertResponseStatusCodeSame(404);
        self::assertSame(
            ['error' => sprintf('File reference %d was not found.', $created['id'])],
            $this->json(),
        );

        $this->request('GET', '/api/file-references');

        self::assertResponseIsSuccessful();
        self::assertSame([], $this->json());
    }

    public function testCreateRejectsABlankFileReference(): void
    {
        $this->request('POST', '/api/file-references', [
            'fileType' => 1,
            'fileName' => '',
            'fileVersion' => '1',
            'filePath' => '/files/cv.pdf',
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testMissingFileReferenceReturnsNotFound(): void
    {
        $this->request('GET', '/api/file-references/999');

        self::assertResponseStatusCodeSame(404);
        self::assertSame(['error' => 'File reference 999 was not found.'], $this->json());

        $this->request('DELETE', '/api/file-references/999');

        self::assertResponseStatusCodeSame(404);
        self::assertSame(['error' => 'File reference 999 was not found.'], $this->json());
    }

    public function testDeleteDetachesTheFileReferenceFromItsJobOffers(): void
    {
        $this->request('POST', '/api/file-references', [
            'fileType' => 1,
            'fileName' => 'cv.pdf',
            'fileVersion' => '1',
            'filePath' => '/files/cv.pdf',
        ]);

        self::assertResponseStatusCodeSame(201);
        $created = $this->json();

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $fileReference = $entityManager->find(FileReference::class, $created['id']);
        self::assertInstanceOf(FileReference::class, $fileReference);

        $entreprise = (new Entreprise())
            ->setLabel('Acme')
            ->setAddress('1 Main Street')
            ->setWebsite('https://acme.example');
        $jobOffer = (new JobOffer())
            ->setEntreprise($entreprise)
            ->setSourceId(42)
            ->setOriginalLink('https://example.com/jobs/42')
            ->setPublicationDate(new \DateTimeImmutable('2026-10-02'))
            ->setMinimumSalary(45000)
            ->setMaximumSalary(55000)
            ->setLocation('Lyon')
            ->setPresenceMode(1)
            ->setPositionTitle('Backend developer')
            ->setDescription('Symfony API work')
            ->setApplicationStatus(0)
            ->addFileReference($fileReference);
        $entityManager->persist($entreprise);
        $entityManager->persist($jobOffer);
        $entityManager->flush();
        $jobOfferId = $jobOffer->getId();

        $this->request('DELETE', '/api/file-references/'.$created['id']);

        self::assertResponseStatusCodeSame(204);

        $entityManager->clear();
        $reloaded = $entityManager->find(JobOffer::class, $jobOfferId);
        self::assertInstanceOf(JobOffer::class, $reloaded);
        self::assertCount(0, $reloaded->getFileReferences());

        $this->request('GET', '/api/file-references/'.$created['id']);

        self::assertResponseStatusCodeSame(404);
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
