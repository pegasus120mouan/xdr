<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\Asset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DedupeAssets extends Command
{
    protected $signature = 'xdr:dedupe-assets {--dry-run : Lister sans supprimer}';

    protected $description = 'Fusionne les assets en double (même hostname + groupe), garde le plus récent';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $removed = 0;

        $groups = Asset::query()
            ->select('hostname', 'tenant_group_id', DB::raw('COUNT(*) as c'))
            ->groupBy('hostname', 'tenant_group_id')
            ->having('c', '>', 1)
            ->get();

        if ($groups->isEmpty()) {
            $this->info('Aucun doublon trouvé.');

            return self::SUCCESS;
        }

        foreach ($groups as $row) {
            $dupes = Asset::query()
                ->where('hostname', $row->hostname)
                ->when(
                    $row->tenant_group_id === null,
                    fn ($q) => $q->whereNull('tenant_group_id'),
                    fn ($q) => $q->where('tenant_group_id', $row->tenant_group_id)
                )
                ->orderByDesc('last_seen')
                ->orderByDesc('id')
                ->get();

            $keeper = $dupes->first();
            $toDelete = $dupes->slice(1);

            $this->line(sprintf(
                '%s (group=%s) → keep #%d, remove %s',
                $row->hostname,
                $row->tenant_group_id ?? 'null',
                $keeper->id,
                $toDelete->pluck('id')->implode(',')
            ));

            if ($dry) {
                continue;
            }

            foreach ($toDelete as $asset) {
                Agent::where('asset_id', $asset->id)->update(['asset_id' => $keeper->id]);
                $asset->delete();
                $removed++;
            }

            // Relier les agents orphelins du même hostname au keeper
            Agent::query()
                ->where('hostname', $keeper->hostname)
                ->where(function ($q) use ($keeper) {
                    $q->whereNull('asset_id')->orWhere('asset_id', '!=', $keeper->id);
                })
                ->when($keeper->tenant_group_id, fn ($q) => $q->where('tenant_group_id', $keeper->tenant_group_id))
                ->update(['asset_id' => $keeper->id]);
        }

        $this->info($dry
            ? 'Dry-run terminé (aucune suppression).'
            : "Doublons fusionnés : {$removed} asset(s) supprimé(s).");

        return self::SUCCESS;
    }
}
