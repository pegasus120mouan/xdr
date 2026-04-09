<?php

namespace Database\Seeders;

use App\Models\DetectionRule;
use Illuminate\Database\Seeder;

class DetectionRulesSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            // Brute Force Rules
            [
                'name' => 'Login Brute Force - IP Based',
                'slug' => 'brute-force-ip',
                'description' => 'Detects multiple failed login attempts from the same IP address within a short time window.',
                'category' => 'brute_force',
                'severity' => 'high',
                'conditions' => ['ip_based'],
                'actions' => [
                    ['type' => 'log'],
                    ['type' => 'notify', 'channel' => 'email'],
                ],
                'threshold' => 5,
                'time_window' => 300, // 5 minutes
                'cooldown' => 3600,
                'is_active' => true,
                'is_system' => true,
                'mitre_tactics' => ['TA0006'], // Credential Access
                'mitre_techniques' => ['T1110', 'T1110.001'], // Brute Force
            ],
            [
                'name' => 'Login Brute Force - Account Based',
                'slug' => 'brute-force-account',
                'description' => 'Detects multiple failed login attempts targeting the same account from different IPs.',
                'category' => 'brute_force',
                'severity' => 'high',
                'conditions' => ['email_based'],
                'actions' => [
                    ['type' => 'log'],
                    ['type' => 'notify', 'channel' => 'email'],
                ],
                'threshold' => 10,
                'time_window' => 600, // 10 minutes
                'cooldown' => 3600,
                'is_active' => true,
                'is_system' => true,
                'mitre_tactics' => ['TA0006'],
                'mitre_techniques' => ['T1110', 'T1110.003'], // Password Spraying
            ],
            [
                'name' => 'Aggressive Brute Force Attack',
                'slug' => 'brute-force-aggressive',
                'description' => 'Detects aggressive brute force attacks with automatic IP blocking.',
                'category' => 'brute_force',
                'severity' => 'critical',
                'conditions' => ['ip_based'],
                'actions' => [
                    ['type' => 'block_ip', 'duration' => 24], // Block for 24 hours
                    ['type' => 'log'],
                    ['type' => 'notify', 'channel' => 'email'],
                ],
                'threshold' => 20,
                'time_window' => 300, // 5 minutes
                'cooldown' => 86400, // 24 hours
                'is_active' => true,
                'is_system' => true,
                'mitre_tactics' => ['TA0006'],
                'mitre_techniques' => ['T1110'],
            ],
            [
                'name' => 'Distributed Brute Force',
                'slug' => 'brute-force-distributed',
                'description' => 'Detects distributed brute force attacks targeting multiple accounts.',
                'category' => 'brute_force',
                'severity' => 'critical',
                'conditions' => ['ip_based', 'email_based'],
                'actions' => [
                    ['type' => 'block_ip', 'duration' => 48],
                    ['type' => 'log'],
                    ['type' => 'notify', 'channel' => 'email'],
                ],
                'threshold' => 50,
                'time_window' => 900, // 15 minutes
                'cooldown' => 86400,
                'is_active' => true,
                'is_system' => true,
                'mitre_tactics' => ['TA0006'],
                'mitre_techniques' => ['T1110.003'], // Password Spraying
            ],
            [
                'name' => 'Slow Brute Force',
                'slug' => 'brute-force-slow',
                'description' => 'Detects slow brute force attacks spread over a longer time period.',
                'category' => 'brute_force',
                'severity' => 'medium',
                'conditions' => ['ip_based'],
                'actions' => [
                    ['type' => 'log'],
                    ['type' => 'notify', 'channel' => 'email'],
                ],
                'threshold' => 15,
                'time_window' => 3600, // 1 hour
                'cooldown' => 7200,
                'is_active' => true,
                'is_system' => true,
                'mitre_tactics' => ['TA0006'],
                'mitre_techniques' => ['T1110'],
            ],
            [
                'name' => 'Credential Stuffing Detection',
                'slug' => 'credential-stuffing',
                'description' => 'Detects credential stuffing attacks using leaked credentials.',
                'category' => 'brute_force',
                'severity' => 'high',
                'conditions' => ['ip_based', 'email_based'],
                'actions' => [
                    ['type' => 'block_ip', 'duration' => 12],
                    ['type' => 'log'],
                    ['type' => 'notify', 'channel' => 'email'],
                ],
                'threshold' => 30,
                'time_window' => 600, // 10 minutes
                'cooldown' => 3600,
                'is_active' => true,
                'is_system' => true,
                'mitre_tactics' => ['TA0006'],
                'mitre_techniques' => ['T1110.004'], // Credential Stuffing
            ],
        ];

        foreach ($rules as $rule) {
            DetectionRule::updateOrCreate(
                ['slug' => $rule['slug']],
                $rule
            );
        }
    }
}
