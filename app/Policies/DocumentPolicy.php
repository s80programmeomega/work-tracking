<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Document;
use App\Models\DocumentPermission;
use App\Models\User;
use App\Permissions\ContextualPermissionGate;
use App\Permissions\Permission;

class DocumentPolicy
{
    public function __construct(protected ContextualPermissionGate $gate) {}

    public function view(User $user, Document $document): bool
    {
        if ($document->user_id === $user->id) {
            return true;
        }

        if ($this->hasExplicitPermission($user, $document, 'can_view')) {
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
        if ($document->user_id === $user->id) {
            return true;
        }

        return $this->hasExplicitPermission($user, $document, 'can_edit');
    }

    public function delete(User $user, Document $document): bool
    {
        if ($document->user_id === $user->id) {
            return true;
        }

        return $this->hasExplicitPermission($user, $document, 'can_delete');
    }

    public function download(User $user, Document $document): bool
    {
        if ($document->user_id === $user->id) {
            return true;
        }

        if ($this->hasExplicitPermission($user, $document, 'can_download')) {
            return true;
        }

        $resource = $document->documentable;
        if (! $resource) {
            return false;
        }

        return $this->gate->userCan($user, Permission::DOCUMENTS_VIEW, $resource);
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
        if ($document->user_id === $user->id) {
            return true;
        }

        if ($this->hasExplicitPermission($user, $document, 'can_share')) {
            return true;
        }

        $resource = $document->documentable;
        if (! $resource) {
            return false;
        }

        return $this->gate->userCan($user, Permission::DOCUMENTS_SHARE, $resource);
    }

    /** Vérifie qu'une permission explicite non expirée existe pour cet utilisateur. */
    private function hasExplicitPermission(User $user, Document $document, string $flag): bool
    {
        return DocumentPermission::where('document_id', $document->id)
            ->forUser($user->id)
            ->where($flag, true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }
}
