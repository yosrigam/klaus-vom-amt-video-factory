<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_posts', function (Blueprint $table) {
            $table->dropForeign(['video_idea_id']);
            $table->dropUnique(['video_idea_id', 'social_account_id']);
        });

        Schema::table('social_posts', function (Blueprint $table) {
            $table->foreignId('video_idea_id')->nullable()->change();
            $table->foreignId('hub_idea_id')->nullable()->after('video_idea_id')->constrained()->cascadeOnDelete();
            $table->foreign('video_idea_id')->references('id')->on('video_ideas')->cascadeOnDelete();
            $table->unique(['video_idea_id', 'social_account_id']);
            $table->unique(['hub_idea_id', 'social_account_id']);
        });
    }

    public function down(): void
    {
        Schema::table('social_posts', function (Blueprint $table) {
            $table->dropForeign(['hub_idea_id']);
            $table->dropUnique(['hub_idea_id', 'social_account_id']);
            $table->dropForeign(['video_idea_id']);
            $table->dropUnique(['video_idea_id', 'social_account_id']);
        });

        Schema::table('social_posts', function (Blueprint $table) {
            $table->dropColumn('hub_idea_id');
            $table->foreignId('video_idea_id')->nullable(false)->change();
            $table->foreign('video_idea_id')->references('id')->on('video_ideas')->cascadeOnDelete();
            $table->unique(['video_idea_id', 'social_account_id']);
        });
    }
};
