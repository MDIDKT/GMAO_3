<?php

declare(strict_types=1);

    namespace App\Service;

    use Symfony\Component\HttpFoundation\File\UploadedFile;

    class FileUploadService
    {
        public function __construct(
            private string $uploadDirectory
        )
        {
        }

        public function upload(UploadedFile $file): string
        {
            $newFilename = bin2hex(random_bytes(16)) . '.' . $file->guessExtension();
            $file->move($this->uploadDirectory, $newFilename);
            return $newFilename;
        }
    }
