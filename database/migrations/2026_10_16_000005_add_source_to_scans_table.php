<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Scans can now come from photos and uploaded documents (PDF, Word, TXT).
     */
    public function up(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->renameColumn('image_path', 'file_path');
        });

        Schema::table('scans', function (Blueprint $table) {
            $table->string('source', 10)->default('photo')->after('text_type'); // photo, pdf, word, text
            $table->string('original_name')->nullable()->after('file_path');
            $table->string('extraction_method', 20)->nullable()->after('original_name'); // ocr, text_layer, document
        });
    }

    public function down(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->dropColumn(['source', 'original_name', 'extraction_method']);
        });

        Schema::table('scans', function (Blueprint $table) {
            $table->renameColumn('file_path', 'image_path');
        });
    }
};
