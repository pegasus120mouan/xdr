<?php

namespace Database\Seeders;

use App\Models\TenantGroup;
use App\Models\Asset;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TenantsSeeder extends Seeder
{
    public function run(): void
    {
        // Root groups
        $uncategorized = TenantGroup::create([
            'name' => 'Uncategorized',
            'slug' => 'uncategorized',
            'type' => 'folder',
            'sort_order' => 1,
            'is_system' => true,
        ]);

        $managedIpRange = TenantGroup::create([
            'name' => 'Managed IP Range',
            'slug' => 'managed-ip-range',
            'type' => 'ip_range',
            'sort_order' => 2,
        ]);

        $orange = TenantGroup::create([
            'name' => 'Orange',
            'slug' => 'orange',
            'type' => 'group',
            'color' => '#f97316',
            'sort_order' => 3,
        ]);

        // Testgroup with children
        $testgroup = TenantGroup::create([
            'name' => 'Testgroup',
            'slug' => 'testgroup',
            'type' => 'folder',
            'sort_order' => 4,
        ]);

        $customerC = TenantGroup::create([
            'name' => 'Customer C',
            'slug' => 'customer-c',
            'type' => 'group',
            'parent_id' => $testgroup->id,
            'sort_order' => 1,
        ]);

        $customerA = TenantGroup::create([
            'name' => 'Customer A',
            'slug' => 'customer-a',
            'type' => 'group',
            'parent_id' => $testgroup->id,
            'sort_order' => 2,
        ]);

        // Other groups
        $groups = [
            ['name' => 'testljq', 'sort_order' => 5],
            ['name' => 'Customer123', 'sort_order' => 6],
            ['name' => 'POC09081530', 'sort_order' => 7],
            ['name' => 'POC09281258', 'sort_order' => 8],
            ['name' => 'MDR-POC-Group', 'sort_order' => 9],
            ['name' => 'staonly', 'sort_order' => 10],
            ['name' => 'BusinessGroup', 'sort_order' => 11],
            ['name' => 'Small_Customer_1', 'sort_order' => 12],
            ['name' => 'Internal Group', 'sort_order' => 13],
            ['name' => 'Internal IP Range', 'type' => 'ip_range', 'sort_order' => 14],
        ];

        foreach ($groups as $group) {
            TenantGroup::create([
                'name' => $group['name'],
                'slug' => Str::slug($group['name']),
                'type' => $group['type'] ?? 'group',
                'sort_order' => $group['sort_order'],
            ]);
        }

        // Customer 1 with Critical Assets
        $customer1 = TenantGroup::create([
            'name' => 'Customer 1',
            'slug' => 'customer-1',
            'type' => 'folder',
            'sort_order' => 15,
        ]);

        $criticalAssets = TenantGroup::create([
            'name' => 'Critical Assets',
            'slug' => 'critical-assets',
            'type' => 'group',
            'parent_id' => $customer1->id,
            'sort_order' => 1,
        ]);

        // Create sample assets
        $this->createSampleAssets($uncategorized, $customerA, $customerC, $criticalAssets, $orange);
    }

    private function createSampleAssets(...$groups): void
    {
        $hostnames = [
            'WS-PC001', 'WS-PC002', 'WS-PC003', 'SRV-DC01', 'SRV-DB01',
            'SRV-WEB01', 'SRV-APP01', 'LAPTOP-001', 'LAPTOP-002', 'LAPTOP-003',
            'WS-DEV01', 'WS-DEV02', 'SRV-FILE01', 'SRV-MAIL01', 'WS-HR001',
            'WS-FIN001', 'WS-MKT001', 'SRV-BACKUP01', 'WS-IT001', 'WS-IT002',
        ];

        $types = ['workstation', 'server', 'laptop'];
        $osTypes = ['windows', 'linux', 'macos'];
        $statuses = ['online', 'online', 'online', 'offline', 'alerting'];
        $riskLevels = ['none', 'none', 'low', 'medium', 'high', 'critical'];

        $i = 0;
        foreach ($groups as $group) {
            $assetCount = rand(3, 6);
            for ($j = 0; $j < $assetCount; $j++) {
                if ($i >= count($hostnames)) {
                    $hostname = 'ASSET-' . Str::random(6);
                } else {
                    $hostname = $hostnames[$i];
                }

                Asset::create([
                    'hostname' => $hostname,
                    'ip_address' => '192.168.' . rand(1, 254) . '.' . rand(1, 254),
                    'mac_address' => implode(':', array_map(fn() => strtoupper(Str::random(2)), range(1, 6))),
                    'type' => $types[array_rand($types)],
                    'os_type' => $osTypes[array_rand($osTypes)],
                    'os_version' => 'Windows 11 Pro',
                    'tenant_group_id' => $group->id,
                    'status' => $statuses[array_rand($statuses)],
                    'risk_level' => $riskLevels[array_rand($riskLevels)],
                    'is_critical' => $group->slug === 'critical-assets',
                    'agent_version' => '3.2.' . rand(0, 9),
                    'last_seen' => now()->subMinutes(rand(0, 1440)),
                    'agent_installed_at' => now()->subDays(rand(1, 365)),
                ]);

                $i++;
            }
        }
    }
}
