<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workflow_configuration_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_configuration_id')->constrained()->restrictOnDelete();
            $table->foreignId('stage_definition_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('position');
            $table->json('rules');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['workflow_configuration_id', 'position']);
            $table->unique(['workflow_configuration_id', 'stage_definition_id']);
            $table->unique(['id', 'workflow_configuration_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_configuration_steps');
    }
};
