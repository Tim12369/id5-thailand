<?php

namespace App\Policies;

use App\Models\CommandCenterIncident;
use App\Models\User;

class CommandCenterPolicy
{
    public function view(User $user): bool
    {
        return $user->hasPermission('command-center.view');
    }

    public function manage(User $user): bool
    {
        return $user->hasPermission('command-center.manage');
    }

    /**
     * Officers scoped to a single office may only act on incidents
     * belonging to their own station.
     */
    public function updateIncident(User $user, CommandCenterIncident $incident): bool
    {
        if (!$this->manage($user)) {
            return false;
        }

        if ($user->isScopedToOwnStation()) {
            return $incident->station_id === $user->station_id;
        }

        return true;
    }
}
