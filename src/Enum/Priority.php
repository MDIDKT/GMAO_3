<?php

    namespace App\Enum;
    enum Priority: string
    {
        case P1 = 'URGENTE';
        case P2 = 'HAUTE';
        case P3 = 'NORMALE';
        case P4 = 'BASSE';
    }
