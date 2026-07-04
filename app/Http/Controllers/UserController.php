<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::with('roles');

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(10)->withQueryString();

        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::all();
        return view('users.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Rules\Password::defaults()],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi 👤',
            'email.required' => 'Email wajib diisi ✉️',
            'email.email' => 'Format email tidak valid ✉️',
            'email.unique' => 'Email ini sudah terdaftar ✉️',
            'password.required' => 'Password wajib diisi 🔑',
            'password.min' => 'Password minimal terdiri dari 8 karakter 🔑',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if ($request->has('roles')) {
            $user->roles()->sync($request->roles);
        } else {
            // Assign default user role
            $user->assignRole('user');
        }

        return redirect()->route('users.index')
            ->with('success', 'User ' . $user->name . ' berhasil dibuat! 👤✨');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $roles = Role::all();
        $userRoleIds = $user->roles->pluck('id')->toArray();
        return view('users.edit', compact('user', 'roles', 'userRoleIds'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', Rules\Password::defaults()],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi 👤',
            'email.required' => 'Email wajib diisi ✉️',
            'email.email' => 'Format email tidak valid ✉️',
            'email.unique' => 'Email ini sudah terdaftar ✉️',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        if ($request->has('roles')) {
            $user->roles()->sync($request->roles);
        } else {
            $user->roles()->detach();
        }

        return redirect()->route('users.index')
            ->with('success', 'Informasi user ' . $user->name . ' berhasil diperbarui! 👤⚙️');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Security guard: prevent deleting current user
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')
                ->with('error', 'Keamanan Terjaga: Anda tidak diperbolehkan menghapus akun Anda sendiri! 🚫🛡️');
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User ' . $userName . ' berhasil dihapus dari sistem! 🗑️👋');
    }
}
