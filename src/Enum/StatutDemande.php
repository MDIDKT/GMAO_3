<?php

declare(strict_types=1);

    namespace App\Enum;
    enum StatutDemande: string
    {
        case NOUVEAU = 'NOUVEAU';
        case A_QUALIFIER = 'A_QUALIFIER';
        case QUALIFIE = 'QUALIFIE';
        case PLANIFIE = 'PLANIFIE';
        case EN_COURS = 'EN_COURS';
        case CLOTURE = 'CLOTURE';
        case REJETEE = 'REJETEE';

        public function label(): string
        {
            return match ($this) {
                self::NOUVEAU => 'Nouveau',
                self::A_QUALIFIER => 'À qualifier',
                self::QUALIFIE => 'Qualifié',
                self::PLANIFIE => 'Planifié',
                self::EN_COURS => 'En cours',
                self::CLOTURE => 'Clôturé',
                self::REJETEE => 'Rejetée',
            };
        }
    }
