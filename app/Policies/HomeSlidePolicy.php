<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\HomeSlide;
use Illuminate\Auth\Access\HandlesAuthorization;

class HomeSlidePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:HomeSlide');
    }

    public function view(AuthUser $authUser, HomeSlide $homeSlide): bool
    {
        return $authUser->can('View:HomeSlide');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:HomeSlide');
    }

    public function update(AuthUser $authUser, HomeSlide $homeSlide): bool
    {
        return $authUser->can('Update:HomeSlide');
    }

    public function delete(AuthUser $authUser, HomeSlide $homeSlide): bool
    {
        return $authUser->can('Delete:HomeSlide');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:HomeSlide');
    }

    public function restore(AuthUser $authUser, HomeSlide $homeSlide): bool
    {
        return $authUser->can('Restore:HomeSlide');
    }

    public function forceDelete(AuthUser $authUser, HomeSlide $homeSlide): bool
    {
        return $authUser->can('ForceDelete:HomeSlide');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:HomeSlide');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:HomeSlide');
    }

    public function replicate(AuthUser $authUser, HomeSlide $homeSlide): bool
    {
        return $authUser->can('Replicate:HomeSlide');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:HomeSlide');
    }

}