<?php

namespace App\Message;

final class JobOfferUpdateMessage
{
    public function __construct(
        private readonly int $id,
        private readonly string $description,
        private readonly ?string $logoUrl = null,
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getLogoUrl(): ?string
    {
        return $this->logoUrl;
    }
}
