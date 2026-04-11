<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Wara XDR')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #0f1419;
            color: #e2e8f0;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 220px;
            height: 100vh;
            background: #1a1f2e;
            border-right: 1px solid #2d3748;
            overflow-y: auto;
            z-index: 100;
        }

        .sidebar-header {
            padding: 16px 20px;
            border-bottom: 1px solid #2d3748;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-logo {
            font-size: 1.2rem;
            font-weight: 700;
            color: #fff;
        }

        .sidebar-logo span {
            color: #00d4ff;
        }

        .nav-section {
            padding: 10px 0;
        }

        .nav-section-title {
            padding: 8px 20px;
            font-size: 0.7rem;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 1px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.2s;
            cursor: pointer;
        }

        .nav-item:hover {
            background: rgba(0, 212, 255, 0.1);
            color: #fff;
        }

        .nav-item.active {
            background: linear-gradient(90deg, rgba(0, 212, 255, 0.2) 0%, transparent 100%);
            color: #00d4ff;
            border-left: 3px solid #00d4ff;
        }

        .nav-item .icon {
            width: 18px;
            text-align: center;
        }

        .nav-item .badge {
            margin-left: auto;
            padding: 2px 6px;
            background: #ef4444;
            border-radius: 10px;
            font-size: 0.7rem;
            color: #fff;
        }

        .nav-item .badge.new {
            background: #22c55e;
        }

        .nav-submenu {
            padding-left: 48px;
        }

        .nav-submenu .nav-item {
            padding: 8px 20px 8px 0;
            font-size: 0.8rem;
        }

        /* Main Content */
        .main-content {
            margin-left: 220px;
            min-height: 100vh;
        }

        /* Top Bar */
        .topbar {
            background: #1a1f2e;
            border-bottom: 1px solid #2d3748;
            padding: 0 24px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-stats {
            display: flex;
            align-items: center;
            gap: 24px;
            font-size: 0.8rem;
        }

        .topbar-stat {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .topbar-stat .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .topbar-stat .dot.green { background: #22c55e; }
        .topbar-stat .dot.orange { background: #f97316; }
        .topbar-stat .dot.blue { background: #3b82f6; }
        .topbar-stat .dot.cyan { background: #00d4ff; }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .topbar-link {
            color: #00d4ff;
            text-decoration: none;
            font-size: 0.8rem;
        }

        /* Alert Banner */
        .alert-banner {
            background: rgba(234, 179, 8, 0.1);
            border-bottom: 1px solid rgba(234, 179, 8, 0.3);
            padding: 8px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.8rem;
        }

        .alert-banner .icon {
            color: #eab308;
        }

        .alert-banner a {
            color: #00d4ff;
            text-decoration: none;
        }

        /* Status Bar */
        .status-bar {
            background: #151a24;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            gap: 32px;
            border-bottom: 1px solid #2d3748;
            font-size: 0.8rem;
            flex-wrap: wrap;
        }

        .status-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-item .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-item .value {
            font-weight: 600;
            color: #fff;
        }

        /* Page Content */
        .page-content {
            padding: 24px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .page-title {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .date-range {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: #94a3b8;
        }

        .date-input {
            background: #1a1f2e;
            border: 1px solid #2d3748;
            border-radius: 4px;
            padding: 6px 12px;
            color: #e2e8f0;
            font-size: 0.8rem;
        }

        /* Cards Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }

        .card {
            background: #1a1f2e;
            border: 1px solid #2d3748;
            border-radius: 8px;
            padding: 20px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .card-title {
            font-size: 0.95rem;
            font-weight: 600;
        }

        .card-actions {
            display: flex;
            gap: 8px;
        }

        .btn-tab {
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 0.75rem;
            border: 1px solid #2d3748;
            background: transparent;
            color: #94a3b8;
            cursor: pointer;
        }

        .btn-tab.active {
            background: #00d4ff;
            border-color: #00d4ff;
            color: #000;
        }

        /* Stats Row */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: #1a1f2e;
            border: 1px solid #2d3748;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 4px;
        }

        .stat-value.success { color: #22c55e; }
        .stat-value.warning { color: #f97316; }
        .stat-value.danger { color: #ef4444; }
        .stat-value.info { color: #00d4ff; }

        .stat-label {
            font-size: 0.75rem;
            color: #64748b;
        }

        /* Progress Card */
        .progress-card {
            background: linear-gradient(135deg, #1e3a5f 0%, #1a1f2e 100%);
            border: 1px solid #2d3748;
            border-radius: 8px;
            padding: 20px;
        }

        .progress-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }

        .progress-badge {
            background: #22c55e;
            color: #fff;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .progress-text {
            font-size: 0.85rem;
        }

        .progress-text span {
            color: #f97316;
            font-weight: 600;
        }

        /* Donut Chart Container */
        .donut-container {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .donut-chart {
            position: relative;
            width: 120px;
            height: 120px;
        }

        .donut-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }

        .donut-value {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .donut-label {
            font-size: 0.7rem;
            color: #64748b;
        }

        .donut-legend {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
        }

        .legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .legend-dot.critical { background: #ef4444; }
        .legend-dot.high { background: #f97316; }
        .legend-dot.medium { background: #eab308; }
        .legend-dot.low { background: #22c55e; }

        /* Line Chart */
        .chart-container {
            height: 150px;
            margin-top: 16px;
        }

        /* Auto Containment */
        .containment-card {
            position: relative;
        }

        .containment-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .mode-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            background: rgba(34, 197, 94, 0.2);
            border: 1px solid #22c55e;
            border-radius: 20px;
            font-size: 0.75rem;
            color: #22c55e;
        }

        .mode-warning {
            background: rgba(234, 179, 8, 0.1);
            border: 1px solid rgba(234, 179, 8, 0.3);
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 0.75rem;
            color: #eab308;
            margin-bottom: 16px;
        }

        .containment-visual {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .threat-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(239, 68, 68, 0.2);
            border: 2px solid #ef4444;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .threat-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: #ef4444;
        }

        .threat-label {
            font-size: 0.65rem;
            color: #94a3b8;
            text-align: center;
        }

        .block-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(34, 197, 94, 0.2);
            border: 2px solid #22c55e;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .block-value {
            font-size: 1rem;
            font-weight: 700;
            color: #22c55e;
        }

        .threat-entities {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #2d3748;
        }

        .entity-title {
            font-size: 0.8rem;
            color: #64748b;
            margin-bottom: 12px;
        }

        .entity-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .entity-item {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
        }

        .entity-value {
            font-weight: 600;
            color: #fff;
        }

        /* Asset Protection */
        .asset-section {
            margin-top: 24px;
        }

        .asset-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .asset-title {
            font-size: 1rem;
            font-weight: 600;
        }

        .asset-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .asset-card {
            background: #1a1f2e;
            border: 1px solid #2d3748;
            border-radius: 8px;
            padding: 20px;
        }

        .asset-stat {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .asset-icon {
            width: 48px;
            height: 48px;
            background: rgba(0, 212, 255, 0.1);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .asset-info .value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
        }

        .asset-info .label {
            font-size: 0.75rem;
            color: #64748b;
        }

        /* User Menu */
        .user-menu {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #00d4ff 0%, #0066cc 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .notification-btn {
            position: relative;
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 1.1rem;
        }

        .notification-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            width: 16px;
            height: 16px;
            background: #ef4444;
            border-radius: 50%;
            font-size: 0.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        /* Trend Chart */
        .trend-chart {
            height: 120px;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #1a1f2e;
        }

        ::-webkit-scrollbar-thumb {
            background: #3d4a5c;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #4d5a6c;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <span class="sidebar-logo">Athena <span>XDR</span></span>
        </div>

        <nav class="nav-section">
            <a href="#" class="nav-item">
                <span class="icon">📊</span>
                Functions
            </a>
            <a href="#" class="nav-item">
                <span class="icon">⭐</span>
                Favorites
            </a>
            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="icon">🏠</span>
                Home
            </a>
            <div class="nav-submenu">
                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">Monitor Overview</a>
                <a href="#" class="nav-item">Guarding Overview</a>
                <a href="{{ route('monitor.monitors') }}" target="_blank" rel="noopener noreferrer" class="nav-item">Monitors</a>
                <a href="{{ route('monitor.attack-map') }}" target="_blank" rel="noopener noreferrer" class="nav-item">Map Attacks</a>
                <a href="#" class="nav-item">Reports</a>
            </div>
            <a href="#" class="nav-item">
                <span class="icon">🔍</span>
                Detections
            </a>
            <div class="nav-submenu">
                <a href="{{ route('detection.rules') }}" class="nav-item {{ request()->routeIs('detection.rules') ? 'active' : '' }}">Detection Rules</a>
                <a href="{{ route('detection.alerts') }}" class="nav-item {{ request()->routeIs('detection.alerts*') ? 'active' : '' }}">Security Alerts <span class="badge new">NEW</span></a>
                <a href="{{ route('detection.login-attempts') }}" class="nav-item {{ request()->routeIs('detection.login-attempts') ? 'active' : '' }}">Login Attempts</a>
                <a href="{{ route('detection.blocked-ips') }}" class="nav-item {{ request()->routeIs('detection.blocked-ips') ? 'active' : '' }}">Blocked IPs</a>
                <a href="#" class="nav-item">Security Logs</a>
            </div>
            <a href="#" class="nav-item">
                <span class="icon">🤖</span>
                AI Learning <span class="badge new">NEW</span>
            </a>
            <a href="#" class="nav-item">
                <span class="icon">🛡️</span>
                Responses
            </a>
            <div class="nav-submenu">
                <a href="#" class="nav-item">Auto Containment <span class="badge new">NEW</span></a>
                <a href="#" class="nav-item">Responses</a>
                <a href="#" class="nav-item">SOAR</a>
                <a href="#" class="nav-item">Ticketing System</a>
            </div>
            <a href="#" class="nav-item">
                <span class="icon">⚠️</span>
                Risk Management
            </a>
            <div class="nav-submenu">
                <a href="#" class="nav-item">Weaknesses</a>
                <a href="#" class="nav-item">Asset Check 🔥</a>
                <a href="#" class="nav-item">Risky Apps</a>
            </div>
            <a href="#" class="nav-item">
                <span class="icon">🎯</span>
                Threat Hunting Tools
            </a>
            <a href="{{ route('tenants.index') }}" class="nav-item {{ request()->routeIs('tenants.*') ? 'active' : '' }}">
                <span class="icon">👥</span>
                All Tenants
            </a>
            <a href="{{ route('agents.index') }}" class="nav-item {{ request()->routeIs('agents.*') ? 'active' : '' }}">
                <span class="icon">📡</span>
                Log Agents
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar -->
        <header class="topbar">
            <div class="topbar-stats">
                <div class="topbar-stat">
                    <span>Logs</span>
                    <span class="dot cyan"></span>
                    <strong>23.6 m</strong>
                    <span style="color: #22c55e;">99.9%▲</span>
                </div>
                <div class="topbar-stat">
                    <span>Alerts</span>
                    <strong>30.1 k</strong>
                    <span style="color: #22c55e;">99.3%▲</span>
                </div>
                <div class="topbar-stat">
                    <span>Incidents</span>
                    <strong style="color: #f97316;">219</strong>
                </div>
            </div>
            <div class="topbar-right">
                <a href="#" class="topbar-link">Licensed Services</a>
                <a href="#" class="topbar-link">Ticketing Workflow Management</a>
                <button class="notification-btn">
                    🔔
                    <span class="notification-badge">3</span>
                </button>
                <div class="user-menu">
                    <div class="user-avatar">{{ substr(Auth::user()->name, 0, 1) }}</div>
                </div>
                <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" style="background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 0.8rem;">Déconnexion</button>
                </form>
            </div>
        </header>

        <!-- Alert Banner -->
        <div class="alert-banner">
            <span class="icon">⚠️</span>
            <span>The current response allowlist lacks valid data, and auto containment is downgraded to monitoring mode. Please handle it promptly to prevent high network risks.</span>
            <a href="#">Go to Allowlist</a>
        </div>

        <!-- Status Bar -->
        <div class="status-bar">
            <div class="status-item">
                <span class="dot" style="background: #22c55e;"></span>
                <span>Native Devices:</span>
                <span class="value">70</span>
            </div>
            <div class="status-item">
                <span class="dot" style="background: #f97316;"></span>
                <span>Offline & Alerting Devices:</span>
                <span class="value">41</span>
            </div>
            <div class="status-item">
                <span class="dot" style="background: #22c55e;"></span>
                <span>Network Secure:</span>
                <span class="value">23</span>
            </div>
            <div class="status-item">
                <span class="dot" style="background: #00d4ff;"></span>
                <span>Stealth Threat Analytics:</span>
                <span class="value">12</span>
            </div>
        </div>

        @yield('content')
    </main>

    @yield('scripts')
</body>
</html>
