<?php

namespace App\Message;

final class MonitoredPageMessage
{
    public function __construct(
        private readonly string $url,
        private readonly string $hash,
        private readonly int $userId,
    ) {}

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
