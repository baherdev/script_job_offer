<?php

namespace App\MessageHandler;

use App\Entity\JobApplication;
use App\Entity\JobOffer;
use App\Entity\User;
use App\Message\JobApplicationMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class JobApplicationMessageHandler
{
    public function __construct(private EntityManagerInterface $em) {}

    public function __invoke(JobApplicationMessage $message): void
    {
        $jobOffer = $this->em->find(JobOffer::class, $message->getJobOfferId());
        $user = $this->em->find(User::class, $message->getUserId());
        if (!$jobOffer instanceof JobOffer || !$user instanceof User) {
            return;
        }

        $existing = $this->em->getRepository(JobApplication::class)->findOneBy([
            'user' => $user,
            'jobOffer' => $jobOffer,
        ]);
        if ($existing instanceof JobApplication) {
            return;
        }

        $application = new JobApplication();
        $application->setJobOffer($jobOffer);
        $application->setUser($user);
        $application->setApplicationStatus($message->getApplicationStatus());
        $application->setDesiredSalaryAmount($message->getDesiredSalaryAmount());
        $application->setDesiredSalaryCurrency($message->getDesiredSalaryCurrency());

        $applicationDate = $message->getApplicationDate();
        if (is_string($applicationDate) && $applicationDate !== '') {
            $application->setApplicationDate(new \DateTimeImmutable($applicationDate));
        }

        $this->em->persist($application);
        $this->em->flush();
    }
}
