<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function index()
    {
        $totalUsers  = User::count();
        $adminsCount = User::where('is_admin', true)->count();
        $recentUsers = User::latest()->take(5)->get();

        return view('admin.index', compact('totalUsers','adminsCount','recentUsers'));
    }

    public function users(Request $request)
    {
        $q = trim((string) $request->get('q'));
        $users = User::when($q, function ($query) use ($q) {
                        $query->where(function($qq) use ($q){
                            $qq->where('name','like',"%{$q}%")
                               ->orWhere('email','like',"%{$q}%");
                        });
                  })
                  ->orderByDesc('created_at')
                  ->paginate(10)
                  ->withQueryString();

        return view('admin.users', compact('users','q'));
    }

    public function updateRole(User $user, Request $request)
    {
        // don't let someone change their own role
        if (Auth::id() === $user->id) {
            return back()->withErrors('You cannot change your own role.');
        }

        $request->validate(['is_admin' => 'required|boolean']);
        $user->update(['is_admin' => $request->boolean('is_admin')]);

        return back()->with('status', 'Role updated.');
    }
}
