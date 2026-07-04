<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::with(['permissions'])->withCount('users')->latest()->get();
        return view('roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $permissions = \App\Models\Permission::all();
        return view('roles.create', compact('permissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:roles'],
            'description' => ['nullable', 'string'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ], [
            'name.required' => 'Nama role wajib diisi 🛡️',
            'slug.required' => 'Slug role wajib diisi 🔑',
            'slug.alpha_dash' => 'Slug hanya boleh berisi huruf, angka, strip, dan underscore 🔑',
            'slug.unique' => 'Slug ini sudah terdaftar 🔑',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'slug' => strtolower($request->slug),
            'description' => $request->description,
        ]);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return redirect()->route('roles.index')
            ->with('success', 'Role ' . $role->name . ' berhasil ditambahkan! 🛡️✨');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        $permissions = \App\Models\Permission::all();
        $rolePermissionIds = $role->permissions->pluck('id')->toArray();
        return view('roles.edit', compact('role', 'permissions', 'rolePermissionIds'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        // Security checks: protect admin/user core role slugs from changing
        if (in_array($role->slug, ['admin', 'user']) && $request->slug !== $role->slug) {
            return back()->withErrors([
                'slug' => 'Slug untuk role bawaan sistem (admin/user) tidak boleh diubah! 🛡️🔒'
            ]);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:roles,slug,' . $role->id],
            'description' => ['nullable', 'string'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ], [
            'name.required' => 'Nama role wajib diisi 🛡️',
            'slug.required' => 'Slug role wajib diisi 🔑',
            'slug.alpha_dash' => 'Slug hanya boleh berisi huruf, angka, strip, dan underscore 🔑',
            'slug.unique' => 'Slug ini sudah terdaftar 🔑',
        ]);

        $role->name = $request->name;
        $role->slug = strtolower($request->slug);
        $role->description = $request->description;
        $role->save();

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        } else {
            $role->permissions()->detach();
        }

        return redirect()->route('roles.index')
            ->with('success', 'Role ' . $role->name . ' berhasil diperbarui! 🛡️⚙️');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        // Security guard: prevent deleting default admin/user roles
        if (in_array($role->slug, ['admin', 'user'])) {
            return redirect()->route('roles.index')
                ->with('error', 'Keamanan Terjaga: Role bawaan sistem (' . $role->name . ') tidak boleh dihapus! 🛡️🔒');
        }

        $roleName = $role->name;
        $role->delete();

        return redirect()->route('roles.index')
            ->with('success', 'Role ' . $roleName . ' berhasil dihapus dari sistem! 🗑️🛡️');
    }
}
