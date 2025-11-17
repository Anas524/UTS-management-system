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
        // no edits if the sheet (month) is closed
        return $user->role !== 'consultant' && !$expenseSheet->is_closed;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ExpenseSheet $expenseSheet): bool
    {
        // no deletes if the sheet is closed
        return $user->role !== 'consultant' && !$expenseSheet->is_closed;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ExpenseSheet $expenseSheet): bool
    {
        return (int)($user->is_admin ?? 0) === 1;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ExpenseSheet $expenseSheet): bool
    {
        return (int)($user->is_admin ?? 0) === 1;
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

    // Used as: $this->authorize('closeYear', ExpenseSheet::class);
    public function closeYear(User $user): bool
    {
        return (int)($user->is_admin ?? 0) === 1;
    }

    public function reopenYear(User $user): bool
    {
        return (int)($user->is_admin ?? 0) === 1;
    }

    public function openNextYear(User $user): bool
    {
        return (int)($user->is_admin ?? 0) === 1;
    }
}
