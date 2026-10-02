<?php

namespace App\Message;

final class JobOfferMessage
{
    public function __construct(
        private readonly string $title,
        private readonly string $company,
        private readonly ?string $location = null,
        private readonly ?string $description = null,
        private readonly ?string $url = null,
    ) {}

    public function getTitle(): string { return $this->title; }
    public function getCompany(): string { return $this->company; }
    public function getLocation(): ?string { return $this->location; }
    public function getDescription(): ?string { return $this->description; }
    public function getUrl(): ?string { return $this->url; }
}
