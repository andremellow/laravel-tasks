<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('description', 500)->nullable();
            $table->string('color', 20)->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('task_tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('color', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority', 20)->index();
            $table->string('status', 30)->index();
            $table->unsignedBigInteger('board_position')->default(0);
            $table->date('due_date')->nullable()->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'board_position', 'id']);
            $table->index(['status', 'completed_at', 'id']);
            $table->index(['task_type_id', 'status']);
            $table->index(['assignee_id', 'status']);
        });
        Schema::create('task_tag', function (Blueprint $table): void {
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_tag_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->primary(['task_id', 'task_tag_id']);
        });
        Schema::create('task_status_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->index();
            $table->timestamp('created_at')->useCurrent();
        });
        Schema::create('task_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('operation', 40)->index();
            $table->json('changed_attributes');
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_changes');
        Schema::dropIfExists('task_status_changes');
        Schema::dropIfExists('task_tag');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('task_tags');
        Schema::dropIfExists('task_types');
    }
};
