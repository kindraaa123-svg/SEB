<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_codes', function (Blueprint $table) {
            $table->id();
            $table->string('type', 64)->index();
            $table->string('code', 6)->unique();
            $table->string('supervisor_link', 255)->nullable();
            $table->unsignedBigInteger('generated_by_supervisor_id')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('access_code_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('access_code_id')->constrained('access_codes')->cascadeOnDelete();
            $table->string('student_identifier', 100);
            $table->timestamp('used_at');
            $table->timestamps();

            $table->unique(['access_code_id', 'student_identifier'], 'uniq_code_usage_per_student');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_code_usages');
        Schema::dropIfExists('access_codes');
    }
};

