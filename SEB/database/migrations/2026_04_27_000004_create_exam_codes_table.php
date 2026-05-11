<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('exam_codes')) {
            return;
        }

        Schema::create('exam_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 6)->unique();
            $table->string('target_link', 255)->nullable();
            $table->unsignedBigInteger('generated_by_supervisor_id')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_codes');
    }
};

