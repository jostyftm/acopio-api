<?php

namespace App\Support\Enums;

enum SpatieRole: string
{
    case Admin = 'admin';
    case OrgAdmin = 'org_admin';
    case Operator = 'operator';
    case Viewer = 'viewer';
}
