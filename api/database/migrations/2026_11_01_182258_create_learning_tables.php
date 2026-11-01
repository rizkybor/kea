<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->foreignId('cohort_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('order_item_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('active'); // active|completed|failed|refunded
            $table->unsignedTinyInteger('progress_pct')->default(0);
            $table->foreignId('last_lesson_id')->nullable()->constrained('lessons')->nullOnDelete();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();
            $table->unique(['user_id', 'program_id', 'cohort_id']);
            $table->index(['cohort_id', 'status']);
        });
        DB::statement('CREATE UNIQUE INDEX enrollments_user_program_no_cohort_unique ON enrollments (user_id, program_id) WHERE cohort_id IS NULL');

        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('last_position_sec')->default(0);
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();
            $table->unique(['enrollment_id', 'lesson_id']);
        });

        Schema::create('live_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cohort_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('mentor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->string('join_url')->nullable();
            $table->string('recording_url')->nullable();
            $table->timestampsTz();
            $table->index(['cohort_id', 'starts_at']);
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('live_session_id')->constrained()->cascadeOnDelete();
            $table->timestampTz('joined_at');
            $table->timestampsTz();
            $table->unique(['enrollment_id', 'live_session_id']);
        });

        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->restrictOnDelete();
            $table->string('link_url')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->text('note')->nullable();
            $table->string('status', 20)->default('submitted'); // submitted|reviewed|revision
            $table->unsignedTinyInteger('score')->nullable();
            $table->text('feedback')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('graded_at')->nullable();
            $table->timestampTz('submitted_at');
            $table->timestampsTz();
            $table->unique(['enrollment_id', 'lesson_id']);
            $table->index('status');
        });

        Schema::create('discussions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cohort_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('discussions')->cascadeOnDelete();
            $table->text('body');
            $table->timestampsTz();
            $table->index(['lesson_id', 'cohort_id', 'created_at']);
        });

        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('type', 20)->default('refund'); // refund|job_guarantee
            $table->text('reason');
            $table->unsignedBigInteger('amount');
            $table->string('status', 20)->default('submitted'); // submitted|approved|rejected|paid
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampsTz();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
        Schema::dropIfExists('discussions');
        Schema::dropIfExists('submissions');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('live_sessions');
        Schema::dropIfExists('lesson_progress');
        Schema::dropIfExists('enrollments');
    }
};
