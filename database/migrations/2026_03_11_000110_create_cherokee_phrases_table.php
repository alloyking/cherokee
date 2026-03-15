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
        Schema::create('cherokee_phrases', function (Blueprint $table) {
            $table->id();
            $table->string('seed_id')->unique();
            $table->text('cherokee');
            $table->string('transliteration')->nullable();
            $table->string('ipa')->nullable();
            $table->string('english');
            $table->string('category');
            $table->string('source_key');
            $table->string('source_name')->nullable();
            $table->string('source_kind')->nullable();
            $table->text('source_citation')->nullable();
            $table->text('source_url')->nullable();
            $table->text('source_note');
            $table->string('confidence');
            $table->string('dialect')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('category');
            $table->index('source_key');
            $table->index('confidence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cherokee_phrases');
    }
};
