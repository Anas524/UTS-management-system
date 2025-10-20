<?php

namespace App\Policies;

use App\Models\ExpenseRow;
use App\Models\User;

class ExpenseRowPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ExpenseRow $expenseRow): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role !== 'consultant';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ExpenseRow $expenseRow): bool
    {
        return $user->role !== 'consultant';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ExpenseRow $expenseRow): bool
    {
        return $user->role !== 'consultant';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ExpenseRow $expenseRow): bool
    {
        return $user->is_admin === 1;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ExpenseRow $expenseRow): bool
    {
        return $user->is_admin === 1;
    }

    public function download(User $user, ExpenseRow $row): bool
    {
        return true; // allow viewing/downloading attachments
    }
}
