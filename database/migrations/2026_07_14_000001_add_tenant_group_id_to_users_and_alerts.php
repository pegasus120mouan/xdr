<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'tenant_group_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('tenant_group_id')
                    ->nullable()
                    ->after('role')
                    ->constrained('tenant_groups')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('security_alerts', 'tenant_group_id')) {
            Schema::table('security_alerts', function (Blueprint $table) {
                $table->foreignId('tenant_group_id')
                    ->nullable()
                    ->after('detection_rule_id')
                    ->constrained('tenant_groups')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasColumn('security_alerts', 'tenant_group_id')) {
            Schema::table('security_alerts', function (Blueprint $table) {
                if (! Schema::hasIndex('security_alerts', 'security_alerts_tenant_group_id_status_index')) {
                    $table->index(['tenant_group_id', 'status'], 'security_alerts_tenant_group_id_status_index');
                }
                if (! Schema::hasIndex('security_alerts', 'security_alerts_tenant_group_id_created_at_index')) {
                    $table->index(['tenant_group_id', 'created_at'], 'security_alerts_tenant_group_id_created_at_index');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('security_alerts', 'tenant_group_id')) {
            Schema::table('security_alerts', function (Blueprint $table) {
                $table->dropConstrainedForeignId('tenant_group_id');
            });
        }

        if (Schema::hasColumn('users', 'tenant_group_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('tenant_group_id');
            });
        }
    }
};
