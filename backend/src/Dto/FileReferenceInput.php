<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class FileReferenceInput
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Type('integer')]
        public ?int $fileType = null,
        #[Assert\NotBlank]
        public ?string $fileName = null,
        #[Assert\NotBlank]
        public ?string $fileVersion = null,
        #[Assert\NotBlank]
        public ?string $filePath = null,
        #[Assert\Type('bool')]
        public bool $isActif = true,
    ) {
    }
}
