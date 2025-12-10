<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use App\Services\DocumentAccessResolver;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentPolicy
{
    use HandlesAuthorization;

    protected DocumentAccessResolver $accessResolver;

    public function __construct(DocumentAccessResolver $accessResolver)
    {
        $this->accessResolver = $accessResolver;
    }

    /**
     * Determine if user can create documents for an entity
     */
    public function create(User $user, string $entityType, int $entityId): bool
    {
        return $this->accessResolver->canUpload($user, $entityType, $entityId);
    }

    /**
     * Determine if user can view the document
     */
    public function view(User $user, Document $document): bool
    {
        return $this->accessResolver->canView($user, $document);
    }

    /**
     * Determine if user can download the document
     */
    public function download(User $user, Document $document): bool
    {
        return $this->accessResolver->canDownload($user, $document);
    }

    /**
     * Determine if user can update the document
     */
    public function update(User $user, Document $document): bool
    {
        return $this->accessResolver->canEdit($user, $document);
    }

    /**
     * Determine if user can delete the document
     */
    public function delete(User $user, Document $document): bool
    {
        return $this->accessResolver->canDelete($user, $document);
    }

    /**
     * Determine if user can share the document
     */
    public function share(User $user, Document $document): bool
    {
        return $this->accessResolver->canShare($user, $document);
    }

    /**
     * Determine if user can manage permissions
     */
    public function managePermissions(User $user, Document $document): bool
    {
        // Only owner can manage permissions
        return $user->id === $document->user_id || $user->isSuperAdmin();
    }
}