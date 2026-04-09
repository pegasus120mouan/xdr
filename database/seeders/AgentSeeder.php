<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Asset;
use App\Models\TenantGroup;
use Illuminate\Database\Seeder;

class AgentSeeder extends Seeder
{
    public function run(): void
    {
        $group = TenantGroup::where('name', 'Fisher')->first();
        
        if (!$group) {
            $this->command->error('Group Fisher not found!');
            return;
        }

        // Create the agent
        $agent = Agent::updateOrCreate(
            ['agent_id' => 'AGT-MANUAL-001'],
            [
                'name' => 'client1',
                'hostname' => 'client1',
                'ip_address' => '10.10.0.108',
                'tenant_group_id' => $group->id,
                'os_type' => 'linux',
                'os_version' => '6.8.0-107-generic',
                'api_key' => 'test_key_123',
                'status' => 'active',
                'registered_at' => now(),
                'last_heartbeat' => now(),
                'config' => [
                    'log_types' => ['syslog', 'auth'],
                    'send_interval' => 60,
                ],
            ]
        );

        // Create the asset
        $asset = Asset::updateOrCreate(
            ['hostname' => 'client1', 'tenant_group_id' => $group->id],
            [
                'ip_address' => '10.10.0.108',
                'type' => 'server',
                'os_type' => 'linux',
                'os_version' => 'Ubuntu 24.04.4 LTS',
                'status' => 'online',
                'risk_level' => 'low',
                'last_seen' => now(),
                'agent_version' => '1.0.0',
                'agent_installed_at' => now(),
            ]
        );

        // Link agent to asset
        $agent->update(['asset_id' => $asset->id]);

        $this->command->info('Agent and Asset created successfully!');
        $this->command->info("Agent ID: {$agent->agent_id}");
        $this->command->info("Asset: {$asset->hostname} ({$asset->ip_address})");
    }
}
