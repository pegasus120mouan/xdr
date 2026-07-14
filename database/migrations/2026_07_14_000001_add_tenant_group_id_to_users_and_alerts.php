<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_group_id')
                ->nullable()
                ->after('role')
                ->constrained('tenant_groups')
                ->nullOnDelete();
        });

        Schema::table('security_alerts', function (Blueprint $table) {
            $table->foreignId('tenant_group_id')
                ->nullable()
                ->after('detection_rule_id')
                ->constrained('tenant_groups')
                ->nullOnDelete();
            $table->index(['tenant_group_id', 'status']);
            $table->index(['tenant_group_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('security_alerts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_group_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_group_id');
        });
    }
};
