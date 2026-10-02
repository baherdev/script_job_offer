<?php

namespace App\Tests\Controller;

use App\Entity\Entreprise;
use App\Entity\JobOffer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EntrepriseControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->createTestDatabase();

        $this->client = static::createClient();
        $this->resetSchema();
    }

    public function testCreateListGetUpdateAndDeleteEntreprise(): void
    {
        $this->request('POST', '/api/entreprises', [
            'label' => 'Acme',
            'address' => '1 Main Street',
            'website' => 'https://acme.example',
            'isBlacklisted' => false,
        ]);

        self::assertResponseStatusCodeSame(201);
        $created = $this->json();
        self::assertSame('Acme', $created['label']);
        self::assertSame('1 Main Street', $created['address']);
        self::assertSame('https://acme.example', $created['website']);
        self::assertFalse($created['isBlacklisted']);
        self::assertIsInt($created['id']);

        $this->request('GET', '/api/entreprises');

        self::assertResponseIsSuccessful();
        self::assertSame([$created], $this->json());

        $this->request('GET', '/api/entreprises/'.$created['id']);

        self::assertResponseIsSuccessful();
        self::assertSame($created, $this->json());

        $this->request('PUT', '/api/entreprises/'.$created['id'], [
            'label' => 'Acme Corp',
            'address' => '2 Main Street',
            'website' => 'https://acme-corp.example',
            'isBlacklisted' => true,
        ]);

        self::assertResponseIsSuccessful();
        $updated = $this->json();
        self::assertSame($created['id'], $updated['id']);
        self::assertSame('Acme Corp', $updated['label']);
        self::assertSame('2 Main Street', $updated['address']);
        self::assertSame('https://acme-corp.example', $updated['website']);
        self::assertTrue($updated['isBlacklisted']);

        $this->request('DELETE', '/api/entreprises/'.$created['id']);

        self::assertResponseStatusCodeSame(204);
        self::assertSame('', $this->client->getResponse()->getContent());

        $this->request('GET', '/api/entreprises/'.$created['id']);

        self::assertResponseStatusCodeSame(404);
        self::assertSame(
            ['error' => sprintf('Entreprise %d was not found.', $created['id'])],
            $this->json(),
        );

        $this->request('GET', '/api/entreprises');

        self::assertResponseIsSuccessful();
        self::assertSame([], $this->json());
    }

    public function testCreateRejectsABlankEntreprise(): void
    {
        $this->request('POST', '/api/entreprises', [
            'label' => '',
            'address' => '1 Main Street',
            'website' => 'https://acme.example',
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testMissingEntrepriseReturnsNotFound(): void
    {
        $this->request('GET', '/api/entreprises/999');

        self::assertResponseStatusCodeSame(404);
        self::assertSame(['error' => 'Entreprise 999 was not found.'], $this->json());

        $this->request('DELETE', '/api/entreprises/999');

        self::assertResponseStatusCodeSame(404);
        self::assertSame(['error' => 'Entreprise 999 was not found.'], $this->json());
    }

    public function testDeleteIsRejectedWhileAJobOfferReferencesTheEntreprise(): void
    {
        $this->request('POST', '/api/entreprises', [
            'label' => 'Acme',
            'address' => '1 Main Street',
            'website' => 'https://acme.example',
        ]);

        self::assertResponseStatusCodeSame(201);
        $created = $this->json();

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entreprise = $entityManager->find(Entreprise::class, $created['id']);
        self::assertInstanceOf(Entreprise::class, $entreprise);

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
            ->setApplicationStatus(0);
        $entityManager->persist($jobOffer);
        $entityManager->flush();

        $this->request('DELETE', '/api/entreprises/'.$created['id']);

        self::assertResponseStatusCodeSame(409);
        self::assertSame(
            ['error' => sprintf('Entreprise %d cannot be deleted while job offers still reference it.', $created['id'])],
            $this->json(),
        );

        $this->request('GET', '/api/entreprises/'.$created['id']);

        self::assertResponseIsSuccessful();
        self::assertSame($created['id'], $this->json()['id']);
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
