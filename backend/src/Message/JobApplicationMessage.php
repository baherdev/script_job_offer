<?php

namespace App\Message;

final class JobApplicationMessage
{
    public function __construct(
        private readonly int $jobOfferId,
        private readonly int $userId,
        private readonly int $applicationStatus = 0,
        private readonly ?string $applicationDate = null,
        private readonly ?int $desiredSalaryAmount = null,
        private readonly int $desiredSalaryCurrency = 0,
    ) {}

    public function getJobOfferId(): int { return $this->jobOfferId; }
    public function getUserId(): int { return $this->userId; }
    public function getApplicationStatus(): int { return $this->applicationStatus; }
    public function getApplicationDate(): ?string { return $this->applicationDate; }
    public function getDesiredSalaryAmount(): ?int { return $this->desiredSalaryAmount; }
    public function getDesiredSalaryCurrency(): int { return $this->desiredSalaryCurrency; }
}
