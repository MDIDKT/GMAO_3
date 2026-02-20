<?php

    namespace App\Enum;
    enum StatutIntervention: string
    {
        case A_PLANIFIER = 'A_PLANIFIER';
        case PLANIFIE = 'PLANIFIE';
        case EN_COURS = 'EN_COURS';
        case TERMINEE = 'TERMINEE';
        case VALIDEE = 'VALIDEE';

        public function label(): string
        {
            return match ($this) {
                self::A_PLANIFIER => 'À planifier',
                self::PLANIFIE => 'Planifié',
                self::EN_COURS => 'En cours',
                self::TERMINEE => 'Terminée',
                self::VALIDEE => 'Validée',
            };
        }
    }
