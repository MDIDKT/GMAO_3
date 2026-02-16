<?php

    namespace App\Enum;
    enum StatutDemande: string
    {
        case NOUVEAU = 'nouveau';
        case A_QUALIFIER = 'a_qualifier';
        case QUALIFIE = 'qualifie';
        case PLANIFIE = 'planifie';
        case EN_COURS = 'en_cours';
        case CLOTURE = 'cloture';
        case REJETEE = 'rejetee';

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
