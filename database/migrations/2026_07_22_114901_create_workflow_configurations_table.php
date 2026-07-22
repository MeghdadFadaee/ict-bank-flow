<?php

use App\Domain\Loan\Enums\WorkflowConfigurationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workflow_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_type_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->unsignedInteger('version');
            $table->string('status')->default(WorkflowConfigurationStatus::Draft->value)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['loan_type_id', 'version']);
            $table->unique(['id', 'loan_type_id']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("CREATE UNIQUE INDEX workflow_configurations_one_published_per_loan_type ON workflow_configurations (loan_type_id) WHERE status = 'PUBLISHED'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_configurations');
    }
};
