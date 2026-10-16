<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feedback_id')->constrained('feedbacks')->onDelete('cascade');
            $table->string('wrong_text');
            $table->string('correct_text');
            $table->enum('error_type', ['Grammar', 'Vocabulary', 'Style', 'Technical Term']);
            $table->text('explanation');
            // Character offsets of wrong_text inside scans.raw_extracted_text (null = not found).
            $table->unsignedInteger('start_offset')->nullable();
            $table->unsignedInteger('end_offset')->nullable();
            $table->timestamp('understood_at')->nullable();
            $table->timestamps();

            $table->index('error_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corrections');
    }
};
