<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tenant Groups (hierarchical structure)
        Schema::create('tenant_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('tenant_groups')->nullOnDelete();
            $table->enum('type', ['folder', 'group', 'ip_range'])->default('group');
            $table->string('ip_range_start', 45)->nullable();
            $table->string('ip_range_end', 45)->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->index('parent_id');
        });

        // Assets (machines/endpoints)
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('hostname');
            $table->string('ip_address', 45)->nullable();
            $table->string('mac_address', 17)->nullable();
            $table->enum('type', ['workstation', 'server', 'laptop', 'mobile', 'iot', 'network', 'other'])->default('workstation');
            $table->enum('os_type', ['windows', 'linux', 'macos', 'android', 'ios', 'other'])->default('windows');
            $table->string('os_version')->nullable();
            $table->foreignId('tenant_group_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['online', 'offline', 'alerting', 'unknown'])->default('unknown');
            $table->enum('risk_level', ['critical', 'high', 'medium', 'low', 'none'])->default('none');
            $table->boolean('is_critical')->default(false);
            $table->string('agent_version')->nullable();
            $table->timestamp('last_seen')->nullable();
            $table->timestamp('agent_installed_at')->nullable();
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_group_id', 'status']);
            $table->index('ip_address');
            $table->index('hostname');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
        Schema::dropIfExists('tenant_groups');
    }
};
