<?php

namespace App\Http\Controllers;

use App\Models\BlockedIp;
use App\Models\DetectionRule;
use App\Models\SecurityAlert;
use App\Services\BruteForceDetector;

class ResponseController extends Controller
{
    public function index()
    {
        $rules = DetectionRule::orderBy('category')
            ->orderBy('name')
            ->get()
            ->filter(fn (DetectionRule $rule) => $this->hasAutomatedResponse($rule));

        $blockRules = $rules->filter(
            fn (DetectionRule $rule) => collect($rule->actions ?? [])->contains(
                fn (array $a) => ($a['type'] ?? '') === 'block_ip'
            )
        );

        $stats = [
            'rules_with_auto_block' => $blockRules->where('is_active', true)->count(),
            'system_blocked_ips' => BlockedIp::where('is_active', true)
                ->whereNull('blocked_by')
                ->whereNotNull('security_alert_id')
                ->count(),
            'active_blocked_ips' => BlockedIp::where('is_active', true)->count(),
        ];

        $categories = DetectionRule::getCategories();

        return view('responses.index', compact('rules', 'blockRules', 'stats', 'categories'));
    }

    public function autoContainment()
    {
        $bruteRules = DetectionRule::query()
            ->where('category', 'brute_force')
            ->orderBy('severity')
            ->orderBy('name')
            ->get();

        $blockingEnabled = $bruteRules
            ->where('is_active', true)
            ->contains(
                fn (DetectionRule $rule) => collect($rule->actions ?? [])->contains(
                    fn (array $a) => ($a['type'] ?? '') === 'block_ip'
                )
            );

        $detectorStats = app(BruteForceDetector::class)->getStats();

        return view('responses.auto-containment', compact('bruteRules', 'blockingEnabled', 'detectorStats'));
    }

    /**
     * Hub SOAR : vue d’ensemble et liens vers l’orchestration / réponses (playbooks, alertes, containment).
     */
    public function soar()
    {
        $alertStats = [
            'new' => SecurityAlert::where('status', 'new')->count(),
            'investigating' => SecurityAlert::where('status', 'investigating')->count(),
            'resolved_week' => SecurityAlert::where('status', 'resolved')
                ->where('resolved_at', '>=', now()->subDays(7))
                ->count(),
        ];

        return view('responses.soar', compact('alertStats'));
    }

    protected function hasAutomatedResponse(DetectionRule $rule): bool
    {
        foreach ($rule->actions ?? [] as $action) {
            $type = $action['type'] ?? '';
            if (in_array($type, ['block_ip', 'notify'], true)) {
                return true;
            }
        }

        return false;
    }
}
