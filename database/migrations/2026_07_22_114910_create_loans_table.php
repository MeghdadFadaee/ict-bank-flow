<?php

use App\Domain\Loan\Enums\LoanStatus;
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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->string('customer_id')->index();
            $table->foreignId('loan_type_id');
            $table->foreignId('workflow_configuration_id');
            $table->foreignId('current_workflow_configuration_step_id')->nullable();
            $table->unsignedBigInteger('amount');
            $table->string('phone', 11);
            $table->unsignedBigInteger('monthly_income');
            $table->unsignedInteger('credit_score');
            $table->boolean('has_guarantor');
            $table->string('status')->default(LoanStatus::Submitted->value)->index();
            $table->timestamps();

            $table->foreign(['workflow_configuration_id', 'loan_type_id'])
                ->references(['id', 'loan_type_id'])
                ->on('workflow_configurations')
                ->restrictOnDelete();
            $table->foreign(['current_workflow_configuration_step_id', 'workflow_configuration_id'])
                ->references(['id', 'workflow_configuration_id'])
                ->on('workflow_configuration_steps')
                ->restrictOnDelete();
            $table->index('current_workflow_configuration_step_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
