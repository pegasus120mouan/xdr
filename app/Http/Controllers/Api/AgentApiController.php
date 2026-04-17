<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentLog;
use App\Models\Asset;
use App\Services\LogAnalyzer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AgentApiController extends Controller
{
    public function heartbeat(Request $request): JsonResponse
    {
        $agentId = $request->header('X-Agent-ID');
        $apiKey = $request->header('X-API-Key');

        if (!$agentId || !$apiKey) {
            return response()->json(['error' => 'Missing authentication headers'], 401);
        }

        $agent = Agent::where('agent_id', $agentId)
            ->where('api_key', $apiKey)
            ->first();

        if (!$agent) {
            return response()->json(['error' => 'Invalid agent credentials'], 401);
        }

        $data = $request->validate([
            'hostname' => 'required|string',
            'ip_address' => 'nullable|string',
            'os_type' => 'nullable|string',
            'os_version' => 'nullable|string',
        ]);

        // Update agent info
        $agent->update([
            'hostname' => $data['hostname'],
            'ip_address' => $data['ip_address'] ?? $request->ip(),
            'os_type' => $data['os_type'] ?? 'linux',
            'os_version' => $data['os_version'] ?? null,
            'last_heartbeat' => now(),
            'status' => 'active',
            'registered_at' => $agent->registered_at ?? now(),
        ]);

        // Create or update associated asset
        $this->syncAsset($agent);

        return response()->json([
            'status' => 'ok',
            'message' => 'Heartbeat received',
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function receiveLogs(Request $request): JsonResponse
    {
        $agentId = $request->header('X-Agent-ID');
        $apiKey = $request->header('X-API-Key');

        if (!$agentId || !$apiKey) {
            return response()->json(['error' => 'Missing authentication headers'], 401);
        }

        $agent = Agent::where('agent_id', $agentId)
            ->where('api_key', $apiKey)
            ->first();

        if (!$agent) {
            return response()->json(['error' => 'Invalid agent credentials'], 401);
        }

        $data = $request->validate([
            'hostname' => 'required|string',
            'ip_address' => 'nullable|string',
            'logs' => 'required|array',
            'logs.*.log_type' => 'required|string',
            'logs.*.message' => 'required|string',
            'logs.*.severity' => 'nullable|string',
            'logs.*.hostname' => 'nullable|string',
            'logs.*.process' => 'nullable|string',
            'logs.*.log_timestamp' => 'nullable|string',
        ]);

        // Update agent heartbeat
        $agent->update([
            'hostname' => $data['hostname'],
            'ip_address' => $data['ip_address'] ?? $request->ip(),
            'last_heartbeat' => now(),
            'status' => 'active',
        ]);

        // Store logs
        $logsCreated = 0;
        $storedLogs = [];
        foreach ($data['logs'] as $log) {
            $agentLog = AgentLog::create([
                'agent_id' => $agent->id,
                'log_type' => $log['log_type'] ?? 'syslog',
                'message' => $log['message'],
                'severity' => $this->normalizeSeverity($log['severity'] ?? 'info'),
                'hostname' => $log['hostname'] ?? $data['hostname'],
                'process' => $log['process'] ?? null,
                'log_timestamp' => $this->parseTimestamp($log['log_timestamp'] ?? null),
                'raw_data' => $log,
            ]);
            $storedLogs[] = $log;
            $logsCreated++;
        }

        // Analyze logs with detection rules
        $analyzer = new LogAnalyzer();
        $alerts = $analyzer->analyzeLogs($agent, $storedLogs);
        $alertsCreated = count($alerts);

        return response()->json([
            'status' => 'ok',
            'message' => "Received {$logsCreated} logs, {$alertsCreated} alerts created",
            'logs_processed' => $logsCreated,
            'alerts_created' => $alertsCreated,
        ], 201);
    }

    private function normalizeSeverity(string $severity): string
    {
        $validSeverities = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];
        $severity = strtolower($severity);
        
        return in_array($severity, $validSeverities) ? $severity : 'info';
    }

    private function parseTimestamp(?string $timestamp): ?\DateTime
    {
        if (!$timestamp) {
            return now();
        }

        try {
            return new \DateTime($timestamp);
        } catch (\Exception $e) {
            return now();
        }
    }

    private function syncAsset(Agent $agent): void
    {
        if ($agent->asset_id) {
            // Update existing asset
            Asset::where('id', $agent->asset_id)->update([
                'hostname' => $agent->hostname,
                'ip_address' => $agent->ip_address,
                'status' => 'online',
                'last_seen' => now(),
                'os_type' => $agent->os_type,
                'os_version' => $agent->os_version,
                'agent_version' => $agent->agent_version,
            ]);
        } else {
            // Create new asset
            $asset = Asset::create([
                'hostname' => $agent->hostname,
                'ip_address' => $agent->ip_address,
                'type' => 'server',
                'os_type' => $agent->os_type ?? 'linux',
                'os_version' => $agent->os_version,
                'tenant_group_id' => $agent->tenant_group_id,
                'status' => 'online',
                'last_seen' => now(),
                'agent_version' => $agent->agent_version,
                'agent_installed_at' => now(),
            ]);

            $agent->update(['asset_id' => $asset->id]);
        }
    }

    private function analyzeLogsForThreats(Agent $agent, array $logs): void
    {
        // Count failed auth attempts
        $failedAuthCount = 0;
        $suspiciousPatterns = [
            'Failed password',
            'authentication failure',
            'Invalid user',
            'Connection refused',
            'Permission denied',
        ];

        foreach ($logs as $log) {
            $message = $log['message'] ?? '';
            foreach ($suspiciousPatterns as $pattern) {
                if (stripos($message, $pattern) !== false) {
                    $failedAuthCount++;
                    break;
                }
            }
        }

        // If many failed attempts, could trigger brute force detection
        // This would integrate with the existing BruteForceDetector service
        if ($failedAuthCount >= 5) {
            // Log security event - could create SecurityAlert here
            \Log::warning("Potential brute force detected on agent {$agent->agent_id}: {$failedAuthCount} failed attempts");
        }
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'hostname' => 'required|string',
            'ip_address' => 'nullable|string',
            'os_type' => 'nullable|string',
            'os_version' => 'nullable|string',
            'group_name' => 'required|string',
            'agent_name' => 'nullable|string',
        ]);

        // Find or create the group
        $group = \App\Models\TenantGroup::where('name', $data['group_name'])
            ->orWhere('slug', \Illuminate\Support\Str::slug($data['group_name']))
            ->first();

        if (!$group) {
            return response()->json(['error' => 'Group not found'], 404);
        }

        $osType = $data['os_type'] ?? 'linux';

        // Create the agent
        $agent = Agent::create([
            'agent_id' => Agent::generateAgentId(),
            'name' => $data['agent_name'] ?? $data['hostname'],
            'hostname' => $data['hostname'],
            'ip_address' => $data['ip_address'] ?? $request->ip(),
            'tenant_group_id' => $group->id,
            'os_type' => $osType,
            'os_version' => $data['os_version'] ?? null,
            'api_key' => Agent::generateApiKey(),
            'status' => 'active',
            'registered_at' => now(),
            'last_heartbeat' => now(),
            'config' => [
                'log_types' => $osType === 'windows' ? ['eventlog', 'security'] : ['syslog', 'auth'],
                'send_interval' => 60,
                'batch_size' => 100,
            ],
        ]);

        // Create associated asset
        $this->syncAsset($agent);

        return response()->json([
            'status' => 'ok',
            'agent_id' => $agent->agent_id,
            'api_key' => $agent->api_key,
            'message' => 'Agent registered successfully',
        ], 201);
    }

    public function installScript(Request $request)
    {
        $script = <<<'BASH'
#!/bin/bash
#
# Wara XDR Agent Installation Script
# Auto-registration version
#

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration from environment
XDR_MANAGER="${XDR_MANAGER:-}"
XDR_AGENT_GROUP="${XDR_AGENT_GROUP:-default}"
XDR_AGENT_NAME="${XDR_AGENT_NAME:-$(hostname)}"
INSTALL_DIR="/opt/athena-xdr"

echo -e "${BLUE}"
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║              Wara XDR - Agent Installation                 ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# Check required vars
if [ -z "$XDR_MANAGER" ]; then
    echo -e "${RED}[ERROR] XDR_MANAGER environment variable is required${NC}"
    echo "Usage: XDR_MANAGER='your-server-ip' XDR_AGENT_GROUP='group-name' bash install.sh"
    exit 1
fi

# Check root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}[ERROR] Please run as root (sudo)${NC}"
    exit 1
fi

echo -e "${YELLOW}[*] Registering agent with XDR server...${NC}"

# Register agent and get API key
REGISTER_RESPONSE=$(curl -s -X POST "https://${XDR_MANAGER}/api/agent/register" \
    -H "Content-Type: application/json" \
    -d "{
        \"hostname\": \"$(hostname)\",
        \"ip_address\": \"$(hostname -I 2>/dev/null | awk '{print $1}' || echo 'unknown')\",
        \"os_type\": \"linux\",
        \"os_version\": \"$(uname -r)\",
        \"group_name\": \"${XDR_AGENT_GROUP}\",
        \"agent_name\": \"${XDR_AGENT_NAME}\"
    }" 2>/dev/null || curl -s -X POST "http://${XDR_MANAGER}/api/agent/register" \
    -H "Content-Type: application/json" \
    -d "{
        \"hostname\": \"$(hostname)\",
        \"ip_address\": \"$(hostname -I 2>/dev/null | awk '{print $1}' || echo 'unknown')\",
        \"os_type\": \"linux\",
        \"os_version\": \"$(uname -r)\",
        \"group_name\": \"${XDR_AGENT_GROUP}\",
        \"agent_name\": \"${XDR_AGENT_NAME}\"
    }")

# Parse response
AGENT_ID=$(echo "$REGISTER_RESPONSE" | grep -o '"agent_id":"[^"]*"' | cut -d'"' -f4)
API_KEY=$(echo "$REGISTER_RESPONSE" | grep -o '"api_key":"[^"]*"' | cut -d'"' -f4)

if [ -z "$AGENT_ID" ] || [ -z "$API_KEY" ]; then
    echo -e "${RED}[ERROR] Failed to register agent${NC}"
    echo "Response: $REGISTER_RESPONSE"
    exit 1
fi

echo -e "${GREEN}[✓] Agent registered: ${AGENT_ID}${NC}"

echo -e "${YELLOW}[*] Creating installation directory...${NC}"
mkdir -p $INSTALL_DIR
mkdir -p $INSTALL_DIR/logs
mkdir -p $INSTALL_DIR/queue
mkdir -p $INSTALL_DIR/state

# Create configuration file
echo -e "${YELLOW}[*] Creating configuration...${NC}"
cat > $INSTALL_DIR/config.conf << EOF
# Wara XDR Agent Configuration
XDR_SERVER=$XDR_MANAGER
API_KEY=$API_KEY
AGENT_ID=$AGENT_ID
LOG_TYPES="syslog auth app"
SEND_INTERVAL=60
BATCH_SIZE=100
EOF

chmod 600 $INSTALL_DIR/config.conf

# Create the main agent script
echo -e "${YELLOW}[*] Installing agent script...${NC}"
cat > $INSTALL_DIR/xdr-agent.sh << 'AGENT_SCRIPT'
#!/bin/bash
source /opt/athena-xdr/config.conf

QUEUE_DIR="/opt/athena-xdr/queue"
STATE_DIR="/opt/athena-xdr/state"
LOG_FILE="/opt/athena-xdr/logs/agent.log"

log() { echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> $LOG_FILE; }

parse_syslog() {
    local line="$1"
    local source="$2"
    local severity="info"
    
    if [[ $line =~ (error|fail|denied|invalid) ]]; then severity="error"
    elif [[ $line =~ (warn|warning) ]]; then severity="warning"
    elif [[ $line =~ (critical|emergency|panic) ]]; then severity="critical"
    fi
    
    local escaped_msg=$(echo "$line" | sed 's/"/\\"/g' | tr -d '\n')
    echo "{\"log_type\":\"$source\",\"message\":\"$escaped_msg\",\"severity\":\"$severity\",\"hostname\":\"$(hostname)\",\"log_timestamp\":\"$(date '+%Y-%m-%d %H:%M:%S')\"}"
}

collect_logs() {
    local log_file="$1"
    local log_type="$2"
    local state_file="$STATE_DIR/$(echo $log_file | md5sum | cut -d' ' -f1)"
    
    [ ! -f "$log_file" ] && return
    
    local last_pos=0
    [ -f "$state_file" ] && last_pos=$(cat $state_file)
    local current_size=$(stat -c%s "$log_file" 2>/dev/null || echo 0)
    
    [ $current_size -lt $last_pos ] && last_pos=0
    
    if [ $current_size -gt $last_pos ]; then
        tail -c +$((last_pos + 1)) "$log_file" 2>/dev/null | head -n 100 | while IFS= read -r line; do
            [ -n "$line" ] && parse_syslog "$line" "$log_type" >> "$QUEUE_DIR/batch_$$.json"
        done
        echo $current_size > $state_file
    fi
}

send_logs() {
    local batch_file="$1"
    [ ! -f "$batch_file" ] || [ ! -s "$batch_file" ] && { rm -f "$batch_file" 2>/dev/null; return; }
    
    local logs="[$(cat $batch_file | head -n $BATCH_SIZE | tr '\n' ',' | sed 's/,$//')]"
    
    local response=$(curl -s -w "\n%{http_code}" -X POST "https://$XDR_SERVER/api/agent/logs" \
        -H "Content-Type: application/json" \
        -H "X-Agent-ID: $AGENT_ID" \
        -H "X-API-Key: $API_KEY" \
        -d "{\"agent_id\":\"$AGENT_ID\",\"hostname\":\"$(hostname)\",\"ip_address\":\"$(hostname -I | awk '{print $1}')\",\"logs\":$logs}" 2>/dev/null || \
        curl -s -w "\n%{http_code}" -X POST "http://$XDR_SERVER/api/agent/logs" \
        -H "Content-Type: application/json" \
        -H "X-Agent-ID: $AGENT_ID" \
        -H "X-API-Key: $API_KEY" \
        -d "{\"agent_id\":\"$AGENT_ID\",\"hostname\":\"$(hostname)\",\"ip_address\":\"$(hostname -I | awk '{print $1}')\",\"logs\":$logs}")
    
    local http_code=$(echo "$response" | tail -n1)
    [ "$http_code" = "200" ] || [ "$http_code" = "201" ] && { log "Sent $(wc -l < $batch_file) logs"; rm -f "$batch_file"; } || log "Failed: HTTP $http_code"
}

send_heartbeat() {
    curl -s -X POST "https://$XDR_SERVER/api/agent/heartbeat" \
        -H "Content-Type: application/json" -H "X-Agent-ID: $AGENT_ID" -H "X-API-Key: $API_KEY" \
        -d "{\"agent_id\":\"$AGENT_ID\",\"hostname\":\"$(hostname)\",\"ip_address\":\"$(hostname -I | awk '{print $1}')\",\"os_type\":\"linux\",\"os_version\":\"$(uname -r)\"}" > /dev/null 2>&1 || \
    curl -s -X POST "http://$XDR_SERVER/api/agent/heartbeat" \
        -H "Content-Type: application/json" -H "X-Agent-ID: $AGENT_ID" -H "X-API-Key: $API_KEY" \
        -d "{\"agent_id\":\"$AGENT_ID\",\"hostname\":\"$(hostname)\",\"ip_address\":\"$(hostname -I | awk '{print $1}')\",\"os_type\":\"linux\",\"os_version\":\"$(uname -r)\"}" > /dev/null 2>&1
}

main() {
    log "Agent started"
    local heartbeat_counter=0
    
    while true; do
        for log_type in $LOG_TYPES; do
            case $log_type in
                syslog) collect_logs "/var/log/syslog" "syslog"; collect_logs "/var/log/messages" "syslog" ;;
                auth) collect_logs "/var/log/auth.log" "auth"; collect_logs "/var/log/secure" "auth" ;;
                app) 
                    # Application logs - Laravel, Apache, Nginx, etc.
                    collect_logs "/var/log/apache2/error.log" "apache"
                    collect_logs "/var/log/apache2/access.log" "apache"
                    collect_logs "/var/log/nginx/error.log" "nginx"
                    collect_logs "/var/log/nginx/access.log" "nginx"
                    collect_logs "/var/log/mysql/error.log" "mysql"
                    # Laravel logs (common paths)
                    for laravel_log in /var/www/*/storage/logs/laravel.log; do
                        [ -f "$laravel_log" ] && collect_logs "$laravel_log" "laravel"
                    done
                    # Docker logs
                    collect_logs "/var/log/docker.log" "docker"
                    ;;
            esac
        done
        
        for batch in $QUEUE_DIR/batch_*.json; do [ -f "$batch" ] && send_logs "$batch"; done
        
        heartbeat_counter=$((heartbeat_counter + 1))
        [ $heartbeat_counter -ge 5 ] && { send_heartbeat; heartbeat_counter=0; }
        
        sleep $SEND_INTERVAL
    done
}

main
AGENT_SCRIPT

chmod +x $INSTALL_DIR/xdr-agent.sh

# Create systemd service
echo -e "${YELLOW}[*] Creating systemd service...${NC}"
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

systemctl daemon-reload
systemctl enable athena-xdr-agent
systemctl start athena-xdr-agent

sleep 2
if systemctl is-active --quiet athena-xdr-agent; then
    echo -e "${GREEN}"
    echo "╔══════════════════════════════════════════════════════════════╗"
    echo "║           ✓ Installation completed successfully!            ║"
    echo "╚══════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
    echo ""
    echo -e "Agent ID:     ${BLUE}$AGENT_ID${NC}"
    echo -e "Group:        ${BLUE}$XDR_AGENT_GROUP${NC}"
    echo -e "Status:       ${GREEN}Running${NC}"
    echo ""
else
    echo -e "${RED}[ERROR] Service failed to start${NC}"
    journalctl -u athena-xdr-agent -n 20
    exit 1
fi
BASH;

        return response($script, 200)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="athena-xdr-agent.sh"');
    }

    /**
     * One-shot Windows installer (auto-register), same role as install.sh for Linux.
     * Served at GET /api/agent/install.ps1
     */
    public function installPowerShellScript()
    {
        $script = <<<'PS1'
# Wara XDR Agent — Windows installer (auto-register)
#requires -RunAsAdministrator

param(
    [Parameter(Mandatory = $true)]
    [string] $Server,

    [Parameter(Mandatory = $true)]
    [string] $Group,

    [Parameter(Mandatory = $false)]
    [string] $AgentName = ""
)

$ErrorActionPreference = "Stop"

$Server = $Server.Trim()
if ($Server -match '^\s*https?://') {
    $Server = ([Uri]$Server).Host
    if ([string]::IsNullOrWhiteSpace($Server)) {
        Write-Host "[ERROR] Invalid server address." -ForegroundColor Red
        exit 1
    }
}

function Get-DeviceIp {
    $ip = Get-NetIPAddress -AddressFamily IPv4 |
        Where-Object { $_.IPAddress -ne "127.0.0.1" } |
        Select-Object -First 1 -ExpandProperty IPAddress
    if ([string]::IsNullOrWhiteSpace($ip)) { return "unknown" }
    return $ip
}

function Invoke-XdrRegister {
    param([string] $BaseUrl)

    $payload = [ordered]@{
        hostname    = $env:COMPUTERNAME
        ip_address  = Get-DeviceIp
        os_type     = "windows"
        os_version  = [Environment]::OSVersion.VersionString
        group_name  = $Group
    }
    if ($AgentName) { $payload["agent_name"] = $AgentName }

    $uri = "$BaseUrl/api/agent/register"
    $body = ($payload | ConvertTo-Json -Depth 4)
    return Invoke-RestMethod -Uri $uri -Method Post -Body $body -ContentType "application/json; charset=utf-8"
}

Write-Host "[*] Registering agent with XDR server..." -ForegroundColor Yellow

$registerResult = $null
$xdrBase = $null
foreach ($scheme in @("https", "http")) {
    try {
        # PowerShell parses "$scheme://..." as a drive-style scope; use -f or ${scheme}://
        $xdrBase = ('{0}://{1}' -f $scheme, ($Server.Trim().TrimEnd('/')))
        $registerResult = Invoke-XdrRegister -BaseUrl $xdrBase
        break
    } catch {
        $registerResult = $null
    }
}

if (-not $registerResult -or -not $registerResult.agent_id -or -not $registerResult.api_key) {
    Write-Host "[ERROR] Failed to register agent. Check server address, TLS, and that the tenant group exists." -ForegroundColor Red
    exit 1
}

$AgentId = $registerResult.agent_id
$ApiKey = $registerResult.api_key

Write-Host "[OK] Agent registered: $AgentId" -ForegroundColor Green

$InstallDir = "C:\ProgramData\AthenaXDR"
$LogDir = Join-Path $InstallDir "logs"
$SendInterval = 60
$BatchSize = 100
$ConfigFile = Join-Path $InstallDir "config.json"
$AgentScript = Join-Path $InstallDir "xdr-agent.ps1"

New-Item -Path $InstallDir -ItemType Directory -Force | Out-Null
New-Item -Path $LogDir -ItemType Directory -Force | Out-Null

$config = @{
    xdr_server     = $xdrBase
    api_key        = $ApiKey
    agent_id       = $AgentId
    send_interval  = $SendInterval
    batch_size     = $BatchSize
}
$config | ConvertTo-Json -Depth 4 | Set-Content -Path $ConfigFile -Encoding UTF8

@'
param()
$ErrorActionPreference = "SilentlyContinue"

$InstallDir = "C:\ProgramData\AthenaXDR"
$LogDir = Join-Path $InstallDir "logs"
$Config = Get-Content (Join-Path $InstallDir "config.json") -Raw | ConvertFrom-Json
$StateFile = Join-Path $InstallDir "last-state.json"
$AgentLog = Join-Path $LogDir "agent.log"

function Write-AgentLog {
    param([string]$Message)
    $line = "{0} - {1}" -f (Get-Date -Format "yyyy-MM-dd HH:mm:ss"), $Message
    Add-Content -Path $AgentLog -Value $line
}

function Get-DeviceIp {
    $ip = (Get-NetIPAddress -AddressFamily IPv4 | Where-Object { $_.IPAddress -ne "127.0.0.1" } | Select-Object -First 1 -ExpandProperty IPAddress)
    if ([string]::IsNullOrWhiteSpace($ip)) { return "unknown" }
    return $ip
}

function Send-Heartbeat {
    $body = @{
        agent_id = $Config.agent_id
        hostname = $env:COMPUTERNAME
        ip_address = (Get-DeviceIp)
        os_type = "windows"
        os_version = [Environment]::OSVersion.VersionString
    } | ConvertTo-Json -Depth 4

    Invoke-RestMethod -Uri "$($Config.xdr_server.TrimEnd('/'))/api/agent/heartbeat" -Method Post -Headers @{
        "X-Agent-ID" = $Config.agent_id
        "X-API-Key" = $Config.api_key
        "Content-Type" = "application/json"
    } -Body $body | Out-Null
}

function Get-SecurityEvents {
    $lastRun = (Get-Date).AddMinutes(-5)
    if (Test-Path $StateFile) {
        try {
            $state = Get-Content $StateFile -Raw | ConvertFrom-Json
            if ($state.last_run) { $lastRun = [DateTime]$state.last_run }
        } catch {}
    }

    try {
        $events = Get-WinEvent -FilterHashtable @{LogName='Security'; StartTime=$lastRun} -MaxEvents 100 -ErrorAction Stop
    } catch {
        return @()
    }

    $payload = foreach ($event in $events) {
        @{
            log_type = "security"
            message = $event.Message
            severity = if ($event.LevelDisplayName -eq "Error") { "error" } elseif ($event.LevelDisplayName -eq "Warning") { "warning" } else { "info" }
            hostname = $env:COMPUTERNAME
            process = "winevent"
            log_timestamp = $event.TimeCreated.ToString("yyyy-MM-dd HH:mm:ss")
        }
    }

    @{ last_run = (Get-Date).ToString("o"); logs = $payload } | ConvertTo-Json -Depth 5 | Set-Content -Path $StateFile -Encoding UTF8
    return $payload
}

function Send-Logs {
    param([array]$Logs)
    if (-not $Logs -or $Logs.Count -eq 0) { return }

    $body = @{
        agent_id = $Config.agent_id
        hostname = $env:COMPUTERNAME
        ip_address = (Get-DeviceIp)
        logs = $Logs | Select-Object -First $Config.batch_size
    } | ConvertTo-Json -Depth 7

    Invoke-RestMethod -Uri "$($Config.xdr_server.TrimEnd('/'))/api/agent/logs" -Method Post -Headers @{
        "X-Agent-ID" = $Config.agent_id
        "X-API-Key" = $Config.api_key
        "Content-Type" = "application/json"
    } -Body $body | Out-Null
}

Write-AgentLog "Agent loop started"
try {
    Send-Heartbeat
    $logs = @(Get-SecurityEvents)
    Send-Logs -Logs $logs
    Write-AgentLog ("Cycle complete, sent {0} logs" -f $logs.Count)
} catch {
    Write-AgentLog ("Cycle failed: {0}" -f $_.Exception.Message)
}
'@ | Set-Content -Path $AgentScript -Encoding UTF8

Write-Host "[*] Registering scheduled task..." -ForegroundColor Yellow

$action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-NoProfile -ExecutionPolicy Bypass -File `"$AgentScript`""
$trigger = New-ScheduledTaskTrigger -Once -At ((Get-Date).AddMinutes(1)) -RepetitionInterval (New-TimeSpan -Seconds $SendInterval) -RepetitionDuration ([TimeSpan]::FromDays(3650))
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
Register-ScheduledTask -TaskName "AthenaXdrAgent" -Action $action -Trigger $trigger -Principal $principal -Force | Out-Null

Start-ScheduledTask -TaskName "AthenaXdrAgent"

Write-Host "[OK] Windows agent installed and started." -ForegroundColor Green
Write-Host "Task name: AthenaXdrAgent"
Write-Host "Logs: $LogDir\agent.log"
PS1;

        return response($script, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="install.ps1"');
    }

    /**
     * Agent update script - updates agent without losing configuration
     * GET /api/agent/update.sh
     */
    public function updateScript()
    {
        $script = <<<'BASH'
#!/bin/bash
#
# Wara XDR Agent Update Script
# Updates agent without losing configuration
#

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

INSTALL_DIR="/opt/athena-xdr"
CONFIG_FILE="$INSTALL_DIR/config.conf"
BACKUP_DIR="$INSTALL_DIR/backup_$(date +%Y%m%d_%H%M%S)"

echo -e "${BLUE}"
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║              Wara XDR - Agent Update                        ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# Check root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}[ERROR] Please run as root (sudo)${NC}"
    exit 1
fi

# Check if agent is installed
if [ ! -f "$CONFIG_FILE" ]; then
    echo -e "${RED}[ERROR] Agent not installed. Use install.sh instead.${NC}"
    exit 1
fi

# Load current config
source "$CONFIG_FILE"

echo -e "${YELLOW}[*] Current agent: ${AGENT_ID}${NC}"
echo -e "${YELLOW}[*] Server: ${XDR_SERVER}${NC}"

# Backup current installation
echo -e "${YELLOW}[*] Creating backup...${NC}"
mkdir -p "$BACKUP_DIR"
cp "$CONFIG_FILE" "$BACKUP_DIR/"
cp "$INSTALL_DIR/xdr-agent.sh" "$BACKUP_DIR/" 2>/dev/null || true

# Stop the service
echo -e "${YELLOW}[*] Stopping agent service...${NC}"
systemctl stop athena-xdr-agent 2>/dev/null || true

# Update config to include app logs if not present
echo -e "${YELLOW}[*] Updating configuration...${NC}"
if ! grep -q "app" "$CONFIG_FILE" 2>/dev/null; then
    sed -i 's/LOG_TYPES="syslog auth"/LOG_TYPES="syslog auth app"/' "$CONFIG_FILE"
    echo -e "${GREEN}[✓] Added application log collection${NC}"
fi

# Create updated agent script
echo -e "${YELLOW}[*] Updating agent script...${NC}"
cat > $INSTALL_DIR/xdr-agent.sh << 'AGENT_SCRIPT'
#!/bin/bash
source /opt/athena-xdr/config.conf

QUEUE_DIR="/opt/athena-xdr/queue"
STATE_DIR="/opt/athena-xdr/state"
LOG_FILE="/opt/athena-xdr/logs/agent.log"

log() { echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> $LOG_FILE; }

parse_syslog() {
    local line="$1"
    local source="$2"
    local severity="info"
    
    if [[ $line =~ (error|fail|denied|invalid|attack|injection|unauthorized) ]]; then severity="error"
    elif [[ $line =~ (warn|warning) ]]; then severity="warning"
    elif [[ $line =~ (critical|emergency|panic|exploit|malicious) ]]; then severity="critical"
    fi
    
    local escaped_msg=$(echo "$line" | sed 's/"/\\"/g' | tr -d '\n')
    echo "{\"log_type\":\"$source\",\"message\":\"$escaped_msg\",\"severity\":\"$severity\",\"hostname\":\"$(hostname)\",\"log_timestamp\":\"$(date '+%Y-%m-%d %H:%M:%S')\"}"
}

collect_logs() {
    local log_file="$1"
    local log_type="$2"
    local state_file="$STATE_DIR/$(echo $log_file | md5sum | cut -d' ' -f1)"
    
    [ ! -f "$log_file" ] && return
    
    local last_pos=0
    [ -f "$state_file" ] && last_pos=$(cat $state_file)
    local current_size=$(stat -c%s "$log_file" 2>/dev/null || echo 0)
    
    [ $current_size -lt $last_pos ] && last_pos=0
    
    if [ $current_size -gt $last_pos ]; then
        tail -c +$((last_pos + 1)) "$log_file" 2>/dev/null | head -n 100 | while IFS= read -r line; do
            [ -n "$line" ] && parse_syslog "$line" "$log_type" >> "$QUEUE_DIR/batch_$$.json"
        done
        echo $current_size > $state_file
    fi
}

send_logs() {
    local batch_file="$1"
    [ ! -f "$batch_file" ] || [ ! -s "$batch_file" ] && { rm -f "$batch_file" 2>/dev/null; return; }
    
    local logs="[$(cat $batch_file | head -n $BATCH_SIZE | tr '\n' ',' | sed 's/,$//')]"
    
    local response=$(curl -s -w "\n%{http_code}" -X POST "https://$XDR_SERVER/api/agent/logs" \
        -H "Content-Type: application/json" \
        -H "X-Agent-ID: $AGENT_ID" \
        -H "X-API-Key: $API_KEY" \
        -d "{\"agent_id\":\"$AGENT_ID\",\"hostname\":\"$(hostname)\",\"ip_address\":\"$(hostname -I | awk '{print $1}')\",\"logs\":$logs}" 2>/dev/null || \
        curl -s -w "\n%{http_code}" -X POST "http://$XDR_SERVER/api/agent/logs" \
        -H "Content-Type: application/json" \
        -H "X-Agent-ID: $AGENT_ID" \
        -H "X-API-Key: $API_KEY" \
        -d "{\"agent_id\":\"$AGENT_ID\",\"hostname\":\"$(hostname)\",\"ip_address\":\"$(hostname -I | awk '{print $1}')\",\"logs\":$logs}")
    
    local http_code=$(echo "$response" | tail -n1)
    [ "$http_code" = "200" ] || [ "$http_code" = "201" ] && { log "Sent $(wc -l < $batch_file) logs"; rm -f "$batch_file"; } || log "Failed: HTTP $http_code"
}

send_heartbeat() {
    curl -s -X POST "https://$XDR_SERVER/api/agent/heartbeat" \
        -H "Content-Type: application/json" -H "X-Agent-ID: $AGENT_ID" -H "X-API-Key: $API_KEY" \
        -d "{\"agent_id\":\"$AGENT_ID\",\"hostname\":\"$(hostname)\",\"ip_address\":\"$(hostname -I | awk '{print $1}')\",\"os_type\":\"linux\",\"os_version\":\"$(uname -r)\"}" > /dev/null 2>&1 || \
    curl -s -X POST "http://$XDR_SERVER/api/agent/heartbeat" \
        -H "Content-Type: application/json" -H "X-Agent-ID: $AGENT_ID" -H "X-API-Key: $API_KEY" \
        -d "{\"agent_id\":\"$AGENT_ID\",\"hostname\":\"$(hostname)\",\"ip_address\":\"$(hostname -I | awk '{print $1}')\",\"os_type\":\"linux\",\"os_version\":\"$(uname -r)\"}" > /dev/null 2>&1
}

main() {
    log "Agent started (v2.0 - with app logs)"
    local heartbeat_counter=0
    
    while true; do
        for log_type in $LOG_TYPES; do
            case $log_type in
                syslog) 
                    collect_logs "/var/log/syslog" "syslog"
                    collect_logs "/var/log/messages" "syslog" 
                    ;;
                auth) 
                    collect_logs "/var/log/auth.log" "auth"
                    collect_logs "/var/log/secure" "auth" 
                    ;;
                app) 
                    # Apache logs
                    collect_logs "/var/log/apache2/error.log" "apache"
                    collect_logs "/var/log/apache2/access.log" "apache"
                    collect_logs "/var/log/httpd/error_log" "apache"
                    collect_logs "/var/log/httpd/access_log" "apache"
                    # Nginx logs
                    collect_logs "/var/log/nginx/error.log" "nginx"
                    collect_logs "/var/log/nginx/access.log" "nginx"
                    # MySQL/MariaDB logs
                    collect_logs "/var/log/mysql/error.log" "mysql"
                    collect_logs "/var/log/mariadb/mariadb.log" "mysql"
                    collect_logs "/var/log/mysqld.log" "mysql"
                    # PostgreSQL logs
                    collect_logs "/var/log/postgresql/postgresql-main.log" "postgresql"
                    # Laravel logs
                    for laravel_log in /var/www/*/storage/logs/laravel.log; do
                        [ -f "$laravel_log" ] && collect_logs "$laravel_log" "laravel"
                    done
                    for laravel_log in /home/*/public_html/storage/logs/laravel.log; do
                        [ -f "$laravel_log" ] && collect_logs "$laravel_log" "laravel"
                    done
                    # Node.js PM2 logs
                    for pm2_log in /root/.pm2/logs/*.log; do
                        [ -f "$pm2_log" ] && collect_logs "$pm2_log" "nodejs"
                    done
                    # Docker logs
                    collect_logs "/var/log/docker.log" "docker"
                    # Fail2ban logs
                    collect_logs "/var/log/fail2ban.log" "fail2ban"
                    ;;
            esac
        done
        
        for batch in $QUEUE_DIR/batch_*.json; do [ -f "$batch" ] && send_logs "$batch"; done
        
        heartbeat_counter=$((heartbeat_counter + 1))
        [ $heartbeat_counter -ge 5 ] && { send_heartbeat; heartbeat_counter=0; }
        
        sleep $SEND_INTERVAL
    done
}

main
AGENT_SCRIPT

chmod +x $INSTALL_DIR/xdr-agent.sh

# Restart the service
echo -e "${YELLOW}[*] Restarting agent service...${NC}"
systemctl start athena-xdr-agent

sleep 2
if systemctl is-active --quiet athena-xdr-agent; then
    echo -e "${GREEN}"
    echo "╔══════════════════════════════════════════════════════════════╗"
    echo "║           ✓ Agent updated successfully!                     ║"
    echo "╚══════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
    echo ""
    echo -e "Agent ID:     ${BLUE}$AGENT_ID${NC}"
    echo -e "Version:      ${BLUE}2.0 (with app logs)${NC}"
    echo -e "Status:       ${GREEN}Running${NC}"
    echo -e "Backup:       ${YELLOW}$BACKUP_DIR${NC}"
    echo ""
    echo -e "${GREEN}New log sources:${NC}"
    echo "  - Apache/Nginx access & error logs"
    echo "  - MySQL/MariaDB logs"
    echo "  - PostgreSQL logs"
    echo "  - Laravel application logs"
    echo "  - Node.js PM2 logs"
    echo "  - Docker logs"
    echo "  - Fail2ban logs"
    echo ""
else
    echo -e "${RED}[ERROR] Service failed to start${NC}"
    echo -e "${YELLOW}[*] Restoring backup...${NC}"
    cp "$BACKUP_DIR/xdr-agent.sh" "$INSTALL_DIR/" 2>/dev/null || true
    cp "$BACKUP_DIR/config.conf" "$INSTALL_DIR/" 2>/dev/null || true
    systemctl start athena-xdr-agent
    exit 1
fi
BASH;

        return response($script, 200)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="update.sh"');
    }
}
