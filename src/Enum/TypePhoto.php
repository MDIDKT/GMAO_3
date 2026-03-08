<?php

declare(strict_types=1);

    namespace App\Enum;
    enum TypePhoto: string
    {
        case SIGNALEMENT = 'SIGNALEMENT';
        case AVANT = 'AVANT';
        case APRES = 'APRES';
        case COMPLEMENT = 'COMPLEMENT';

        public function label(): string
        {
            return match ($this) {
                self::SIGNALEMENT => 'Signalement',
                self::AVANT => 'Avant',
                self::APRES => 'Après',
                self::COMPLEMENT => 'Complement',
            };
        }
    }
