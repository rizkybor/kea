<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Job Connector (JOB-01..03): perusahaan mitra, lowongan, lamaran + riwayat status, dan data talent pool.
 * Tabel lowongan bernama `job_postings` karena `jobs` dipakai queue Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('industry')->nullable();
            $table->string('city')->nullable();
            $table->string('website')->nullable();
            $table->string('logo_url')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestampsTz();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('city')->nullable();
            $table->jsonb('skills')->nullable();
            $table->boolean('open_to_work')->default(false);
            // Consent UU PDP: profil hanya tampil di talent pool bila learner setuju.
            $table->timestampTz('talent_consent_at')->nullable();
        });

        Schema::table('programs', function (Blueprint $table) {
            $table->jsonb('career_tracks')->nullable();
        });

        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('employment_type', 20); // full_time|internship|contract|part_time
            $table->string('work_mode', 20); // onsite|hybrid|remote
            $table->string('location')->nullable();
            $table->unsignedBigInteger('salary_min')->nullable();
            $table->unsignedBigInteger('salary_max')->nullable();
            $table->boolean('show_salary')->default(false);
            $table->text('description');
            $table->jsonb('requirements')->nullable();
            $table->jsonb('skills')->nullable();
            $table->boolean('alumni_only')->default(true);
            $table->string('status', 20)->default('draft'); // draft|open|closed
            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('closes_at')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'published_at']);
        });

        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('applied'); // applied|reviewing|interview|offered|hired|rejected|withdrawn
            $table->text('cover_letter')->nullable();
            $table->string('cv_path')->nullable(); // snapshot CV saat melamar
            $table->string('portfolio_url')->nullable();
            $table->timestampTz('hired_at')->nullable();
            $table->timestampsTz();
            $table->unique(['job_posting_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index(['job_posting_id', 'status']);
        });

        Schema::create('job_application_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20);
            $table->text('note')->nullable();
            $table->timestampTz('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_application_events');
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_postings');
        Schema::table('programs', fn (Blueprint $table) => $table->dropColumn('career_tracks'));
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn(['city', 'skills', 'open_to_work', 'talent_consent_at']);
        });
        Schema::dropIfExists('companies');
    }
};
