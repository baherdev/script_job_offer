<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class EntrepriseInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public ?string $label = null,
        #[Assert\NotBlank]
        public ?string $address = null,
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public ?string $website = null,
        #[Assert\Type('bool')]
        public bool $isBlacklisted = false,
    ) {
    }
}
