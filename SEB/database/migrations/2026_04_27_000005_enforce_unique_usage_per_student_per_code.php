<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('used_code')) {
            return;
        }

        // Keep one record for each (codeid, studentid) pair before adding unique index.
        DB::statement('
            DELETE uc1
            FROM used_code uc1
            INNER JOIN used_code uc2
                ON uc1.codeid = uc2.codeid
                AND uc1.studentid = uc2.studentid
                AND uc1.usedid > uc2.usedid
        ');

        Schema::table('used_code', function (Blueprint $table): void {
            $table->unique(['codeid', 'studentid'], 'uniq_used_code_per_student');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('used_code')) {
            return;
        }

        Schema::table('used_code', function (Blueprint $table): void {
            $table->dropUnique('uniq_used_code_per_student');
        });
    }
};

