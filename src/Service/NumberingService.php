<?php

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
            $year = (int) date('Y');
            $lastNumero = $this->demandeRepository->findLastNumeroForPrefixAndYear($prefix, $year) ?? $this->interventionRepository->findLastNumeroForPrefixAndYear($prefix, $year);
            $nextSequence = 1;

            if ($lastNumero !== null) {
                $parts = explode('-', $lastNumero);
                $nextSequence = ((int) ($parts[2] ?? 0)) + 1;
            }

            do {
                $numero = sprintf('%s-%d-%04d', $prefix, $year, $nextSequence);
                ++$nextSequence;
            } while ($this->demandeRepository->numeroExists($numero) || $this->interventionRepository->numeroExists($numero));

            return $numero;
        }

    }
