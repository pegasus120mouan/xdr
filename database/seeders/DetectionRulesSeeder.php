<?php

namespace Database\Seeders;

use App\Models\DetectionRule;
use Illuminate\Database\Seeder;

class DetectionRulesSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            // ============================================
            // SSH/Authentication Rules (for Agent Logs)
            // ============================================
            [
                'name' => 'SSH Failed Login Attempt',
                'slug' => 'ssh-failed-login',
                'description' => 'Detects failed SSH login attempts on monitored servers.',
                'category' => 'authentication',
                'severity' => 'medium',
                'conditions' => [
                    'log_types' => ['auth', 'syslog'],
                    'patterns' => ['Failed password', 'authentication failure', 'Failed publickey'],
                    'match_type' => 'any',
                ],
                'actions' => [['type' => 'log'], ['type' => 'alert']],
                'threshold' => 1,
                'time_window' => 60,
                'cooldown' => 300,
                'is_active' => true,
                'is_system' => true,
                'mitre_tactics' => ['TA0006'],
                'mitre_techniques' => ['T1110'],
            ],
            [
                'name' => 'SSH Brute Force Attack',
                'slug' => 'ssh-brute-force',
                'description' => 'Detects multiple failed SSH login attempts indicating a brute force attack.',
                'category' => 'brute_force',
                'severity' => 'high',
                'conditions' => [
                    'log_types' => ['auth', 'syslog'],
                    'patterns' => ['Failed password for', 'Failed password for invalid user'],
                    'match_type' => 'any',
                ],
                'actions' => [['type' => 'log'], ['type' => 'alert'], ['type' => 'notify']],
                'threshold' => 5,
                'time_window' => 300,
                'cooldown' => 3600,
                'is_active' => true,
                'is_system' => true,
                'mitre_tactics' => ['TA0006'],
                'mitre_techniques' => ['T1110', 'T1110.001'],
            ],
            [
                'name' => 'Invalid User SSH Attempt',
                'slug' => 'ssh-invalid-user',
                'description' => 'Detects SSH login attempts with non-existent usernames.',
                'category' => 'authentication',
                'severity' => 'high',
                'conditions' => [
                    'log_types' => ['auth', 'syslog'],
                    'patterns' => ['Invalid user', 'invalid user'],
                    'match_type' => 'any',
                ],
                'actions' => [['type' => 'log'], ['type' => 'alert']],
                'threshold' => 1,
                'time_window' => 60,
                'cooldown' => 300,
                'is_active' => true,
                'is_system' => true,
                'mitre_tactics' => ['TA0006'],
                'mitre_techniques' => ['T1078'],
            ],
            [
                'name' => 'Successful Root Login',
                'slug' => 'root-login-success',
                'description' => 'Detects successful root login which may indicate unauthorized access.',
                'category' => 'authentication',
                'severity' => 'high',
                'conditions' => [
                    'log_types' => ['auth', 'syslog'],
                    'patterns' => ['Accepted password for root', 'Accepted publickey for root', 'session opened for user root'],
                    'match_type' => 'any',
                ],
                'actions' => [['type' => 'log'], ['type' => 'alert'], ['type' => 'notify']],
                'threshold' => 1,
                'time_window' => 60,
                'cooldown' => 600,
                'is_active' => true,
                'is_system' => true,
                'mitre_tactics' => ['TA0004'],
                'mitre_techniques' => ['T1078.003'],
            ],
            
            // ============================================
            // Privilege Escalation Rules
            // ============================================
            [
                'name' => 'Sudo Command Executed',
                'slug' => 'sudo-command',
                'description' => 'Detects sudo command execution for privilege escalation monitoring.',
                'category' => 'privilege_escalation',
                'severity' => 'low',
                'conditions' => [
                    'log_types' => ['auth', 'syslog'],
                    'patterns' => ['sudo:', 'COMMAND='],
                    'match_type' => 'all',
                ],
                'actions' => [['type' => 'log']],
                'threshold' => 1,
                'time_window' => 60,
                'cooldown' => 60,
                'is_active' => true,
                'is_system' => true,
                'mitre_tactics' => ['TA0004'],
                'mitre_techniques' => ['T1548.003'],
            ],
            [
                'name' => 'Sudo Authentication Failure',
                'slug' => 'sudo-auth-failure',
                'description' => 'Detects failed sudo authentication attempts.',
                'category' => 'privilege_escalation',
                'severity' => 'medium',
                'conditions' => [
                    'log_types' => ['auth', 'syslog'],
                    'patterns' => ['sudo', 'authentication failure', '3 incorrect password attempts'],
                    'match_type' => 'any',
                ],
                'actions' => [['type' => 'log'], ['type' => 'alert']],
                'threshold' => 1,
                'time_window' => 60,
                'cooldown' => 300,
                'is_active' => true,
                'is_system' => true,
                'mitre_tactics' => ['TA0004'],
                'mitre_techniques' => ['T1548'],
            ],
            [
                'name' => 'User Added to Sudoers',
                'slug' => 'sudoers-modified',
                'description' => 'Detects modifications to sudoers file or group.',
                'category' => 'privilege_escalation',
                'severity' => 'critical',
                'conditions' => [
                    'log_types' => ['auth', 'syslog'],
                    'patterns' => ['added to group sudo', 'visudo', '/etc/sudoers'],
                    'match_type' => 'any',
                ],
                'actions' => [['type' => 'log'], ['type' => 'alert'], ['type' => 'notify']],
                'threshold' => 1,
                'time_window' => 60,
                'cooldown' => 3600,
                'is_active' => true,
                'is_system' => true,
                'mitre_tactics' => ['TA0003', 'TA0004'],
                'mitre_techniques' => ['T1548.003'],
            ],

            // ============================================
            // System Security Rules
            // ============================================
            [
                'name' => 'Service Started/Stopped',
                'slug' => 'service-change',
                'description' => 'Detects system service state changes.',
                'category' => 'system',
                'severity' => 'low',
                'conditions' => [
                    'log_types' => ['syslog'],
                    'patterns' => ['Started', 'Stopped', 'systemd'],
                    'match_type' => 'any',
                ],
                'actions' => [['type' => 'log']],
                'threshold' => 1,
                'time_window' => 60,
                'cooldown' => 60,
                'is_active' => false, // Disabled by default (too noisy)
                'is_system' => true,
                'mitre_tactics' => ['TA0003'],
                'mitre_techniques' => ['T1543'],
            ],
            [
                'name' => 'Firewall Rule Changed',
                'slug' => 'firewall-change',
                'description' => 'Detects changes to firewall rules.',
                'category' => 'system',
                'severity' => 'high',
                'conditions' => [
                    'log_types' => ['syslog', 'auth'],
                    'patterns' => ['iptables', 'ufw', 'firewalld', 'nftables'],
                    'match_type' => 'any',
                ],
                'actions' => [['type' => 'log'], ['type' => 'alert']],
                'threshold' => 1,
                'time_window' => 60,
                'cooldown' => 300,
                'is_active' => true,
                'is_system' => true,
                'mitre_tactics' => ['TA0005'],
                'mitre_techniques' => ['T1562.004'],
            ],
            [
                'name' => 'New User Created',
                'slug' => 'user-created',
                'description' => 'Detects creation of new user accounts.',
                'category' => 'persistence',
                'severity' => 'medium',
                'conditions' => [
                    'log_types' => ['auth', 'syslog'],
                    'patterns' => ['useradd', 'adduser', 'new user'],
                    'match_type' => 'any',
                ],
                'actions' => [['type' => 'log'], ['type' => 'alert']],
                'threshold' => 1,
                'time_window' => 60,
                'cooldown' => 600,
                'is_active' => true,
                'is_system' => true,
                'mitre_tactics' => ['TA0003'],
                'mitre_techniques' => ['T1136.001'],
            ],
            [
                'name' => 'User Deleted',
                'slug' => 'user-deleted',
                'description' => 'Detects deletion of user accounts.',
                'category' => 'system',
                'severity' => 'medium',
                'conditions' => [
                    'log_types' => ['auth', 'syslog'],
                    'patterns' => ['userdel', 'deluser', 'delete user'],
                    'match_type' => 'any',
                ],
                'actions' => [['type' => 'log'], ['type' => 'alert']],
                'threshold' => 1,
                'time_window' => 60,
                'cooldown' => 600,
                'is_active' => true,
                'is_system' => true,
                'mitre_tactics' => ['TA0040'],
                'mitre_techniques' => ['T1531'],
            ],

            // ============================================
            // Malware/Suspicious Activity Rules
            // ============================================
            [
                'name' => 'Suspicious Command Execution',
                'slug' => 'suspicious-command',
                'description' => 'Detects execution of potentially malicious commands.',
                'category' => 'malware',
                'severity' => 'critical',
                'conditions' => [
                    'log_types' => ['auth', 'syslog'],
                    'patterns' => [
                        'wget http',
                        'curl http',
                        '/dev/tcp/',
                        'base64 -d',
                        'python -c',
                        'perl -e',
                        'nc -e',
                        'bash -i',
                    ],
                    'match_type' => 'any',
                ],
                'actions' => [['type' => 'log'], ['type' => 'alert'], ['type' => 'notify']],
                'threshold' => 1,
                'time_window' => 60,
                'cooldown' => 300,
                'is_active' => true,
                'is_system' => true,
                'mitre_tactics' => ['TA0002'],
                'mitre_techniques' => ['T1059'],
            ],
            [
                'name' => 'Reverse Shell Detection',
                'slug' => 'reverse-shell',
                'description' => 'Detects potential reverse shell connections.',
                'category' => 'malware',
                'severity' => 'critical',
                'conditions' => [
                    'log_types' => ['auth', 'syslog'],
                    'patterns' => [
                        '/dev/tcp/',
                        'bash -i >& /dev',
                        'nc -e /bin',
                        'mkfifo',
                        'telnet.*\|.*sh',
                    ],
                    'match_type' => 'any',
                ],
                'actions' => [['type' => 'log'], ['type' => 'alert'], ['type' => 'notify']],
                'threshold' => 1,
                'time_window' => 60,
                'cooldown' => 60,
                'is_active' => true,
                'is_system' => true,
                'mitre_tactics' => ['TA0011'],
                'mitre_techniques' => ['T1059.004'],
            ],
            [
                'name' => 'Cron Job Modified',
                'slug' => 'cron-modified',
                'description' => 'Detects modifications to cron jobs which could indicate persistence.',
                'category' => 'persistence',
                'severity' => 'medium',
                'conditions' => [
                    'log_types' => ['syslog', 'cron'],
                    'patterns' => ['crontab', 'REPLACE', 'LIST', '/etc/cron'],
                    'match_type' => 'any',
                ],
                'actions' => [['type' => 'log'], ['type' => 'alert']],
                'threshold' => 1,
                'time_window' => 60,
                'cooldown' => 600,
                'is_active' => true,
                'is_system' => true,
                'mitre_tactics' => ['TA0003'],
                'mitre_techniques' => ['T1053.003'],
            ],

            // ============================================
            // Web Server Rules (Apache/Nginx)
            // ============================================
            [
                'name' => 'Web Server Error Spike',
                'slug' => 'web-error-spike',
                'description' => 'Detects spike in web server errors indicating potential attack.',
                'category' => 'web',
                'severity' => 'medium',
                'conditions' => [
                    'log_types' => ['apache', 'nginx'],
                    'patterns' => ['error', '500', '502', '503', '504'],
                    'match_type' => 'any',
                ],
                'actions' => [['type' => 'log'], ['type' => 'alert']],
                'threshold' => 10,
                'time_window' => 300,
                'cooldown' => 600,
                'is_active' => true,
                'is_system' => true,
                'mitre_tactics' => ['TA0040'],
                'mitre_techniques' => ['T1499'],
            ],
            [
                'name' => 'SQL Injection Attempt',
                'slug' => 'sql-injection',
                'description' => 'Detects potential SQL injection attempts in web logs.',
                'category' => 'web',
                'severity' => 'high',
                'conditions' => [
                    'log_types' => ['apache', 'nginx'],
                    'patterns' => [
                        'UNION SELECT',
                        'OR 1=1',
                        "' OR '",
                        'DROP TABLE',
                        'INSERT INTO',
                        '--',
                        'SLEEP(',
                    ],
                    'match_type' => 'any',
                ],
                'actions' => [['type' => 'log'], ['type' => 'alert'], ['type' => 'notify']],
                'threshold' => 1,
                'time_window' => 60,
                'cooldown' => 300,
                'is_active' => true,
                'is_system' => true,
                'mitre_tactics' => ['TA0001'],
                'mitre_techniques' => ['T1190'],
            ],
            [
                'name' => 'Path Traversal Attempt',
                'slug' => 'path-traversal',
                'description' => 'Detects path traversal/directory traversal attacks.',
                'category' => 'web',
                'severity' => 'high',
                'conditions' => [
                    'log_types' => ['apache', 'nginx'],
                    'patterns' => ['../', '..\\', '/etc/passwd', '/etc/shadow', 'win.ini'],
                    'match_type' => 'any',
                ],
                'actions' => [['type' => 'log'], ['type' => 'alert']],
                'threshold' => 1,
                'time_window' => 60,
                'cooldown' => 300,
                'is_active' => true,
                'is_system' => true,
                'mitre_tactics' => ['TA0001'],
                'mitre_techniques' => ['T1083'],
            ],

            // ============================================
            // Original Brute Force Rules (for Web Login)
            // ============================================
            [
                'name' => 'Login Brute Force - IP Based',
                'slug' => 'brute-force-ip',
                'description' => 'Detects multiple failed login attempts from the same IP address within a short time window.',
                'category' => 'brute_force',
                'severity' => 'high',
                'conditions' => [
                    'patterns' => ['Failed password', 'authentication failure', 'Login failed'],
                    'match_type' => 'any',
                ],
                'actions' => [
                    ['type' => 'log'],
                    ['type' => 'notify', 'channel' => 'email'],
                ],
                'threshold' => 5,
                'time_window' => 300,
                'cooldown' => 3600,
                'is_active' => true,
                'is_system' => true,
                'mitre_tactics' => ['TA0006'],
                'mitre_techniques' => ['T1110', 'T1110.001'],
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
