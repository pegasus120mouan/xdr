<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change category from enum to string to support more categories
        DB::statement("ALTER TABLE detection_rules MODIFY COLUMN category VARCHAR(50)");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE detection_rules MODIFY COLUMN category ENUM('brute_force', 'malware', 'intrusion', 'data_exfiltration', 'privilege_escalation', 'lateral_movement', 'persistence', 'command_control')");
    }
};
