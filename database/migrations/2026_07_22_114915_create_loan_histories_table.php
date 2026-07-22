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
        Schema::create('loan_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_configuration_step_id')->constrained()->restrictOnDelete();
            $table->string('stage_code');
            $table->json('rules_snapshot');
            $table->string('result');
            $table->string('reason');
            $table->timestamp('executed_at')->index();
            $table->timestamps();

            $table->unique(['loan_id', 'workflow_configuration_step_id']);
            $table->index(['loan_id', 'executed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_histories');
    }
};
