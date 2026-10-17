<?php

namespace App\Tests\Integration;

use App\Entity\JobApplication;
use App\Entity\JobOffer;
use App\Entity\SearchQuery;
use App\Entity\User;
use App\Message\JobApplicationMessage;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Messenger\Worker;

final class CreateJobOfferCreatesJobApplicationTest extends WebTestCase
{
    private const OFFER_URL = 'https://example.com/jobs/integration-test';

    public function testApiOfferCreatesJobApplicationForSearchQueryOwner(): void
    {
        $client = static::createClient();
        $entityManager = $this->entityManager();
        $this->resetSchema($entityManager);

        $user = new User();
        $user->setEmail('owner@example.com');
        $user->setPassword('hashed-password');
        $user->setRoles(['ROLE_USER']);

        $searchQuery = new SearchQuery();
        $searchQuery->setKeyword('drupal');
        $searchQuery->setLocation('Switzerland');
        $searchQuery->setIsActive(true);
        $searchQuery->setCreatedBy($user);

        $entityManager->persist($user);
        $entityManager->persist($searchQuery);
        $entityManager->flush();

        $client->request(
            'POST',
            '/api/job-offers',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'offers' => [[
                    'title' => 'API simulation offer',
                    'company' => 'Simulation AG',
                    'location' => 'Bern',
                    'url' => self::OFFER_URL,
                    'searchQueryId' => $searchQuery->getId(),
                ]],
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(202);
        self::assertCount(1, $this->transport('async')->getSent());

        $this->consumeQueues();
        self::assertCount(1, $this->transport('job_applications')->getAcknowledged());

        $entityManager->clear();
        $offer = $entityManager->getRepository(JobOffer::class)->findOneBy(['url' => self::OFFER_URL]);
        self::assertInstanceOf(JobOffer::class, $offer);
        self::assertCount(1, $offer->getSearchQueries());
        self::assertSame($searchQuery->getId(), $offer->getSearchQueries()->first()->getId());

        $applications = $entityManager->getRepository(JobApplication::class)->findBy(['jobOffer' => $offer]);
        self::assertCount(1, $applications);
        self::assertSame($user->getId(), $applications[0]->getUser()?->getId());
        self::assertSame(0, $applications[0]->getApplicationStatus());

        $bus = static::getContainer()->get(MessageBusInterface::class);
        self::assertInstanceOf(MessageBusInterface::class, $bus);
        $bus->dispatch(new JobApplicationMessage(
            jobOfferId: $offer->getId(),
            userId: $user->getId(),
        ));
        $this->consumeQueues();

        $entityManager->clear();
        $applications = $entityManager->getRepository(JobApplication::class)->findBy(['jobOffer' => $offer->getId()]);
        self::assertCount(1, $applications);
    }

    private function entityManager(): EntityManagerInterface
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        return $entityManager;
    }

    private function resetSchema(EntityManagerInterface $entityManager): void
    {
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        if ($entityManager->getConnection()->createSchemaManager()->listTableNames() !== []) {
            $schemaTool->dropSchema($metadata);
        }
        $schemaTool->createSchema($metadata);
    }

    private function consumeQueues(): void
    {
        $dispatcher = static::getContainer()->get(EventDispatcherInterface::class);
        self::assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
        $dispatcher->addListener(WorkerRunningEvent::class, static function (WorkerRunningEvent $event): void {
            if ($event->isWorkerIdle()) {
                $event->getWorker()->stop();
            }
        });

        $bus = static::getContainer()->get(MessageBusInterface::class);
        self::assertInstanceOf(MessageBusInterface::class, $bus);
        $worker = new Worker([
            'async' => $this->transport('async'),
            'job_applications' => $this->transport('job_applications'),
        ], $bus, $dispatcher);
        $worker->run(['sleep' => 0]);
    }

    private function transport(string $name): InMemoryTransport
    {
        $transport = static::getContainer()->get('messenger.transport.'.$name);
        self::assertInstanceOf(InMemoryTransport::class, $transport);

        return $transport;
    }
}
