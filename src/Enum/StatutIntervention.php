<?php

    namespace App\Enum;
    enum StatutIntervention: string
    {
        case A_PLANIFIER = 'a_planifier';
        case PLANIFIE = 'planifie';
        case EN_COURS = 'en_cours';
        case TERMINEE = 'terminee';
        case VALIDEE = 'validee';

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
