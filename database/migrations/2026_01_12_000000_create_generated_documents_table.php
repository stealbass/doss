<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('generated_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('conversation_id')->nullable()->index();
            $table->unsignedBigInteger('message_id')->nullable()->index();
            $table->unsignedBigInteger('template_id')->nullable()->index();
            $table->string('template_name')->comment('Template name for reference');
            $table->longText('document_content')->nullable()->comment('First 5000 chars for preview');
            $table->string('file_path')->comment('Storage path for the generated file');
            $table->string('file_name')->comment('Original file name');
            $table->string('file_type', 10)->default('txt')->comment('txt, pdf, docx');
            $table->unsignedInteger('file_size')->default(0)->comment('File size in bytes');
            $table->json('extracted_variables')->nullable()->comment('Variables used to generate');
            $table->enum('status', ['pending', 'generating', 'generated', 'failed', 'downloaded', 'expired'])
                ->default('pending')
                ->index();
            $table->text('generation_prompt')->nullable()->comment('Prompt used for generation');
            $table->json('generated_by')->nullable()->comment('AI model and tokens used');
            $table->timestamp('downloaded_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('conversation_id')
                ->references('id')
                ->on('conversations')
                ->onDelete('cascade');

            $table->foreign('message_id')
                ->references('id')
                ->on('messages')
                ->onDelete('cascade');

            $table->foreign('template_id')
                ->references('id')
                ->on('document_templates')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_documents');
    }
};
