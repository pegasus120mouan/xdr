<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentLog;
use App\Models\TenantGroup;
use App\Support\SecurityAudit;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function index(Request $request)
    {
        $query = Agent::with('tenantGroup');

        if ($request->filled('group')) {
            $query->where('tenant_group_id', $request->group);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $agents = $query->orderBy('created_at', 'desc')->paginate(20);
        $groups = TenantGroup::orderBy('name')->get();

        $stats = [
            'total' => Agent::count(),
            'active' => Agent::where('status', 'active')->count(),
            'inactive' => Agent::where('status', 'inactive')->count(),
            'pending' => Agent::where('status', 'pending')->count(),
        ];

        return view('agents.index', compact('agents', 'groups', 'stats'));
    }

    public function create(Request $request)
    {
        $groups = TenantGroup::orderBy('name')->get();
        $selectedGroup = $request->filled('group') ? TenantGroup::find($request->group) : null;

        return view('agents.create', compact('groups', 'selectedGroup'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tenant_group_id' => 'required|exists:tenant_groups,id',
            'os_type' => 'required|in:linux,windows',
            'log_types' => 'nullable|array',
            'log_types.*' => 'string',
        ]);

        $defaultLogTypes = $validated['os_type'] === 'windows'
            ? ['eventlog', 'security']
            : ['syslog', 'auth'];
        $logTypes = ! empty($validated['log_types']) ? $validated['log_types'] : $defaultLogTypes;

        $agent = Agent::create([
            'agent_id' => Agent::generateAgentId(),
            'name' => $validated['name'],
            'hostname' => 'pending-registration',
            'tenant_group_id' => $validated['tenant_group_id'],
            'os_type' => $validated['os_type'],
            'api_key' => Agent::generateApiKey(),
            'status' => 'pending',
            'config' => [
                'log_types' => $logTypes,
                'send_interval' => 60,
                'batch_size' => 100,
            ],
        ]);

        return redirect()->route('agents.show', $agent)
            ->with('success', 'Agent created. Use the installation script to deploy.');
    }

    public function show(Agent $agent)
    {
        $agent->load('tenantGroup', 'logs');

        $recentLogs = $agent->logs()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $logStats = [
            'total' => $agent->logs()->count(),
            'today' => $agent->logs()->whereDate('created_at', today())->count(),
            'errors' => $agent->logs()->whereIn('severity', ['error', 'critical', 'alert', 'emergency'])->count(),
        ];

        $serverUrl = config('app.url');

        return view('agents.show', compact('agent', 'recentLogs', 'logStats', 'serverUrl'));
    }

    public function installScript(Agent $agent)
    {
        $serverUrl = config('app.url');
        $apiKey = $agent->api_key;
        $agentId = $agent->agent_id;
        $isWindows = $agent->os_type === 'windows';

        $script = $isWindows
            ? $this->generateWindowsInstallScript($serverUrl, $apiKey, $agentId, $agent->config ?? [])
            : $this->generateInstallScript($serverUrl, $apiKey, $agentId, $agent->config ?? []);

        return response($script, 200)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="'.($isWindows ? 'xdr-agent-install.ps1' : 'xdr-agent-install.sh').'"');
    }

    private function generateInstallScript(string $serverUrl, string $apiKey, string $agentId, array $config): string
    {
        $logTypes = implode(' ', $config['log_types'] ?? ['syslog', 'auth']);
        $sendInterval = $config['send_interval'] ?? 60;
        $batchSize = $config['batch_size'] ?? 100;

        return <<<BASH
#!/bin/bash
#
# Wara XDR Agent Installation Script
# Generated for Agent: {$agentId}
#

set -e

# Configuration
XDR_SERVER="{$serverUrl}"
API_KEY="{$apiKey}"
AGENT_ID="{$agentId}"
INSTALL_DIR="/opt/athena-xdr"
LOG_TYPES="{$logTypes}"
SEND_INTERVAL={$sendInterval}
BATCH_SIZE={$batchSize}

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "\${BLUE}"
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║              Wara XDR - Agent Installation                 ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo -e "\${NC}"

# Check root
if [ "\$EUID" -ne 0 ]; then
    echo -e "\${RED}[ERROR] Please run as root (sudo)\${NC}"
    exit 1
fi

echo -e "\${YELLOW}[*] Creating installation directory...\${NC}"
mkdir -p \$INSTALL_DIR
mkdir -p \$INSTALL_DIR/logs
mkdir -p \$INSTALL_DIR/queue

# Create configuration file
echo -e "\${YELLOW}[*] Creating configuration...\${NC}"
cat > \$INSTALL_DIR/config.conf << EOF
# Wara XDR Agent Configuration
XDR_SERVER=\$XDR_SERVER
API_KEY=\$API_KEY
AGENT_ID=\$AGENT_ID
LOG_TYPES=\$LOG_TYPES
SEND_INTERVAL=\$SEND_INTERVAL
BATCH_SIZE=\$BATCH_SIZE
EOF

chmod 600 \$INSTALL_DIR/config.conf

# Create the main agent script
echo -e "\${YELLOW}[*] Installing agent script...\${NC}"
cat > \$INSTALL_DIR/xdr-agent.sh << 'AGENT_SCRIPT'
#!/bin/bash
#
# Wara XDR Log Collection Agent
#

source /opt/athena-xdr/config.conf

QUEUE_DIR="/opt/athena-xdr/queue"
STATE_DIR="/opt/athena-xdr/state"
LOG_FILE="/opt/athena-xdr/logs/agent.log"

mkdir -p \$STATE_DIR

log() {
    echo "\$(date '+%Y-%m-%d %H:%M:%S') - \$1" >> \$LOG_FILE
}

get_hostname() {
    hostname -f 2>/dev/null || hostname
}

get_ip() {
    hostname -I 2>/dev/null | awk '{print \$1}' || echo "unknown"
}

# Parse syslog line
parse_syslog() {
    local line="\$1"
    local source="\$2"
    
    # Extract timestamp, hostname, process, message
    if [[ \$line =~ ^([A-Za-z]+[[:space:]]+[0-9]+[[:space:]]+[0-9:]+)[[:space:]]+([^[:space:]]+)[[:space:]]+([^:]+):[[:space:]]*(.*)\$ ]]; then
        local timestamp="\${BASH_REMATCH[1]}"
        local host="\${BASH_REMATCH[2]}"
        local process="\${BASH_REMATCH[3]}"
        local message="\${BASH_REMATCH[4]}"
        
        # Determine severity based on keywords
        local severity="info"
        if [[ \$message =~ (error|fail|denied|invalid) ]]; then
            severity="error"
        elif [[ \$message =~ (warn|warning) ]]; then
            severity="warning"
        elif [[ \$message =~ (critical|emergency|panic) ]]; then
            severity="critical"
        fi
        
        echo "{\\"log_type\\":\\"\$source\\",\\"message\\":\\"\$(echo \$message | sed 's/"/\\\\"/g')\\",\\"severity\\":\\"\$severity\\",\\"hostname\\":\\"\$host\\",\\"process\\":\\"\$process\\",\\"log_timestamp\\":\\"\$(date -d "\$timestamp" '+%Y-%m-%d %H:%M:%S' 2>/dev/null || echo \$timestamp)\\"}"
    else
        echo "{\\"log_type\\":\\"\$source\\",\\"message\\":\\"\$(echo \$line | sed 's/"/\\\\"/g')\\",\\"severity\\":\\"info\\",\\"hostname\\":\\"\\",\\"process\\":\\"\\",\\"log_timestamp\\":\\"\$(date '+%Y-%m-%d %H:%M:%S')\\"}"
    fi
}

# Collect logs from a file
collect_logs() {
    local log_file="\$1"
    local log_type="\$2"
    local state_file="\$STATE_DIR/\$(echo \$log_file | md5sum | cut -d' ' -f1)"
    
    if [ ! -f "\$log_file" ]; then
        return
    fi
    
    local last_pos=0
    if [ -f "\$state_file" ]; then
        last_pos=\$(cat \$state_file)
    fi
    
    local current_size=\$(stat -c%s "\$log_file" 2>/dev/null || echo 0)
    
    # If file was rotated (smaller than last position), start from beginning
    if [ \$current_size -lt \$last_pos ]; then
        last_pos=0
    fi
    
    if [ \$current_size -gt \$last_pos ]; then
        tail -c +\$((last_pos + 1)) "\$log_file" | while IFS= read -r line; do
            if [ -n "\$line" ]; then
                parse_syslog "\$line" "\$log_type" >> "\$QUEUE_DIR/batch_\$\$.json"
            fi
        done
        echo \$current_size > \$state_file
    fi
}

# Send logs to server
send_logs() {
    local batch_file="\$1"
    
    if [ ! -f "\$batch_file" ] || [ ! -s "\$batch_file" ]; then
        rm -f "\$batch_file" 2>/dev/null
        return
    fi
    
    local logs="[\$(cat \$batch_file | head -n \$BATCH_SIZE | tr '\n' ',' | sed 's/,\$//'))]"
    
    local response=\$(curl -s -w "\\n%{http_code}" -X POST "\$XDR_SERVER/api/agent/logs" \\
        -H "Content-Type: application/json" \\
        -H "X-Agent-ID: \$AGENT_ID" \\
        -H "X-API-Key: \$API_KEY" \\
        -d "{\\"agent_id\\":\\"\$AGENT_ID\\",\\"hostname\\":\\"\$(get_hostname)\\",\\"ip_address\\":\\"\$(get_ip)\\",\\"logs\\":\$logs}" 2>/dev/null)
    
    local http_code=\$(echo "\$response" | tail -n1)
    
    if [ "\$http_code" = "200" ] || [ "\$http_code" = "201" ]; then
        log "Successfully sent \$(wc -l < \$batch_file) logs"
        rm -f "\$batch_file"
    else
        log "Failed to send logs. HTTP: \$http_code"
    fi
}

# Send heartbeat
send_heartbeat() {
    curl -s -X POST "\$XDR_SERVER/api/agent/heartbeat" \\
        -H "Content-Type: application/json" \\
        -H "X-Agent-ID: \$AGENT_ID" \\
        -H "X-API-Key: \$API_KEY" \\
        -d "{\\"agent_id\\":\\"\$AGENT_ID\\",\\"hostname\\":\\"\$(get_hostname)\\",\\"ip_address\\":\\"\$(get_ip)\\",\\"os_type\\":\\"linux\\",\\"os_version\\":\\"\$(uname -r)\\"}" > /dev/null 2>&1
}

# Main collection loop
main() {
    log "Agent started"
    send_heartbeat
    
    while true; do
        # Collect from various log sources
        for log_type in \$LOG_TYPES; do
            case \$log_type in
                syslog)
                    collect_logs "/var/log/syslog" "syslog"
                    collect_logs "/var/log/messages" "syslog"
                    ;;
                auth)
                    collect_logs "/var/log/auth.log" "auth"
                    collect_logs "/var/log/secure" "auth"
                    ;;
                apache)
                    collect_logs "/var/log/apache2/access.log" "apache"
                    collect_logs "/var/log/apache2/error.log" "apache"
                    collect_logs "/var/log/httpd/access_log" "apache"
                    collect_logs "/var/log/httpd/error_log" "apache"
                    ;;
                nginx)
                    collect_logs "/var/log/nginx/access.log" "nginx"
                    collect_logs "/var/log/nginx/error.log" "nginx"
                    ;;
                mysql)
                    collect_logs "/var/log/mysql/error.log" "mysql"
                    ;;
                docker)
                    collect_logs "/var/log/docker.log" "docker"
                    ;;
                kernel)
                    collect_logs "/var/log/kern.log" "kernel"
                    ;;
                cron)
                    collect_logs "/var/log/cron" "cron"
                    collect_logs "/var/log/cron.log" "cron"
                    ;;
            esac
        done
        
        # Send queued logs
        for batch in \$QUEUE_DIR/batch_*.json; do
            [ -f "\$batch" ] && send_logs "\$batch"
        done
        
        # Send heartbeat every 5 minutes
        if [ \$((SECONDS % 300)) -lt \$SEND_INTERVAL ]; then
            send_heartbeat
        fi
        
        sleep \$SEND_INTERVAL
    done
}

main
AGENT_SCRIPT

chmod +x \$INSTALL_DIR/xdr-agent.sh

# Create systemd service
echo -e "\${YELLOW}[*] Creating systemd service...\${NC}"
cat > /etc/systemd/system/athena-xdr-agent.service << EOF
[Unit]
Description=Wara XDR Log Collection Agent
After=network.target

[Service]
Type=simple
ExecStart=/opt/athena-xdr/xdr-agent.sh
Restart=always
RestartSec=10
User=root

[Install]
WantedBy=multi-user.target
EOF

# Enable and start service
echo -e "\${YELLOW}[*] Starting agent service...\${NC}"
systemctl daemon-reload
systemctl enable athena-xdr-agent
systemctl start athena-xdr-agent

# Verify installation
sleep 2
if systemctl is-active --quiet athena-xdr-agent; then
    echo -e "\${GREEN}"
    echo "╔══════════════════════════════════════════════════════════════╗"
    echo "║           ✓ Installation completed successfully!            ║"
    echo "╚══════════════════════════════════════════════════════════════╝"
    echo -e "\${NC}"
    echo ""
    echo -e "Agent ID:     \${BLUE}\$AGENT_ID\${NC}"
    echo -e "Install Dir:  \${BLUE}\$INSTALL_DIR\${NC}"
    echo -e "Status:       \${GREEN}Running\${NC}"
    echo ""
    echo "Commands:"
    echo "  - Check status:  systemctl status athena-xdr-agent"
    echo "  - View logs:     tail -f \$INSTALL_DIR/logs/agent.log"
    echo "  - Restart:       systemctl restart athena-xdr-agent"
    echo "  - Stop:          systemctl stop athena-xdr-agent"
    echo ""
else
    echo -e "\${RED}[ERROR] Service failed to start. Check logs:\${NC}"
    journalctl -u athena-xdr-agent -n 20
    exit 1
fi
BASH;
    }

    private function generateWindowsInstallScript(string $serverUrl, string $apiKey, string $agentId, array $config): string
    {
        $sendInterval = $config['send_interval'] ?? 60;
        $batchSize = $config['batch_size'] ?? 100;

        return <<<POWERSHELL
# Wara XDR Agent Installation Script (Windows)
# Generated for Agent: {$agentId}

\$ErrorActionPreference = "Stop"

\$XdrServer = "{$serverUrl}"
\$ApiKey = "{$apiKey}"
\$AgentId = "{$agentId}"
\$InstallDir = "C:\\ProgramData\\AthenaXDR"
\$LogDir = Join-Path \$InstallDir "logs"
\$SendInterval = {$sendInterval}
\$BatchSize = {$batchSize}
\$ConfigFile = Join-Path \$InstallDir "config.json"
\$AgentScript = Join-Path \$InstallDir "xdr-agent.ps1"

Write-Host "[*] Preparing directories..." -ForegroundColor Yellow
New-Item -Path \$InstallDir -ItemType Directory -Force | Out-Null
New-Item -Path \$LogDir -ItemType Directory -Force | Out-Null

\$config = @{
    xdr_server = \$XdrServer
    api_key = \$ApiKey
    agent_id = \$AgentId
    send_interval = \$SendInterval
    batch_size = \$BatchSize
}

\$config | ConvertTo-Json -Depth 4 | Set-Content -Path \$ConfigFile -Encoding UTF8

@'
param()
\$ErrorActionPreference = "SilentlyContinue"

\$InstallDir = "C:\\ProgramData\\AthenaXDR"
\$LogDir = Join-Path \$InstallDir "logs"
\$Config = Get-Content (Join-Path \$InstallDir "config.json") -Raw | ConvertFrom-Json
\$StateFile = Join-Path \$InstallDir "last-state.json"
\$AgentLog = Join-Path \$LogDir "agent.log"

function Write-AgentLog {
    param([string]\$Message)
    \$line = "{0} - {1}" -f (Get-Date -Format "yyyy-MM-dd HH:mm:ss"), \$Message
    Add-Content -Path \$AgentLog -Value \$line
}

function Get-DeviceIp {
    \$ip = (Get-NetIPAddress -AddressFamily IPv4 | Where-Object { \$_.IPAddress -ne "127.0.0.1" } | Select-Object -First 1 -ExpandProperty IPAddress)
    if ([string]::IsNullOrWhiteSpace(\$ip)) { return "unknown" }
    return \$ip
}

function Send-Heartbeat {
    \$body = @{
        agent_id = \$Config.agent_id
        hostname = \$env:COMPUTERNAME
        ip_address = (Get-DeviceIp)
        os_type = "windows"
        os_version = [Environment]::OSVersion.VersionString
    } | ConvertTo-Json -Depth 4

    Invoke-RestMethod -Uri "\$((\$Config.xdr_server).TrimEnd('/'))/api/agent/heartbeat" -Method POST -Headers @{
        "X-Agent-ID" = \$Config.agent_id
        "X-API-Key" = \$Config.api_key
        "Content-Type" = "application/json"
    } -Body \$body | Out-Null
}

function Get-SecurityEvents {
    \$lastRun = (Get-Date).AddMinutes(-5)
    if (Test-Path \$StateFile) {
        try {
            \$state = Get-Content \$StateFile -Raw | ConvertFrom-Json
            if (\$state.last_run) { \$lastRun = [DateTime]\$state.last_run }
        } catch {}
    }

    \$events = Get-WinEvent -FilterHashtable @{LogName='Security'; StartTime=\$lastRun} -MaxEvents 100
    \$payload = foreach (\$event in \$events) {
        @{
            log_type = "security"
            message = \$event.Message
            severity = if (\$event.LevelDisplayName -eq "Error") { "error" } elseif (\$event.LevelDisplayName -eq "Warning") { "warning" } else { "info" }
            hostname = \$env:COMPUTERNAME
            process = "winevent"
            log_timestamp = \$event.TimeCreated.ToString("yyyy-MM-dd HH:mm:ss")
        }
    }

    @{ last_run = (Get-Date).ToString("o"); logs = \$payload } | ConvertTo-Json -Depth 5 | Set-Content -Path \$StateFile -Encoding UTF8
    return \$payload
}

function Send-Logs {
    param([array]\$Logs)
    if (-not \$Logs -or \$Logs.Count -eq 0) { return }

    \$body = @{
        agent_id = \$Config.agent_id
        hostname = \$env:COMPUTERNAME
        ip_address = (Get-DeviceIp)
        logs = \$Logs | Select-Object -First \$Config.batch_size
    } | ConvertTo-Json -Depth 7

    Invoke-RestMethod -Uri "\$((\$Config.xdr_server).TrimEnd('/'))/api/agent/logs" -Method POST -Headers @{
        "X-Agent-ID" = \$Config.agent_id
        "X-API-Key" = \$Config.api_key
        "Content-Type" = "application/json"
    } -Body \$body | Out-Null
}

Write-AgentLog "Agent loop started"
try {
    Send-Heartbeat
    \$logs = Get-SecurityEvents
    Send-Logs -Logs \$logs
    Write-AgentLog "Cycle complete, sent \$((\$logs | Measure-Object).Count) logs"
} catch {
    Write-AgentLog "Cycle failed: \$($_.Exception.Message)"
}
'@ | Set-Content -Path \$AgentScript -Encoding UTF8

Write-Host "[*] Registering scheduled task..." -ForegroundColor Yellow
\$action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-NoProfile -ExecutionPolicy Bypass -File `"\$AgentScript`""
\$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(1) -RepetitionInterval (New-TimeSpan -Seconds \$SendInterval)
\$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
Register-ScheduledTask -TaskName "AthenaXdrAgent" -Action \$action -Trigger \$trigger -Principal \$principal -Force | Out-Null

Start-ScheduledTask -TaskName "AthenaXdrAgent"
Write-Host "[OK] Windows agent installed and started." -ForegroundColor Green
Write-Host "Task name: AthenaXdrAgent"
Write-Host "Logs: \$LogDir\\agent.log"
POWERSHELL;
    }

    public function logs(Agent $agent, Request $request)
    {
        $query = $agent->logs();

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('type')) {
            $query->where('log_type', $request->type);
        }

        if ($request->filled('search')) {
            $query->where('message', 'like', '%'.$request->search.'%');
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(50);
        $severities = AgentLog::getSeverities();
        $logTypes = AgentLog::getLogTypes();

        return view('agents.logs', compact('agent', 'logs', 'severities', 'logTypes'));
    }

    public function delete(Agent $agent)
    {
        $aid = $agent->id;
        $label = $agent->hostname ?? $agent->agent_id;

        $agent->logs()->delete();
        $agent->delete();

        SecurityAudit::log('agent.deleted', [
            'agent_id' => $aid,
            'hostname' => $label,
        ], Agent::class, $aid);

        return redirect()->route('agents.index')
            ->with('success', 'Agent deleted successfully.');
    }
}
