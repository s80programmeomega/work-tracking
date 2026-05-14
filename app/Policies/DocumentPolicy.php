<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use App\Services\PermissionService;

class DocumentPolicy
{
    public function __construct(protected PermissionService $permissionService) {}

    public function view(User $user, Document $document): bool
    {
        return $this->permissionService->canViewDocument($user, $document);
    }

    public function update(User $user, Document $document): bool
    {
        return $this->permissionService->canEditDocument($user, $document);
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->permissionService->canDeleteDocument($user, $document);
    }
}
