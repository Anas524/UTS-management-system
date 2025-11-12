<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PurchaseOrder;

class PurchaseOrderPolicy
{
    // Admins can do anything
    public function before(User $user, $ability)
    {
        if ($user->is_admin ?? false) return true;
    }

    public function viewAny(User $user): bool
    {
        // Everyone can list; controller will filter by role
        return true;
    }

    public function view(User $user, PurchaseOrder $po): bool
    {
        // Owner or consultant can view
        return $po->user_id === $user->id || ($user->role ?? null) === 'consultant';
    }

    public function create(User $user): bool
    {
        // Consultants are read-only
        return ($user->role ?? 'user') !== 'consultant';
    }

    public function update(User $user, PurchaseOrder $po): bool
    {
        // Only owner (or admin via before) can update
        return $po->user_id === $user->id && ($user->role ?? 'user') !== 'consultant';
    }

    public function delete(User $user, PurchaseOrder $po): bool
    {
        return $po->user_id === $user->id && ($user->role ?? 'user') !== 'consultant';
    }

    // Match expense sheet pattern
    public function export(User $user): bool
    {
        return true; // consultants can export/download
    }

    public function download(User $user, PurchaseOrder $po): bool
    {
        return $this->view($user, $po);
    }
}
