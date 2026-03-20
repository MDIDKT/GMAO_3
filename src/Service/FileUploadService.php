<?php

    namespace App\Service;

    use InvalidArgumentException;
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
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $maxSize = 5 * 1024 * 1024; // 5 Mo

            if (!in_array($file->getMimeType(), $allowedMimeTypes, true)) {
                throw new InvalidArgumentException(
                    sprintf('Type de fichier non autorisé : %s. Formats acceptés : JPEG, PNG, GIF.', $file->getMimeType())
                );
            }

            if ($file->getSize() > $maxSize) {
                throw new InvalidArgumentException(
                    sprintf('Fichier trop volumineux : %s Mo. Maximum autorisé : 5 Mo.', round($file->getSize() / 1024 / 1024, 2))
                );
            }

            $newFilename = uniqid() . '.' . $file->guessExtension();
            $file->move($this->uploadDirectory, $newFilename);
            return $newFilename;
        }

        /**
         * supprimer la photo de mes uploads si elle existe
         */
        public function delete(string $filename): void
        {
            // Valider que filename n'a pas de chemins dangereux
            if (strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
                throw new InvalidArgumentException('Filename invalide : chemin non autorisé');
            }

            $filePath = $this->uploadDirectory . '/' . $filename;
            $realPath = realpath($filePath);
            $realUploadDir = realpath($this->uploadDirectory);

            // Vérifier que le chemin reste dans l'upload directory
            if ($realPath === false || !str_starts_with($realPath, $realUploadDir)) {
                throw new InvalidArgumentException('Tentative de suppression en dehors du répertoire d\'upload');
            }

            if (file_exists($realPath) && !@unlink($realPath)) {
                throw new \RuntimeException(sprintf('Impossible de supprimer le fichier : %s', $realPath));
            }
        }

    }
