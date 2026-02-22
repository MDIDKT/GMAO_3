<?php

    namespace App\Service;

    use App\Repository\DemandeRepository;

    readonly class NumberingService
    {
        public function __construct(private DemandeRepository $demandeRepository)
        {
        }

        /**
         * @param string $prefix
         * @return string
         */
        public function generateNumero(string $prefix): string
        {
            $year = (int) date('Y');
            $lastNumero = $this->demandeRepository->findLastNumeroForPrefixAndYear($prefix, $year);
            $nextSequence = 1;

            if ($lastNumero !== null) {
                $parts = explode('-', $lastNumero);
                $nextSequence = ((int) ($parts[2] ?? 0)) + 1;
            }

            do {
                $numero = sprintf('%s-%d-%04d', $prefix, $year, $nextSequence);
                ++$nextSequence;
            } while ($this->demandeRepository->numeroExists($numero));

            return $numero;
        }

    }
