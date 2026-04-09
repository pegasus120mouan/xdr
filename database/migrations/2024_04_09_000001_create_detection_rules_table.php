<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detection_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('category', ['brute_force', 'malware', 'intrusion', 'data_exfiltration', 'privilege_escalation', 'lateral_movement', 'persistence', 'command_control']);
            $table->enum('severity', ['critical', 'high', 'medium', 'low'])->default('medium');
            $table->json('conditions'); // Conditions de déclenchement
            $table->json('actions'); // Actions à effectuer
            $table->integer('threshold')->default(5); // Seuil de déclenchement
            $table->integer('time_window')->default(300); // Fenêtre de temps en secondes
            $table->integer('cooldown')->default(3600); // Temps avant nouvelle alerte
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false); // Règle système non modifiable
            $table->json('mitre_tactics')->nullable(); // MITRE ATT&CK tactics
            $table->json('mitre_techniques')->nullable(); // MITRE ATT&CK techniques
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detection_rules');
    }
};
