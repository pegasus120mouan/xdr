<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            // MySQL STRICT + NO_ZERO_DATE : un ALTER échoue si last_seen/first_seen = 0000-00-00.
            $previousMode = (string) (DB::selectOne('SELECT @@SESSION.sql_mode AS m')->m ?? '');
            DB::statement("SET SESSION sql_mode = ''");

            try {
                DB::statement("
                    UPDATE security_alerts
                    SET first_seen = COALESCE(created_at, UTC_TIMESTAMP())
                    WHERE first_seen IS NULL
                       OR CAST(first_seen AS CHAR) LIKE '0000-00-00%'
                       OR first_seen < '1970-01-01 00:00:01'
                ");
                DB::statement("
                    UPDATE security_alerts
                    SET last_seen = COALESCE(first_seen, created_at, UTC_TIMESTAMP())
                    WHERE last_seen IS NULL
                       OR CAST(last_seen AS CHAR) LIKE '0000-00-00%'
                       OR last_seen < '1970-01-01 00:00:01'
                ");
                DB::statement("
                    UPDATE security_alerts
                    SET resolved_at = NULL
                    WHERE resolved_at IS NOT NULL
                      AND (
                        CAST(resolved_at AS CHAR) LIKE '0000-00-00%'
                        OR resolved_at < '1970-01-01 00:00:01'
                      )
                ");

                Schema::table('security_alerts', function (Blueprint $table) {
                    $table->foreignId('tenant_group_id')
                        ->nullable()
                        ->after('detection_rule_id')
                        ->constrained('tenant_groups')
                        ->nullOnDelete();
                });
            } finally {
                DB::statement('SET SESSION sql_mode = '.DB::getPdo()->quote($previousMode));
            }
        }

        if (Schema::hasColumn('security_alerts', 'tenant_group_id')) {
            if (! Schema::hasIndex('security_alerts', 'security_alerts_tenant_group_id_status_index')) {
                Schema::table('security_alerts', function (Blueprint $table) {
                    $table->index(['tenant_group_id', 'status'], 'security_alerts_tenant_group_id_status_index');
                });
            }
            if (! Schema::hasIndex('security_alerts', 'security_alerts_tenant_group_id_created_at_index')) {
                Schema::table('security_alerts', function (Blueprint $table) {
                    $table->index(['tenant_group_id', 'created_at'], 'security_alerts_tenant_group_id_created_at_index');
                });
            }
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
