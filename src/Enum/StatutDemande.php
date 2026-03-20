<?php

    namespace App\Enum;
    enum StatutDemande: string
    {
        case NOUVELLE = 'A_QUALIFIER';
        // Legacy status kept only to avoid breaking existing rows already stored with QUALIFIE.
        case VALIDEE = 'QUALIFIE';
        case PLANIFIEE = 'PLANIFIE';
        case EN_COURS = 'EN_COURS';
        case CLOTUREE = 'CLOTURE';
        case REJETEE = 'REJETEE';

        public function label(): string
        {
            return match ($this) {
                self::NOUVELLE => 'Nouvelle',
                self::VALIDEE => 'Planifiée',
                self::PLANIFIEE => 'Planifiée',
                self::EN_COURS => 'En cours',
                self::CLOTUREE => 'Clôturée',
                self::REJETEE => 'Rejetée',
            };
        }
    }
