<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BotConversation;
use Illuminate\Auth\Access\HandlesAuthorization;

class BotConversationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BotConversation');
    }

    public function view(AuthUser $authUser, BotConversation $botConversation): bool
    {
        return $authUser->can('View:BotConversation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BotConversation');
    }

    public function update(AuthUser $authUser, BotConversation $botConversation): bool
    {
        return $authUser->can('Update:BotConversation');
    }

    public function delete(AuthUser $authUser, BotConversation $botConversation): bool
    {
        return $authUser->can('Delete:BotConversation');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:BotConversation');
    }

    public function restore(AuthUser $authUser, BotConversation $botConversation): bool
    {
        return $authUser->can('Restore:BotConversation');
    }

    public function forceDelete(AuthUser $authUser, BotConversation $botConversation): bool
    {
        return $authUser->can('ForceDelete:BotConversation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BotConversation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BotConversation');
    }

    public function replicate(AuthUser $authUser, BotConversation $botConversation): bool
    {
        return $authUser->can('Replicate:BotConversation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BotConversation');
    }

}