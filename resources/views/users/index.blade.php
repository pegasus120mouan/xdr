@extends('layouts.app')

@section('title', 'Utilisateurs - Wara XDR')

@section('content')
<div class="page-content users-page">
    <div class="page-header">
        <div>
            <h1 class="page-title">Utilisateurs</h1>
            <p class="users-sub">Création de comptes et rattachement à un espace tenant.</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="document.getElementById('createUserModal').style.display='flex'">
            + Nouvel utilisateur
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error">
            @foreach($errors->all() as $err)
                <div>{{ $err }}</div>
            @endforeach
        </div>
    @endif

    <form method="get" class="users-filters" action="{{ route('users.index') }}">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Nom ou e-mail…" class="users-input">
        <select name="tenant" class="users-select" onchange="this.form.submit()">
            <option value="">Tous les espaces</option>
            <option value="platform" @selected(request('tenant') === 'platform')>Plateforme (tous tenants)</option>
            @foreach($groups as $g)
                <option value="{{ $g->id }}" @selected((string) request('tenant') === (string) $g->id)>{{ $g->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-secondary btn-sm">Filtrer</button>
    </form>

    <div class="table-container users-table-wrap">
        <table class="data-table users-table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>E-mail</th>
                    <th>Rôle</th>
                    <th>Espace / Tenant</th>
                    <th>Créé</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                    <tr>
                        <td class="users-name">{{ $u->name }}</td>
                        <td class="users-mono">{{ $u->email }}</td>
                        <td>
                            <span class="users-role users-role--{{ $u->role }}">{{ $u->role === 'admin' ? 'Admin' : 'Analyste' }}</span>
                        </td>
                        <td>
                            @if($u->tenant_group_id)
                                <span class="users-tenant">{{ $u->tenantGroup?->name ?? ('#'.$u->tenant_group_id) }}</span>
                            @else
                                <span class="users-platform">Plateforme (tous)</span>
                            @endif
                        </td>
                        <td class="users-muted">{{ $u->created_at?->format('Y-m-d') }}</td>
                        <td class="users-actions">
                            <button type="button" class="btn btn-sm btn-secondary"
                                onclick="openEditUser({{ json_encode([
                                    'id' => $u->id,
                                    'name' => $u->name,
                                    'email' => $u->email,
                                    'role' => $u->role,
                                    'tenant_group_id' => $u->tenant_group_id,
                                    'update_url' => route('users.update', $u),
                                ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }})">
                                Modifier
                            </button>
                            @if($u->id !== auth()->id())
                                <form action="{{ route('users.destroy', $u) }}" method="POST" class="users-inline-form"
                                    onsubmit="return confirm('Supprimer cet utilisateur ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-state" style="text-align:center;padding:2rem;color:#64748b;">
                            Aucun utilisateur.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-container">
        {{ $users->withQueryString()->links() }}
    </div>
</div>

{{-- Create modal --}}
<div id="createUserModal" class="modal" style="display:none;" onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Nouvel utilisateur</h2>
            <button type="button" class="modal-close" onclick="document.getElementById('createUserModal').style.display='none'">&times;</button>
        </div>
        <form action="{{ route('users.store') }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label>Nom</label>
                    <input type="text" name="name" class="form-input" required value="{{ old('name') }}">
                </div>
                <div class="form-group">
                    <label>E-mail</label>
                    <input type="email" name="email" class="form-input" required value="{{ old('email') }}">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Mot de passe</label>
                        <input type="password" name="password" class="form-input" required autocomplete="new-password">
                    </div>
                    <div class="form-group">
                        <label>Confirmation</label>
                        <input type="password" name="password_confirmation" class="form-input" required autocomplete="new-password">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Rôle</label>
                        <select name="role" class="form-input" required>
                            <option value="analyst">Analyste</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tenant</label>
                        <select name="tenant_group_id" class="form-input">
                            <option value="">— Plateforme (tous les tenants) —</option>
                            @foreach($groups as $g)
                                <option value="{{ $g->id }}">{{ $g->name }}</option>
                            @endforeach
                        </select>
                        <p class="form-hint">Laisser vide = accès à toute la plateforme. Choisir un groupe = isolement à cet espace.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('createUserModal').style.display='none'">Annuler</button>
                <button type="submit" class="btn btn-primary">Créer</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit modal --}}
<div id="editUserModal" class="modal" style="display:none;" onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Modifier l’utilisateur</h2>
            <button type="button" class="modal-close" onclick="document.getElementById('editUserModal').style.display='none'">&times;</button>
        </div>
        <form id="editUserForm" method="POST" action="">
            @csrf
            @method('PATCH')
            <div class="modal-body">
                <div class="form-group">
                    <label>Nom</label>
                    <input type="text" name="name" id="edit_name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>E-mail</label>
                    <input type="email" name="email" id="edit_email" class="form-input" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Nouveau mot de passe (optionnel)</label>
                        <input type="password" name="password" class="form-input" autocomplete="new-password">
                    </div>
                    <div class="form-group">
                        <label>Confirmation</label>
                        <input type="password" name="password_confirmation" class="form-input" autocomplete="new-password">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Rôle</label>
                        <select name="role" id="edit_role" class="form-input" required>
                            <option value="analyst">Analyste</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tenant</label>
                        <select name="tenant_group_id" id="edit_tenant" class="form-input">
                            <option value="">— Plateforme (tous les tenants) —</option>
                            @foreach($groups as $g)
                                <option value="{{ $g->id }}">{{ $g->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('editUserModal').style.display='none'">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<style>
.users-sub { margin: 0.35rem 0 0; font-size: 0.82rem; color: #64748b; }
.users-filters {
    display: flex; flex-wrap: wrap; gap: 0.65rem; align-items: center;
    margin-bottom: 1.15rem;
}
.users-input, .users-select, .form-input {
    padding: 0.5rem 0.75rem;
    background: #0f1419;
    border: 1px solid #2d3748;
    border-radius: 6px;
    color: #e2e8f0;
    font-size: 0.85rem;
}
.users-input { min-width: 200px; }
.users-select { min-width: 180px; }
.users-table-wrap {
    background: #1a1f2e;
    border: 1px solid #2d3748;
    border-radius: 8px;
    overflow: hidden;
}
.users-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.users-table th {
    background: #151a24;
    text-align: left;
    padding: 12px 14px;
    color: #64748b;
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.users-table td {
    padding: 12px 14px;
    border-top: 1px solid #2d3748;
    vertical-align: middle;
}
.users-name { font-weight: 600; color: #f1f5f9; }
.users-mono { font-family: ui-monospace, monospace; font-size: 0.8rem; color: #94a3b8; }
.users-muted { color: #64748b; font-size: 0.8rem; }
.users-role {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: capitalize;
}
.users-role--admin { background: rgba(139, 92, 246, 0.2); color: #c4b5fd; }
.users-role--analyst { background: rgba(59, 130, 246, 0.2); color: #93c5fd; }
.users-tenant {
    color: #7dd3fc;
    font-weight: 500;
}
.users-platform { color: #94a3b8; font-size: 0.8rem; }
.users-actions { display: flex; flex-wrap: wrap; gap: 0.4rem; align-items: center; }
.users-inline-form { display: inline; margin: 0; }
.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 16px;
    font-size: 0.88rem;
}
.alert-success {
    background: rgba(34, 197, 94, 0.15);
    border: 1px solid rgba(34, 197, 94, 0.35);
    color: #86efac;
}
.alert-error {
    background: rgba(239, 68, 68, 0.12);
    border: 1px solid rgba(239, 68, 68, 0.35);
    color: #fca5a5;
}
.btn {
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    border: none;
    text-decoration: none;
    display: inline-block;
}
.btn-primary {
    background: linear-gradient(180deg, rgba(0, 212, 255, 0.35), rgba(0, 100, 180, 0.5));
    border: 1px solid #00d4ff;
    color: #e0f7ff;
}
.btn-secondary {
    background: #374151;
    color: #e2e8f0;
}
.btn-danger {
    background: rgba(239, 68, 68, 0.2);
    border: 1px solid rgba(239, 68, 68, 0.45);
    color: #fca5a5;
}
.btn-sm { padding: 6px 10px; font-size: 0.78rem; }
.modal {
    position: fixed; inset: 0; z-index: 4000;
    background: rgba(0, 0, 0, 0.65);
    align-items: center; justify-content: center;
    padding: 1rem;
}
.modal-content {
    background: #1a1f2e;
    border: 1px solid #2d3748;
    border-radius: 10px;
    width: 100%;
    max-width: 520px;
    max-height: 90vh;
    overflow: auto;
}
.modal-header {
    display: flex; justify-content: space-between; align-items: center;
    padding: 16px 20px; border-bottom: 1px solid #2d3748;
}
.modal-header h2 { margin: 0; font-size: 1.05rem; color: #f1f5f9; }
.modal-close {
    background: none; border: none; color: #94a3b8;
    font-size: 1.5rem; cursor: pointer; line-height: 1;
}
.modal-body { padding: 18px 20px; }
.modal-footer {
    padding: 14px 20px; border-top: 1px solid #2d3748;
    display: flex; justify-content: flex-end; gap: 0.5rem;
}
.form-group { margin-bottom: 14px; }
.form-group label {
    display: block; margin-bottom: 6px;
    font-size: 0.78rem; color: #94a3b8;
}
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
@media (max-width: 560px) { .form-row { grid-template-columns: 1fr; } }
.form-input { width: 100%; box-sizing: border-box; }
.form-hint { margin: 6px 0 0; font-size: 0.72rem; color: #64748b; line-height: 1.4; }
</style>
@endsection

@section('scripts')
<script>
function openEditUser(data) {
    var form = document.getElementById('editUserForm');
    form.action = data.update_url;
    document.getElementById('edit_name').value = data.name || '';
    document.getElementById('edit_email').value = data.email || '';
    document.getElementById('edit_role').value = data.role || 'analyst';
    document.getElementById('edit_tenant').value = data.tenant_group_id ? String(data.tenant_group_id) : '';
    document.getElementById('editUserModal').style.display = 'flex';
}
</script>
@endsection
