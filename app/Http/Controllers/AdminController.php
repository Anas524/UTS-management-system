<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function index()
    {
        $totalUsers       = User::count();

        // Count admins using either role=admin OR legacy is_admin=1
        $adminsCount      = User::where('role', 'admin')
            ->orWhere('is_admin', 1)
            ->count();

        // Optional: show consultants too (add a card in Blade if you want)
        $consultantsCount = User::where('role', 'consultant')->count();

        $recentUsers = User::latest()
            ->take(5)
            ->get(['id', 'name', 'email', 'created_at', 'role', 'is_admin']);

        return view('admin.index', compact(
            'totalUsers',
            'adminsCount',
            'consultantsCount',
            'recentUsers'
        ));
    }

    public function users(Request $request)
    {
        $q = trim((string) $request->get('q'));
        $users = User::when($q, function ($query) use ($q) {
            $query->where(function ($qq) use ($q) {
                $qq->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.users', compact('users', 'q'));
    }

    public function updateRole(Request $request, User $user)
    {
        // Do not allow changing your own role
        if (Auth::id() === $user->id) {
            return back()->withErrors('You cannot change your own role.');
        }

        // Accept either the new 'role' or legacy 'is_admin'
        if ($request->has('role')) {
            $data = $request->validate([
                'role' => ['required', 'in:user,consultant,admin'],
            ]);

            $user->role     = $data['role'];
            $user->is_admin = $data['role'] === 'admin' ? 1 : 0; // keep legacy flag in sync
            $user->save();

            return back()->with('status', 'Role updated to ' . ucfirst($data['role']) . '.');
        }

        // Legacy path (old forms still posting is_admin)
        $data = $request->validate([
            'is_admin' => ['required', 'boolean'],
        ]);

        $user->is_admin = $request->boolean('is_admin');
        // If promoting/demoting via legacy flag, reflect it in role when empty/standard
        if (in_array($user->role, [null, 'user', 'admin'], true)) {
            $user->role = $user->is_admin ? 'admin' : 'user';
        }
        $user->save();

        return back()->with('status', 'Role updated.');
    }

    public function destroy(User $user)
    {
        $me = Auth::user(); // no red underline

        abort_unless($me && $me->is_admin, 403);

        if ($me->id === $user->id) {
            return back()->withErrors(['You cannot delete your own account.']);
        }

        // optional safety (recommended)
        if ($user->is_admin) {
            return back()->withErrors(['You cannot delete an admin account.']);
        }

        $user->delete();

        return back()->with('status', 'User deleted successfully.');
    }
}
