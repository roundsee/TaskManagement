<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->enum('task_type', ['it_dev', 'ksp_auto', 'ksp_manual']);
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', [
                'new_request',
                'in_progress',
                'qa_testing',
                'waiting_approval',
                'approved',
                'deployed',
                'completed',
            ])->default('new_request');

            // Integrasi KSP Operasional
            $table->string('loan_reference_no')->nullable();
            $table->enum('complexity_flag', ['normal', 'complex'])->default('normal');
            $table->text('complexity_note')->nullable();

            // Integrasi IT Workflow
            $table->foreignId('ba_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('business_requirement')->nullable();
            $table->text('test_case_narration')->nullable();
            $table->string('repository_url')->nullable();
            $table->boolean('is_test_passed_100')->default(false);
            $table->text('rejection_note')->nullable();

            // Tracking Waktu
            $table->integer('estimated_duration_minutes')->default(0);
            $table->integer('actual_duration_minutes')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
