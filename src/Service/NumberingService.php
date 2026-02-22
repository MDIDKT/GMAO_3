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
            $numero = $this->demandeRepository->findNumDemande();
            $date = date('Y');

            if ($numero === null) {
                $numeroDemande = 1;
            } else {
                $numeroDemande = (int)explode('-', $numero['numero'])[2];
                $numeroDemande++;
            }

            return sprintf('%s-%s-%04d', $prefix, $date, $numeroDemande);
        }

    }
