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

    public function view(User $user, PurchaseOrder $po): bool
    {
        // Owner or consultant can view
        return $po->user_id === $user->id || ($user->role ?? null) === 'consultant';
    }

    public function create(User $user): bool
    {
        // Consultants are read-only; others can create
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
}
