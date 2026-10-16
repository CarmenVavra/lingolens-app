<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the student's school profile (shown in the hero and the user chip).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('school_type', 3)->default('BHS')->after('email');
            $table->string('school_form', 10)->nullable()->after('school_type'); // HTL, HAK, HLW, AHS
            $table->string('department')->nullable()->after('school_form');
            $table->string('class_name', 20)->nullable()->after('department'); // e.g. 5CHC
            $table->unsignedTinyInteger('grade')->nullable()->after('class_name'); // 1–8
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['school_type', 'school_form', 'department', 'class_name', 'grade']);
        });
    }
};
