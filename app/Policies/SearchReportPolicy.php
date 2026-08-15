<?php

namespace App\Policies;

use App\Models\SearchReport;
use App\Models\User;

class SearchReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('search-reports.view');
    }

    public function view(User $user, SearchReport $searchReport): bool
    {
        return $user->can('search-reports.view');
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function manage(User $user, SearchReport $searchReport): bool
    {
        return $user->can('search-reports.manage');
    }
}
