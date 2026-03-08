<?php

declare(strict_types=1);

    namespace App\Enum;
    enum Priorite: string
    {
        case P1_URGENTE = 'p1_URGENTE';
        case P2_HAUTE = 'p2_HAUTE';
        case P3_NORMALE = 'p3_NORMALE';
        case P4_BASSE = 'p4_BASSE';

        public function label(): string
        {
            return match ($this) {
                self::P1_URGENTE => 'P1 — Urgente',
                self::P2_HAUTE => 'P2 — Haute',
                self::P3_NORMALE => 'P3 — Normale',
                self::P4_BASSE => 'P4 — Basse',
            };
        }
    }
