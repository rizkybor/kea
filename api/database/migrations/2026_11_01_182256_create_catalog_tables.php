<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestampsTz();
        });

        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('type', 20); // bootcamp|video_course|short_class|event
            $table->string('code', 12); // dipakai di kode sertifikat, mis. FSWD
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('summary', 500);
            $table->text('description')->nullable();
            $table->string('level', 20)->default('pemula');
            $table->string('duration_label')->nullable();
            $table->string('format_label')->nullable();
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('promo_price')->nullable();
            $table->unsignedBigInteger('installment_from')->nullable();
            $table->boolean('has_job_guarantee')->default(false);
            $table->string('thumbnail_url')->nullable();
            $table->string('preview_video_url')->nullable();
            $table->jsonb('benefits')->nullable();
            $table->jsonb('faqs')->nullable();
            $table->decimal('rating', 2, 1)->nullable();
            $table->unsignedInteger('rating_count')->default(0);
            $table->unsignedInteger('enrolled_count')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->index(['type', 'is_published']);
        });

        Schema::create('cohorts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('schedule_type', 20)->default('weekday'); // weekday|weekend
            $table->string('schedule_label')->nullable();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at')->nullable();
            $table->unsignedInteger('quota');
            $table->unsignedInteger('seats_taken')->default(0);
            $table->boolean('is_open')->default(true);
            $table->string('community_url')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->index(['program_id', 'starts_at']);
        });

        Schema::create('cohort_mentors', function (Blueprint $table) {
            $table->foreignId('cohort_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['cohort_id', 'user_id']);
        });

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('position')->default(0);
            $table->jsonb('topics')->nullable();
            $table->timestampsTz();
            $table->index(['program_id', 'position']);
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('type', 20); // video|article|quiz|assignment|live
            $table->unsignedSmallInteger('position')->default(0);
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('video_id')->nullable();
            $table->text('summary')->nullable();
            $table->string('resource_url')->nullable();
            $table->boolean('is_preview')->default(false);
            $table->string('assignment_title')->nullable();
            $table->text('assignment_instructions')->nullable();
            $table->unsignedSmallInteger('assignment_due_day')->nullable();
            $table->boolean('is_final_project')->default(false);
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->index(['section_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('cohort_mentors');
        Schema::dropIfExists('cohorts');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('categories');
    }
};
