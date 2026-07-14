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

        $previousMode = (string) (DB::selectOne('SELECT @@SESSION.sql_mode AS m')->m ?? '');
        DB::statement("SET SESSION sql_mode = ''");

        try {
            $this->sanitizeSecurityAlertTimestamps();

            if (! Schema::hasColumn('security_alerts', 'tenant_group_id')) {
                DB::statement('ALTER TABLE security_alerts ADD COLUMN tenant_group_id BIGINT UNSIGNED NULL AFTER detection_rule_id');

                try {
                    DB::statement('
                        ALTER TABLE security_alerts
                        ADD CONSTRAINT security_alerts_tenant_group_id_foreign
                        FOREIGN KEY (tenant_group_id) REFERENCES tenant_groups (id)
                        ON DELETE SET NULL
                    ');
                } catch (Throwable) {
                    // Contrainte déjà présente ou non critique
                }
            }

            // Indexes optionnels — ne doivent plus faire échouer le deploy
            try {
                if (! Schema::hasIndex('security_alerts', 'security_alerts_tenant_group_id_status_index')) {
                    DB::statement('CREATE INDEX security_alerts_tenant_group_id_status_index ON security_alerts (tenant_group_id, status)');
                }
            } catch (Throwable) {
            }

            try {
                if (! Schema::hasIndex('security_alerts', 'security_alerts_tenant_group_id_created_at_index')) {
                    DB::statement('CREATE INDEX security_alerts_tenant_group_id_created_at_index ON security_alerts (tenant_group_id, created_at)');
                }
            } catch (Throwable) {
            }
        } finally {
            DB::statement('SET SESSION sql_mode = '.DB::getPdo()->quote($previousMode));
        }
    }

    protected function sanitizeSecurityAlertTimestamps(): void
    {
        if (! Schema::hasTable('security_alerts')) {
            return;
        }

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

        try {
            DB::statement('ALTER TABLE security_alerts MODIFY first_seen TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
            DB::statement('ALTER TABLE security_alerts MODIFY last_seen TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
        } catch (Throwable) {
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('security_alerts', 'tenant_group_id')) {
            try {
                Schema::table('security_alerts', function (Blueprint $table) {
                    $table->dropForeign(['tenant_group_id']);
                });
            } catch (Throwable) {
            }

            Schema::table('security_alerts', function (Blueprint $table) {
                $table->dropColumn('tenant_group_id');
            });
        }

        if (Schema::hasColumn('users', 'tenant_group_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('tenant_group_id');
            });
        }
    }
};
