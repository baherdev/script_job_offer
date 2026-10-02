<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ContactInput
{
    public function __construct(
        #[Assert\NotBlank]
        public ?string $name = null,
        #[Assert\NotBlank]
        public ?string $address = null,
        #[Assert\NotBlank]
        public ?string $email = null,
    ) {
    }
}
