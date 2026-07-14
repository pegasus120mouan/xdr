<?php

namespace App\Console\Commands;

use App\Models\AgentLog;
use App\Models\LoginAttempt;
use App\Models\SecurityAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PruneRetention extends Command
{
    protected $signature = 'xdr:prune
                            {--dry-run : Afficher le volume sans supprimer}';

    protected $description = 'Purge agent_logs, alertes résolues, login attempts et audit logs selon la rétention config/xdr.php';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $cfg = config('xdr.retention');

        $jobs = [
            'agent_logs' => [
                'days' => (int) ($cfg['agent_logs_days'] ?? 30),
                'run' => function (int $days) {
                    return AgentLog::where('created_at', '<', now()->subDays($days))->delete();
                },
                'count' => function (int $days) {
                    return AgentLog::where('created_at', '<', now()->subDays($days))->count();
                },
            ],
            'resolved_alerts' => [
                'days' => (int) ($cfg['resolved_alerts_days'] ?? 90),
                'run' => function (int $days) {
                    return SecurityAlert::query()
                        ->where('status', 'resolved')
                        ->where(function ($q) use ($days) {
                            $q->where('resolved_at', '<', now()->subDays($days))
                                ->orWhere(function ($q2) use ($days) {
                                    $q2->whereNull('resolved_at')
                                        ->where('updated_at', '<', now()->subDays($days));
                                });
                        })
                        ->delete();
                },
                'count' => function (int $days) {
                    return SecurityAlert::query()
                        ->where('status', 'resolved')
                        ->where(function ($q) use ($days) {
                            $q->where('resolved_at', '<', now()->subDays($days))
                                ->orWhere(function ($q2) use ($days) {
                                    $q2->whereNull('resolved_at')
                                        ->where('updated_at', '<', now()->subDays($days));
                                });
                        })
                        ->count();
                },
            ],
            'login_attempts' => [
                'days' => (int) ($cfg['login_attempts_days'] ?? 60),
                'run' => function (int $days) {
                    return LoginAttempt::where('attempted_at', '<', now()->subDays($days))->delete();
                },
                'count' => function (int $days) {
                    return LoginAttempt::where('attempted_at', '<', now()->subDays($days))->count();
                },
            ],
            'audit_logs' => [
                'days' => (int) ($cfg['audit_logs_days'] ?? 180),
                'run' => function (int $days) {
                    if (! Schema::hasTable('security_audit_logs')) {
                        return 0;
                    }

                    return DB::table('security_audit_logs')
                        ->where('created_at', '<', now()->subDays($days))
                        ->delete();
                },
                'count' => function (int $days) {
                    if (! Schema::hasTable('security_audit_logs')) {
                        return 0;
                    }

                    return DB::table('security_audit_logs')
                        ->where('created_at', '<', now()->subDays($days))
                        ->count();
                },
            ],
        ];

        foreach ($jobs as $name => $job) {
            $days = max(1, $job['days']);
            $count = $job['count']($days);
            if ($dry) {
                $this->line("[dry-run] {$name}: {$count} ligne(s) > {$days}j");
                continue;
            }
            $deleted = $job['run']($days);
            $this->info("{$name}: {$deleted} supprimé(s) (rétention {$days}j).");
        }

        return self::SUCCESS;
    }
}
