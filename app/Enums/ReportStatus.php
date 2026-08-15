<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Pending = 'pending';
    case Found = 'found';
    case Closed = 'closed';
}
