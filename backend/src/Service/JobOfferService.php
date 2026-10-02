<?php

namespace App\Service;

use App\Message\JobOfferMessage;
use Symfony\Component\Messenger\MessageBusInterface;

class JobOfferService
{
    public function __construct(
        private readonly MessageBusInterface $bus,
    ) {}

    public function dispatchOffers(array $offers): int
    {
        $count = 0;
        foreach ($offers as $offer) {
            $this->bus->dispatch(new JobOfferMessage(
                title:       $offer['title'],
                company:     $offer['company'],
                location:    $offer['location'] ?? null,
                description: $offer['description'] ?? null,
                url:         $offer['url'] ?? null,
            ));
            $count++;
        }
        return $count;
    }
}
