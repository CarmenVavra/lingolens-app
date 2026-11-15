<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional task prompt ("Aufgabenstellung"): photographed or typed by the
 * student, checked by the AI ("Passt der Text zur Aufgabe?").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->text('task_text')->nullable()->after('raw_extracted_text');
            $table->string('task_source')->nullable()->after('task_text'); // photo, pdf, word, text – null when typed or missing
            $table->string('task_file_path')->nullable()->after('task_source'); // deleted again after processing
        });

        Schema::table('feedbacks', function (Blueprint $table) {
            $table->string('task_fit')->nullable()->after('matura_tip'); // fits, partly, off_topic
            $table->text('task_feedback')->nullable()->after('task_fit');
            $table->json('task_tips')->nullable()->after('task_feedback');
        });
    }

    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropColumn(['task_fit', 'task_feedback', 'task_tips']);
        });

        Schema::table('scans', function (Blueprint $table) {
            $table->dropColumn(['task_text', 'task_source', 'task_file_path']);
        });
    }
};
