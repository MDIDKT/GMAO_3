<?php

declare(strict_types=1);

    namespace App\Enum;
    enum StatutEquipement: string
    {
        case EN_SERVICE = 'en_service';
        case HORS_SERVICE = 'hors_service';
        case EN_PANNE = 'en_panne';

        public function label(): string
        {
            return match ($this) {
                self::EN_SERVICE => 'En service',
                self::HORS_SERVICE => 'Hors service',
                self::EN_PANNE => 'En panne',
            };

        }
    }
