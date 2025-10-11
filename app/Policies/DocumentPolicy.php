<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    /**
     * Determine if the user can view any documents.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the document.
     */
    public function view(User $user, Document $document): bool
    {
        return $document->canBeViewedBy($user);
    }

    /**
     * Determine if the user can create documents.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can update the document.
     */
    public function update(User $user, Document $document): bool
    {
        return $document->canBeEditedBy($user);
    }

    /**
     * Determine if the user can delete the document.
     */
    public function delete(User $user, Document $document): bool
    {
        return $document->canBeDeletedBy($user);
    }

    /**
     * Determine if the user can download the document.
     */
    public function download(User $user, Document $document): bool
    {
        return $document->canBeDownloadedBy($user);
    }

    /**
     * Determine if the user can manage permissions for the document.
     */
    public function managePermissions(User $user, Document $document): bool
    {
        // Only the owner can manage permissions
        return $document->user_id === $user->id;
    }

    /**
     * Determine if the user can create a new version of the document.
     */
    public function createVersion(User $user, Document $document): bool
    {
        return $document->canBeEditedBy($user);
    }

    /**
     * Determine if the user can restore the document.
     */
    public function restore(User $user, Document $document): bool
    {
        return $document->user_id === $user->id;
    }

    /**
     * Determine if the user can permanently delete the document.
     */
    public function forceDelete(User $user, Document $document): bool
    {
        return $document->user_id === $user->id;
    }
}
