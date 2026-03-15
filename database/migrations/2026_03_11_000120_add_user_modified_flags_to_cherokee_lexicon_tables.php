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
        Schema::table('cherokee_words', function (Blueprint $table) {
            $table->boolean('is_user_modified')->default(false)->after('alternates');
            $table->index('is_user_modified');
        });

        Schema::table('cherokee_phrases', function (Blueprint $table) {
            $table->boolean('is_user_modified')->default(false)->after('notes');
            $table->index('is_user_modified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cherokee_words', function (Blueprint $table) {
            $table->dropIndex(['is_user_modified']);
            $table->dropColumn('is_user_modified');
        });

        Schema::table('cherokee_phrases', function (Blueprint $table) {
            $table->dropIndex(['is_user_modified']);
            $table->dropColumn('is_user_modified');
        });
    }
};
