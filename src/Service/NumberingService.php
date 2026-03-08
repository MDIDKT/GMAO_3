<?php

declare(strict_types=1);

    namespace App\Service;

    use App\Repository\DemandeRepository;
    use App\Repository\InterventionRepository;
    use InvalidArgumentException;

    readonly class NumberingService
    {
        /** Mapping explicite préfixe → repository responsable */
        private const array PREFIX_MAP = [
            'DEM' => 'demande',
            'INT' => 'intervention',
        ];

        public function __construct(private DemandeRepository $demandeRepository, private InterventionRepository $interventionRepository)
        {
        }

        public function generateNumero(string $prefix): string
        {
            $repository = $this->resolveRepository($prefix);
            $year = (int) date('Y');
            $lastNumero = $repository->findLastNumeroForPrefixAndYear($prefix, $year);
            $nextSequence = 1;

            if ($lastNumero !== null) {
                $parts = explode('-', $lastNumero);
                $nextSequence = ((int) ($parts[2] ?? 0)) + 1;
            }

            do {
                $numero = sprintf('%s-%d-%04d', $prefix, $year, $nextSequence);
                ++$nextSequence;
            } while ($repository->numeroExists($numero));

            return $numero;
        }

        private function resolveRepository(string $prefix): DemandeRepository|InterventionRepository
        {
            return match(self::PREFIX_MAP[$prefix] ?? null) {
                'demande'      => $this->demandeRepository,
                'intervention' => $this->interventionRepository,
                default        => throw new InvalidArgumentException("Préfixe de numérotation inconnu : {$prefix}"),
            };
        }
    }
