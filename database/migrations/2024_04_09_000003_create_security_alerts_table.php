<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('detection_rule_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->enum('severity', ['critical', 'high', 'medium', 'low']);
            $table->enum('status', ['new', 'investigating', 'resolved', 'false_positive', 'escalated'])->default('new');
            $table->string('source_ip', 45)->nullable();
            $table->string('target_ip', 45)->nullable();
            $table->string('source_user')->nullable();
            $table->string('target_user')->nullable();
            $table->string('affected_asset')->nullable();
            $table->json('raw_data')->nullable(); // Données brutes de l'événement
            $table->json('evidence')->nullable(); // Preuves collectées
            $table->json('mitre_mapping')->nullable(); // Mapping MITRE ATT&CK
            $table->integer('event_count')->default(1);
            $table->timestamp('first_seen');
            $table->timestamp('last_seen');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['severity', 'status']);
            $table->index(['source_ip', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_alerts');
    }
};
