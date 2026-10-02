<?php

namespace App\Tests\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ContactControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->createTestDatabase();

        $this->client = static::createClient();
        $this->resetSchema();
    }

    public function testCreateListGetUpdateAndDeleteContact(): void
    {
        $this->request('POST', '/api/contacts', [
            'name' => 'Ada Lovelace',
            'address' => '1 Main Street',
            'email' => 'ada@example.com',
        ]);

        self::assertResponseStatusCodeSame(201);
        $created = $this->json();
        self::assertSame('Ada Lovelace', $created['name']);
        self::assertSame('1 Main Street', $created['address']);
        self::assertSame('ada@example.com', $created['email']);
        self::assertIsInt($created['id']);

        $this->request('GET', '/api/contacts');

        self::assertResponseIsSuccessful();
        self::assertSame([$created], $this->json());

        $this->request('GET', '/api/contacts/'.$created['id']);

        self::assertResponseIsSuccessful();
        self::assertSame($created, $this->json());

        $this->request('PUT', '/api/contacts/'.$created['id'], [
            'name' => 'Ada King',
            'address' => '2 Main Street',
            'email' => 'ada.king@example.com',
        ]);

        self::assertResponseIsSuccessful();
        $updated = $this->json();
        self::assertSame($created['id'], $updated['id']);
        self::assertSame('Ada King', $updated['name']);
        self::assertSame('2 Main Street', $updated['address']);
        self::assertSame('ada.king@example.com', $updated['email']);

        $this->request('DELETE', '/api/contacts/'.$created['id']);

        self::assertResponseStatusCodeSame(204);
        self::assertSame('', $this->client->getResponse()->getContent());

        $this->request('GET', '/api/contacts/'.$created['id']);

        self::assertResponseStatusCodeSame(404);
        self::assertSame(
            ['error' => sprintf('Contact %d was not found.', $created['id'])],
            $this->json(),
        );

        $this->request('GET', '/api/contacts');

        self::assertResponseIsSuccessful();
        self::assertSame([], $this->json());
    }

    public function testCreateRejectsABlankContact(): void
    {
        $this->request('POST', '/api/contacts', [
            'name' => '',
            'address' => '1 Main Street',
            'email' => 'ada@example.com',
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testMissingContactReturnsNotFound(): void
    {
        $this->request('GET', '/api/contacts/999');

        self::assertResponseStatusCodeSame(404);
        self::assertSame(['error' => 'Contact 999 was not found.'], $this->json());

        $this->request('DELETE', '/api/contacts/999');

        self::assertResponseStatusCodeSame(404);
        self::assertSame(['error' => 'Contact 999 was not found.'], $this->json());
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
