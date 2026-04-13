<?php

namespace App\Http\Controllers;

use App\Models\AgentLog;
use App\Models\BlockedIp;
use App\Models\LoginAttempt;
use App\Models\SecurityAlert;
use App\Services\OtxService;
use App\Services\OpenCtiService;
use App\Services\ThreatMinerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ThreatHuntingController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $since = $request->get('since', '168'); // hours, default 7d
        $sinceHours = min(720, max(1, (int) $since));

        $stats = [
            'pending_alerts' => SecurityAlert::whereIn('status', ['new', 'investigating'])->count(),
            'critical_open' => SecurityAlert::whereIn('status', ['new', 'investigating'])
                ->where('severity', 'critical')
                ->count(),
            'failed_logins_24h' => LoginAttempt::where('successful', false)
                ->where('attempted_at', '>=', now()->subDay())
                ->count(),
            'active_blocks' => BlockedIp::where('is_active', true)->count(),
        ];

        $topFailedIps = LoginAttempt::query()
            ->select('ip_address', DB::raw('COUNT(*) as fail_count'))
            ->where('successful', false)
            ->where('attempted_at', '>=', now()->subDay())
            ->groupBy('ip_address')
            ->orderByDesc('fail_count')
            ->limit(8)
            ->get();

        $recentCritical = SecurityAlert::with('rule')
            ->whereIn('status', ['new', 'investigating'])
            ->whereIn('severity', ['critical', 'high'])
            ->orderByDesc('last_seen')
            ->limit(6)
            ->get();

        $search = null;
        if ($q !== '') {
            $search = $this->runInvestigation($q, $sinceHours);
        }

        $threatMiner = null;
        $tmRtOptions = ThreatMinerService::rtLabelsFor('ip');
        if ($q !== '') {
            $classifiedPreview = app(ThreatMinerService::class)->classifyIndicator($q);
            if ($classifiedPreview) {
                $tmRtOptions = ThreatMinerService::rtLabelsFor($classifiedPreview['type']);
            }
        }

        if ($q !== '' && $request->boolean('tm')) {
            $svc = app(ThreatMinerService::class);
            $classified = $svc->classifyIndicator($q);
            $tmRt = max(1, (int) $request->get('tm_rt', 6));

            if ($classified) {
                $threatMiner = $svc->query($classified['type'], $classified['value'], $tmRt);
                $threatMiner['classified'] = $classified;
                $threatMiner['rt_labels'] = ThreatMinerService::rtLabelsFor($classified['type']);
            } else {
                $threatMiner = [
                    'ok' => false,
                    'reason' => 'indicator_not_supported',
                    'message' => 'ThreatMiner accepte une IP, un nom de domaine (FQDN) ou un hash d’échantillon (MD5 / SHA256).',
                ];
            }
        }

        $openCti = null;
        if ($q !== '' && $request->boolean('opencti')) {
            $ocFirst = max(5, min(100, (int) $request->get('opencti_first', config('opencti.default_first', 25))));
            $openCti = app(OpenCtiService::class)->search($q, $ocFirst);
        }

        $otx = null;
        if ($q !== '' && $request->boolean('otx')) {
            $otx = app(OtxService::class)->lookup($q, $request->boolean('otx_extended'));
        }

        return view('threat-hunting.index', compact(
            'q',
            'since',
            'sinceHours',
            'stats',
            'topFailedIps',
            'recentCritical',
            'search',
            'threatMiner',
            'tmRtOptions',
            'openCti',
            'otx'
        ));
    }

    /**
     * @return array<string, mixed>
     */
    protected function runInvestigation(string $q, int $sinceHours): array
    {
        $from = now()->subHours($sinceHours);
        $like = '%'.addcslashes($q, '%_\\').'%';

        $ip = filter_var($q, FILTER_VALIDATE_IP) ? $q : null;
        $email = filter_var($q, FILTER_VALIDATE_EMAIL) ? strtolower($q) : null;

        $blocked = BlockedIp::query()
            ->when($ip, fn ($q) => $q->where('ip_address', $ip))
            ->when(! $ip, fn ($q) => $q->where(function ($q) use ($like) {
                $q->where('ip_address', 'like', $like)->orWhere('reason', 'like', $like);
            }))
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $logins = LoginAttempt::query()
            ->where('attempted_at', '>=', $from)
            ->when($ip, fn ($q) => $q->where('ip_address', $ip))
            ->when($email && ! $ip, fn ($q) => $q->where('email', $email))
            ->when(! $ip && ! $email, function ($q) use ($like) {
                $q->where(function ($q) use ($like) {
                    $q->where('ip_address', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('user_agent', 'like', $like);
                });
            })
            ->orderByDesc('attempted_at')
            ->limit(75)
            ->get();

        $alerts = SecurityAlert::query()
            ->with('rule')
            ->where('created_at', '>=', $from)
            ->when($ip, function ($q) use ($ip) {
                $q->where(function ($q) use ($ip) {
                    $q->where('source_ip', $ip)->orWhere('target_ip', $ip);
                });
            })
            ->when($email && ! $ip, fn ($q) => $q->where(function ($q) use ($email) {
                $q->where('source_user', $email)->orWhere('target_user', $email);
            }))
            ->when(! $ip && ! $email, function ($q) use ($like) {
                $q->where(function ($q) use ($like) {
                    $q->where('title', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhere('source_ip', 'like', $like)
                        ->orWhere('source_user', 'like', $like)
                        ->orWhere('affected_asset', 'like', $like);
                });
            })
            ->orderByDesc('last_seen')
            ->limit(40)
            ->get();

        $agentLogs = AgentLog::query()
            ->with('agent:id,name,hostname,agent_id')
            ->where('created_at', '>=', $from)
            ->where('message', 'like', $like)
            ->orderByDesc('log_timestamp')
            ->orderByDesc('created_at')
            ->limit(40)
            ->get();

        return [
            'ip_mode' => $ip !== null,
            'email_mode' => $email !== null && $ip === null,
            'blocked' => $blocked,
            'logins' => $logins,
            'alerts' => $alerts,
            'agent_logs' => $agentLogs,
            'total_hits' => $blocked->count() + $logins->count() + $alerts->count() + $agentLogs->count(),
        ];
    }
}
