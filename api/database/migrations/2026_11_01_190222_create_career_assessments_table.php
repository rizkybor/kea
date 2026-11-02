<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mission Based Learning (LMS-08): jawaban kuesioner minat & target karier beserta hasil rekomendasinya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('career_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('target_track', 40)->nullable();
            $table->jsonb('answers');
            $table->jsonb('recommendations');
            $table->timestampsTz();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_assessments');
    }
};
