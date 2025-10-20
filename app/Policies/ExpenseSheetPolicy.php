<?php

namespace App\Policies;

use App\Models\ExpenseSheet;
use App\Models\User;

class ExpenseSheetPolicy
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
    public function view(User $user, ExpenseSheet $expenseSheet): bool
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
    public function update(User $user, ExpenseSheet $expenseSheet): bool
    {
        return $user->role !== 'consultant';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ExpenseSheet $expenseSheet): bool
    {
        return $user->role !== 'consultant';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ExpenseSheet $expenseSheet): bool
    {
        return $user->is_admin === 1;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ExpenseSheet $expenseSheet): bool
    {
        return $user->is_admin === 1;
    }

    // custom permission: consultants can still export/download
    public function export(User $user): bool
    {
        return true;
    }

    public function download(User $user, ExpenseSheet $sheet): bool
    {
        return true;
    }
}
