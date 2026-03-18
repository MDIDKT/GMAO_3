<?php

    namespace App\Enum;
    enum TypePhoto: string
    {
        case SIGNALEMENT = 'SIGNALEMENT';
        case AVANT = 'AVANT';
        case APRES = 'APRES';
        case CONSTAT = 'CONSTAT';

        public function label(): string
        {
            return match ($this) {
                self::SIGNALEMENT => 'Signalement',
                self::AVANT => 'Avant',
                self::APRES => 'Après',
                self::CONSTAT => 'Constat',
            };
        }
    }
