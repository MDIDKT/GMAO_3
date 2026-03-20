<?php
    declare(strict_types=1);

    namespace App\Service;

    use App\Repository\DemandeRepository;
    use App\Repository\InterventionRepository;

    readonly class NumberingService
    {
        public function __construct(private DemandeRepository $demandeRepository, private InterventionRepository $interventionRepository)
        {
        }

        /**
         * @param string $prefix
         * @return string
         */
        public function generateNumero(string $prefix): string
        {
            $year = (int)date('Y');
            $lastNumero = $this->demandeRepository->findLastNumeroForPrefixAndYear($prefix, $year) ?? $this->interventionRepository->findLastNumeroForPrefixAndYear($prefix, $year);
            $nextSequence = 1;

            if ($lastNumero !== null) {
                // Format attendu : PREFIX-ANNEE-SEQUENCE (ex: DEM-2026-0042)
                $parts = explode('-', $lastNumero);
                $nextSequence = ((int)(end($parts) ?: '0')) + 1;
            }

            do {
                $numero = sprintf('%s-%d-%04d', $prefix, $year, $nextSequence);
                ++$nextSequence;
            } while ($this->demandeRepository->numeroExists($numero) || $this->interventionRepository->numeroExists($numero));

            return $numero;
        }

    }
