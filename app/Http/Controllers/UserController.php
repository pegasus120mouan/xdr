<?php

namespace App\Http\Controllers;

use App\Models\TenantGroup;
use App\Models\User;
use App\Support\SecurityAudit;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    protected function ensurePlatformAdmin(): void
    {
        $user = auth()->user();
        if (! $user || ! $user->isAdmin() || ! $user->isPlatformUser()) {
            abort(403, 'Gestion des utilisateurs réservée aux administrateurs plateforme.');
        }
    }

    public function index(Request $request)
    {
        $this->ensurePlatformAdmin();

        $query = User::query()->with('tenantGroup')->orderBy('name');

        if ($request->filled('q')) {
            $term = '%'.$request->q.'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        if ($request->filled('tenant')) {
            if ($request->tenant === 'platform') {
                $query->whereNull('tenant_group_id');
            } else {
                $query->where('tenant_group_id', (int) $request->tenant);
            }
        }

        $users = $query->paginate(30)->withQueryString();
        $groups = TenantGroup::orderBy('name')->get(['id', 'name', 'slug', 'type']);

        return view('users.index', compact('users', 'groups'));
    }

    public function store(Request $request)
    {
        $this->ensurePlatformAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_ANALYST])],
            'tenant_group_id' => 'nullable|exists:tenant_groups,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'tenant_group_id' => $validated['tenant_group_id'] ?: null,
        ]);

        SecurityAudit::log('user.created', [
            'email' => $user->email,
            'role' => $user->role,
            'tenant_group_id' => $user->tenant_group_id,
        ], User::class, $user->id);

        return back()->with('success', 'Utilisateur créé : '.$user->email);
    }

    public function update(Request $request, User $user)
    {
        $this->ensurePlatformAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_ANALYST])],
            'tenant_group_id' => 'nullable|exists:tenant_groups,id',
        ]);

        // Empêcher de se retirer soi-même de l’accès plateforme / admin si on est le seul
        if ($user->id === auth()->id()) {
            if (($validated['tenant_group_id'] ?? null) !== null) {
                return back()->with('error', 'Vous ne pouvez pas restreindre votre propre compte à un tenant.');
            }
            if ($validated['role'] !== User::ROLE_ADMIN) {
                return back()->with('error', 'Vous ne pouvez pas retirer votre rôle administrateur.');
            }
        }

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'tenant_group_id' => $validated['tenant_group_id'] ?: null,
        ];

        if (! empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $user->update($data);
        TenantContext::forgetCacheForGroup($user->tenant_group_id ? (int) $user->tenant_group_id : null);

        SecurityAudit::log('user.updated', [
            'email' => $user->email,
            'role' => $user->role,
            'tenant_group_id' => $user->tenant_group_id,
        ], User::class, $user->id);

        return back()->with('success', 'Utilisateur mis à jour.');
    }

    public function destroy(User $user)
    {
        $this->ensurePlatformAdmin();

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Impossible de supprimer votre propre compte.');
        }

        $email = $user->email;
        $id = $user->id;
        $user->delete();

        SecurityAudit::log('user.deleted', [
            'email' => $email,
        ], User::class, $id);

        return back()->with('success', 'Utilisateur supprimé : '.$email);
    }
}
