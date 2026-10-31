<?php

namespace App\Service;

use App\Entity\Enterprise;
use App\Entity\FileReference;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class EnterpriseLogoService
{
    private const MAX_BYTES = 2_000_000;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {}

    public function assignFromUrl(Enterprise $enterprise, string $logoUrl): void
    {
        $binary = $this->downloadImage($logoUrl);
        $extension = $this->imageExtension($binary);
        $relativePath = sprintf('uploads/file-references/%s.%s', bin2hex(random_bytes(16)), $extension);
        $this->writeFile($relativePath, $binary);

        $logo = $enterprise->getLogo();
        $previousPath = $logo?->getFilePath();
        if (!$logo instanceof FileReference) {
            $logo = new FileReference();
            $this->entityManager->persist($logo);
            $enterprise->setLogo($logo);
        }

        $logo->setFileName($this->fileNameFromUrl($logoUrl, $extension));
        $logo->setFilePath($relativePath);
        $logo->setCreationDate(new \DateTimeImmutable('today'));

        if (is_string($previousPath) && $previousPath !== '' && $previousPath !== $relativePath) {
            $this->deleteStoredFile($previousPath);
        }
    }

    private function downloadImage(string $logoUrl): string
    {
        $parts = parse_url($logoUrl);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true) || $host === '' || !$this->isPublicHost($host)) {
            throw new \InvalidArgumentException('logo must be an http(s) URL');
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 15,
                'follow_location' => 1,
                'max_redirects' => 3,
                'header' => "User-Agent: Mozilla/5.0\r\nAccept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8\r\n",
            ],
        ]);

        $binary = @file_get_contents($logoUrl, false, $context, 0, self::MAX_BYTES + 1);
        $status = 0;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', (string) $http_response_header[0], $matches)) {
            $status = (int) $matches[1];
        }
        if (!is_string($binary) || $binary === '' || ($status !== 0 && $status !== 200)) {
            throw new \InvalidArgumentException('Unable to download the logo image');
        }
        if (strlen($binary) > self::MAX_BYTES || $this->imageExtension($binary) === null) {
            throw new \InvalidArgumentException('Logo URL did not return a supported image');
        }

        return $binary;
    }

    private function isPublicHost(string $host): bool
    {
        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return false;
        }
        if (filter_var($host, FILTER_VALIDATE_IP) === false) {
            return true;
        }

        return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    private function imageExtension(string $binary): ?string
    {
        if (str_starts_with($binary, "\xFF\xD8\xFF")) {
            return 'jpg';
        }
        if (str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            return 'png';
        }
        if (str_starts_with($binary, 'GIF87a') || str_starts_with($binary, 'GIF89a')) {
            return 'gif';
        }
        if (strlen($binary) >= 12 && str_starts_with($binary, 'RIFF') && substr($binary, 8, 4) === 'WEBP') {
            return 'webp';
        }

        return null;
    }

    private function writeFile(string $relativePath, string $binary): void
    {
        $absolutePath = $this->projectDir.'/public/'.$relativePath;
        $directory = dirname($absolutePath);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create the logo directory.');
        }
        if (file_put_contents($absolutePath, $binary) === false) {
            throw new \RuntimeException('Unable to store the logo image.');
        }
    }

    private function deleteStoredFile(string $relativePath): void
    {
        $absolutePath = $this->projectDir.'/public/'.$relativePath;
        if (is_file($absolutePath)) {
            unlink($absolutePath);
        }
    }

    private function fileNameFromUrl(string $logoUrl, string $extension): string
    {
        $base = basename((string) parse_url($logoUrl, PHP_URL_PATH));
        $base = preg_replace('/[^A-Za-z0-9._-]/', '', $base) ?: 'logo.'.$extension;
        if (!str_contains($base, '.')) {
            $base .= '.'.$extension;
        }

        return mb_substr($base, 0, 255);
    }
}
