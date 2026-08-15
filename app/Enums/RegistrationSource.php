<?php

namespace App\Enums;

enum RegistrationSource: string
{
    case Web = 'web';
    case Sms = 'sms';
}
