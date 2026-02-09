<?php

    namespace App\Enum;
    enum RequestStatus: string
    {
        case NOUVEAU = 'NOUVEAU';
        case A_QUALIFIER = 'A_QUALIFIER';
        case PLANIFIE = 'PLANIFIE';
        case EN_COURS = 'EN_COURS';
        case CLOTURE = 'CLOTURE';
    }
