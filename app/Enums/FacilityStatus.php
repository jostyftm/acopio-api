<?php

namespace App\Enums;

enum FacilityStatus: string
{
    case Operational = 'operational';
    case Full = 'full';
    case Closed = 'closed';
}
