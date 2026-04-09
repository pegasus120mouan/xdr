<?php

namespace App\Http\Controllers;

use App\Models\DetectionRule;
use App\Models\SecurityAlert;
use App\Models\BlockedIp;
use App\Models\LoginAttempt;
use Illuminate\Http\Request;

class DetectionController extends Controller
{
    public function rules()
    {
        $rules = DetectionRule::orderBy('category')
            ->orderBy('severity')
            ->get()
            ->groupBy('category');

        $categories = DetectionRule::getCategories();
        $severities = DetectionRule::getSeverities();

        return view('detections.rules', compact('rules', 'categories', 'severities'));
    }

    public function toggleRule(DetectionRule $rule)
    {
        if ($rule->is_system) {
            $rule->update(['is_active' => !$rule->is_active]);
        }

        return back()->with('success', 'Rule status updated successfully.');
    }

    public function updateRule(Request $request, DetectionRule $rule)
    {
        $validated = $request->validate([
            'threshold' => 'required|integer|min:1',
            'time_window' => 'required|integer|min:60',
            'severity' => 'required|in:critical,high,medium,low',
        ]);

        $rule->update([
            'threshold' => $validated['threshold'],
            'time_window' => $validated['time_window'],
            'severity' => $validated['severity'],
        ]);

        return back()->with('success', 'Rule updated successfully.');
    }

    public function alerts(Request $request)
    {
        $query = SecurityAlert::with('rule')
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('category')) {
            $query->whereHas('rule', fn($q) => $q->where('category', $request->category));
        }

        $alerts = $query->paginate(20);
        $statuses = SecurityAlert::getStatuses();
        $severities = DetectionRule::getSeverities();
        $categories = DetectionRule::getCategories();

        // Stats
        $stats = [
            'total' => SecurityAlert::count(),
            'new' => SecurityAlert::where('status', 'new')->count(),
            'critical' => SecurityAlert::where('severity', 'critical')->whereIn('status', ['new', 'investigating'])->count(),
            'resolved_today' => SecurityAlert::where('status', 'resolved')
                ->whereDate('resolved_at', today())
                ->count(),
        ];

        return view('detections.alerts', compact('alerts', 'statuses', 'severities', 'categories', 'stats'));
    }

    public function showAlert(SecurityAlert $alert)
    {
        $alert->load(['rule', 'assignedUser', 'resolvedByUser']);
        return view('detections.alert-detail', compact('alert'));
    }

    public function updateAlertStatus(Request $request, SecurityAlert $alert)
    {
        $validated = $request->validate([
            'status' => 'required|in:new,investigating,resolved,false_positive,escalated',
            'resolution_notes' => 'nullable|string',
        ]);

        $data = ['status' => $validated['status']];

        if (in_array($validated['status'], ['resolved', 'false_positive'])) {
            $data['resolved_by'] = auth()->id();
            $data['resolved_at'] = now();
            $data['resolution_notes'] = $validated['resolution_notes'] ?? null;
        }

        $alert->update($data);

        return back()->with('success', 'Alert status updated.');
    }

    public function blockedIps()
    {
        $blockedIps = BlockedIp::with(['alert', 'blockedByUser'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('detections.blocked-ips', compact('blockedIps'));
    }

    public function unblockIp(BlockedIp $blockedIp)
    {
        $blockedIp->update(['is_active' => false]);
        return back()->with('success', 'IP unblocked successfully.');
    }

    public function blockIp(Request $request)
    {
        $validated = $request->validate([
            'ip_address' => 'required|ip',
            'reason' => 'required|string|max:255',
            'duration' => 'nullable|integer|min:1',
        ]);

        BlockedIp::block(
            $validated['ip_address'],
            $validated['reason'],
            null,
            auth()->id(),
            $validated['duration'] ?? null
        );

        return back()->with('success', 'IP blocked successfully.');
    }

    public function loginAttempts(Request $request)
    {
        $query = LoginAttempt::with('user')
            ->orderBy('attempted_at', 'desc');

        if ($request->filled('ip')) {
            $query->where('ip_address', 'like', "%{$request->ip}%");
        }

        if ($request->filled('email')) {
            $query->where('email', 'like', "%{$request->email}%");
        }

        if ($request->filled('status')) {
            $query->where('successful', $request->status === 'success');
        }

        $attempts = $query->paginate(50);

        $stats = [
            'total_today' => LoginAttempt::whereDate('attempted_at', today())->count(),
            'failed_today' => LoginAttempt::whereDate('attempted_at', today())->where('successful', false)->count(),
            'unique_ips' => LoginAttempt::whereDate('attempted_at', today())->distinct('ip_address')->count('ip_address'),
        ];

        return view('detections.login-attempts', compact('attempts', 'stats'));
    }
}
