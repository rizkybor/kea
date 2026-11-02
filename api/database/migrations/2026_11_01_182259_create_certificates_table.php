<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->unique()->constrained()->restrictOnDelete();
            $table->string('code', 40)->unique();
            $table->string('recipient_name');
            $table->string('program_title');
            $table->string('cohort_name')->nullable();
            $table->unsignedTinyInteger('final_score')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestampTz('issued_at');
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
