<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use App\Permissions\ContextualPermissionGate;
use App\Permissions\Permission;

class DocumentPolicy
{
    public function __construct(protected ContextualPermissionGate $gate) {}

    public function view(User $user, Document $document): bool
    {
        // Uploader always has access to their own documents
        if ($document->uploaded_by === $user->id || $document->user_id === $user->id) {
            return true;
        }

        if ($document->visibility === 'public') {
            return true;
        }

        $resource = $document->documentable;
        if (! $resource) {
            return false;
        }

        return $this->gate->userCan($user, Permission::DOCUMENTS_VIEW, $resource);
    }

    public function update(User $user, Document $document): bool
    {
        // Only uploader can edit their document
        return $document->uploaded_by === $user->id || $document->user_id === $user->id;
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->update($user, $document);
    }

    public function upload(User $user, Document $document): bool
    {
        $resource = $document->documentable;
        if (! $resource) {
            return false;
        }

        return $this->gate->userCan($user, Permission::DOCUMENTS_UPLOAD, $resource);
    }

    public function share(User $user, Document $document): bool
    {
        $resource = $document->documentable;
        if (! $resource) {
            return false;
        }

        return $this->gate->userCan($user, Permission::DOCUMENTS_SHARE, $resource);
    }
}
