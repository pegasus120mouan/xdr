<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\Asset;
use App\Models\SecurityAlert;
use Illuminate\Console\Command;

class BackfillAlertTenants extends Command
{
    protected $signature = 'xdr:backfill-alert-tenants';

    protected $description = 'Rattache les alertes existantes à un tenant via agent hostname / IP cible';

    public function handle(): int
    {
        $updated = 0;
        SecurityAlert::query()
            ->whereNull('tenant_group_id')
            ->orderBy('id')
            ->chunkById(200, function ($alerts) use (&$updated) {
                foreach ($alerts as $alert) {
                    $gid = null;
                    if ($alert->affected_asset) {
                        $agent = Agent::where('hostname', $alert->affected_asset)->first();
                        $gid = $agent?->tenant_group_id;
                        if (! $gid) {
                            $gid = Asset::where('hostname', $alert->affected_asset)->value('tenant_group_id');
                        }
                    }
                    if (! $gid && $alert->target_ip) {
                        $gid = Asset::where('ip_address', $alert->target_ip)->value('tenant_group_id')
                            ?: Agent::where('ip_address', $alert->target_ip)->value('tenant_group_id');
                    }
                    if ($gid) {
                        $alert->update(['tenant_group_id' => $gid]);
                        $updated++;
                    }
                }
            });

        $this->info("Alertes mises à jour : {$updated}");

        return self::SUCCESS;
    }
}
