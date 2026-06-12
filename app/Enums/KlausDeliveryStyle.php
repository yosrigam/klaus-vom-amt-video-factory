<?php

namespace App\Enums;

enum KlausDeliveryStyle: string
{
    case NeutralObservation = 'NeutralObservation';
    case MildConcern = 'MildConcern';
    case Disappointed = 'Disappointed';
    case Punchline = 'Punchline';
    case BureaucraticClosure = 'BureaucraticClosure';
}
