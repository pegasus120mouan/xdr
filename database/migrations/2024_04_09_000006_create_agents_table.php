<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('agent_id', 64)->unique();
            $table->string('name');
            $table->string('hostname');
            $table->string('ip_address', 45)->nullable();
            $table->foreignId('tenant_group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('os_type', ['linux', 'windows', 'macos'])->default('linux');
            $table->string('os_version')->nullable();
            $table->string('agent_version')->nullable();
            $table->enum('status', ['active', 'inactive', 'pending', 'error'])->default('pending');
            $table->timestamp('last_heartbeat')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->string('api_key', 64)->unique();
            $table->json('config')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_group_id', 'status']);
            $table->index('api_key');
        });

        Schema::create('agent_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->string('log_type', 50); // syslog, auth, apache, nginx, custom
            $table->string('source_file')->nullable();
            $table->text('message');
            $table->enum('severity', ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'])->default('info');
            $table->string('facility')->nullable();
            $table->string('hostname')->nullable();
            $table->string('process')->nullable();
            $table->integer('pid')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamp('log_timestamp')->nullable();
            $table->timestamps();

            $table->index(['agent_id', 'log_type']);
            $table->index(['severity', 'created_at']);
            $table->index('log_timestamp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_logs');
        Schema::dropIfExists('agents');
    }
};
