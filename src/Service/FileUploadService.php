<?php

    namespace App\Service;

    use InvalidArgumentException;
    use Symfony\Component\HttpFoundation\File\UploadedFile;
    use Symfony\Component\Validator\Constraints as Assert;

    class FileUploadService
    {
        public function __construct(
            private string $uploadDirectory
        )
        {
        }

        #[Assert\File(
            maxSize: '5M',
            mimeTypes: ['image/jpeg', 'image/png', 'image/gif'],
            mimeTypesMessage: 'Veuillez télécharger une image valide (JPEG, PNG, GIF).',
        )]
        public function upload(UploadedFile $file): string
        {
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

            if (file_exists($realPath)) {
                unlink($realPath);
            }
        }

    }
