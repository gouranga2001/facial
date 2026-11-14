<?php

namespace App\Policies;

use App\Models\Organisation;
use App\Models\User;

class OrganisationPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasRole('super_admin');
    }

    public function view(User $actor, Organisation $organisation): bool
    {
        return $actor->hasRole('super_admin')
            || $actor->organisation_id === $organisation->id;
    }

    public function update(User $actor, Organisation $organisation): bool
    {
        return $actor->hasRole('super_admin');
    }

    public function delete(User $actor, Organisation $organisation): bool
    {
        return $actor->hasRole('super_admin');
    }
}