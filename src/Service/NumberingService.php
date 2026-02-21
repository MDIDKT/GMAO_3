<?php

    namespace App\Service;

    use function Symfony\Component\Clock\now;

    class NumberingService
    {

        /**
         * @throws \DateMalformedStringException
         */
        public function generateNumero(string $prefix): string
        {
            $nbAleatoire = rand(1000, 9999);
            $date = date(now()->format('Y'));
            return $prefix . '-' . $date . '-' . $nbAleatoire;
        }

    }
