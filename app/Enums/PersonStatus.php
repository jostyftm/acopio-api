<?php

namespace App\Enums;

enum PersonStatus: string
{
    case Registered = 'registered';
    case Verified = 'verified';
    case Located = 'located';
}
