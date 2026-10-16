<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable(); // short title of the text, suggested by the AI
            $table->text('raw_extracted_text')->nullable();
            $table->unsignedInteger('word_count')->default(0);
            $table->enum('school_type', ['AHS', 'BHS']);
            $table->string('department')->nullable(); // e.g. "Chemieingenieurwesen"
            $table->string('text_type'); // e.g. "Technical Report", "Essay"
            $table->enum('status', ['processing', 'completed', 'failed'])->default('processing');
            $table->unsignedTinyInteger('progress_step')->default(1); // 1 upload done … 4 all done
            $table->string('image_path')->nullable(); // deleted again after processing
            $table->boolean('uses_sample')->default(false);
            $table->string('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scans');
    }
};
