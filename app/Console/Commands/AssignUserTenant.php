<?php

namespace App\Console\Commands;

use App\Models\TenantGroup;
use App\Models\User;
use Illuminate\Console\Command;

class AssignUserTenant extends Command
{
    protected $signature = 'xdr:assign-tenant
                            {email : Email de l’utilisateur}
                            {group : ID ou slug du tenant_group}
                            {--clear : Retirer le rattachement (accès plateforme)}';

    protected $description = 'Rattache un utilisateur à un espace tenant (isolement) ou le remet en accès global (--clear)';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('Utilisateur introuvable.');

            return self::FAILURE;
        }

        if ($this->option('clear')) {
            $user->update(['tenant_group_id' => null]);
            $this->info("{$user->email} → accès plateforme (tous les tenants).");

            return self::SUCCESS;
        }

        $groupArg = $this->argument('group');
        $group = TenantGroup::query()
            ->when(ctype_digit((string) $groupArg), fn ($q) => $q->where('id', (int) $groupArg))
            ->when(! ctype_digit((string) $groupArg), fn ($q) => $q->where('slug', $groupArg)->orWhere('name', $groupArg))
            ->first();

        if (! $group) {
            $this->error('Groupe tenant introuvable.');

            return self::FAILURE;
        }

        $user->update(['tenant_group_id' => $group->id]);
        $this->info("{$user->email} → tenant « {$group->name} » (id {$group->id}).");

        return self::SUCCESS;
    }
}
