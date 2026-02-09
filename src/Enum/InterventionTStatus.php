<?php

    namespace App\Enum;
    enum InterventionTStatus: string
    {
        case A_PLANIFIER = 'A_PLANIFIER';
        case PLANIFIE = 'PLANIFIE';
        case EN_COURS = 'EN_COURS';
        case CLOTURE = 'CLOTURE';
    }
